<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AiActionPendingListTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'ai.action.pending_list';
    }

    public function toolDescription(): string
    {
        return 'List all pending AI workflow action proposals awaiting server-side approval within authorized scope.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
