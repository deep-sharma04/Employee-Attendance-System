<?php

namespace App\Services\AI\Tools;

use App\Services\AI\McpBaseTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ClientCreateTool extends McpBaseTool
{
    public function toolName(): string
    {
        return 'client.create';
    }

    public function toolDescription(): string
    {
        return 'Create a new client with company and contact information.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'company_name' => $schema->string()->description('Company name')->required(),
            'status' => $schema->string()->enum(['lead', 'active', 'inactive'])->description('Client status')->required(),
            'company_code' => $schema->string()->description('Unique client code (e.g. CLT-ACME)'),
            'email' => $schema->string()->description('Primary email address'),
            'phone' => $schema->string()->description('Primary phone number'),
            'website' => $schema->string()->description('Company website URL'),
            'address' => $schema->string()->description('Physical address'),
            'currency' => $schema->string()->description('Billing currency (default: USD)'),
            'billing_type' => $schema->string()->description('Billing type (hourly, fixed, retainer)'),
            'notes' => $schema->string()->description('Internal client notes'),
        ];
    }
}
