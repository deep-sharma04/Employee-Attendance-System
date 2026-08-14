<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AiActionApproveTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'ai.action.approve';
    }

    public function toolDescription(): string
    {
        return 'Approve a pending AI workflow action proposal and trigger its atomic execution (Super Admin & Manager only).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action_log_id' => $schema->integer()->description('ID of the pending action proposal to approve')->required(),
        ];
    }
}
