<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectManagementReportTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.management_report';
    }

    public function toolDescription(): string
    {
        return 'Generate structured management intelligence summaries for productivity, team workload, and authorized budget utilization.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'report_type' => $schema->string()->enum(['productivity', 'workload', 'budget_utilization'])->description('Type of management report to generate')->required(),
            'project_id' => $schema->integer()->description('Filter report to specific Project ID'),
            'team_id' => $schema->integer()->description('Filter report to specific Team ID'),
        ];
    }
}
