<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\ClientContact;
use App\Models\ClientDocument;
use App\Models\ClientUser;
use App\Models\Project;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of clients.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Client::class);

        $query = Client::with(['primaryContact', 'contacts', 'projects'])
            ->withCount(['contacts', 'projects', 'documents']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $clients = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => Client::count(),
            'active' => Client::where('status', ClientStatus::ACTIVE->value)->count(),
            'lead' => Client::where('status', ClientStatus::LEAD->value)->count(),
            'inactive' => Client::where('status', ClientStatus::INACTIVE->value)->count(),
        ];

        return view('manager.clients.index', compact('clients', 'stats'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): View
    {
        Gate::authorize('create', Client::class);

        return view('manager.clients.create');
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Client::class);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_code' => ['nullable', 'string', 'max:50', 'unique:clients,company_code'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['currency'] = $validated['currency'] ?? 'USD';

        $client = Client::create($validated);

        $this->auditLogger->logClient(
            action: 'client.created',
            clientId: $client->id,
            afterValues: $client->toArray(),
            description: "Client '{$client->company_name}' was created."
        );

        return redirect()->route('manager.clients.show', $client)
            ->with('success', "Client '{$client->company_name}' created successfully.");
    }

    /**
     * Display the specified client profile and management tabs.
     */
    public function show(Client $client): View
    {
        Gate::authorize('view', $client);

        $client->load([
            'contacts',
            'primaryContact',
            'projects' => fn ($q) => $q->with(['team', 'manager'])->latest(),
            'documents' => fn ($q) => $q->with('uploader')->latest(),
            'communications' => fn ($q) => $q->with('user')->latest('communication_date'),
            'clientUsers.user',
            'creator',
        ]);

        $activeProjectsCount = $client->projects->where('status', \App\Enums\ProjectStatus::ACTIVE)->count();
        $availableProjects = Project::whereNull('client_id')->latest()->get();

        return view('manager.clients.show', compact('client', 'activeProjectsCount', 'availableProjects'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): View
    {
        Gate::authorize('update', $client);

        return view('manager.clients.edit', compact('client'));
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_code' => ['nullable', 'string', 'max:50', Rule::unique('clients', 'company_code')->ignore($client->id)],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $before = $client->toArray();
        $client->update($validated);

        $this->auditLogger->logClient(
            action: 'client.updated',
            clientId: $client->id,
            beforeValues: $before,
            afterValues: $client->toArray(),
            description: "Client '{$client->company_name}' details were updated."
        );

        return redirect()->route('manager.clients.show', $client)
            ->with('success', "Client '{$client->company_name}' updated successfully.");
    }

    /**
     * Remove the specified client from storage (Soft delete with safeguards).
     */
    public function destroy(Client $client): RedirectResponse
    {
        Gate::authorize('delete', $client);

        $before = $client->toArray();
        $name = $client->company_name;

        $client->delete();

        $this->auditLogger->logClient(
            action: 'client.deleted',
            clientId: $client->id,
            beforeValues: $before,
            description: "Client '{$name}' was deleted."
        );

        return redirect()->route('manager.clients.index')
            ->with('success', "Client '{$name}' was deleted successfully.");
    }

    // ==========================================
    // Contacts Management (T208)
    // ==========================================

    /**
     * Store a new contact for the client.
     */
    public function storeContact(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        // If marked primary or first contact, update others
        if ($isPrimary || $client->contacts()->count() === 0) {
            $client->contacts()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $validated['client_id'] = $client->id;
        $validated['is_primary'] = $isPrimary;

        $contact = ClientContact::create($validated);

        $this->auditLogger->logClient(
            action: 'client_contact.created',
            clientId: $client->id,
            afterValues: $contact->toArray(),
            description: "Contact '{$contact->name}' added to client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', "Contact '{$contact->name}' added successfully.");
    }

    /**
     * Update contact details.
     */
    public function updateContact(Request $request, Client $client, ClientContact $contact): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['is_primary'])) {
            $client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $before = $contact->toArray();
        $contact->update($validated);

        $this->auditLogger->logClient(
            action: 'client_contact.updated',
            clientId: $client->id,
            beforeValues: $before,
            afterValues: $contact->toArray(),
            description: "Contact '{$contact->name}' was updated."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', "Contact '{$contact->name}' updated successfully.");
    }

    /**
     * Delete a contact.
     */
    public function destroyContact(Client $client, ClientContact $contact): RedirectResponse
    {
        Gate::authorize('update', $client);

        $before = $contact->toArray();
        $name = $contact->name;
        $contact->delete();

        $this->auditLogger->logClient(
            action: 'client_contact.deleted',
            clientId: $client->id,
            beforeValues: $before,
            description: "Contact '{$name}' was removed from client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', "Contact '{$name}' deleted successfully.");
    }

    /**
     * Set a contact as primary.
     */
    public function setPrimaryContact(Client $client, ClientContact $contact): RedirectResponse
    {
        Gate::authorize('update', $client);

        $client->contacts()->update(['is_primary' => false]);
        $contact->update(['is_primary' => true]);

        $this->auditLogger->logClient(
            action: 'client_contact.primary_set',
            clientId: $client->id,
            afterValues: ['primary_contact_id' => $contact->id],
            description: "Contact '{$contact->name}' set as primary contact for client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', "Primary contact set to '{$contact->name}'.");
    }

    // ==========================================
    // Project Linkage (T209)
    // ==========================================

    /**
     * Link an existing project to this client.
     */
    public function linkProject(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        $project = Project::findOrFail($validated['project_id']);
        $beforeClientId = $project->client_id;
        $project->update(['client_id' => $client->id]);

        $this->auditLogger->logClient(
            action: 'client_project.linked',
            clientId: $client->id,
            afterValues: ['project_id' => $project->id, 'previous_client_id' => $beforeClientId],
            description: "Project '{$project->name}' was linked to client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'projects'])
            ->with('success', "Project '{$project->name}' linked to client successfully.");
    }

    /**
     * Unlink a project from this client.
     */
    public function unlinkProject(Client $client, Project $project): RedirectResponse
    {
        Gate::authorize('update', $client);

        if ($project->client_id === $client->id) {
            $project->update(['client_id' => null]);

            $this->auditLogger->logClient(
                action: 'client_project.unlinked',
                clientId: $client->id,
                afterValues: ['project_id' => $project->id],
                description: "Project '{$project->name}' was unlinked from client '{$client->company_name}'."
            );
        }

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'projects'])
            ->with('success', "Project '{$project->name}' unlinked successfully.");
    }

    // ==========================================
    // Document Management (T210)
    // ==========================================

    /**
     * Upload a document for the client.
     */
    public function uploadDocument(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'document' => [
                'required',
                'file',
                'max:2048', // 2 MB max size
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
            ],
            'is_shared_with_client' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();
        $mime = $file->getClientMimeType();

        // Isolated logical folder: clients/{client_id}/documents
        $storedPath = $file->store("clients/{$client->id}/documents", 'local');

        $doc = ClientDocument::create([
            'client_id' => $client->id,
            'uploaded_by' => Auth::id(),
            'title' => $validated['title'],
            'file_path' => $storedPath,
            'file_name' => $originalName,
            'file_size' => $size,
            'mime_type' => $mime,
            'is_shared_with_client' => (bool) ($validated['is_shared_with_client'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->auditLogger->logClient(
            action: 'client_document.uploaded',
            clientId: $client->id,
            afterValues: $doc->toArray(),
            description: "Document '{$doc->title}' uploaded for client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'documents'])
            ->with('success', "Document '{$doc->title}' uploaded successfully.");
    }

    /**
     * Download client document with authorization.
     */
    public function downloadDocument(Client $client, ClientDocument $document): StreamedResponse
    {
        Gate::authorize('view', $client);

        // Client portal role checks
        $user = Auth::user();
        if ($user->isClient()) {
            if (!$document->is_shared_with_client) {
                abort(403, 'This document is for internal use and not shared with client portal.');
            }
            if ((int) $user->clientUser?->client_id !== (int) $client->id) {
                abort(403, 'Unauthorized access to client document.');
            }
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found in storage.');
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Toggle document sharing with client portal.
     */
    public function toggleDocumentSharing(Client $client, ClientDocument $document): RedirectResponse
    {
        Gate::authorize('update', $client);

        $document->is_shared_with_client = !$document->is_shared_with_client;
        $document->save();

        $this->auditLogger->logClient(
            action: 'client_document.sharing_updated',
            clientId: $client->id,
            afterValues: ['document_id' => $document->id, 'is_shared_with_client' => $document->is_shared_with_client],
            description: "Document '{$document->title}' sharing set to " . ($document->is_shared_with_client ? 'shared' : 'private') . "."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'documents'])
            ->with('success', "Document visibility updated to " . ($document->is_shared_with_client ? 'Shared with Client' : 'Internal Only') . ".");
    }

    /**
     * Delete client document.
     */
    public function destroyDocument(Client $client, ClientDocument $document): RedirectResponse
    {
        Gate::authorize('update', $client);

        $before = $document->toArray();
        $title = $document->title;
        $document->delete();

        $this->auditLogger->logClient(
            action: 'client_document.deleted',
            clientId: $client->id,
            beforeValues: $before,
            description: "Document '{$title}' removed from client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'documents'])
            ->with('success', "Document '{$title}' deleted successfully.");
    }

    // ==========================================
    // Communication Log (T211)
    // ==========================================

    /**
     * Store communication entry.
     */
    public function storeCommunication(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:email,call,meeting,note'],
            'subject' => ['required', 'string', 'max:200'],
            'details' => ['required', 'string'],
            'communication_date' => ['required', 'date'],
        ]);

        $validated['client_id'] = $client->id;
        $validated['user_id'] = Auth::id();

        $comm = ClientCommunication::create($validated);

        $this->auditLogger->logClient(
            action: 'client_communication.created',
            clientId: $client->id,
            afterValues: $comm->toArray(),
            description: "Communication log '{$comm->subject}' ({$comm->type}) recorded for client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'communications'])
            ->with('success', "Communication log added successfully.");
    }

    /**
     * Delete communication entry.
     */
    public function destroyCommunication(Client $client, ClientCommunication $communication): RedirectResponse
    {
        Gate::authorize('update', $client);

        $before = $communication->toArray();
        $subject = $communication->subject;
        $communication->delete();

        $this->auditLogger->logClient(
            action: 'client_communication.deleted',
            clientId: $client->id,
            beforeValues: $before,
            description: "Communication log '{$subject}' removed from client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'communications'])
            ->with('success', "Communication record removed successfully.");
    }

    // ==========================================
    // Portal Access Management (T212)
    // ==========================================

    /**
     * Create client portal login user.
     */
    public function storePortalUser(Request $request, Client $client): RedirectResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::CLIENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $clientUser = ClientUser::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'is_primary' => (bool) ($validated['is_primary'] ?? false),
            'status' => 'active',
        ]);

        $this->auditLogger->logClient(
            action: 'client_portal_user.created',
            clientId: $client->id,
            afterValues: ['user_id' => $user->id, 'username' => $user->username, 'email' => $user->email],
            description: "Portal user account '{$user->username}' created for client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'portal'])
            ->with('success', "Client portal user '{$user->username}' created successfully.");
    }

    /**
     * Toggle client portal user active status.
     */
    public function togglePortalUserStatus(Client $client, User $user): RedirectResponse
    {
        Gate::authorize('update', $client);

        // Security check: ensure user is linked to this client
        if (!$client->clientUsers()->where('user_id', $user->id)->exists()) {
            abort(403, 'User is not linked to this client.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $client->clientUsers()->where('user_id', $user->id)->update([
            'status' => $user->is_active ? 'active' : 'inactive',
        ]);

        $this->auditLogger->logClient(
            action: 'client_portal_user.status_toggled',
            clientId: $client->id,
            afterValues: ['user_id' => $user->id, 'is_active' => $user->is_active],
            description: "Portal user '{$user->username}' status changed to " . ($user->is_active ? 'Active' : 'Deactivated') . "."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'portal'])
            ->with('success', "Portal user '{$user->username}' status updated.");
    }

    /**
     * Revoke client portal user access.
     */
    public function destroyPortalUser(Client $client, User $user): RedirectResponse
    {
        Gate::authorize('update', $client);

        // Security check
        if (!$client->clientUsers()->where('user_id', $user->id)->exists()) {
            abort(403, 'User is not linked to this client.');
        }

        $username = $user->username;
        $client->clientUsers()->where('user_id', $user->id)->delete();
        $user->delete();

        $this->auditLogger->logClient(
            action: 'client_portal_user.revoked',
            clientId: $client->id,
            afterValues: ['user_id' => $user->id, 'username' => $username],
            description: "Portal user access '{$username}' revoked for client '{$client->company_name}'."
        );

        return redirect()->route('manager.clients.show', ['client' => $client, 'tab' => 'portal'])
            ->with('success', "Portal access for '{$username}' has been revoked.");
    }
}
