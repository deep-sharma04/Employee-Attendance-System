<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskCompleteTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.complete';
    }

    public function toolDescription(): string
    {
        return 'Mark a task as completed (enforcing blocker dependency verification).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->description('ID of the task to complete')->required(),
        ];
    }
}
