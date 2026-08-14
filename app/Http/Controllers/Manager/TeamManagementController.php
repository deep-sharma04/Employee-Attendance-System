<?php

namespace App\Http\Controllers\Manager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamManagementController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of teams.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Team::class);

        $query = Team::with(['manager', 'teamLead'])
            ->withCount(['teamMembers', 'projects']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', (bool) $request->input('status'));
        }

        $teams = $query->latest()->paginate(12)->withQueryString();

        $stats = [
            'total' => Team::count(),
            'active' => Team::where('is_active', true)->count(),
            'total_members' => TeamMember::count(),
            'total_projects' => \App\Models\Project::whereNotNull('team_id')->count(),
        ];

        return view('manager.teams.index', compact('teams', 'stats'));
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(): View
    {
        Gate::authorize('create', Team::class);

        $managers = User::whereIn('role', [UserRole::MANAGER, UserRole::SUPER_ADMIN])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $teamLeads = User::whereIn('role', [UserRole::TEAM_LEAD, UserRole::MANAGER, UserRole::SUPER_ADMIN, UserRole::EMPLOYEE])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.teams.create', compact('managers', 'teamLeads'));
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Team::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:teams,code'],
            'department' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['required', 'exists:users,id'],
            'team_lead_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $team = Team::create($validated);

        $this->auditLogger->logTeam(
            action: 'team.created',
            teamId: $team->id,
            afterValues: $team->toArray(),
            description: "Team '{$team->name}' ({$team->code}) was created."
        );

        return redirect()->route('manager.teams.show', $team)
            ->with('success', "Team '{$team->name}' created successfully.");
    }

    /**
     * Display the specified team profile and member roster.
     */
    public function show(Team $team): View
    {
        Gate::authorize('view', $team);

        $team->load([
            'manager',
            'teamLead',
            'teamMembers.user.employee.projectProfile',
            'teamMembers.employee.projectProfile',
            'projects' => fn ($q) => $q->with('manager')->latest(),
        ]);

        // Get list of active employees not already in this team
        $existingMemberEmployeeIds = $team->teamMembers->pluck('employee_id')->filter()->toArray();
        $availableEmployees = Employee::with(['user', 'projectProfile'])
            ->where('status', \App\Enums\EmployeeStatus::ACTIVE)
            ->whereNotIn('id', $existingMemberEmployeeIds)
            ->orderBy('first_name')
            ->get();

        return view('manager.teams.show', compact('team', 'availableEmployees'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team): View
    {
        Gate::authorize('update', $team);

        $managers = User::whereIn('role', [UserRole::MANAGER, UserRole::SUPER_ADMIN])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $teamLeads = User::whereIn('role', [UserRole::TEAM_LEAD, UserRole::MANAGER, UserRole::SUPER_ADMIN, UserRole::EMPLOYEE])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('manager.teams.edit', compact('team', 'managers', 'teamLeads'));
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('teams', 'code')->ignore($team->id)],
            'department' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['required', 'exists:users,id'],
            'team_lead_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $before = $team->toArray();
        $team->update($validated);

        $this->auditLogger->logTeam(
            action: 'team.updated',
            teamId: $team->id,
            beforeValues: $before,
            afterValues: $team->toArray(),
            description: "Team '{$team->name}' details were updated."
        );

        return redirect()->route('manager.teams.show', $team)
            ->with('success', "Team '{$team->name}' updated successfully.");
    }

    /**
     * Remove the specified team from storage (Soft delete).
     */
    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $before = $team->toArray();
        $name = $team->name;
        $team->delete();

        $this->auditLogger->logTeam(
            action: 'team.deleted',
            teamId: $team->id,
            beforeValues: $before,
            description: "Team '{$name}' was deleted."
        );

        return redirect()->route('manager.teams.index')
            ->with('success', "Team '{$name}' deleted successfully.");
    }

    // ==========================================
    // Membership Management (T215)
    // ==========================================

    /**
     * Add an employee to the team roster.
     */
    public function addMember(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageMembers', $team);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $employee = Employee::with('user')->findOrFail($validated['employee_id']);

        if (!$employee->user) {
            return back()->with('error', 'Cannot add employee without an associated user login.');
        }

        if ($team->teamMembers()->where('user_id', $employee->user->id)->exists()) {
            return back()->with('error', 'Employee is already a member of this team.');
        }

        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        // If marked primary or if employee has no primary team, set primary and unset others
        $hasOtherPrimary = TeamMember::where('employee_id', $employee->id)->where('is_primary', true)->exists();
        if ($isPrimary || !$hasOtherPrimary) {
            TeamMember::where('employee_id', $employee->id)->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $member = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $employee->user->id,
            'employee_id' => $employee->id,
            'is_primary' => $isPrimary,
            'joined_at' => now(),
        ]);

        $this->auditLogger->logTeam(
            action: 'team_member.added',
            teamId: $team->id,
            afterValues: $member->toArray(),
            description: "Employee '{$employee->first_name} {$employee->last_name}' was added to team '{$team->name}'."
        );

        return redirect()->route('manager.teams.show', $team)
            ->with('success', "Member added to team successfully.");
    }

    /**
     * Set a team member's primary team designation.
     */
    public function setPrimary(Team $team, TeamMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $team);

        // Unset primary on other teams for this employee/user
        TeamMember::where('employee_id', $member->employee_id)->update(['is_primary' => false]);
        $member->update(['is_primary' => true]);

        $this->auditLogger->logTeam(
            action: 'team_member.primary_set',
            teamId: $team->id,
            afterValues: ['team_member_id' => $member->id, 'employee_id' => $member->employee_id],
            description: "Team '{$team->name}' set as primary team for member ID {$member->employee_id}."
        );

        return redirect()->route('manager.teams.show', $team)
            ->with('success', "Primary team assignment updated.");
    }

    /**
     * Remove an employee from the team.
     */
    public function removeMember(Team $team, TeamMember $member): RedirectResponse
    {
        Gate::authorize('manageMembers', $team);

        $before = $member->toArray();
        $member->delete();

        $this->auditLogger->logTeam(
            action: 'team_member.removed',
            teamId: $team->id,
            beforeValues: $before,
            description: "Member removed from team '{$team->name}'."
        );

        return redirect()->route('manager.teams.show', $team)
            ->with('success', "Member removed from team successfully.");
    }
}
