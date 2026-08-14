<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectIntelligenceSearchTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.intelligence_search';
    }

    public function toolDescription(): string
    {
        return 'Execute natural-language project queries (overdue tasks, upcoming deadlines, project status, workloads) with authorized scoping.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Natural-language question or search terms (e.g. "Show overdue tasks in Project Alpha")'),
            'intent' => $schema->string()->enum(['overdue_tasks', 'upcoming_tasks', 'projects_with_overdue', 'incomplete_projects', 'project_workload', 'project_status', 'project_tasks'])->description('Explicit query intent'),
            'project_id' => $schema->integer()->description('Filter to specific Project ID'),
        ];
    }
}
