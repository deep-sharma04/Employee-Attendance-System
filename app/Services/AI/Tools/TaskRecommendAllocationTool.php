<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class TaskRecommendAllocationTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'task.recommend_allocation';
    }

    public function toolDescription(): string
    {
        return 'Recommend candidate team members for task allocation based on skills, availability, and active workload (Read-only; does not mutate assignments).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Project ID under which the task belongs'),
            'task_id' => $schema->integer()->description('Existing Task ID to find candidates for'),
            'required_skills' => $schema->array()->description('List of required technical skill tags'),
            'estimated_hours' => $schema->number()->description('Estimated task effort in hours'),
        ];
    }
}
