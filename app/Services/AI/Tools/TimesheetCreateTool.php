<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TimesheetCreateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'timesheet.create';
    }

    public function toolDescription(): string
    {
        return 'Create a draft timesheet with logged project hours.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Start date of the timesheet period (YYYY-MM-DD)')->required(),
            'end_date' => $schema->string()->description('End date of the timesheet period (YYYY-MM-DD)')->required(),
            'period_type' => $schema->string()->enum(['weekly', 'daily'])->description('Period type')->required(),
            'employee_id' => $schema->integer()->description('Employee ID (optional for self)'),
            'entries' => $schema->array()->description('List of project time entries')->required(),
        ];
    }
}
