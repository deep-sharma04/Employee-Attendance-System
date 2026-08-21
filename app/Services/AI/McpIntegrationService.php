<?php

namespace App\Services\AI;

use App\DTOs\AI\McpRequestContext;
use App\DTOs\AI\McpResponse;
use App\Models\AiActionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class McpIntegrationService
{
    /**
     * Tool handlers registered in memory (to be populated in Phase 31).
     *
     * @var array<string, callable>
     */
    protected array $toolHandlers = [];

    /**
     * Required RBAC permissions for each tool.
     *
     * @var array<string, array<string>>
     */
    protected array $toolPermissions = [];

    /**
     * Sensitive tools that require explicit server-side approval before execution (T272).
     *
     * @var array<string>
     */
    protected array $approvalRequiredTools = [
        'project.delete',
        'task.bulk_reassign',
        'task.delete',
        'timesheet.bulk_override',
    ];

    public function __construct(
        protected McpSecurityGuard $securityGuard,
        protected McpUsagePolicyService $usagePolicy
    ) {}

    /**
     * Register a tool execution handler and its required RBAC permissions.
     */
    public function registerToolHandler(string $toolName, callable $handler, array $requiredPermissions = []): void
    {
        $this->toolHandlers[$toolName] = $handler;
        $this->toolPermissions[$toolName] = $requiredPermissions;
    }

    public function hasToolHandler(string $toolName): bool
    {
        return isset($this->toolHandlers[$toolName]);
    }

    /**
     * T268, T269, T270, T271, T273, T274: Execute an MCP tool request.
     */
    public function handleRequest(McpRequestContext $context): McpResponse
    {
        // 1. T274: Idempotency Check — Check if already executed with success
        if (!empty($context->idempotencyKey)) {
            $existingLog = AiActionLog::where('idempotency_key', $context->idempotencyKey)->first();
            if ($existingLog) {
                if ($existingLog->execution_status === 'success') {
                    return McpResponse::success((array) $existingLog->execution_result, $existingLog->id);
                }
                if ($existingLog->execution_status === 'pending') {
                    return McpResponse::error('Duplicate request is currently processing.', 409, null, $existingLog->id);
                }
            }
        }

        // 2. T271: Strict HR / Payroll Data Isolation Check
        if (!$this->securityGuard->checkHrDataIsolation($context)) {
            $log = $this->logAction($context, 'mutation', 'not_required', 'failed', null, 'Access to restricted HR/Payroll data is forbidden through MCP.');
            return McpResponse::error('Access to sensitive HR/Payroll data is strictly prohibited via MCP.', 403, null, $log->id);
        }

        // 3. T270: Strict User, Project, Team, and Client Scope Check (Fail-closed)
        if (!$this->securityGuard->validateScope($context)) {
            $log = $this->logAction($context, 'mutation', 'not_required', 'failed', null, 'Operation outside authorized user or project scope.');
            return McpResponse::error('Unauthorized: Action falls outside permitted project/team/client scope.', 403, null, $log->id);
        }

        // 3b. Role-Based Access Control (RBAC) Check
        $requiredPermissions = $this->toolPermissions[$context->toolName] ?? [];
        if (!empty($requiredPermissions) && !$context->user->hasAnyPermission($requiredPermissions)) {
            $log = $this->logAction($context, 'mutation', 'not_required', 'failed', null, 'Missing required RBAC permission.');
            return McpResponse::error('Unauthorized: Missing required RBAC permission.', 403, null, $log->id);
        }

        // 4. T275: Usage Policy Check
        if (!$this->usagePolicy->checkUsagePolicy($context)) {
            $log = $this->logAction($context, 'mutation', 'not_required', 'failed', null, 'Rate policy exceeded.');
            return McpResponse::error('AI/MCP usage limit exceeded.', 429, null, $log->id);
        }

        // 5. T272: Approval Flow Interception
        if ($this->requiresApproval($context->toolName, $context->arguments)) {
            $proposal = [
                'tool' => $context->toolName,
                'target_project_id' => $context->projectId,
                'target_team_id' => $context->teamId,
                'arguments' => $this->sanitizeParameters($context->arguments),
                'requested_by' => $context->user->name,
                'timestamp' => now()->toIso8601String(),
            ];

            $log = $this->logAction($context, 'destructive', 'pending_approval', 'pending', $proposal);
            return McpResponse::pendingApproval($log->id, $proposal);
        }

        // 6. T274: Tool Availability & Execution
        if (!isset($this->toolHandlers[$context->toolName])) {
            $log = $this->logAction($context, 'query', 'not_required', 'failed', null, "Tool '{$context->toolName}' is not registered.");
            return McpResponse::error("Tool '{$context->toolName}' is not registered or unavailable.", 404, null, $log->id);
        }

        // Execute inside database transaction for mutation safety (T274)
        $actionLog = $this->logAction($context, 'mutation', 'not_required', 'pending');

        try {
            $result = DB::transaction(function () use ($context) {
                $handler = $this->toolHandlers[$context->toolName];
                return $handler($context);
            });

            // Finalize audit log (T273)
            $actionLog->update([
                'execution_status' => 'success',
                'execution_result' => (array) $result,
            ]);

            $this->usagePolicy->recordUsage($context);

            return McpResponse::success((array) $result, $actionLog->id);
        } catch (\Throwable $e) {
            Log::error("MCP Tool '{$context->toolName}' execution failure: " . $e->getMessage());

            $actionLog->update([
                'execution_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return McpResponse::error(
                message: 'An error occurred during tool execution: ' . $e->getMessage(),
                code: 500,
                details: null,
                actionLogId: $actionLog->id
            );
        }
    }

    /**
     * T272: Check if tool requires explicit server-side approval.
     */
    public function requiresApproval(string $toolName, array $arguments = []): bool
    {
        return in_array($toolName, $this->approvalRequiredTools, true)
            || !empty($arguments['requires_approval']);
    }

    /**
     * T272: Approve an action log and proceed with execution.
     */
    public function approveAction(int $actionLogId, User $approver): McpResponse
    {
        $log = AiActionLog::findOrFail($actionLogId);

        if (!$log->isPendingApproval()) {
            return McpResponse::error('Action is not pending approval.', 400, null, $log->id);
        }

        // Verify approver authority
        if (!$this->securityGuard->canApproveAction($approver, $log->project_id)) {
            return McpResponse::error('You do not have permission to approve this action.', 403, null, $log->id);
        }

        $log->update([
            'approval_state' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // If tool handler is registered, execute now
        if (isset($this->toolHandlers[$log->tool_name])) {
            try {
                $context = new McpRequestContext(
                    user: $log->user,
                    toolName: $log->tool_name,
                    arguments: (array) $log->parameters,
                    projectId: $log->project_id,
                    teamId: $log->team_id,
                    clientId: $log->client_id,
                    conversationId: $log->conversation_id
                );

                $handler = $this->toolHandlers[$log->tool_name];
                $result = DB::transaction(fn () => $handler($context));

                $log->update([
                    'execution_status' => 'success',
                    'execution_result' => (array) $result,
                ]);

                return McpResponse::success((array) $result, $log->id);
            } catch (\Throwable $e) {
                $log->update([
                    'execution_status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                return McpResponse::error('Approved action execution failed: ' . $e->getMessage(), 500, null, $log->id);
            }
        }

        return McpResponse::success(['message' => 'Action successfully approved.'], $log->id);
    }

    /**
     * T272: Reject an action log.
     */
    public function rejectAction(int $actionLogId, User $rejector, string $reason): McpResponse
    {
        $log = AiActionLog::findOrFail($actionLogId);

        if (!$log->isPendingApproval()) {
            return McpResponse::error('Action is not pending approval.', 400, null, $log->id);
        }

        if (!$this->securityGuard->canApproveAction($rejector, $log->project_id)) {
            return McpResponse::error('You do not have permission to reject this action.', 403, null, $log->id);
        }

        $log->update([
            'approval_state' => 'rejected',
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'execution_status' => 'aborted',
        ]);

        return McpResponse::success(['message' => 'Action successfully rejected.'], $log->id);
    }

    /**
     * T273: Record immutable AI action audit log.
     */
    protected function logAction(
        McpRequestContext $context,
        string $actionType,
        string $approvalState,
        string $executionStatus,
        ?array $proposal = null,
        ?string $errorMessage = null
    ): AiActionLog {
        return AiActionLog::create([
            'conversation_id' => $context->conversationId,
            'user_id' => $context->user->id,
            'project_id' => $context->projectId,
            'team_id' => $context->teamId,
            'client_id' => $context->clientId,
            'tool_name' => $context->toolName ?: 'unknown_tool',
            'action_type' => $actionType,
            'parameters' => $this->sanitizeParameters($context->arguments),
            'approval_state' => $approvalState,
            'execution_status' => $executionStatus,
            'execution_result' => $proposal,
            'error_message' => $errorMessage,
            'idempotency_key' => $context->idempotencyKey,
            'created_at' => now(),
        ]);
    }

    /**
     * T273: Sanitize parameters before storing in audit logs (remove passwords/tokens).
     */
    protected function sanitizeParameters(array $parameters): array
    {
        $sanitized = [];
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'auth', 'api_key'];

        foreach ($parameters as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParameters($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
