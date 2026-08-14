<?php

namespace App\DTOs\AI;

class McpResponse
{
    public function __construct(
        public bool $isSuccess,
        public ?array $data = null,
        public ?array $error = null,
        public bool $requiresApproval = false,
        public ?int $actionLogId = null,
        public ?array $approvalProposal = null
    ) {}

    public static function success(array $data, ?int $actionLogId = null): self
    {
        return new self(
            isSuccess: true,
            data: $data,
            actionLogId: $actionLogId
        );
    }

    public static function error(string $message, int $code = 400, ?array $details = null, ?int $actionLogId = null): self
    {
        return new self(
            isSuccess: false,
            error: [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            actionLogId: $actionLogId
        );
    }

    public static function pendingApproval(int $actionLogId, array $proposal): self
    {
        return new self(
            isSuccess: true,
            requiresApproval: true,
            actionLogId: $actionLogId,
            approvalProposal: $proposal
        );
    }

    public function toArray(): array
    {
        if ($this->requiresApproval) {
            return [
                'status' => 'pending_approval',
                'action_log_id' => $this->actionLogId,
                'proposal' => $this->approvalProposal,
                'message' => 'Action registered and requires approval before execution.',
            ];
        }

        if (!$this->isSuccess) {
            return [
                'status' => 'error',
                'error' => $this->error,
                'action_log_id' => $this->actionLogId,
            ];
        }

        return [
            'status' => 'success',
            'data' => $this->data,
            'action_log_id' => $this->actionLogId,
        ];
    }
}
