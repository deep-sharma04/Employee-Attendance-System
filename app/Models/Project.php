<?php

namespace App\Models;

use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'code',
        'description',
        'objectives',
        'scope',
        'client_id',
        'team_id',
        'manager_id',
        'budget',
        'estimated_hours',
        'start_date',
        'deadline',
        'end_date',
        'status',
        'priority',
        'health',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'health' => ProjectHealth::class,
            'budget' => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'start_date' => 'date',
            'deadline' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['employee_id', 'project_role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_members', 'project_id', 'employee_id')
            ->withPivot(['user_id', 'project_role', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->ordered();
    }

    public function completedMilestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->completed();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Calculate project completion progress percentage (0 - 100).
     */
    public function progressPercentage(): int
    {
        if ($this->status === ProjectStatus::COMPLETED) {
            return 100;
        }

        $totalMilestones = $this->milestones()->count();
        if ($totalMilestones === 0) {
            return $this->status === ProjectStatus::ACTIVE ? 25 : 0;
        }

        $completedCount = $this->completedMilestones()->count();
        return (int) round(($completedCount / $totalMilestones) * 100);
    }

    /**
     * Check if project deadline is passed.
     */
    public function isPastDeadline(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== ProjectStatus::COMPLETED;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::ACTIVE->value);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }
}
