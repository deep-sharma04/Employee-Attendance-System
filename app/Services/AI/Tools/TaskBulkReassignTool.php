<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskBulkReassignTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.bulk_reassign';
    }

    public function toolDescription(): string
    {
        return 'Reassign all active tasks from one team member to another (Sensitive/Destructive action; requires approval if requested by Team Lead).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_user_id' => $schema->integer()->description('Source User ID whose active tasks will be transferred')->required(),
            'to_user_id' => $schema->integer()->description('Destination User ID who will receive the tasks')->required(),
            'project_id' => $schema->integer()->description('Optional Project ID to restrict reassignment to a specific project'),
            'requires_approval' => $schema->boolean()->description('Force proposal submission for explicit approval gate'),
            'idempotency_key' => $schema->string()->description('Unique idempotency key to prevent duplicate execution'),
        ];
    }
}
