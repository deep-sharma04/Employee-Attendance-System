<?php

namespace App\Traits;

use App\Services\Audit\AuditLoggerService;

trait Auditable
{
    /**
     * Record an audit log for an action performed on this model.
     */
    public function logAuditAction(
        string $action,
        ?array $beforeValues = null,
        ?array $afterValues = null,
        ?string $description = null
    ): void {
        $auditLogger = app(AuditLoggerService::class);
        $targetType = class_basename($this);
        $targetId = $this->getKey();

        $auditLogger->log(
            action: $action,
            targetType: $targetType,
            targetId: is_numeric($targetId) ? (int) $targetId : null,
            beforeValues: $beforeValues,
            afterValues: $afterValues,
            description: $description
        );
    }
}
