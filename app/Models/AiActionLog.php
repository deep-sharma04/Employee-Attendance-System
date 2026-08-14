<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'project_id',
        'team_id',
        'client_id',
        'tool_name',
        'action_type',
        'parameters',
        'approval_state',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'execution_status',
        'execution_result',
        'error_message',
        'idempotency_key',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'execution_result' => 'array',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // T273: Immutability Protection for completed AI action records
        static::updating(function (AiActionLog $log) {
            // Allow state transitions from pending_approval -> approved/rejected or pending -> success/failed
            $originalStatus = $log->getOriginal('execution_status');
            if (in_array($originalStatus, ['success', 'failed', 'aborted'])) {
                throw new \RuntimeException('AI action audit logs are immutable once execution is finalized.');
            }
        });

        static::deleting(function (AiActionLog $log) {
            throw new \RuntimeException('AI action audit logs cannot be deleted.');
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_state === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->approval_state === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_state === 'rejected';
    }
}
