<?php

namespace App\Services\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class OverdueTaskDetectionService
{
    /**
     * Get all active tasks that have passed their due date (Task T234).
     */
    public function getOverdueTasks(): Collection
    {
        return Task::with(['project', 'assignee', 'milestone'])
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get overdue tasks count for a specific project.
     */
    public function getOverdueCountForProject(int $projectId): int
    {
        return Task::where('project_id', $projectId)
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();
    }
}
