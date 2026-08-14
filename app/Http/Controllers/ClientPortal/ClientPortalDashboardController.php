<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Project;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientPortalDashboardController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Helper to retrieve currently authenticated client organization.
     */
    protected function getAuthenticatedClient(): ?Client
    {
        $user = Auth::user();
        $clientUser = $user->clientUser;
        return $clientUser ? Client::with('contacts')->find($clientUser->client_id) : null;
    }

    /**
     * Display the Client Portal overview & project summary (Task T243).
     * Strictly read-only data isolation.
     */
    public function index(Request $request): View
    {
        $client = $this->getAuthenticatedClient();

        $projects = collect();
        $sharedDocuments = collect();
        $completedMilestonesCount = 0;
        $totalMilestonesCount = 0;

        if ($client) {
            $projects = Project::where('client_id', $client->id)
                ->with(['milestones'])
                ->latest()
                ->get();

            foreach ($projects as $proj) {
                $completedMilestonesCount += $proj->milestones->where('status', 'completed')->count();
                $totalMilestonesCount += $proj->milestones->count();
            }

            $sharedDocuments = ClientDocument::where('client_id', $client->id)
                ->sharedWithClient()
                ->latest()
                ->get();
        }

        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status.value', 'active')->count(),
            'completed_milestones' => $completedMilestonesCount,
            'total_milestones' => $totalMilestonesCount,
            'shared_documents' => $sharedDocuments->count(),
        ];

        return view('client-portal.dashboard', compact('client', 'projects', 'sharedDocuments', 'stats'));
    }

    /**
     * View read-only progress and milestones for a permitted project (Task T244).
     */
    public function project(Project $project): View
    {
        $client = $this->getAuthenticatedClient();

        if (!$client || (int) $project->client_id !== (int) $client->id) {
            $this->auditLogger->logProject(
                action: 'client.access_denied',
                projectId: $project->id,
                description: "Unauthorized project view attempt by client user " . Auth::user()->email
            );
            abort(403, 'Unauthorized access to project. This project is not associated with your client account.');
        }

        $project->load([
            'milestones' => fn ($q) => $q->orderBy('due_date'),
            'tasks' => fn ($q) => $q->whereNull('parent_id')->orderBy('due_date'),
        ]);

        $this->auditLogger->logProject(
            action: 'client.project_viewed',
            projectId: $project->id,
            description: "Client portal user " . Auth::user()->name . " viewed project " . $project->name
        );

        return view('client-portal.project-show', compact('client', 'project'));
    }

    /**
     * View shared documents repository (Task T245).
     */
    public function documents(Request $request): View
    {
        $client = $this->getAuthenticatedClient();

        if (!$client) {
            abort(403, 'No client profile found for current user.');
        }

        $documents = ClientDocument::where('client_id', $client->id)
            ->sharedWithClient()
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return view('client-portal.documents', compact('client', 'documents'));
    }

    /**
     * Download an authorized shared client document (Task T245).
     */
    public function downloadDocument(ClientDocument $document): StreamedResponse
    {
        $client = $this->getAuthenticatedClient();

        if (!$client || (int) $document->client_id !== (int) $client->id || !$document->is_shared_with_client) {
            $this->auditLogger->logProject(
                action: 'client.access_denied',
                projectId: 1,
                description: "Unauthorized document download attempt by user " . Auth::user()->email
            );
            abort(403, 'Unauthorized access to document.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'The requested document was not found in storage.');
        }

        $this->auditLogger->logProject(
            action: 'client.document_downloaded',
            projectId: 1,
            afterValues: ['document_id' => $document->id, 'file_name' => $document->file_name],
            description: "Client portal user " . Auth::user()->name . " downloaded shared document '{$document->title}'"
        );

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }
}
