<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskAssignTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.assign';
    }

    public function toolDescription(): string
    {
        return 'Assign a task to an active, eligible project team member.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('ID of the task to assign')->required(),
            'assigned_to' => $schema->integer()->description('User ID of the assigned team member')->required(),
        ];
    }
}
