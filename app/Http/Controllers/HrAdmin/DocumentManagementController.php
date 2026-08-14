<?php

namespace App\Http\Controllers\HrAdmin;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Services\Notification\NotificationService;

class DocumentManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Display all employee documents with filtering and status summary.
     */
    public function index(Request $request): View
    {
        $query = Document::with(['employee.user', 'documentType', 'uploader', 'verifier']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('document_type_id')) {
            $query->where('document_type_id', $request->input('document_type_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();
        $employees = Employee::orderBy('first_name')->get();
        $documentTypes = DocumentType::orderBy('name')->get();

        $stats = [
            'total' => Document::count(),
            'pending' => Document::where('status', DocumentStatus::PENDING)->count(),
            'verified' => Document::where('status', DocumentStatus::VERIFIED)->count(),
            'rejected' => Document::where('status', DocumentStatus::REJECTED)->count(),
        ];

        return view('hr-admin.documents.index', compact('documents', 'employees', 'documentTypes', 'stats'));
    }

    /**
     * Show the document upload form.
     */
    public function create(Request $request): View
    {
        $selectedEmployeeId = $request->input('employee_id');
        $employees = Employee::where('status', 'active')->orderBy('first_name')->get();
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('hr-admin.documents.create', compact('employees', 'documentTypes', 'selectedEmployeeId'));
    }

    /**
     * Store a newly uploaded employee document.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('document_file');
        $originalFileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        // Store file securely outside the public webroot in storage/app/documents
        $storedPath = $file->store('documents', 'local');

        $document = Document::create([
            'employee_id' => $request->input('employee_id'),
            'document_type_id' => $request->input('document_type_id'),
            'title' => $request->input('title'),
            'file_path' => $storedPath,
            'file_name' => $originalFileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => Auth::id(),
        ]);

        $this->auditLogger->log(
            action: 'document.uploaded',
            targetType: 'Document',
            targetId: $document->id,
            beforeValues: null,
            afterValues: [
                'employee_id' => $document->employee_id,
                'document_type_id' => $document->document_type_id,
                'title' => $document->title,
                'file_name' => $document->file_name,
                'file_size' => $document->file_size,
                'mime_type' => $document->mime_type,
            ],
            description: "Uploaded document '{$document->title}' for Employee ID {$document->employee_id}"
        );

        return redirect()->route('hr-admin.documents.index', ['employee_id' => $document->employee_id])
            ->with('success', "Document '{$document->title}' uploaded successfully and queued for verification.");
    }

    /**
     * View/Stream the document file securely in the browser.
     */
    public function viewFile(int $id): BinaryFileResponse|StreamedResponse
    {
        $document = Document::findOrFail($id);

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'The requested document file could not be found on secure storage.');
        }

        $fullPath = Storage::disk('local')->path($document->file_path);

        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }

    /**
     * Download the document file securely.
     */
    public function download(int $id): BinaryFileResponse|StreamedResponse
    {
        $document = Document::findOrFail($id);

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'The requested document file could not be found on secure storage.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Verify an uploaded document.
     */
    public function verify(int $id): RedirectResponse
    {
        $document = Document::with(['employee.user', 'documentType'])->findOrFail($id);
        $beforeValues = ['status' => $document->status instanceof DocumentStatus ? $document->status->value : $document->status];

        $document->update([
            'status' => DocumentStatus::VERIFIED,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        // In-App Notification (T168)
        $this->notificationService->notifyDocumentStatus($document, 'verified');

        $this->auditLogger->log(
            action: 'document.verified',
            targetType: 'Document',
            targetId: $document->id,
            beforeValues: $beforeValues,
            afterValues: [
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now()->toDateTimeString(),
            ],
            description: "Verified document '{$document->title}' for Employee ID {$document->employee_id}"
        );

        return back()->with('success', "Document '{$document->title}' has been successfully verified.");
    }

    /**
     * Reject an uploaded document with a mandatory reason.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ], [
            'rejection_reason.required' => 'Please provide a clear reason for rejecting this document.',
        ]);

        $document = Document::with(['employee.user', 'documentType'])->findOrFail($id);
        $beforeValues = ['status' => $document->status instanceof DocumentStatus ? $document->status->value : $document->status];

        $document->update([
            'status' => DocumentStatus::REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // In-App Notification (T168)
        $this->notificationService->notifyDocumentStatus($document, 'rejected', $request->input('rejection_reason'));

        $this->auditLogger->log(
            action: 'document.rejected',
            targetType: 'Document',
            targetId: $document->id,
            beforeValues: $beforeValues,
            afterValues: [
                'status' => 'rejected',
                'rejection_reason' => $request->input('rejection_reason'),
                'verified_by' => Auth::id(),
                'verified_at' => now()->toDateTimeString(),
            ],
            description: "Rejected document '{$document->title}' with reason: {$request->input('rejection_reason')}"
        );

        return back()->with('success', "Document '{$document->title}' was rejected with reason recorded.");
    }

    /**
     * Delete a document and remove its stored file.
     */
    public function destroy(int $id): RedirectResponse
    {
        $document = Document::findOrFail($id);
        $beforeValues = [
            'id' => $document->id,
            'title' => $document->title,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
        ];

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        $this->auditLogger->log(
            action: 'document.deleted',
            targetType: 'Document',
            targetId: $id,
            beforeValues: $beforeValues,
            afterValues: null,
            description: "Deleted document '{$beforeValues['title']}'"
        );

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Manage Document Types.
     */
    public function types(): View
    {
        $types = DocumentType::withCount('documents')->orderBy('name')->get();
        return view('hr-admin.documents.types', compact('types'));
    }

    /**
     * Store a new Document Type.
     */
    public function storeType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        if (DocumentType::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        $type = DocumentType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'is_mandatory' => $request->boolean('is_mandatory'),
        ]);

        $this->auditLogger->log(
            action: 'document_type.created',
            targetType: 'DocumentType',
            targetId: $type->id,
            beforeValues: null,
            afterValues: $type->toArray(),
            description: "Created Document Type '{$type->name}'"
        );

        return redirect()->route('hr-admin.documents.types')
            ->with('success', "Document Type '{$type->name}' created successfully.");
    }

    /**
     * Update an existing Document Type.
     */
    public function updateType(Request $request, int $id): RedirectResponse
    {
        $type = DocumentType::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        $beforeValues = $type->toArray();

        $type->update([
            'name' => $validated['name'],
            'is_mandatory' => $request->boolean('is_mandatory'),
        ]);

        $this->auditLogger->log(
            action: 'document_type.updated',
            targetType: 'DocumentType',
            targetId: $type->id,
            beforeValues: $beforeValues,
            afterValues: $type->toArray(),
            description: "Updated Document Type '{$type->name}'"
        );

        return redirect()->route('hr-admin.documents.types')
            ->with('success', "Document Type '{$type->name}' updated successfully.");
    }

    /**
     * Delete a Document Type if not in use.
     */
    public function destroyType(int $id): RedirectResponse
    {
        $type = DocumentType::withCount('documents')->findOrFail($id);

        if ($type->documents_count > 0) {
            return back()->with('error', "Cannot delete '{$type->name}' because {$type->documents_count} documents are associated with it.");
        }

        $beforeValues = $type->toArray();
        $type->delete();

        $this->auditLogger->log(
            action: 'document_type.deleted',
            targetType: 'DocumentType',
            targetId: $id,
            beforeValues: $beforeValues,
            afterValues: null,
            description: "Deleted Document Type '{$beforeValues['name']}'"
        );

        return redirect()->route('hr-admin.documents.types')
            ->with('success', "Document Type deleted successfully.");
    }
}
