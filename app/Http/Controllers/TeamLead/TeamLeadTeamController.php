<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamLeadTeamController extends Controller
{
    /**
     * Display the team(s) assigned to the logged-in Team Lead.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Get teams where user is team lead or member
        $teams = Team::with([
            'manager',
            'teamMembers.user.employee.projectProfile',
            'teamMembers.employee.projectProfile',
            'projects.manager',
        ])
        ->where('team_lead_id', $user->id)
        ->get();

        return view('team-lead.team.index', compact('teams'));
    }

    /**
     * Display a team member's project profile (Read-only for Team Lead).
     */
    public function showMember(Employee $employee): View
    {
        $user = Auth::user();

        // Check if employee is in one of the teams led by this team lead
        $ledTeamIds = Team::where('team_lead_id', $user->id)->pluck('id')->toArray();
        $isMember = $employee->teamMemberships()->whereIn('team_id', $ledTeamIds)->exists();

        if (!$isMember && !$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'You do not have access to view this employee project profile.');
        }

        $employee->load([
            'user.projectMemberships.project',
            'projectProfile',
            'teamMemberships.team',
        ]);

        $projectProfile = $employee->projectProfile ?? new EmployeeProjectProfile([
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'skills' => [],
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
        ]);

        $primaryTeamMembership = $employee->teamMemberships->firstWhere('is_primary', true);

        return view('manager.employees.show', compact('employee', 'projectProfile', 'primaryTeamMembership'));
    }
}
