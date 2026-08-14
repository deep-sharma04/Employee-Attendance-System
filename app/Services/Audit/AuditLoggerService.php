<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

class AuditLoggerService
{
    /**
     * Record an immutable audit log entry.
     */
    public function log(
        string $action,
        string $targetType,
        ?int $targetId = null,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $user = Auth::user();

        DB::table('audit_logs')->insert([
            'actor_id' => $user?->id,
            'actor_name' => $user?->name ?? 'System',
            'actor_role' => $user?->role?->value ?? ($user?->role ?? 'system'),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_values' => $beforeValues ? json_encode($beforeValues) : null,
            'after_values' => $afterValues ? json_encode($afterValues) : null,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => substr(Request::userAgent() ?? 'System CLI', 0, 255),
            'created_at' => now(),
        ]);
    }

    /**
     * Alias for log.
     */
    public function logAction(
        string $action,
        string $targetType,
        ?int $targetId = null,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        $this->log($action, $targetType, $targetId, $beforeValues, $afterValues, $description);
    }

    /**
     * Record an authentication event (login, logout, password change).
     */
    public function logAuth(string $action, ?User $user = null, ?string $description = null): void
    {
        $targetUser = $user ?? Auth::user();

        $this->log(
            action: $action,
            targetType: 'User',
            targetId: $targetUser?->id,
            description: $description ?? "User {$targetUser?->username} performed {$action}"
        );
    }

    /**
     * Record a project lifecycle action.
     */
    public function logProject(
        string $action,
        int $projectId,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        $this->log(
            action: $action,
            targetType: 'Project',
            targetId: $projectId,
            beforeValues: $beforeValues,
            afterValues: $afterValues,
            description: $description
        );
    }

    /**
     * Record a client lifecycle action.
     */
    public function logClient(
        string $action,
        int $clientId,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        $this->log(
            action: $action,
            targetType: 'Client',
            targetId: $clientId,
            beforeValues: $beforeValues,
            afterValues: $afterValues,
            description: $description
        );
    }

    /**
     * Record a team lifecycle action.
     */
    public function logTeam(
        string $action,
        int $teamId,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        $this->log(
            action: $action,
            targetType: 'Team',
            targetId: $teamId,
            beforeValues: $beforeValues,
            afterValues: $afterValues,
            description: $description
        );
    }
}
