<?php

namespace App\Services\Report;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimesheetEntry;
use App\Models\User;
use App\Services\Project\ProjectLaborCostService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportingService
{
    public function __construct(
        protected ProjectLaborCostService $laborCostService
    ) {}

    /**
     * Get authorized projects query for a user based on their role and scope.
     */
    public function getAuthorizedProjectsQuery(User $user): Builder
    {
        $query = Project::with(['client', 'team', 'manager', 'members']);

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

        // Default: restricted
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get authorized teams query for a user.
     */
    public function getAuthorizedTeamsQuery(User $user): Builder
    {
        $query = Team::with(['manager', 'teamLead', 'members', 'employees']);

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isManager()) {
            return $query->where('manager_id', $user->id);
        }

        if ($user->isTeamLead()) {
            return $query->where('team_lead_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * T256: Get Executive Dashboard Metrics.
     */
    public function getExecutiveDashboardMetrics(User $user, array $filters = []): array
    {
        $projectsQuery = $this->getAuthorizedProjectsQuery($user);

        if (!empty($filters['team_id'])) {
            $projectsQuery->where('team_id', $filters['team_id']);
        }
        if (!empty($filters['client_id'])) {
            $projectsQuery->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['status'])) {
            $projectsQuery->where('status', $filters['status']);
        }
        if (!empty($filters['health'])) {
            $projectsQuery->where('health', $filters['health']);
        }
        if (!empty($filters['start_date'])) {
            $projectsQuery->where('start_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $projectsQuery->where('deadline', '<=', $filters['end_date']);
        }

        $projects = $projectsQuery->latest()->get();
        $projectIds = $projects->pluck('id')->toArray();

        // Project Status Breakdown
        $statusCounts = [
            'active' => $projects->where('status', ProjectStatus::ACTIVE)->count(),
            'planning' => $projects->where('status', ProjectStatus::PLANNING)->count(),
            'on_hold' => $projects->where('status', ProjectStatus::ON_HOLD)->count(),
            'completed' => $projects->where('status', ProjectStatus::COMPLETED)->count(),
            'cancelled' => $projects->where('status', ProjectStatus::CANCELLED)->count(),
            'total' => $projects->count(),
        ];

        // Project Health Breakdown
        $healthCounts = [
            'good' => $projects->where('health', ProjectHealth::GOOD)->count(),
            'at_risk' => $projects->where('health', ProjectHealth::AT_RISK)->count(),
            'critical' => $projects->where('health', ProjectHealth::CRITICAL)->count(),
            'not_started' => $projects->where('health', ProjectHealth::NOT_STARTED)->count(),
        ];

        // Deadlines & Overdue Projects
        $today = now()->toDateString();
        $overdueProjects = $projects->filter(fn (Project $p) => $p->deadline && $p->deadline->toDateString() < $today && $p->status !== ProjectStatus::COMPLETED);
        $dueSoonProjects = $projects->filter(fn (Project $p) => $p->deadline && $p->deadline->toDateString() >= $today && $p->deadline->toDateString() <= now()->addDays(14)->toDateString() && $p->status !== ProjectStatus::COMPLETED);

        // Budget & Financial Totals
        $totalBudget = (float) $projects->sum('budget');
        $totalLaborCost = 0.0;
        foreach ($projectIds as $pid) {
            $totalLaborCost += $this->laborCostService->getTotalLaborCostForProject($pid);
        }
        $budgetUtilizationPercent = $totalBudget > 0 ? round(($totalLaborCost / $totalBudget) * 100, 1) : 0.0;

        // Timesheet Hours Summary
        $approvedHours = (float) TimesheetEntry::whereIn('project_id', $projectIds)
            ->whereHas('timesheet', fn ($q) => $q->where('status', TimesheetStatus::APPROVED->value))
            ->sum('hours');

        return [
            'projects' => $projects,
            'statusCounts' => $statusCounts,
            'healthCounts' => $healthCounts,
            'overdueCount' => $overdueProjects->count(),
            'dueSoonCount' => $dueSoonProjects->count(),
            'totalBudget' => $totalBudget,
            'totalLaborCost' => round($totalLaborCost, 2),
            'budgetUtilizationPercent' => $budgetUtilizationPercent,
            'approvedHours' => $approvedHours,
        ];
    }

    /**
     * T257: Calculate Employee Productivity Metrics.
     */
    public function getEmployeeProductivityMetrics(User $user, array $filters = []): Collection
    {
        $teamsQuery = $this->getAuthorizedTeamsQuery($user);
        $teams = $teamsQuery->get();
        $teamIds = $teams->pluck('id')->toArray();

        // Get users belonging to these teams or assigned to projects
        $projectsQuery = $this->getAuthorizedProjectsQuery($user);
        $projectIds = $projectsQuery->pluck('id')->toArray();

        $employeesQuery = Employee::with(['user']);

        if (!$user->isSuperAdmin()) {
            $employeesQuery->where(function ($q) use ($teamIds, $projectIds) {
                $q->whereHas('user.teamMemberships', fn ($tm) => $tm->whereIn('team_id', $teamIds))
                  ->orWhereHas('user', function ($uq) use ($projectIds) {
                      $uq->whereHas('projectMemberships', fn ($pm) => $pm->whereIn('project_id', $projectIds));
                  });
            });
        }

        if (!empty($filters['department'])) {
            $employeesQuery->where('department', $filters['department']);
        }
        if (!empty($filters['employee_id'])) {
            $employeesQuery->where('id', $filters['employee_id']);
        }

        $employees = $employeesQuery->get();
        $today = now()->toDateString();

        return $employees->map(function (Employee $employee) use ($today, $projectIds, $user) {
            $userModel = $employee->user;
            if (!$userModel) {
                return null;
            }

            // Tasks assigned to employee
            $tasksQuery = Task::where('assigned_to', $userModel->id);
            if (!$user->isSuperAdmin() && !empty($projectIds)) {
                $tasksQuery->whereIn('project_id', $projectIds);
            }

            $tasks = $tasksQuery->get();
            $totalAssigned = $tasks->count();
            $completedTasks = $tasks->where('status', TaskStatus::DONE);
            $totalCompleted = $completedTasks->count();

            // Overdue tasks
            $overdueTasks = $tasks->filter(function (Task $task) use ($today) {
                return $task->due_date 
                    && $task->due_date->toDateString() < $today 
                    && !in_array($task->status, [TaskStatus::DONE, TaskStatus::CANCELLED]);
            });

            // On-time completion rate
            $onTimeCount = $completedTasks->filter(function (Task $task) {
                if (!$task->due_date) {
                    return true;
                }
                $completionDate = $task->completed_at ? $task->completed_at->toDateString() : ($task->updated_at ? $task->updated_at->toDateString() : null);
                return $completionDate ? $completionDate <= $task->due_date->toDateString() : true;
            })->count();

            $onTimePercentage = $totalCompleted > 0 
                ? round(($onTimeCount / $totalCompleted) * 100, 1) 
                : ($overdueTasks->count() > 0 ? 0.0 : 100.0);

            // Estimated vs Logged Approved Hours
            $estimatedHours = (float) $tasks->sum('estimated_hours');
            $loggedApprovedHours = (float) TimesheetEntry::whereHas('timesheet', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id)
                  ->where('status', TimesheetStatus::APPROVED->value);
            })->sum('hours');

            $hourVariance = round($loggedApprovedHours - $estimatedHours, 2);

            return [
                'employee' => $employee,
                'user' => $userModel,
                'total_assigned' => $totalAssigned,
                'total_completed' => $totalCompleted,
                'overdue_count' => $overdueTasks->count(),
                'on_time_percentage' => $onTimePercentage,
                'estimated_hours' => $estimatedHours,
                'logged_approved_hours' => $loggedApprovedHours,
                'hour_variance' => $hourVariance,
            ];
        })->filter()->values();
    }

    /**
     * T258: Build Team Workload View Metrics.
     */
    public function getTeamWorkloadMetrics(User $user, array $filters = []): Collection
    {
        $teamsQuery = $this->getAuthorizedTeamsQuery($user);

        if (!empty($filters['team_id'])) {
            $teamsQuery->where('id', $filters['team_id']);
        }

        $teams = $teamsQuery->get();
        $today = now()->toDateString();
        $next7Days = now()->addDays(7)->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return $teams->map(function (Team $team) use ($today, $next7Days, $monthStart, $monthEnd) {
            $members = $team->members()->with(['employee'])->get();

            $memberWorkloads = $members->map(function (User $member) use ($today, $next7Days, $monthStart, $monthEnd) {
                $employee = $member->employee;

                // Active assigned tasks
                $activeTasks = Task::where('assigned_to', $member->id)
                    ->whereIn('status', [TaskStatus::TODO, TaskStatus::IN_PROGRESS, TaskStatus::IN_REVIEW, TaskStatus::BLOCKED])
                    ->get();

                // Tasks due soon (next 7 days)
                $dueSoonTasks = $activeTasks->filter(function (Task $task) use ($today, $next7Days) {
                    return $task->due_date 
                        && $task->due_date->toDateString() >= $today 
                        && $task->due_date->toDateString() <= $next7Days;
                });

                // Hours logged this month (approved vs pending)
                $approvedMonthHours = 0.0;
                $pendingMonthHours = 0.0;

                if ($employee) {
                    $approvedMonthHours = (float) TimesheetEntry::whereHas('timesheet', function ($q) use ($employee, $monthStart, $monthEnd) {
                        $q->where('employee_id', $employee->id)
                          ->where('status', TimesheetStatus::APPROVED->value)
                          ->whereBetween('start_date', [$monthStart, $monthEnd]);
                    })->sum('hours');

                    $pendingMonthHours = (float) TimesheetEntry::whereHas('timesheet', function ($q) use ($employee, $monthStart, $monthEnd) {
                        $q->where('employee_id', $employee->id)
                          ->where('status', TimesheetStatus::SUBMITTED->value)
                          ->whereBetween('start_date', [$monthStart, $monthEnd]);
                    })->sum('hours');
                }

                // Standard 160h month capacity
                $capacityUtilization = round(($approvedMonthHours / 160) * 100, 1);

                return [
                    'user' => $member,
                    'employee' => $employee,
                    'active_tasks_count' => $activeTasks->count(),
                    'due_soon_count' => $dueSoonTasks->count(),
                    'approved_month_hours' => $approvedMonthHours,
                    'pending_month_hours' => $pendingMonthHours,
                    'capacity_utilization' => $capacityUtilization,
                ];
            });

            return [
                'team' => $team,
                'members' => $memberWorkloads,
                'total_members' => $members->count(),
                'total_active_tasks' => $memberWorkloads->sum('active_tasks_count'),
                'total_due_soon' => $memberWorkloads->sum('due_soon_count'),
            ];
        });
    }

    /**
     * T259: Build Project Budget & Cost Utilization Report.
     */
    public function getProjectBudgetUtilization(User $user, array $filters = []): Collection
    {
        $projectsQuery = $this->getAuthorizedProjectsQuery($user);

        if (!empty($filters['project_id'])) {
            $projectsQuery->where('id', $filters['project_id']);
        }
        if (!empty($filters['team_id'])) {
            $projectsQuery->where('team_id', $filters['team_id']);
        }
        if (!empty($filters['status'])) {
            $projectsQuery->where('status', $filters['status']);
        }

        $projects = $projectsQuery->latest()->get();

        return $projects->map(function (Project $project) {
            $budget = (float) ($project->budget ?? 0.0);
            $estimatedHours = (float) ($project->estimated_hours ?? 0.0);

            // Calculate approved labor cost
            $laborCost = $this->laborCostService->getTotalLaborCostForProject($project->id);

            // Calculate approved logged hours
            $loggedApprovedHours = (float) TimesheetEntry::where('project_id', $project->id)
                ->whereHas('timesheet', fn ($q) => $q->where('status', TimesheetStatus::APPROVED->value))
                ->sum('hours');

            $consumedPercent = $budget > 0 ? round(($laborCost / $budget) * 100, 1) : 0.0;
            $budgetRemaining = max(0.0, round($budget - $laborCost, 2));

            $utilizationStatus = 'under_budget';
            if ($consumedPercent > 100.0) {
                $utilizationStatus = 'over_budget';
            } elseif ($consumedPercent >= 85.0) {
                $utilizationStatus = 'near_limit';
            }

            return [
                'project' => $project,
                'budget' => $budget,
                'labor_cost' => $laborCost,
                'consumed_percent' => $consumedPercent,
                'budget_remaining' => $budgetRemaining,
                'estimated_hours' => $estimatedHours,
                'logged_approved_hours' => $loggedApprovedHours,
                'utilization_status' => $utilizationStatus,
            ];
        });
    }

    /**
     * T260: Export Reports to CSV.
     */
    public function exportToCsv(string $type, User $user, array $filters = []): StreamedResponse
    {
        $timestamp = date('Ymd_His');

        switch ($type) {
            case 'executive':
                $metrics = $this->getExecutiveDashboardMetrics($user, $filters);
                $filename = "executive_report_{$timestamp}.csv";
                $headers = ['Project Code', 'Project Name', 'Client', 'Team', 'Manager', 'Status', 'Health', 'Budget', 'Labor Cost', 'Progress %', 'Deadline'];

                $callback = function () use ($metrics, $headers) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $headers);

                    foreach ($metrics['projects'] as $p) {
                        $laborCost = $this->laborCostService->getTotalLaborCostForProject($p->id);
                        fputcsv($file, [
                            $p->code,
                            $p->name,
                            $p->client?->company_name ?? 'N/A',
                            $p->team?->name ?? 'N/A',
                            $p->manager?->name ?? 'N/A',
                            $p->status?->value ?? 'N/A',
                            $p->health?->value ?? 'N/A',
                            number_format((float) $p->budget, 2, '.', ''),
                            number_format($laborCost, 2, '.', ''),
                            $p->progressPercentage() . '%',
                            $p->deadline ? $p->deadline->toDateString() : 'N/A',
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'productivity':
                $productivity = $this->getEmployeeProductivityMetrics($user, $filters);
                $filename = "productivity_report_{$timestamp}.csv";
                $headers = ['Employee Code', 'Employee Name', 'Department', 'Assigned Tasks', 'Completed Tasks', 'Overdue Tasks', 'On-Time %', 'Estimated Hours', 'Logged Approved Hours', 'Variance Hours'];

                $callback = function () use ($productivity, $headers) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $headers);

                    foreach ($productivity as $row) {
                        fputcsv($file, [
                            $row['employee']->employee_code,
                            $row['employee']->full_name,
                            $row['employee']->department ?? 'N/A',
                            $row['total_assigned'],
                            $row['total_completed'],
                            $row['overdue_count'],
                            $row['on_time_percentage'] . '%',
                            $row['estimated_hours'],
                            $row['logged_approved_hours'],
                            $row['hour_variance'],
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'workload':
                $workload = $this->getTeamWorkloadMetrics($user, $filters);
                $filename = "team_workload_report_{$timestamp}.csv";
                $headers = ['Team Name', 'Member Name', 'Email', 'Active Tasks', 'Due Soon (7d)', 'Approved Hours (Month)', 'Pending Hours (Month)', 'Capacity Utilization %'];

                $callback = function () use ($workload, $headers) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $headers);

                    foreach ($workload as $teamData) {
                        foreach ($teamData['members'] as $mem) {
                            fputcsv($file, [
                                $teamData['team']->name,
                                $mem['user']->name,
                                $mem['user']->email,
                                $mem['active_tasks_count'],
                                $mem['due_soon_count'],
                                $mem['approved_month_hours'],
                                $mem['pending_month_hours'],
                                $mem['capacity_utilization'] . '%',
                            ]);
                        }
                    }
                    fclose($file);
                };
                break;

            case 'budget':
            default:
                $budgetData = $this->getProjectBudgetUtilization($user, $filters);
                $filename = "budget_utilization_report_{$timestamp}.csv";
                $headers = ['Project Code', 'Project Name', 'Client', 'Total Budget', 'Labor Cost', 'Consumed %', 'Budget Remaining', 'Estimated Hours', 'Logged Approved Hours', 'Utilization Status'];

                $callback = function () use ($budgetData, $headers) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $headers);

                    foreach ($budgetData as $b) {
                        fputcsv($file, [
                            $b['project']->code,
                            $b['project']->name,
                            $b['project']->client?->company_name ?? 'N/A',
                            number_format($b['budget'], 2, '.', ''),
                            number_format($b['labor_cost'], 2, '.', ''),
                            $b['consumed_percent'] . '%',
                            number_format($b['budget_remaining'], 2, '.', ''),
                            $b['estimated_hours'],
                            $b['logged_approved_hours'],
                            strtoupper(str_replace('_', ' ', $b['utilization_status'])),
                        ]);
                    }
                    fclose($file);
                };
                break;
        }

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
