<?php

namespace App\Services\Audit;

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
}
