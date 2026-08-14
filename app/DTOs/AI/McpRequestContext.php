<?php

namespace App\DTOs\AI;

use App\Models\User;

class McpRequestContext
{
    public function __construct(
        public User $user,
        public string $toolName,
        public array $arguments = [],
        public ?int $projectId = null,
        public ?int $teamId = null,
        public ?int $clientId = null,
        public ?int $conversationId = null,
        public ?string $idempotencyKey = null,
        public array $clientInfo = []
    ) {}

    public static function fromArray(User $user, array $payload): self
    {
        return new self(
            user: $user,
            toolName: (string) ($payload['tool'] ?? $payload['name'] ?? ''),
            arguments: (array) ($payload['arguments'] ?? $payload['parameters'] ?? []),
            projectId: isset($payload['project_id']) ? (int) $payload['project_id'] : null,
            teamId: isset($payload['team_id']) ? (int) $payload['team_id'] : null,
            clientId: isset($payload['client_id']) ? (int) $payload['client_id'] : null,
            conversationId: isset($payload['conversation_id']) ? (int) $payload['conversation_id'] : null,
            idempotencyKey: isset($payload['idempotency_key']) ? (string) $payload['idempotency_key'] : null,
            clientInfo: (array) ($payload['client_info'] ?? [])
        );
    }
}
