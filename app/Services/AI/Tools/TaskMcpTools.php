<?php

namespace App\Services\AI\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Task\RecurringTaskService;
use App\Services\Task\TaskDependencyService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskMcpTools
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected TaskDependencyService $dependencyService,
        protected RecurringTaskService $recurringService
    ) {}

    /**
     * T281: task.create
     */
    public function create(User $user, array $args): array
    {
        // Clients cannot create internal tasks
        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Clients are not permitted to create tasks.');
        }

        $validator = validator($args, [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'task_code' => ['required', 'string', 'max:50', 'unique:tasks,task_code'],
            'milestone_id' => ['nullable', 'exists:project_milestones,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $project = Project::findOrFail($args['project_id']);

        // Check project scope authorization
        if ($user->isManager() && $project->manager_id !== $user->id && (!$project->team || $project->team->manager_id !== $user->id)) {
            throw new \RuntimeException('Unauthorized: You do not manage this project.');
        }
        if ($user->isTeamLead() && (!$project->team || $project->team->team_lead_id !== $user->id)) {
            throw new \RuntimeException('Unauthorized: You do not lead the team assigned to this project.');
        }

        $data = $validator->validated();
        $data['team_id'] = $project->team_id;
        $data['created_by'] = $user->id;
        $data['estimated_hours'] = $data['estimated_hours'] ?? 0;

        if ($data['status'] === TaskStatus::DONE->value) {
            $data['completed_at'] = now();
        }

        $task = Task::create($data);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'task.created',
            'details' => "Task '{$task->title}' ({$task->task_code}) created via MCP.",
        ]);

        $this->auditLogger->logProject(
            action: 'task.created',
            projectId: $project->id,
            afterValues: $task->toArray(),
            description: "Task '{$task->title}' ({$task->task_code}) created via MCP by {$user->name}."
        );

        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'task_code' => $task->task_code,
            'status' => $task->status->value,
            'message' => "Task '{$task->title}' created successfully.",
        ];
    }

    /**
     * T281: task.update
     */
    public function update(User $user, array $args): array
    {
        $taskId = (int) ($args['task_id'] ?? 0);
        $task = Task::with('project.team')->findOrFail($taskId);

        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Clients cannot update tasks.');
        }

        $validator = validator($args, [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'milestone_id' => ['nullable', 'exists:project_milestones,id'],
            'priority' => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        if (isset($data['status']) && $data['status'] === TaskStatus::DONE->value && $task->status !== TaskStatus::DONE) {
            $blockers = $this->dependencyService->getUnresolvedBlockers($task);
            if ($blockers->isNotEmpty()) {
                throw new \RuntimeException("Cannot complete task! Blocked by {$blockers->count()} unresolved dependencies.");
            }
            $data['completed_at'] = now();
            if ($task->is_recurring) {
                $this->recurringService->generateNextOccurrence($task);
            }
        } elseif (isset($data['status']) && $data['status'] !== TaskStatus::DONE->value) {
            $data['completed_at'] = null;
        }

        $before = $task->toArray();
        $task->update($data);

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'task.updated',
            'details' => 'Task parameters updated via MCP.',
        ]);

        $this->auditLogger->logProject(
            action: 'task.updated',
            projectId: $task->project_id,
            beforeValues: $before,
            afterValues: $task->toArray(),
            description: "Task '{$task->title}' updated via MCP by {$user->name}."
        );

        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'message' => "Task '{$task->title}' updated successfully.",
        ];
    }

    /**
     * T281: task.assign
     */
    public function assign(User $user, array $args): array
    {
        $taskId = (int) ($args['task_id'] ?? 0);
        $task = Task::with('project.team.members')->findOrFail($taskId);

        if ($user->isClient() || $user->isEmployee()) {
            throw new \RuntimeException('Unauthorized: Only Managers, Team Leads, and Super Admins can assign tasks.');
        }

        $assigneeId = (int) ($args['assigned_to'] ?? 0);
        $assignee = User::findOrFail($assigneeId);

        if (!$assignee->is_active) {
            throw new \RuntimeException('Assignee user is inactive.');
        }

        // Verify team/project membership eligibility
        if ($task->project && $task->project->team) {
            $isMember = $task->project->team->members()->where('users.id', $assignee->id)->exists();
            if (!$isMember && !$assignee->isSuperAdmin() && !$assignee->isManager()) {
                throw new \RuntimeException("Target user [{$assignee->name}] is not a member of the project team.");
            }
        }

        $oldAssigneeName = $task->assignee?->name ?? 'Unassigned';
        $task->assigned_to = $assignee->id;
        $task->save();

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'task.assigned',
            'old_value' => $oldAssigneeName,
            'new_value' => $assignee->name,
            'details' => "Task reassigned from {$oldAssigneeName} to {$assignee->name} via MCP.",
        ]);

        $this->auditLogger->logProject(
            action: 'task.assigned',
            projectId: $task->project_id,
            afterValues: ['task_id' => $task->id, 'assigned_to' => $assignee->id],
            description: "Task '{$task->title}' assigned to {$assignee->name} via MCP."
        );

        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'assigned_to' => $assignee->id,
            'assignee_name' => $assignee->name,
            'message' => "Task '{$task->title}' successfully assigned to {$assignee->name}.",
        ];
    }

    /**
     * T281: task.complete
     */
    public function complete(User $user, array $args): array
    {
        $taskId = (int) ($args['task_id'] ?? 0);
        $task = Task::findOrFail($taskId);

        if ($user->isClient()) {
            throw new \RuntimeException('Unauthorized: Clients cannot complete tasks.');
        }

        $blockers = $this->dependencyService->getUnresolvedBlockers($task);
        if ($blockers->isNotEmpty()) {
            throw new \RuntimeException("Cannot complete task: blocked by {$blockers->count()} unresolved dependencies ({$blockers->pluck('title')->implode(', ')}).");
        }

        $oldStatus = $task->status;
        $task->status = TaskStatus::DONE;
        $task->completed_at = now();
        $task->save();

        if ($task->is_recurring) {
            $this->recurringService->generateNextOccurrence($task);
        }

        TaskHistory::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'task.completed',
            'old_value' => $oldStatus->value,
            'new_value' => TaskStatus::DONE->value,
            'details' => "Task marked as completed via MCP by {$user->name}.",
        ]);

        $this->auditLogger->logProject(
            action: 'task.completed',
            projectId: $task->project_id,
            afterValues: ['task_id' => $task->id, 'status' => TaskStatus::DONE->value],
            description: "Task '{$task->title}' marked as completed via MCP."
        );

        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'status' => TaskStatus::DONE->value,
            'completed_at' => $task->completed_at?->toIso8601String(),
            'message' => "Task '{$task->title}' marked as Done.",
        ];
    }
}
