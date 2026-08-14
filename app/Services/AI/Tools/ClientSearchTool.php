<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ClientSearchTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'client.search';
    }

    public function toolDescription(): string
    {
        return 'Search clients by company name, code, or status within authorized scope.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search keyword for company name, code, or email'),
            'status' => $schema->string()->enum(['lead', 'active', 'inactive'])->description('Filter by client status'),
            'limit' => $schema->integer()->description('Maximum number of records to return (1-50)'),
        ];
    }
}
