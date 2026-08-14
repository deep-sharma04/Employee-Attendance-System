<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskUpdateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.update';
    }

    public function toolDescription(): string
    {
        return 'Update task details, deadline, estimated hours, or status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('ID of the task to update')->required(),
            'title' => $schema->string()->description('Task title'),
            'priority' => $schema->string()->enum(['low', 'medium', 'high', 'urgent'])->description('Task priority'),
            'status' => $schema->string()->enum(['todo', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled'])->description('Task status'),
            'milestone_id' => $schema->integer()->description('Milestone ID'),
            'estimated_hours' => $schema->number()->description('Estimated hours'),
            'due_date' => $schema->string()->description('Due date (YYYY-MM-DD)'),
            'description' => $schema->string()->description('Task description'),
        ];
    }
}
