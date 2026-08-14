<?php

namespace App\Services\AI;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Project\ProjectHealthService;
use App\Services\Report\ProjectReportingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ProjectIntelligenceService
{
    public function __construct(
        protected ProjectHealthService $healthService,
        protected ProjectReportingService $reportingService
    ) {}

    /**
     * Get authorized projects query for a user based on RBAC and scoping policies.
     */
    public function getAuthorizedProjectsQuery(User $user): Builder
    {
        $query = Project::with(['client', 'team', 'manager', 'milestones']);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isManager()) {
            $managedTeamIds = Team::where('manager_id', $user->id)->pluck('id');
            return $query->where(function ($q) use ($user, $managedTeamIds) {
                $q->where('manager_id', $user->id)
                  ->orWhereIn('team_id', $managedTeamIds)
                  ->orWhereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id));
            });
        }

        if ($user->isTeamLead()) {
            $ledTeamIds = Team::where('team_lead_id', $user->id)->pluck('id');
            return $query->where(function ($q) use ($user, $ledTeamIds) {
                $q->whereIn('team_id', $ledTeamIds)
                  ->orWhereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id));
            });
        }

        if ($user->isEmployee()) {
            return $query->whereHas('projectMembers', fn ($pm) => $pm->where('user_id', $user->id));
        }

        if ($user->isClient()) {
            $clientUser = $user->clientUser;
            $clientId = $clientUser?->client_id;
            return $clientId ? $query->where('client_id', $clientId) : $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get authorized tasks query for a user.
     */
    public function getAuthorizedTasksQuery(User $user): Builder
    {
        $query = Task::with(['project', 'assignee', 'milestone']);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $projectIds = $this->getAuthorizedProjectsQuery($user)->pluck('id')->toArray();

        if ($user->isEmployee()) {
            return $query->where(function ($q) use ($user, $projectIds) {
                $q->where('assigned_to', $user->id)
                  ->orWhereIn('project_id', $projectIds);
            });
        }

        return $query->whereIn('project_id', $projectIds);
    }

    /**
     * T285: Support Natural-Language Project Search.
     * Maps questions and intents to authorized project queries without raw SQL.
     */
    public function searchProjectIntelligence(User $user, array $params): array
    {
        $queryText = trim((string) ($params['query'] ?? ''));
        $intent = $params['intent'] ?? $this->detectIntent($queryText);
        $projectId = !empty($params['project_id']) ? (int) $params['project_id'] : null;

        if ($projectId) {
            $authorizedProject = $this->getAuthorizedProjectsQuery($user)->where('id', $projectId)->first();
            if (!$authorizedProject) {
                return [
                    'intent' => $intent,
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'not_authorized',
                        'is_factual' => false,
                        'message' => "User is not authorized to view Project #{$projectId} or project does not exist.",
                        'evidence_sources' => [],
                    ],
                    'results' => [],
                    'count' => 0,
                ];
            }
        }

        $today = now()->toDateString();
        $tasksQuery = $this->getAuthorizedTasksQuery($user);
        $projectsQuery = $this->getAuthorizedProjectsQuery($user);

        if ($projectId) {
            $tasksQuery->where('project_id', $projectId);
            $projectsQuery->where('id', $projectId);
        }

        switch ($intent) {
            case 'overdue_tasks':
                $tasks = $tasksQuery
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $today)
                    ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                    ->orderBy('due_date', 'asc')
                    ->get();

                $evidenceIds = $tasks->pluck('id')->map(fn ($id) => "task:{$id}")->all();

                return [
                    'intent' => 'overdue_tasks',
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => $evidenceIds,
                        'missing_information' => [],
                    ],
                    'count' => $tasks->count(),
                    'results' => $tasks->map(fn (Task $t) => [
                        'id' => $t->id,
                        'task_code' => $t->task_code,
                        'title' => $t->title,
                        'project_id' => $t->project_id,
                        'project_name' => $t->project?->name,
                        'due_date' => $t->due_date?->toDateString(),
                        'status' => $t->status->value,
                        'priority' => $t->priority->value,
                        'assigned_to_name' => $t->assignee?->name,
                    ])->all(),
                ];

            case 'upcoming_tasks':
                $next7Days = now()->addDays(7)->toDateString();
                $tasks = $tasksQuery
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', $today)
                    ->where('due_date', '<=', $next7Days)
                    ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                    ->orderBy('due_date', 'asc')
                    ->get();

                $evidenceIds = $tasks->pluck('id')->map(fn ($id) => "task:{$id}")->all();

                return [
                    'intent' => 'upcoming_tasks',
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => $evidenceIds,
                        'missing_information' => [],
                    ],
                    'count' => $tasks->count(),
                    'results' => $tasks->map(fn (Task $t) => [
                        'id' => $t->id,
                        'task_code' => $t->task_code,
                        'title' => $t->title,
                        'project_id' => $t->project_id,
                        'project_name' => $t->project?->name,
                        'due_date' => $t->due_date?->toDateString(),
                        'status' => $t->status->value,
                        'priority' => $t->priority->value,
                        'assigned_to_name' => $t->assignee?->name,
                    ])->all(),
                ];

            case 'incomplete_projects':
            case 'projects_with_overdue':
                $projects = $projectsQuery->get();
                $results = [];
                $evidenceSources = [];

                foreach ($projects as $proj) {
                    $overdueCount = Task::where('project_id', $proj->id)
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', $today)
                        ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                        ->count();

                    $incompleteCount = Task::where('project_id', $proj->id)
                        ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
                        ->count();

                    if ($intent === 'projects_with_overdue' && $overdueCount === 0) {
                        continue;
                    }

                    $evidenceSources[] = "project:{$proj->id}";
                    $results[] = [
                        'project_id' => $proj->id,
                        'name' => $proj->name,
                        'code' => $proj->code,
                        'status' => $proj->status->value,
                        'health' => $proj->health->value,
                        'deadline' => $proj->deadline?->toDateString(),
                        'overdue_tasks_count' => $overdueCount,
                        'incomplete_tasks_count' => $incompleteCount,
                        'progress_percentage' => $proj->progressPercentage(),
                    ];
                }

                return [
                    'intent' => $intent,
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => $evidenceSources,
                        'missing_information' => [],
                    ],
                    'count' => count($results),
                    'results' => $results,
                ];

            case 'project_workload':
                $projects = $projectsQuery->get();
                $results = [];
                $evidenceSources = [];

                foreach ($projects as $proj) {
                    $activeTasksCount = Task::where('project_id', $proj->id)
                        ->whereIn('status', [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::IN_REVIEW, TaskStatus::BLOCKED])
                        ->count();

                    $totalEstHours = (float) Task::where('project_id', $proj->id)->sum('estimated_hours');

                    $evidenceSources[] = "project:{$proj->id}";
                    $results[] = [
                        'project_id' => $proj->id,
                        'name' => $proj->name,
                        'code' => $proj->code,
                        'active_tasks_count' => $activeTasksCount,
                        'total_estimated_hours' => $totalEstHours,
                        'team_name' => $proj->team?->name,
                    ];
                }

                usort($results, fn ($a, $b) => $b['active_tasks_count'] <=> $a['active_tasks_count']);

                return [
                    'intent' => 'project_workload',
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => $evidenceSources,
                        'missing_information' => [],
                    ],
                    'count' => count($results),
                    'results' => $results,
                ];

            case 'project_status':
            case 'project_tasks':
                $projects = $projectsQuery->get();
                $evidenceSources = [];
                $results = $projects->map(function (Project $p) use (&$evidenceSources) {
                    $evidenceSources[] = "project:{$p->id}";
                    return [
                        'project_id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'status' => $p->status->value,
                        'health' => $p->health->value,
                        'start_date' => $p->start_date?->toDateString(),
                        'deadline' => $p->deadline?->toDateString(),
                        'progress' => $p->progressPercentage(),
                    ];
                })->all();

                return [
                    'intent' => $intent,
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => $evidenceSources,
                        'missing_information' => [],
                    ],
                    'count' => count($results),
                    'results' => $results,
                ];

            default:
                return [
                    'intent' => 'unsupported',
                    'query' => $queryText,
                    'grounding' => [
                        'status' => 'insufficient_data',
                        'is_factual' => false,
                        'message' => 'Insufficient/unsupported project data for this request.',
                        'evidence_sources' => [],
                        'missing_information' => ['Query does not match any recognized project-intelligence intents.'],
                    ],
                    'results' => [],
                    'count' => 0,
                ];
        }
    }

    /**
     * Helper to detect search intent from natural language query.
     */
    protected function detectIntent(string $query): string
    {
        $q = strtolower($query);

        if (str_contains($q, 'overdue') && (str_contains($q, 'projects with') || str_contains($q, 'which projects') || str_contains($q, 'show projects'))) {
            return 'projects_with_overdue';
        }
        if (str_contains($q, 'overdue')) {
            return 'overdue_tasks';
        }
        if (str_contains($q, 'this week') || str_contains($q, 'upcoming') || str_contains($q, 'due soon') || str_contains($q, 'next')) {
            return 'upcoming_tasks';
        }
        if (str_contains($q, 'workload') || str_contains($q, 'highest work') || str_contains($q, 'capacity')) {
            return 'project_workload';
        }
        if (str_contains($q, 'incomplete') || str_contains($q, 'pending') || str_contains($q, 'unfinished')) {
            return 'incomplete_projects';
        }
        if (str_contains($q, 'status') || str_contains($q, 'health') || str_contains($q, 'progress')) {
            return 'project_status';
        }
        if (str_contains($q, 'task') || str_contains($q, 'all tasks')) {
            return 'project_tasks';
        }

        return 'unsupported';
    }

    /**
     * T286: Project Health Explanation.
     * Explains the deterministic project health calculated by ProjectHealthService with verified evidence.
     */
    public function explainProjectHealth(User $user, int $projectId): array
    {
        /** @var Project|null $project */
        $project = $this->getAuthorizedProjectsQuery($user)->where('id', $projectId)->first();

        if (!$project) {
            return [
                'project_id' => $projectId,
                'health' => 'unknown',
                'grounding' => [
                    'status' => 'not_authorized',
                    'is_factual' => false,
                    'message' => "Project #{$projectId} is not authorized for this user or does not exist.",
                    'evidence_sources' => [],
                ],
            ];
        }

        // Re-evaluate deterministic health from ProjectHealthService
        $health = $this->healthService->calculateHealth($project);
        $thresholds = $this->healthService->getThresholds();

        $today = now()->toDateString();
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', TaskStatus::DONE)->count();
        $progress = $project->progressPercentage();

        $overdueTasks = $project->tasks()
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED])
            ->get();

        $overdueMilestones = $project->milestones()
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->get();

        // Calculate schedule variance
        $expectedProgress = 0;
        $scheduleVariance = 0;
        $isMissingTimeline = false;

        if ($project->start_date && $project->deadline) {
            $totalDays = $project->start_date->diffInDays($project->deadline, false);
            if ($totalDays > 0) {
                if (now()->lt($project->start_date)) {
                    $expectedProgress = 0;
                } elseif (now()->gte($project->deadline)) {
                    $expectedProgress = 100;
                } else {
                    $daysElapsed = $project->start_date->diffInDays(now(), false);
                    $expectedProgress = ($daysElapsed / $totalDays) * 100;
                }
            }
            $scheduleVariance = max(0, round($expectedProgress - $progress, 1));
        } else {
            $isMissingTimeline = true;
        }

        $isDeadlinePassed = $project->deadline && $project->deadline->isPast() && $progress < 100;

        $evidence = [];
        $evidenceSources = ["project:{$project->id}"];

        if ($project->status === ProjectStatus::COMPLETED) {
            $evidence[] = 'Project is officially marked as Completed.';
        } elseif ($project->status === ProjectStatus::CANCELLED) {
            $evidence[] = 'Project was Cancelled by management.';
        } elseif ($project->status === ProjectStatus::PLANNING) {
            $evidence[] = 'Project is currently in Planning stage.';
        } else {
            if ($isDeadlinePassed) {
                $evidence[] = "Project deadline was {$project->deadline->toDateString()} and progress is only {$progress}%.";
            }
            if ($overdueTasks->count() > 0) {
                $evidence[] = "There are {$overdueTasks->count()} overdue tasks requiring resolution.";
                foreach ($overdueTasks->take(5) as $ot) {
                    $evidenceSources[] = "task:{$ot->id}";
                }
            }
            if ($overdueMilestones->count() > 0) {
                $evidence[] = "There are {$overdueMilestones->count()} overdue milestones.";
                foreach ($overdueMilestones as $om) {
                    $evidenceSources[] = "milestone:{$om->id}";
                }
            }
            if ($scheduleVariance > 0) {
                $evidence[] = "Schedule variance is {$scheduleVariance}% behind expected progress ({$expectedProgress}% expected vs {$progress}% actual).";
            }
            if (empty($evidence)) {
                $evidence[] = "All milestones and tasks are progressing on schedule ({$progress}% completed).";
            }
        }

        $confidence = $isMissingTimeline ? 'insufficient_data' : 'confirmed';

        return [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'project_code' => $project->code,
            'status' => $project->status->value,
            'health' => $health->value,
            'progress_percentage' => $progress,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks_count' => $overdueTasks->count(),
            'overdue_milestones_count' => $overdueMilestones->count(),
            'schedule_variance_percent' => $scheduleVariance,
            'start_date' => $project->start_date?->toDateString(),
            'deadline' => $project->deadline?->toDateString(),
            'evidence' => $evidence,
            'grounding' => [
                'status' => $confidence,
                'is_factual' => true,
                'evidence_sources' => $evidenceSources,
                'missing_information' => $isMissingTimeline ? ['Project lacks explicit start date or deadline for timeline calculation.'] : [],
            ],
        ];
    }

    /**
     * T287: Task Allocation Recommendations.
     * Evaluates active team members for task allocation without modifying database state.
     */
    public function recommendTaskAllocation(User $user, array $params): array
    {
        // Only Super Admin, Manager, and Team Lead can request allocation recommendations
        if (!$user->isSuperAdmin() && !$user->isManager() && !$user->isTeamLead()) {
            return [
                'recommendations' => [],
                'grounding' => [
                    'status' => 'not_authorized',
                    'is_factual' => false,
                    'message' => 'Unauthorized: Only Managers and Team Leads may access task allocation recommendations.',
                    'evidence_sources' => [],
                ],
            ];
        }

        $projectId = !empty($params['project_id']) ? (int) $params['project_id'] : null;
        $taskId = !empty($params['task_id']) ? (int) $params['task_id'] : null;
        $requiredSkills = (array) ($params['required_skills'] ?? []);
        $estimatedHours = !empty($params['estimated_hours']) ? (float) $params['estimated_hours'] : 0.0;

        /** @var Task|null $task */
        $task = null;
        if ($taskId) {
            $task = $this->getAuthorizedTasksQuery($user)->where('id', $taskId)->first();
            if (!$task) {
                return [
                    'recommendations' => [],
                    'grounding' => [
                        'status' => 'not_authorized',
                        'is_factual' => false,
                        'message' => "Task #{$taskId} not found or not authorized for this user.",
                        'evidence_sources' => [],
                    ],
                ];
            }
            $projectId = $task->project_id;
            if (empty($requiredSkills) && !empty($task->description)) {
                // Infer skills if specified in title or description
                $skillKeywords = ['PHP', 'Laravel', 'Vue', 'React', 'TypeScript', 'JavaScript', 'MySQL', 'Docker', 'QA', 'Python', 'AWS'];
                foreach ($skillKeywords as $kw) {
                    if (stripos($task->title . ' ' . $task->description, $kw) !== false) {
                        $requiredSkills[] = $kw;
                    }
                }
            }
            if ($estimatedHours <= 0.0 && $task->estimated_hours) {
                $estimatedHours = (float) $task->estimated_hours;
            }
        }

        if (!$projectId) {
            return [
                'recommendations' => [],
                'grounding' => [
                    'status' => 'insufficient_data',
                    'is_factual' => false,
                    'message' => 'Insufficient data: Target project ID or task ID is required to determine team scope.',
                    'evidence_sources' => [],
                    'missing_information' => ['project_id'],
                ],
            ];
        }

        /** @var Project|null $project */
        $project = $this->getAuthorizedProjectsQuery($user)->where('id', $projectId)->first();
        if (!$project) {
            return [
                'recommendations' => [],
                'grounding' => [
                    'status' => 'not_authorized',
                    'is_factual' => false,
                    'message' => "Project #{$projectId} not authorized for this user.",
                    'evidence_sources' => [],
                ],
            ];
        }

        // Get candidate team members
        $teamId = $project->team_id;
        $candidateQuery = Employee::with(['user', 'projectProfile', 'teamMemberships.team'])
            ->where('status', 'active');

        if ($teamId) {
            $candidateQuery->whereHas('teamMemberships', fn ($tm) => $tm->where('team_id', $teamId));
        } else {
            // If project has no team, look at project members
            $projectUserIds = $project->projectMembers()->pluck('user_id');
            $candidateQuery->whereIn('user_id', $projectUserIds);
        }

        $candidates = $candidateQuery->get();

        if ($candidates->isEmpty()) {
            return [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'recommendations' => [],
                'grounding' => [
                    'status' => 'insufficient_data',
                    'is_factual' => true,
                    'message' => 'No active team members found in the project scope.',
                    'evidence_sources' => ["project:{$projectId}"],
                    'missing_information' => ['No active team members assigned to team/project.'],
                ],
            ];
        }

        $recommendations = [];
        $evidenceSources = ["project:{$projectId}"];
        if ($taskId) {
            $evidenceSources[] = "task:{$taskId}";
        }

        foreach ($candidates as $emp) {
            $userModel = $emp->user;
            if (!$userModel) {
                continue;
            }

            $profile = $emp->projectProfile;
            $empSkills = $profile && is_array($profile->skills) ? $profile->skills : [];
            $availabilityStatus = $profile?->availability_status ?? 'available';
            $weeklyCapacity = $profile?->weekly_capacity_hours ?? 40;

            // Active task load
            $activeTasks = Task::where('assigned_to', $userModel->id)
                ->whereIn('status', [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::IN_REVIEW, TaskStatus::BLOCKED])
                ->get();
            $activeCount = $activeTasks->count();
            $totalActiveHours = (float) $activeTasks->sum('estimated_hours');

            // Compute skill match score
            $matchedSkills = [];
            $skillScore = 1.0;
            if (!empty($requiredSkills)) {
                $matchedSkills = array_values(array_intersect(
                    array_map('strtolower', $empSkills),
                    array_map('strtolower', $requiredSkills)
                ));
                $skillScore = count($matchedSkills) / max(1, count($requiredSkills));
            }

            // Compute availability score
            $availabilityScore = match ($availabilityStatus) {
                'available' => 1.0,
                'partially_available' => 0.6,
                'allocated' => 0.2,
                'on_leave' => 0.0,
                default => 0.5,
            };

            // Workload factor (lower active tasks = higher score)
            $workloadScore = max(0.1, 1.0 - ($activeCount * 0.15));

            // Composite fit score
            $fitScore = round(($skillScore * 0.45) + ($availabilityScore * 0.35) + ($workloadScore * 0.20), 2);

            $reasons = [];
            if (!empty($requiredSkills)) {
                if (!empty($matchedSkills)) {
                    $reasons[] = 'Matched skills: ' . implode(', ', array_map('ucfirst', $matchedSkills)) . '.';
                } else {
                    $reasons[] = 'No direct match for requested skills (' . implode(', ', $requiredSkills) . ').';
                }
            }
            $reasons[] = "Current active tasks: {$activeCount} ({$totalActiveHours} estimated hrs).";
            $reasons[] = "Availability status: {$availabilityStatus} (Weekly capacity: {$weeklyCapacity}h).";

            $evidenceSources[] = "employee:{$emp->id}";

            $recommendations[] = [
                'employee_id' => $emp->id,
                'user_id' => $userModel->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->first_name . ' ' . $emp->last_name,
                'department' => $emp->department,
                'designation' => $emp->designation,
                'fit_score' => $fitScore,
                'availability_status' => $availabilityStatus,
                'current_active_tasks' => $activeCount,
                'active_estimated_hours' => $totalActiveHours,
                'skills' => $empSkills,
                'matched_skills' => $matchedSkills,
                'reasons' => $reasons,
            ];
        }

        // Sort by fit score descending
        usort($recommendations, fn ($a, $b) => $b['fit_score'] <=> $a['fit_score']);

        return [
            'project_id' => $projectId,
            'task_id' => $taskId,
            'required_skills' => $requiredSkills,
            'recommendations_count' => count($recommendations),
            'recommendations' => $recommendations,
            'grounding' => [
                'status' => 'confirmed',
                'is_factual' => true,
                'message' => 'Recommendation calculated deterministically from team membership, skills, and current active task load. No mutations performed.',
                'evidence_sources' => $evidenceSources,
                'missing_information' => empty($requiredSkills) ? ['No explicit required skills provided; evaluated general workload and availability.'] : [],
            ],
        ];
    }

    /**
     * T288: Generate Management Reports via MCP Data.
     * Aggregates productivity, workload, deadline, and financial metrics using authorized data.
     */
    public function generateManagementReport(User $user, array $params): array
    {
        $reportType = $params['report_type'] ?? 'productivity';
        $projectId = !empty($params['project_id']) ? (int) $params['project_id'] : null;
        $teamId = !empty($params['team_id']) ? (int) $params['team_id'] : null;

        $filters = [];
        if ($projectId) {
            $filters['project_id'] = $projectId;
        }
        if ($teamId) {
            $filters['team_id'] = $teamId;
        }

        switch ($reportType) {
            case 'productivity':
                $metrics = $this->reportingService->getEmployeeProductivityMetrics($user, $filters);
                $evidenceSources = [];

                $sanitizedMetrics = $metrics->map(function ($row) use (&$evidenceSources) {
                    $evidenceSources[] = "employee:{$row['employee']->id}";
                    return [
                        'employee_id' => $row['employee']->id,
                        'name' => $row['employee']->first_name . ' ' . $row['employee']->last_name,
                        'department' => $row['employee']->department,
                        'designation' => $row['employee']->designation,
                        'total_assigned' => $row['total_assigned'],
                        'total_completed' => $row['total_completed'],
                        'overdue_count' => $row['overdue_count'],
                        'on_time_percentage' => $row['on_time_percentage'],
                        'estimated_hours' => $row['estimated_hours'],
                        'logged_approved_hours' => $row['logged_approved_hours'],
                        'hour_variance' => $row['hour_variance'],
                    ];
                })->all();

                return [
                    'report_type' => 'project_productivity',
                    'generated_at' => now()->toIso8601String(),
                    'summary' => [
                        'total_employees_evaluated' => count($sanitizedMetrics),
                        'average_on_time_percentage' => count($sanitizedMetrics) > 0 ? round(array_sum(array_column($sanitizedMetrics, 'on_time_percentage')) / count($sanitizedMetrics), 1) : 100.0,
                        'total_overdue_tasks' => array_sum(array_column($sanitizedMetrics, 'overdue_count')),
                    ],
                    'metrics' => $sanitizedMetrics,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => array_unique($evidenceSources),
                        'missing_information' => [],
                    ],
                ];

            case 'workload':
                $workloads = $this->reportingService->getTeamWorkloadMetrics($user, $filters);
                $evidenceSources = [];

                $sanitizedTeams = $workloads->map(function ($teamData) use (&$evidenceSources) {
                    $team = $teamData['team'];
                    $evidenceSources[] = "team:{$team->id}";

                    $members = $teamData['members']->map(fn ($m) => [
                        'user_id' => $m['user']->id,
                        'name' => $m['user']->name,
                        'active_tasks_count' => $m['active_tasks_count'],
                        'due_soon_count' => $m['due_soon_count'],
                        'approved_month_hours' => $m['approved_month_hours'],
                        'capacity_utilization_percent' => $m['capacity_utilization'],
                    ])->all();

                    return [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'total_members' => $teamData['total_members'],
                        'total_active_tasks' => $teamData['total_active_tasks'],
                        'total_due_soon' => $teamData['total_due_soon'],
                        'members' => $members,
                    ];
                })->all();

                return [
                    'report_type' => 'team_workload',
                    'generated_at' => now()->toIso8601String(),
                    'summary' => [
                        'total_teams' => count($sanitizedTeams),
                        'total_active_tasks' => array_sum(array_column($sanitizedTeams, 'total_active_tasks')),
                        'total_due_soon' => array_sum(array_column($sanitizedTeams, 'total_due_soon')),
                    ],
                    'metrics' => $sanitizedTeams,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => array_unique($evidenceSources),
                        'missing_information' => [],
                    ],
                ];

            case 'budget_utilization':
            case 'financial':
                $projectsQuery = $this->getAuthorizedProjectsQuery($user);
                if ($projectId) {
                    $projectsQuery->where('id', $projectId);
                }
                $projects = $projectsQuery->get();

                $sanitizedBudget = [];
                $evidenceSources = [];
                $unauthorizedCount = 0;

                foreach ($projects as $proj) {
                    // Check financial view permission for this specific project
                    if (!Gate::forUser($user)->allows('viewFinancials', $proj)) {
                        $unauthorizedCount++;
                        continue;
                    }

                    $evidenceSources[] = "project:{$proj->id}";
                    $budgetData = $this->reportingService->getProjectBudgetUtilization($user, ['project_id' => $proj->id])->first();

                    if ($budgetData) {
                        $sanitizedBudget[] = [
                            'project_id' => $proj->id,
                            'project_name' => $proj->name,
                            'budget' => $budgetData['budget'],
                            'labor_cost' => $budgetData['labor_cost'],
                            'consumed_percent' => $budgetData['consumed_percent'],
                            'budget_remaining' => $budgetData['budget_remaining'],
                            'estimated_hours' => $budgetData['estimated_hours'],
                            'logged_approved_hours' => $budgetData['logged_approved_hours'],
                            'utilization_status' => $budgetData['utilization_status'],
                        ];
                    }
                }

                if (empty($sanitizedBudget) && $unauthorizedCount > 0) {
                    return [
                        'report_type' => 'budget_utilization',
                        'grounding' => [
                            'status' => 'not_authorized',
                            'is_factual' => false,
                            'message' => 'User does not possess financial viewing permissions (viewFinancials) for the requested project(s).',
                            'evidence_sources' => [],
                        ],
                        'metrics' => [],
                    ];
                }

                return [
                    'report_type' => 'budget_utilization',
                    'generated_at' => now()->toIso8601String(),
                    'summary' => [
                        'projects_evaluated' => count($sanitizedBudget),
                        'total_budget' => array_sum(array_column($sanitizedBudget, 'budget')),
                        'total_labor_cost' => array_sum(array_column($sanitizedBudget, 'labor_cost')),
                        'overall_consumed_percent' => array_sum(array_column($sanitizedBudget, 'budget')) > 0
                            ? round((array_sum(array_column($sanitizedBudget, 'labor_cost')) / array_sum(array_column($sanitizedBudget, 'budget'))) * 100, 1)
                            : 0.0,
                    ],
                    'metrics' => $sanitizedBudget,
                    'grounding' => [
                        'status' => 'confirmed',
                        'is_factual' => true,
                        'evidence_sources' => array_unique($evidenceSources),
                        'missing_information' => [],
                    ],
                ];

            default:
                return [
                    'report_type' => $reportType,
                    'grounding' => [
                        'status' => 'insufficient_data',
                        'is_factual' => false,
                        'message' => "Unsupported report type: {$reportType}. Supported types are: productivity, workload, budget_utilization.",
                        'evidence_sources' => [],
                    ],
                    'metrics' => [],
                ];
        }
    }
}
