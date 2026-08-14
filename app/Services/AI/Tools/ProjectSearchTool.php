<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ProjectSearchTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'project.search';
    }

    public function toolDescription(): string
    {
        return 'Search projects authorized for the authenticated user.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term for project name or code'),
            'status' => $schema->string()->enum(['planning', 'active', 'on_hold', 'completed', 'cancelled'])->description('Filter by project status'),
            'priority' => $schema->string()->enum(['low', 'medium', 'high', 'urgent'])->description('Filter by priority'),
            'client_id' => $schema->integer()->description('Filter by client ID'),
            'limit' => $schema->integer()->description('Maximum number of projects to return (1-50)'),
        ];
    }
}
