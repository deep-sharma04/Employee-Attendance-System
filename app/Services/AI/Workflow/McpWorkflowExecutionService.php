<?php

namespace App\Services\AI\Workflow;

use App\DTOs\AI\McpRequestContext;
use App\DTOs\AI\McpResponse;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AiActionLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\AI\McpIntegrationService;
use App\Services\AI\McpSecurityGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class McpWorkflowExecutionService
{
    public function __construct(
        protected McpIntegrationService $integrationService,
        protected McpSecurityGuard $securityGuard
    ) {}

    /**
     * T290: AI-Assisted Project Creation.
     * Validates scoping, applies approval gate for high budget (>50k) or Team Lead, and creates project.
     */
    public function createProject(User $user, array $params, ?string $idempotencyKey = null): array
    {
        // 1. Authorization: Only Super Admin and Manager can create projects
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            throw new \RuntimeException('Unauthorized: Only Super Administrators and Managers can create projects.');
        }

        // 2. Idempotency Check (T294)
        if ($idempotencyKey) {
            $existingLog = AiActionLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existingLog && $existingLog->execution_status === 'success') {
                return (array) $existingLog->execution_result;
            }
        }

        // 3. Validation
        if (empty($params['name']) || empty($params['code']) || empty($params['start_date']) || empty($params['deadline'])) {
            throw new \InvalidArgumentException('Missing required project fields: name, code, start_date, deadline.');
        }

        $clientId = !empty($params['client_id']) ? (int) $params['client_id'] : null;
        $teamId = !empty($params['team_id']) ? (int) $params['team_id'] : null;
        $budget = !empty($params['budget']) ? (float) $params['budget'] : 0.0;

        // Team Lead proposals or High Budget (> $50,000) by Manager require explicit approval gate (T292)
        $requiresApproval = !empty($params['requires_approval']) || ($budget > 50000.00 && !$user->isSuperAdmin());

        if ($requiresApproval) {
            $proposal = [
                'action' => 'project.create',
                'name' => $params['name'],
                'code' => $params['code'],
                'budget' => $budget,
                'client_id' => $clientId,
                'team_id' => $teamId,
                'requested_by' => $user->name,
            ];

            $context = new McpRequestContext(
                user: $user,
                toolName: 'project.create',
                arguments: $params,
                projectId: null,
                teamId: $teamId,
                clientId: $clientId,
                idempotencyKey: $idempotencyKey
            );

            $log = AiActionLog::create([
                'user_id' => $user->id,
                'tool_name' => 'project.create',
                'action_type' => 'creation',
                'parameters' => $params,
                'approval_state' => 'pending_approval',
                'execution_status' => 'pending',
                'execution_result' => $proposal,
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'status' => 'pending_approval',
                'action_log_id' => $log->id,
                'message' => 'Project creation exceeds auto-approval threshold and requires server-side approval.',
                'proposal' => $proposal,
            ];
        }

        // Transactional creation (T295)
        return DB::transaction(function () use ($user, $params, $clientId, $teamId, $budget, $idempotencyKey) {
            $project = Project::create([
                'name' => $params['name'],
                'code' => $params['code'],
                'client_id' => $clientId,
                'team_id' => $teamId,
                'manager_id' => !empty($params['manager_id']) ? (int) $params['manager_id'] : $user->id,
                'budget' => $budget,
                'status' => ProjectStatus::tryFrom($params['status'] ?? 'planning') ?? ProjectStatus::PLANNING,
                'priority' => ProjectPriority::tryFrom($params['priority'] ?? 'medium') ?? ProjectPriority::MEDIUM,
                'health' => ProjectHealth::tryFrom($params['health'] ?? 'good') ?? ProjectHealth::GOOD,
                'start_date' => $params['start_date'],
                'deadline' => $params['deadline'],
                'description' => $params['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $result = [
                'status' => 'created',
                'project_id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'budget' => (float) $project->budget,
                'created_at' => $project->created_at->toIso8601String(),
            ];

            if ($idempotencyKey) {
                AiActionLog::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'tool_name' => 'project.create',
                    'action_type' => 'creation',
                    'parameters' => $params,
                    'approval_state' => 'not_required',
                    'execution_status' => 'success',
                    'execution_result' => $result,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            return $result;
        });
    }

    /**
     * T290: AI-Assisted Task Creation.
     */
    public function createTask(User $user, array $params, ?string $idempotencyKey = null): array
    {
        if (empty($params['project_id']) || empty($params['title']) || empty($params['task_code'])) {
            throw new \InvalidArgumentException('Missing required task fields: project_id, title, task_code.');
        }

        $projectId = (int) $params['project_id'];
        $project = Project::findOrFail($projectId);

        // Scope verification: User must manage or lead or belong to project
        if (!$user->isSuperAdmin()) {
            if ($user->isManager() && $project->manager_id !== $user->id && (!$project->team || $project->team->manager_id !== $user->id)) {
                throw new \RuntimeException('Unauthorized: Manager does not own target project.');
            }
            if ($user->isTeamLead() && (!$project->team || $project->team->team_lead_id !== $user->id)) {
                throw new \RuntimeException('Unauthorized: Team Lead does not lead target project team.');
            }
        }

        // Idempotency Check (T294)
        if ($idempotencyKey) {
            $existingLog = AiActionLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existingLog && $existingLog->execution_status === 'success') {
                return (array) $existingLog->execution_result;
            }
        }

        // Transactional creation (T295)
        return DB::transaction(function () use ($user, $project, $params, $idempotencyKey) {
            $assignedTo = !empty($params['assigned_to']) ? (int) $params['assigned_to'] : null;

            if ($assignedTo) {
                $assignee = User::findOrFail($assignedTo);
                if (!$assignee->is_active) {
                    throw new \InvalidArgumentException("Cannot assign task to inactive user [{$assignee->email}].");
                }
            }

            $task = Task::create([
                'project_id' => $project->id,
                'milestone_id' => !empty($params['milestone_id']) ? (int) $params['milestone_id'] : null,
                'parent_id' => !empty($params['parent_id']) ? (int) $params['parent_id'] : null,
                'title' => $params['title'],
                'task_code' => $params['task_code'],
                'priority' => TaskPriority::tryFrom($params['priority'] ?? 'medium') ?? TaskPriority::MEDIUM,
                'status' => TaskStatus::tryFrom($params['status'] ?? 'todo') ?? TaskStatus::TODO,
                'assigned_to' => $assignedTo,
                'estimated_hours' => !empty($params['estimated_hours']) ? (float) $params['estimated_hours'] : 0.0,
                'due_date' => $params['due_date'] ?? null,
                'description' => $params['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $result = [
                'status' => 'created',
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'title' => $task->title,
                'task_code' => $task->task_code,
                'assigned_to' => $task->assigned_to,
                'created_at' => $task->created_at->toIso8601String(),
            ];

            if ($idempotencyKey) {
                AiActionLog::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'tool_name' => 'task.create',
                    'action_type' => 'creation',
                    'parameters' => $params,
                    'approval_state' => 'not_required',
                    'execution_status' => 'success',
                    'execution_result' => $result,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            return $result;
        });
    }

    /**
     * T291: AI-Assisted Task Assignment.
     * Assigns task to active, eligible team member within authorized scope.
     */
    public function assignTask(User $user, array $params, ?string $idempotencyKey = null): array
    {
        if (empty($params['task_id']) || empty($params['assigned_to'])) {
            throw new \InvalidArgumentException('Missing required fields: task_id, assigned_to.');
        }

        $taskId = (int) $params['task_id'];
        $assigneeId = (int) $params['assigned_to'];

        /** @var Task $task */
        $task = Task::with('project.team')->findOrFail($taskId);
        $project = $task->project;

        // Scoping: Only Super Admin, Project Manager, or Team Lead can assign
        if (!$user->isSuperAdmin()) {
            if ($user->isManager() && $project->manager_id !== $user->id && (!$project->team || $project->team->manager_id !== $user->id)) {
                throw new \RuntimeException('Unauthorized: Manager does not own target project.');
            }
            if ($user->isTeamLead() && (!$project->team || $project->team->team_lead_id !== $user->id)) {
                throw new \RuntimeException('Unauthorized: Team Lead does not lead target project team.');
            }
            if ($user->isEmployee() || $user->isClient()) {
                throw new \RuntimeException('Unauthorized: Employees and Clients cannot assign tasks.');
            }
        }

        // Validate Assignee Eligibility
        $assignee = User::findOrFail($assigneeId);
        if (!$assignee->is_active) {
            throw new \InvalidArgumentException("Cannot assign task to inactive user [{$assignee->email}].");
        }

        // If project is assigned to a team, verify assignee is in the team or project members
        if ($project->team_id) {
            $isTeamMember = $project->team->members()->where('users.id', $assigneeId)->exists();
            $isProjectMember = $project->projectMembers()->where('user_id', $assigneeId)->exists();
            if (!$isTeamMember && !$isProjectMember && !$assignee->isSuperAdmin()) {
                throw new \InvalidArgumentException("User [{$assignee->name}] is not a member of the project team.");
            }
        }

        // Idempotency Check (T294)
        if ($idempotencyKey) {
            $existingLog = AiActionLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existingLog && $existingLog->execution_status === 'success') {
                return (array) $existingLog->execution_result;
            }
        }

        // Transactional Assignment (T295)
        return DB::transaction(function () use ($user, $task, $assignee, $params, $idempotencyKey) {
            $previousAssigneeId = $task->assigned_to;
            $task->assigned_to = $assignee->id;
            $task->save();

            $result = [
                'status' => 'assigned',
                'task_id' => $task->id,
                'title' => $task->title,
                'previous_assignee_id' => $previousAssigneeId,
                'new_assignee_id' => $assignee->id,
                'new_assignee_name' => $assignee->name,
                'assigned_at' => now()->toIso8601String(),
            ];

            if ($idempotencyKey) {
                AiActionLog::create([
                    'user_id' => $user->id,
                    'project_id' => $task->project_id,
                    'tool_name' => 'task.assign',
                    'action_type' => 'mutation',
                    'parameters' => $params,
                    'approval_state' => 'not_required',
                    'execution_status' => 'success',
                    'execution_result' => $result,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            return $result;
        });
    }

    /**
     * T293: Execute Destructive / Bulk MCP Actions (e.g. task.bulk_reassign).
     * Sensitive multi-task reassignment with transactional guarantee (T295) and approval gates (T292).
     */
    public function bulkReassignTasks(User $user, array $params, ?string $idempotencyKey = null): array
    {
        if (empty($params['from_user_id']) || empty($params['to_user_id'])) {
            throw new \InvalidArgumentException('Missing required fields: from_user_id, to_user_id.');
        }

        $fromUserId = (int) $params['from_user_id'];
        $toUserId = (int) $params['to_user_id'];
        $projectId = !empty($params['project_id']) ? (int) $params['project_id'] : null;

        $toUser = User::findOrFail($toUserId);
        if (!$toUser->is_active) {
            throw new \InvalidArgumentException("Target assignee [{$toUser->email}] is inactive.");
        }

        // Idempotency Check (T294)
        if ($idempotencyKey) {
            $existingLog = AiActionLog::where('idempotency_key', $idempotencyKey)->first();
            if ($existingLog && $existingLog->execution_status === 'success') {
                return (array) $existingLog->execution_result;
            }
        }

        // Approval Gate check (T292, T293): Team Leads CANNOT execute bulk reassignments directly; must be proposed
        $requiresApproval = $user->isTeamLead() || !empty($params['requires_approval']);

        if ($requiresApproval) {
            $proposal = [
                'action' => 'task.bulk_reassign',
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'project_id' => $projectId,
                'requested_by' => $user->name,
                'timestamp' => now()->toIso8601String(),
            ];

            $log = AiActionLog::create([
                'user_id' => $user->id,
                'project_id' => $projectId,
                'tool_name' => 'task.bulk_reassign',
                'action_type' => 'destructive',
                'parameters' => $params,
                'approval_state' => 'pending_approval',
                'execution_status' => 'pending',
                'execution_result' => $proposal,
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'status' => 'pending_approval',
                'action_log_id' => $log->id,
                'message' => 'Bulk task reassignment requires server-side Manager or Super Admin approval.',
                'proposal' => $proposal,
            ];
        }

        // Only Super Admin or Manager with valid scope can execute directly
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            throw new \RuntimeException('Unauthorized: Only Super Administrators and Managers can execute bulk task reassignments.');
        }

        // Transactional Execution (T295)
        return DB::transaction(function () use ($user, $fromUserId, $toUser, $projectId, $params, $idempotencyKey) {
            $query = Task::where('assigned_to', $fromUserId)
                ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::CANCELLED]);

            if ($projectId) {
                $query->where('project_id', $projectId);
            } elseif ($user->isManager()) {
                // Scoped to manager's projects
                $managedProjectIds = Project::where('manager_id', $user->id)->pluck('id');
                $query->whereIn('project_id', $managedProjectIds);
            }

            $taskIds = $query->pluck('id')->toArray();
            $reassignedCount = count($taskIds);

            if ($reassignedCount > 0) {
                Task::whereIn('id', $taskIds)->update([
                    'assigned_to' => $toUser->id,
                    'updated_at' => now(),
                ]);
            }

            $result = [
                'status' => 'reassigned',
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUser->id,
                'to_user_name' => $toUser->name,
                'reassigned_count' => $reassignedCount,
                'reassigned_task_ids' => $taskIds,
            ];

            if ($idempotencyKey) {
                AiActionLog::create([
                    'user_id' => $user->id,
                    'project_id' => $projectId,
                    'tool_name' => 'task.bulk_reassign',
                    'action_type' => 'destructive',
                    'parameters' => $params,
                    'approval_state' => 'not_required',
                    'execution_status' => 'success',
                    'execution_result' => $result,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            return $result;
        });
    }

    /**
     * T292: Get Pending Approval Proposals scoped to the authenticated user.
     */
    public function getPendingApprovals(User $user): array
    {
        if ($user->isEmployee() || $user->isClient()) {
            return [
                'pending_count' => 0,
                'proposals' => [],
                'message' => 'Unauthorized: Employees and Clients do not have approval permissions.',
            ];
        }

        $query = AiActionLog::with(['user', 'project'])
            ->where('approval_state', 'pending_approval')
            ->where('execution_status', 'pending');

        if ($user->isManager()) {
            $managedProjectIds = Project::where('manager_id', $user->id)->pluck('id')->toArray();
            $managedTeamIds = Team::where('manager_id', $user->id)->pluck('id')->toArray();

            $query->where(function ($q) use ($managedProjectIds, $managedTeamIds) {
                $q->whereIn('project_id', $managedProjectIds)
                  ->orWhereIn('team_id', $managedTeamIds);
            });
        } elseif ($user->isTeamLead()) {
            // Team Leads can view their proposed requests but cannot approve them
            $query->where('user_id', $user->id);
        }

        $logs = $query->latest()->get();

        $proposals = $logs->map(fn (AiActionLog $log) => [
            'action_log_id' => $log->id,
            'tool_name' => $log->tool_name,
            'action_type' => $log->action_type,
            'project_id' => $log->project_id,
            'project_name' => $log->project?->name,
            'requested_by' => $log->user?->name,
            'parameters' => $log->parameters,
            'proposal' => $log->execution_result,
            'created_at' => $log->created_at->toIso8601String(),
        ])->all();

        return [
            'pending_count' => count($proposals),
            'proposals' => $proposals,
        ];
    }

    /**
     * T292, T293: Approve a Pending Action and execute it atomically.
     */
    public function approveAction(int $actionLogId, User $approver): array
    {
        $log = AiActionLog::findOrFail($actionLogId);

        if (!$log->isPendingApproval()) {
            throw new \RuntimeException("Action Log #{$actionLogId} is not pending approval (Current state: {$log->approval_state}).");
        }

        if (!$this->securityGuard->canApproveAction($approver, $log->project_id)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to approve this action.');
        }

        // Atomically approve and execute (T295)
        return DB::transaction(function () use ($log, $approver) {
            $log->update([
                'approval_state' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $params = (array) $log->parameters;
            $params['requires_approval'] = false; // Clear approval flag so it executes

            $executionResult = null;

            switch ($log->tool_name) {
                case 'project.create':
                    $executionResult = $this->createProject($log->user, $params);
                    break;

                case 'task.create':
                    $executionResult = $this->createTask($log->user, $params);
                    break;

                case 'task.assign':
                    $executionResult = $this->assignTask($log->user, $params);
                    break;

                case 'task.bulk_reassign':
                    $executionResult = $this->bulkReassignTasks($approver, $params);
                    break;

                default:
                    $executionResult = ['status' => 'approved_and_executed'];
            }

            $log->update([
                'execution_status' => 'success',
                'execution_result' => $executionResult,
            ]);

            return [
                'status' => 'approved_and_executed',
                'action_log_id' => $log->id,
                'approved_by' => $approver->name,
                'execution_result' => $executionResult,
            ];
        });
    }

    /**
     * T292: Reject a Pending Action Proposal with a mandatory reason.
     */
    public function rejectAction(int $actionLogId, User $rejector, string $reason): array
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Rejection reason cannot be empty.');
        }

        $log = AiActionLog::findOrFail($actionLogId);

        if (!$log->isPendingApproval()) {
            throw new \RuntimeException("Action Log #{$actionLogId} is not pending approval.");
        }

        if (!$this->securityGuard->canApproveAction($rejector, $log->project_id)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to reject this action.');
        }

        $log->update([
            'approval_state' => 'rejected',
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'execution_status' => 'aborted',
        ]);

        return [
            'status' => 'rejected',
            'action_log_id' => $log->id,
            'rejected_by' => $rejector->name,
            'rejection_reason' => $reason,
        ];
    }
}
