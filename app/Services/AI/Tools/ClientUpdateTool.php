<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ClientUpdateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'client.update';
    }

    public function toolDescription(): string
    {
        return 'Update existing client company details and status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->description('ID of the client to update')->required(),
            'company_name' => $schema->string()->description('Company name'),
            'status' => $schema->string()->enum(['lead', 'active', 'inactive'])->description('Client status'),
            'email' => $schema->string()->description('Primary email address'),
            'phone' => $schema->string()->description('Primary phone number'),
            'website' => $schema->string()->description('Company website URL'),
            'address' => $schema->string()->description('Physical address'),
            'currency' => $schema->string()->description('Billing currency'),
            'billing_type' => $schema->string()->description('Billing type'),
            'notes' => $schema->string()->description('Internal client notes'),
        ];
    }
}
