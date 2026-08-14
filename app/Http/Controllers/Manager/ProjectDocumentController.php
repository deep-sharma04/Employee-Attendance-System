<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectDocumentVersion;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    public function __construct(protected AuditLoggerService $auditLogger) {}

    /**
     * T251 & T255: List and Search Project Documents
     */
    public function index(Request $request, Project $project): View
    {
        // T254: Enforce Access Control
        Gate::authorize('view', $project);

        $query = ProjectDocument::with(['latestVersion', 'uploader', 'versions.uploader'])
            ->where('project_id', $project->id);

        // T253: Manage Client Document Sharing (Filter out internal docs if user is client)
        $userRole = Auth::user()->role instanceof \App\Enums\UserRole 
            ? Auth::user()->role->value 
            : (string) Auth::user()->role;

        if ($userRole === 'client') {
            $query->where('is_client_visible', true);
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        return view('manager.projects.documents', compact('project', 'documents'));
    }

    /**
     * T250: Upload & Manage Project Documents
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('uploadDocument', $project);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_client_visible' => 'nullable|boolean',
            'file' => 'required|file|max:2048|mimes:pdf,doc,docx,xls,xlsx,png,jpeg',
        ]);

        $file = $request->file('file');
        $directory = "projects/{$project->id}/documents";
        
        $safeOriginalName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $file->getClientOriginalName());
        $fileName = time() . '_' . uniqid() . '_' . $safeOriginalName;
        $path = $file->storeAs($directory, $fileName, 'local');

        $user = Auth::user();
        $canManageSharing = Gate::allows('manageDocuments', $project);

        // Check if document with same name exists in this project to create a new version
        $existingDoc = ProjectDocument::where('project_id', $project->id)
            ->where('name', $request->input('name'))
            ->first();

        if ($existingDoc) {
            $newVersionNumber = $existingDoc->current_version + 1;

            ProjectDocumentVersion::create([
                'project_document_id' => $existingDoc->id,
                'version_number' => $newVersionNumber,
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);

            $updateData = [
                'current_version' => $newVersionNumber,
                'description' => $request->input('description', $existingDoc->description),
            ];

            if ($canManageSharing && $request->has('is_client_visible')) {
                $updateData['is_client_visible'] = $request->boolean('is_client_visible');
            }

            $existingDoc->update($updateData);
            $doc = $existingDoc;
        } else {
            $isClientVisible = $canManageSharing ? $request->boolean('is_client_visible') : false;

            $doc = ProjectDocument::create([
                'project_id' => $project->id,
                'uploaded_by' => $user->id,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_client_visible' => $isClientVisible,
                'current_version' => 1,
            ]);

            ProjectDocumentVersion::create([
                'project_document_id' => $doc->id,
                'version_number' => 1,
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }

        // T252: Purge older versions (keep exactly latest 10 versions)
        $oldVersions = ProjectDocumentVersion::where('project_document_id', $doc->id)
            ->orderBy('version_number', 'desc')
            ->skip(10)
            ->take(PHP_INT_MAX)
            ->get();

        foreach ($oldVersions as $oldVersion) {
            if (Storage::disk('local')->exists($oldVersion->file_path)) {
                Storage::disk('local')->delete($oldVersion->file_path);
            }
            $purgedVerNumber = $oldVersion->version_number;
            $oldVersion->delete();

            $this->auditLogger->log(
                action: 'project_document.version_purged',
                targetType: 'ProjectDocument',
                targetId: $doc->id,
                beforeValues: ['purged_version' => $purgedVerNumber],
                afterValues: ['retained_latest' => $doc->current_version],
                description: "Purged version {$purgedVerNumber} for document '{$doc->name}' to maintain 10-version limit."
            );
        }

        $this->auditLogger->log(
            action: 'project_document.uploaded',
            targetType: 'ProjectDocument',
            targetId: $doc->id,
            beforeValues: null,
            afterValues: [
                'name' => $doc->name,
                'version' => $doc->current_version,
                'is_client_visible' => $doc->is_client_visible,
                'project_id' => $project->id,
            ],
            description: "Uploaded version {$doc->current_version} for project document '{$doc->name}' in project '{$project->name}'."
        );

        return back()->with('success', "Document '{$doc->name}' (v{$doc->current_version}) uploaded successfully.");
    }

    /**
     * T254: Secure File Download
     */
    public function download(Project $project, ProjectDocument $document, $version = null): StreamedResponse
    {
        // Prevent IDOR: ensure document belongs to the requested project
        if ((int) $document->project_id !== (int) $project->id) {
            abort(404, 'Document not found in this project.');
        }

        Gate::authorize('view', $project);

        $userRole = Auth::user()->role instanceof \App\Enums\UserRole 
            ? Auth::user()->role->value 
            : (string) Auth::user()->role;

        // T254: Enforce strict client visibility
        if ($userRole === 'client' && !$document->is_client_visible) {
            $this->auditLogger->log(
                action: 'project_document.access_denied',
                targetType: 'ProjectDocument',
                targetId: $document->id,
                description: "Unauthorized project document download attempt by client user " . Auth::user()->email
            );
            abort(403, 'You do not have access to this document.');
        }

        $versionModel = $version 
            ? $document->versions()->where('version_number', $version)->first()
            : ($document->latestVersion ?? $document->versions()->orderBy('version_number', 'desc')->first());

        if (!$versionModel) {
            abort(404, 'Specified document version not found.');
        }

        if (!Storage::disk('local')->exists($versionModel->file_path)) {
            abort(404, 'File not found in storage.');
        }

        $this->auditLogger->log(
            action: 'project_document.downloaded',
            targetType: 'ProjectDocument',
            targetId: $document->id,
            afterValues: [
                'name' => $document->name,
                'version' => $versionModel->version_number,
                'project_id' => $project->id,
            ],
            description: "User " . Auth::user()->name . " downloaded document '{$document->name}' (v{$versionModel->version_number})."
        );

        $downloadName = pathinfo($document->name, PATHINFO_FILENAME) . '_v' . $versionModel->version_number . '.' . pathinfo($versionModel->file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($versionModel->file_path, $downloadName);
    }

    /**
     * T253: Manage Client Document Sharing Toggle
     */
    public function toggleShare(Project $project, ProjectDocument $document): RedirectResponse
    {
        if ((int) $document->project_id !== (int) $project->id) {
            abort(404, 'Document not found in this project.');
        }

        Gate::authorize('manageDocuments', $project);

        $oldStatus = $document->is_client_visible;
        $document->is_client_visible = !$oldStatus;
        $document->save();

        $statusText = $document->is_client_visible ? 'shared with client' : 'made internal-only';

        $this->auditLogger->log(
            action: 'project_document.share_toggled',
            targetType: 'ProjectDocument',
            targetId: $document->id,
            beforeValues: ['is_client_visible' => $oldStatus],
            afterValues: ['is_client_visible' => $document->is_client_visible],
            description: "Document '{$document->name}' was {$statusText} by " . Auth::user()->name . "."
        );

        return back()->with('success', "Document '{$document->name}' is now {$statusText}.");
    }

    /**
     * T250 & T254: Delete Project Document & All Version Files
     */
    public function destroy(Project $project, ProjectDocument $document): RedirectResponse
    {
        if ((int) $document->project_id !== (int) $project->id) {
            abort(404, 'Document not found in this project.');
        }

        Gate::authorize('manageDocuments', $project);

        // Delete all physical files
        foreach ($document->versions as $ver) {
            if (Storage::disk('local')->exists($ver->file_path)) {
                Storage::disk('local')->delete($ver->file_path);
            }
        }

        $docName = $document->name;
        $docId = $document->id;
        $document->delete();

        $this->auditLogger->log(
            action: 'project_document.deleted',
            targetType: 'ProjectDocument',
            targetId: $docId,
            beforeValues: ['name' => $docName, 'project_id' => $project->id],
            description: "Deleted project document '{$docName}' and all associated versions."
        );

        return back()->with('success', "Document '{$docName}' deleted successfully.");
    }
}