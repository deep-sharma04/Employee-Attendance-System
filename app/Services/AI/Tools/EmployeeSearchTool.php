<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class EmployeeSearchTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'employee.search';
    }

    public function toolDescription(): string
    {
        return 'Search project and team staff (strictly excludes salary, bank, tax, payroll, and IP allowlist data).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search keyword for employee name, code, department, or designation'),
            'department' => $schema->string()->description('Filter by department'),
            'designation' => $schema->string()->description('Filter by designation'),
            'team_id' => $schema->integer()->description('Filter by Team ID'),
            'limit' => $schema->integer()->description('Maximum number of results (1-50)'),
        ];
    }
}
