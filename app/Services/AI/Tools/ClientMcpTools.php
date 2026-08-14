<?php

namespace App\Services\AI\Tools;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientMcpTools
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * T279: client.search
     */
    public function search(User $user, array $args): array
    {
        if (!Gate::forUser($user)->allows('viewAny', Client::class)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to view clients.');
        }

        $query = Client::query();

        // Client user scope isolation
        if ($user->isClient()) {
            $clientUser = $user->clientUser;
            if (!$clientUser || !$clientUser->client_id) {
                return ['clients' => [], 'count' => 0];
            }
            $query->where('id', $clientUser->client_id);
        }

        if (!empty($args['search'])) {
            $search = (string) $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($args['status'])) {
            $query->where('status', (string) $args['status']);
        }

        $limit = min(50, max(1, (int) ($args['limit'] ?? 15)));
        $clients = $query->latest('id')->take($limit)->get();

        $sanitized = $clients->map(fn (Client $c) => [
            'id' => $c->id,
            'company_name' => $c->company_name,
            'company_code' => $c->company_code,
            'email' => $c->email,
            'phone' => $c->phone,
            'website' => $c->website,
            'status' => $c->status,
            'currency' => $c->currency,
            'created_at' => $c->created_at?->toIso8601String(),
        ])->all();

        return [
            'clients' => $sanitized,
            'count' => count($sanitized),
        ];
    }

    /**
     * T279: client.create
     */
    public function create(User $user, array $args): array
    {
        if (!Gate::forUser($user)->allows('create', Client::class)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to create clients.');
        }

        $validator = validator($args, [
            'company_name' => ['required', 'string', 'max:150'],
            'company_code' => ['nullable', 'string', 'max:50', 'unique:clients,company_code'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $data['created_by'] = $user->id;
        $data['currency'] = $data['currency'] ?? 'USD';

        $client = Client::create($data);

        $this->auditLogger->logClient(
            action: 'client.created',
            clientId: $client->id,
            afterValues: $client->toArray(),
            description: "Client '{$client->company_name}' was created via MCP by {$user->name}."
        );

        return [
            'client_id' => $client->id,
            'company_name' => $client->company_name,
            'company_code' => $client->company_code,
            'status' => $client->status,
            'message' => "Client '{$client->company_name}' created successfully.",
        ];
    }

    /**
     * T279: client.update
     */
    public function update(User $user, array $args): array
    {
        $clientId = (int) ($args['client_id'] ?? 0);
        $client = Client::findOrFail($clientId);

        if (!Gate::forUser($user)->allows('update', $client)) {
            throw new \RuntimeException('Unauthorized: You do not have permission to update this client.');
        }

        $validator = validator($args, [
            'company_name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(ClientStatus::class)],
            'currency' => ['nullable', 'string', 'max:10'],
            'billing_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $before = $client->toArray();
        $client->update($validator->validated());

        $this->auditLogger->logClient(
            action: 'client.updated',
            clientId: $client->id,
            beforeValues: $before,
            afterValues: $client->toArray(),
            description: "Client '{$client->company_name}' updated via MCP by {$user->name}."
        );

        return [
            'client_id' => $client->id,
            'company_name' => $client->company_name,
            'status' => $client->status,
            'message' => "Client '{$client->company_name}' updated successfully.",
        ];
    }
}
