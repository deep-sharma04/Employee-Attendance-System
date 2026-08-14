<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ManagerDashboardController extends Controller
{
    /**
     * Display the Manager dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get manager's managed teams or all teams if super admin
        $teamsQuery = Team::with(['manager', 'teamLead', 'members']);
        if (!$user->isSuperAdmin()) {
            $teamsQuery->where('manager_id', $user->id);
        }
        $teams = $teamsQuery->latest()->get();

        // Get managed projects
        $projectsQuery = Project::with(['client', 'team', 'manager', 'members']);
        if (!$user->isSuperAdmin()) {
            $teamsIds = $teams->pluck('id');
            $projectsQuery->where(function ($q) use ($user, $teamsIds) {
                $q->where('manager_id', $user->id)
                  ->orWhereIn('team_id', $teamsIds)
                  ->orWhereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id));
            });
        }
        $projects = $projectsQuery->latest()->get();

        $activeProjectsCount = $projects->where('status', \App\Enums\ProjectStatus::ACTIVE)->count();
        $totalClientsCount = Client::active()->count();
        $totalTeamsCount = $teams->count();

        return view('manager.dashboard', compact(
            'teams',
            'projects',
            'activeProjectsCount',
            'totalClientsCount',
            'totalTeamsCount'
        ));
    }
}
