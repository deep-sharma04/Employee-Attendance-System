<?php

namespace App\Services\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

class RecurringTaskService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Generate the next occurrence for a recurring task (Task T229).
     * Enhanced with parent tracking, idempotency, and assignment notifications.
     */
    public function generateNextOccurrence(Task $task): ?Task
    {
        if (!$task->is_recurring || !$task->recurrence_pattern) {
            return null;
        }

        $baseDate = $task->due_date ?? now();
        $nextDueDate = match ($task->recurrence_pattern) {
            'daily' => $baseDate->copy()->addDay(),
            'weekly' => $baseDate->copy()->addWeek(),
            'monthly' => $baseDate->copy()->addMonth(),
            default => $baseDate->copy()->addWeek(),
        };

        if ($task->recurrence_end_date && $nextDueDate->gt($task->recurrence_end_date)) {
            return null;
        }

        // Determine the recurring parent: if this task IS the definition, use its id.
        // If this task is itself a generated occurrence, use its recurring_parent_id.
        $recurringParentId = $task->recurring_parent_id ?? $task->id;

        // Idempotency: skip if an occurrence with same parent + due_date already exists
        $existingOccurrence = Task::where('recurring_parent_id', $recurringParentId)
            ->whereDate('due_date', $nextDueDate->toDateString())
            ->first();

        if ($existingOccurrence) {
            Log::info("Recurring task occurrence already exists for parent #{$recurringParentId} on {$nextDueDate->toDateString()}, skipping.");
            return $existingOccurrence;
        }

        // Generate unique task code
        $prefix = strtoupper(substr($task->project->code ?? 'TSK', 0, 8));
        $uniqueNumber = Task::max('id') + 1;
        $nextCode = "{$prefix}-" . str_pad((string) $uniqueNumber, 4, '0', STR_PAD_LEFT);

        $nextTask = Task::create([
            'project_id' => $task->project_id,
            'milestone_id' => $task->milestone_id,
            'parent_id' => $task->parent_id,
            'team_id' => $task->team_id,
            'title' => $task->title,
            'task_code' => $nextCode,
            'description' => $task->description,
            'assigned_to' => $task->assigned_to,
            'priority' => $task->priority,
            'status' => TaskStatus::TODO->value,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => 0.00,
            'due_date' => $nextDueDate->toDateString(),
            'is_recurring' => true,
            'recurrence_pattern' => $task->recurrence_pattern,
            'recurrence_end_date' => $task->recurrence_end_date,
            'recurring_parent_id' => $recurringParentId,
            'created_by' => $task->created_by,
        ]);

        TaskHistory::create([
            'task_id' => $nextTask->id,
            'user_id' => $task->created_by,
            'action' => 'recurring_task.generated',
            'details' => "Automated recurring instance generated from task #{$task->task_code}.",
        ]);

        // Notify assignee about the generated occurrence
        if ($nextTask->assigned_to) {
            try {
                $nextTask->load(['project', 'assignee']);
                $assigner = $task->creator;
                $this->notificationService->notifyTaskAssigned($nextTask, $nextTask->assignee, $assigner);
            } catch (\Throwable $e) {
                // Failed notification dispatch must not corrupt task generation
                Log::warning("Failed to send recurring task assignment notification for task #{$nextTask->id}: " . $e->getMessage());
            }
        }

        return $nextTask;
    }
}
