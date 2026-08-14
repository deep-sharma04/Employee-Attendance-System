<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskCreateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.create';
    }

    public function toolDescription(): string
    {
        return 'Create a new task under an authorized project.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Target Project ID')->required(),
            'title' => $schema->string()->description('Task title')->required(),
            'task_code' => $schema->string()->description('Unique task code (e.g. TSK-001)')->required(),
            'priority' => $schema->string()->enum(['low', 'medium', 'high', 'urgent'])->description('Task priority')->required(),
            'status' => $schema->string()->enum(['todo', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled'])->description('Task status')->required(),
            'milestone_id' => $schema->integer()->description('Milestone ID'),
            'parent_id' => $schema->integer()->description('Parent Task ID for subtasks'),
            'assigned_to' => $schema->integer()->description('Assignee User ID'),
            'estimated_hours' => $schema->number()->description('Estimated hours'),
            'due_date' => $schema->string()->description('Due date (YYYY-MM-DD)'),
            'description' => $schema->string()->description('Task description and acceptance criteria'),
        ];
    }
}
