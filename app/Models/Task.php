<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'parent_id',
        'team_id',
        'title',
        'task_code',
        'description',
        'assigned_to',
        'priority',
        'status',
        'estimated_hours',
        'actual_hours',
        'due_date',
        'completed_at',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_end_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'is_recurring' => 'boolean',
            'recurrence_end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function blockingTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withPivot('dependency_type')
            ->withTimestamps();
    }

    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withPivot('dependency_type')
            ->withTimestamps();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('order')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->latest();
    }

    /**
     * Check if task has uncompleted blocking dependencies (Task T228).
     */
    public function isBlocked(): bool
    {
        return $this->blockingTasks()
            ->wherePivot('dependency_type', 'blocks')
            ->where('status', '!=', TaskStatus::DONE->value)
            ->exists();
    }

    /**
     * Get list of uncompleted blocking tasks.
     */
    public function getUncompletedBlockers()
    {
        return $this->blockingTasks()
            ->wherePivot('dependency_type', 'blocks')
            ->where('status', '!=', TaskStatus::DONE->value)
            ->get();
    }

    /**
     * Calculate checklist completion progress percentage (0 - 100).
     */
    public function checklistProgress(): int
    {
        $total = $this->checklists()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->checklists()->where('is_completed', true)->count();
        return (int) round(($completed / $total) * 100);
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::DONE
            && $this->status !== TaskStatus::CANCELLED
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value]);
    }

    public function scopeForAssignee(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }
}
