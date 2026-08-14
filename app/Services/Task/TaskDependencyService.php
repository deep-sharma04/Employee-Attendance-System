<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskDependency;

class TaskDependencyService
{
    /**
     * Check whether adding a dependency from $taskId to $dependsOnTaskId creates a circular cycle (Task T228).
     * Returns true if a cycle is detected (illegal).
     */
    public function createsCycle(int $taskId, int $dependsOnTaskId): bool
    {
        // Direct self-dependency is a cycle
        if ($taskId === $dependsOnTaskId) {
            return true;
        }

        // Perform DFS from $dependsOnTaskId to see if we can reach $taskId
        $visited = [];
        return $this->dfsSearch($dependsOnTaskId, $taskId, $visited);
    }

    protected function dfsSearch(int $currentTaskId, int $targetTaskId, array &$visited): bool
    {
        if ($currentTaskId === $targetTaskId) {
            return true;
        }

        if (isset($visited[$currentTaskId])) {
            return false;
        }

        $visited[$currentTaskId] = true;

        // Find all tasks that $currentTaskId depends on
        $nextTaskIds = TaskDependency::where('task_id', $currentTaskId)
            ->pluck('depends_on_task_id')
            ->toArray();

        foreach ($nextTaskIds as $nextId) {
            if ($this->dfsSearch($nextId, $targetTaskId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate whether task can be started or completed based on blocking dependencies.
     */
    public function getUnresolvedBlockers(Task $task)
    {
        return $task->blockingTasks()
            ->wherePivot('dependency_type', 'blocks')
            ->where('status', '!=', 'done')
            ->get();
    }
}
