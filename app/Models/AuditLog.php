<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'before_values',
        'after_values',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and enforce immutability (Task T155).
     * Audit log records must NEVER be updated or deleted.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Audit logs are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Scope for operational logs only (excludes super-admin HR Admin management logs).
     */
    public function scopeOperationalOnly(Builder $query): Builder
    {
        return $query->whereNotIn('target_type', ['User', 'HrAdmin'])
            ->orWhere(function ($q) {
                $q->where('target_type', 'User')
                  ->whereNotIn('action', ['hr_admin.created', 'hr_admin.updated', 'hr_admin.suspended', 'hr_admin.activated']);
            });
    }
}
