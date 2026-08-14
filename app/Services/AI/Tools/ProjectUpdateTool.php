<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectUpdateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.update';
    }

    public function toolDescription(): string
    {
        return 'Update existing project parameters, status, priority, or deadline.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('ID of the project to update')->required(),
            'name' => $schema->string()->description('Project name'),
            'client_id' => $schema->integer()->description('Client ID'),
            'team_id' => $schema->integer()->description('Assigned Team ID'),
            'manager_id' => $schema->integer()->description('Manager User ID'),
            'budget' => $schema->number()->description('Allocated project budget'),
            'status' => $schema->string()->enum(['planning', 'active', 'on_hold', 'completed', 'cancelled'])->description('Project status'),
            'priority' => $schema->string()->enum(['low', 'medium', 'high', 'urgent'])->description('Project priority'),
            'health' => $schema->string()->enum(['good', 'warning', 'critical'])->description('Project health'),
            'start_date' => $schema->string()->description('Start date (YYYY-MM-DD)'),
            'deadline' => $schema->string()->description('Deadline (YYYY-MM-DD)'),
            'description' => $schema->string()->description('Project description'),
        ];
    }
}
