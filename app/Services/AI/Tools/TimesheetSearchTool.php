<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TimesheetSearchTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'timesheet.search';
    }

    public function toolDescription(): string
    {
        return 'Search timesheets within authorized employee and project scope.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'employee_id' => $schema->integer()->description('Filter by Employee ID'),
            'project_id' => $schema->integer()->description('Filter by Project ID'),
            'status' => $schema->string()->enum(['draft', 'submitted', 'approved', 'rejected', 'returned'])->description('Timesheet status'),
            'start_date' => $schema->string()->description('Start date filter (YYYY-MM-DD)'),
            'end_date' => $schema->string()->description('End date filter (YYYY-MM-DD)'),
            'limit' => $schema->integer()->description('Maximum number of records to return (1-50)'),
        ];
    }
}
