<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AiActionRejectTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'ai.action.reject';
    }

    public function toolDescription(): string
    {
        return 'Reject a pending AI workflow action proposal with a mandatory rejection rationale.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action_log_id' => $schema->integer()->description('ID of the pending action proposal to reject')->required(),
            'reason' => $schema->string()->description('Mandatory explanation for the rejection')->required(),
        ];
    }
}
