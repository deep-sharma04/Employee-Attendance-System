<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectExplainHealthTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.explain_health';
    }

    public function toolDescription(): string
    {
        return 'Explain deterministic project health calculation with grounded milestone, schedule variance, and overdue task evidence.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('ID of the project to analyze')->required(),
        ];
    }
}
