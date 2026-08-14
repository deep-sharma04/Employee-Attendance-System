<?php

namespace App\Services\AI\Tools;

use App\Models\User;
use App\Services\AI\Workflow\McpWorkflowExecutionService;

class WorkflowMcpTools
{
    public function __construct(
        protected McpWorkflowExecutionService $workflowService
    ) {}

    /**
     * T293: task.bulk_reassign
     */
    public function bulkReassign(User $user, array $args): array
    {
        $idempotencyKey = $args['idempotency_key'] ?? null;
        return $this->workflowService->bulkReassignTasks($user, $args, $idempotencyKey);
    }

    /**
     * T292: ai.action.pending_list
     */
    public function pendingList(User $user, array $args): array
    {
        return $this->workflowService->getPendingApprovals($user);
    }

    /**
     * T292: ai.action.approve
     */
    public function approve(User $user, array $args): array
    {
        if (empty($args['action_log_id'])) {
            throw new \InvalidArgumentException('Missing required parameter: action_log_id');
        }

        return $this->workflowService->approveAction((int) $args['action_log_id'], $user);
    }

    /**
     * T292: ai.action.reject
     */
    public function reject(User $user, array $args): array
    {
        if (empty($args['action_log_id']) || empty($args['reason'])) {
            throw new \InvalidArgumentException('Missing required parameters: action_log_id, reason');
        }

        return $this->workflowService->rejectAction((int) $args['action_log_id'], $user, (string) $args['reason']);
    }
}
