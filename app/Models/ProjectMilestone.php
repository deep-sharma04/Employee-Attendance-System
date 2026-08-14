<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMilestone extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'status',
        'order',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'order' => 'integer',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('due_date');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_date && $this->due_date->isPast();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }
}
