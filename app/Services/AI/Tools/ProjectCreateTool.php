<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectCreateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.create';
    }

    public function toolDescription(): string
    {
        return 'Create a new project under authorized client and team scope.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Project name')->required(),
            'code' => $schema->string()->description('Unique project code (e.g. PRJ-ACME-01)')->required(),
            'status' => $schema->string()->enum(['planning', 'active', 'on_hold', 'completed', 'cancelled'])->description('Project status')->required(),
            'priority' => $schema->string()->enum(['low', 'medium', 'high', 'urgent'])->description('Project priority')->required(),
            'start_date' => $schema->string()->description('Project start date (YYYY-MM-DD)')->required(),
            'deadline' => $schema->string()->description('Project deadline (YYYY-MM-DD)')->required(),
            'client_id' => $schema->integer()->description('Client ID'),
            'team_id' => $schema->integer()->description('Assigned Team ID'),
            'manager_id' => $schema->integer()->description('Assigned Manager User ID'),
            'budget' => $schema->number()->description('Allocated project budget'),
            'health' => $schema->string()->enum(['good', 'warning', 'critical'])->description('Project health'),
            'description' => $schema->string()->description('Project description and goals'),
        ];
    }
}
