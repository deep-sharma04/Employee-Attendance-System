<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectHealthService $healthService
    ) {}

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Project::class);

        $query = Project::with(['client', 'team', 'manager', 'milestones']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($health = $request->input('health')) {
            $query->where('health', $health);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('team_id', $teamId);
        }

        $projects = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => Project::count(),
            'active' => Project::where('status', ProjectStatus::ACTIVE->value)->count(),
            'at_risk' => Project::where('health', ProjectHealth::AT_RISK->value)->count(),
            'critical' => Project::where('health', ProjectHealth::CRITICAL->value)->count(),
            'completed' => Project::where('status', ProjectStatus::COMPLETED->value)->count(),
        ];

        $clients = Client::orderBy('company_name')->get();
        $teams = Team::active()->orderBy('name')->get();

        return view('manager.projects.index', compact('projects', 'stats', 'clients', 'teams'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): View
    {
        Gate::authorize('create', Project::class);

        $clients = Client::active()->orderBy('company_name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $managers = User::whereIn('role', [UserRole::MANAGER, UserRole::SUPER_ADMIN])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.projects.create', compact('clients', 'teams', 'managers'));
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Project::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'manager_id' => ['required', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'description' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['estimated_hours'] = $validated['estimated_hours'] ?? 0;
        $validated['health'] = ProjectHealth::GOOD->value;

        $project = Project::create($validated);

        // Recalculate deterministic health
        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project.created',
            projectId: $project->id,
            afterValues: $project->toArray(),
            description: "Project '{$project->name}' ({$project->code}) was created."
        );

        return redirect()->route('manager.projects.show', $project)
            ->with('success', "Project '{$project->name}' created successfully.");
    }

    /**
     * Display the specified project profile and details.
     */
    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        // Always ensure health is deterministic and fresh
        $this->healthService->recalculateAndSave($project);

        $project->load([
            'client',
            'team.manager',
            'team.teamLead',
            'manager',
            'milestones.creator',
            'projectMembers.user.employee.projectProfile',
            'creator',
        ]);

        $existingMemberUserIds = $project->projectMembers->pluck('user_id')->toArray();
        $availableUsers = User::whereIn('role', [UserRole::EMPLOYEE, UserRole::TEAM_LEAD, UserRole::MANAGER])
            ->where('is_active', true)
            ->whereNotIn('id', $existingMemberUserIds)
            ->with('employee')
            ->orderBy('name')
            ->get();

        return view('manager.projects.show', compact('project', 'availableUsers'));
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        $clients = Client::orderBy('company_name')->get();
        $teams = Team::active()->orderBy('name')->get();
        $managers = User::whereIn('role', [UserRole::MANAGER, UserRole::SUPER_ADMIN])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.projects.edit', compact('project', 'clients', 'teams', 'managers'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('projects', 'code')->ignore($project->id)],
            'client_id' => ['nullable', 'exists:clients,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'manager_id' => ['required', 'exists:users,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'description' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $before = $project->toArray();
        $project->update($validated);

        // Recalculate deterministic health
        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project.updated',
            projectId: $project->id,
            beforeValues: $before,
            afterValues: $project->toArray(),
            description: "Project '{$project->name}' details were updated."
        );

        return redirect()->route('manager.projects.show', $project)
            ->with('success', "Project '{$project->name}' updated successfully.");
    }

    /**
     * Quick update of project status and priority (Task T222).
     */
    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['nullable', Rule::enum(ProjectPriority::class)],
        ]);

        $before = $project->toArray();
        $project->status = $validated['status'];
        if (!empty($validated['priority'])) {
            $project->priority = $validated['priority'];
        }

        if ($project->status === ProjectStatus::COMPLETED && !$project->end_date) {
            $project->end_date = now()->toDateString();
        }

        $project->save();

        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project.status_changed',
            projectId: $project->id,
            beforeValues: $before,
            afterValues: $project->toArray(),
            description: "Project '{$project->name}' status changed to '{$project->status->value}'."
        );

        return redirect()->route('manager.projects.show', $project)
            ->with('success', "Project status updated to '{$project->status->label()}'.");
    }

    /**
     * Remove the specified project from storage (Soft delete).
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $before = $project->toArray();
        $name = $project->name;
        $project->delete();

        $this->auditLogger->logProject(
            action: 'project.deleted',
            projectId: $project->id,
            beforeValues: $before,
            description: "Project '{$name}' was deleted."
        );

        return redirect()->route('manager.projects.index')
            ->with('success', "Project '{$name}' was deleted successfully.");
    }

    // ==========================================
    // Milestones Management (T221)
    // ==========================================

    /**
     * Store a new milestone for the project.
     */
    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['project_id'] = $project->id;
        $validated['created_by'] = Auth::id();
        $validated['order'] = $validated['order'] ?? ($project->milestones()->max('order') + 1);

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $milestone = ProjectMilestone::create($validated);

        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project_milestone.created',
            projectId: $project->id,
            afterValues: $milestone->toArray(),
            description: "Milestone '{$milestone->title}' added to project '{$project->name}'."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'milestones'])
            ->with('success', "Milestone '{$milestone->title}' created successfully.");
    }

    /**
     * Update an existing milestone.
     */
    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'completed' && $milestone->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $before = $milestone->toArray();
        $milestone->update($validated);

        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project_milestone.updated',
            projectId: $project->id,
            beforeValues: $before,
            afterValues: $milestone->toArray(),
            description: "Milestone '{$milestone->title}' was updated."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'milestones'])
            ->with('success', "Milestone '{$milestone->title}' updated successfully.");
    }

    /**
     * Toggle milestone completion status.
     */
    public function toggleMilestoneStatus(Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        Gate::authorize('update', $project);

        if ($milestone->status === 'completed') {
            $milestone->status = 'pending';
            $milestone->completed_at = null;
        } else {
            $milestone->status = 'completed';
            $milestone->completed_at = now();
        }

        $milestone->save();

        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project_milestone.status_toggled',
            projectId: $project->id,
            afterValues: ['milestone_id' => $milestone->id, 'status' => $milestone->status],
            description: "Milestone '{$milestone->title}' marked as {$milestone->status}."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'milestones'])
            ->with('success', "Milestone '{$milestone->title}' status updated.");
    }

    /**
     * Delete a milestone.
     */
    public function destroyMilestone(Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        Gate::authorize('update', $project);

        $before = $milestone->toArray();
        $title = $milestone->title;
        $milestone->delete();

        $this->healthService->recalculateAndSave($project);

        $this->auditLogger->logProject(
            action: 'project_milestone.deleted',
            projectId: $project->id,
            beforeValues: $before,
            description: "Milestone '{$title}' was deleted from project '{$project->name}'."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'milestones'])
            ->with('success', "Milestone deleted successfully.");
    }

    // ==========================================
    // Project Members Management
    // ==========================================

    /**
     * Add member to project.
     */
    public function addMember(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'project_role' => ['required', Rule::enum(ProjectMemberRole::class)],
        ]);

        $user = User::with('employee')->findOrFail($validated['user_id']);

        if ($project->projectMembers()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is already a member of this project.');
        }

        $member = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'employee_id' => $user->employee?->id,
            'project_role' => $validated['project_role'],
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->auditLogger->logProject(
            action: 'project_member.added',
            projectId: $project->id,
            afterValues: $member->toArray(),
            description: "User '{$user->name}' was assigned to project '{$project->name}' as {$member->project_role->value}."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', "Member added to project successfully.");
    }

    /**
     * Remove member from project.
     */
    public function removeMember(Project $project, ProjectMember $member): RedirectResponse
    {
        Gate::authorize('update', $project);

        $before = $member->toArray();
        $member->delete();

        $this->auditLogger->logProject(
            action: 'project_member.removed',
            projectId: $project->id,
            beforeValues: $before,
            description: "Member removed from project '{$project->name}'."
        );

        return redirect()->route('manager.projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', "Member removed from project.");
    }
}
