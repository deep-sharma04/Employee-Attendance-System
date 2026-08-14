<?php

namespace App\Http\Controllers\TeamLead;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamLeadDashboardController extends Controller
{
    /**
     * Display the Team Lead workspace dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get team led by this user
        $team = Team::with(['manager', 'teamLead', 'members', 'employees'])
            ->where('team_lead_id', $user->id)
            ->first();

        // Projects linked to team or where team lead is assigned
        $projects = Project::with(['team', 'client', 'members'])
            ->where(function ($q) use ($user, $team) {
                if ($team) {
                    $q->where('team_id', $team->id);
                }
                $q->orWhereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id));
            })
            ->latest()
            ->get();

        $activeProjectsCount = $projects->where('status', \App\Enums\ProjectStatus::ACTIVE)->count();
        $teamMembersCount = $team ? $team->members->count() : 0;

        return view('team-lead.dashboard', compact(
            'team',
            'projects',
            'activeProjectsCount',
            'teamMembersCount'
        ));
    }
}
