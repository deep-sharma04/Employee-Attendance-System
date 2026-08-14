<?php

namespace App\Http\Controllers\Knowledge;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KnowledgeSearchController extends Controller
{
    /**
     * T255: Project Knowledge Search Engine
     * Searches authorized project documents, task descriptions, and internal comments
     * while strictly respecting role-based visibility boundaries.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = trim($request->input('q', ''));
        $type = $request->input('type', 'all'); // all, documents, tasks, comments
        $selectedProjectId = $request->input('project_id');

        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Determine accessible project IDs for the authenticated user
        $accessibleProjectIds = $this->getAccessibleProjectIds($user, $userRole);

        // Filter by specific project if selected
        if ($selectedProjectId && in_array((int) $selectedProjectId, $accessibleProjectIds)) {
            $scopedProjectIds = [(int) $selectedProjectId];
        } else {
            $scopedProjectIds = $accessibleProjectIds;
        }

        $documentResults = collect();
        $taskResults = collect();
        $commentResults = collect();

        if (!empty($query) && count($scopedProjectIds) > 0) {
            // 1. Search Project Documents
            if (in_array($type, ['all', 'documents'])) {
                $docQuery = ProjectDocument::with(['project', 'latestVersion', 'uploader'])
                    ->whereIn('project_id', $scopedProjectIds)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });

                // Clients can only see client-visible documents
                if ($userRole === 'client') {
                    $docQuery->where('is_client_visible', true);
                }

                $documentResults = $docQuery->latest()->take(30)->get();
            }

            // 2. Search Task Descriptions
            if (in_array($type, ['all', 'tasks'])) {
                $taskQuery = Task::with(['project', 'assignee', 'creator'])
                    ->whereIn('project_id', $scopedProjectIds)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });

                $taskResults = $taskQuery->latest()->take(30)->get();
            }

            // 3. Search Internal Comments (NEVER accessible to Clients)
            if (in_array($type, ['all', 'comments']) && $userRole !== 'client') {
                $commentQuery = TaskComment::with(['task.project', 'user'])
                    ->whereHas('task', function ($t) use ($scopedProjectIds) {
                        $t->whereIn('project_id', $scopedProjectIds);
                    })
                    ->where('comment', 'like', "%{$query}%");

                $commentResults = $commentQuery->latest()->take(30)->get();
            }
        }

        // Fetch accessible projects for filter dropdown
        $availableProjects = Project::whereIn('id', $accessibleProjectIds)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $totalResults = $documentResults->count() + $taskResults->count() + $commentResults->count();

        return view('knowledge.search', compact(
            'query',
            'type',
            'selectedProjectId',
            'availableProjects',
            'documentResults',
            'taskResults',
            'commentResults',
            'totalResults',
            'userRole'
        ));
    }

    /**
     * Resolve project IDs accessible by the user based on RBAC & team/member scopes.
     */
    protected function getAccessibleProjectIds($user, string $role): array
    {
        if ($role === 'super_admin') {
            return Project::pluck('id')->toArray();
        }

        if ($role === 'manager') {
            return Project::where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhereHas('team', fn($t) => $t->where('manager_id', $user->id))
                  ->orWhereHas('projectMembers', fn($m) => $m->where('user_id', $user->id));
            })->pluck('id')->toArray();
        }

        if ($role === 'team_lead') {
            return Project::where(function ($q) use ($user) {
                $q->whereHas('team', fn($t) => $t->where('team_lead_id', $user->id))
                  ->orWhereHas('projectMembers', fn($m) => $m->where('user_id', $user->id));
            })->pluck('id')->toArray();
        }

        if ($role === 'employee') {
            return Project::where(function ($q) use ($user) {
                $q->whereHas('projectMembers', fn($m) => $m->where('user_id', $user->id))
                  ->orWhereHas('team.teamMembers', fn($tm) => $tm->where('user_id', $user->id));
            })->pluck('id')->toArray();
        }

        if ($role === 'client') {
            $clientUser = $user->clientUser;
            if ($clientUser) {
                return Project::where('client_id', $clientUser->client_id)->pluck('id')->toArray();
            }
            return [];
        }

        return [];
    }
}
