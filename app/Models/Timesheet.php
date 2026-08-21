<?php

namespace App\Models;

use App\Enums\TimesheetStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'employee_id',
        'user_id',
        'period_type',
        'start_date',
        'end_date',
        'total_hours',
        'status',
        'first_submitted_at',
        'resubmitted_at',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TimesheetStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'total_hours' => 'decimal:2',
            'first_submitted_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class)->orderBy('entry_date')->orderBy('id');
    }

    /**
     * Recalculate and update the total hours from all child entries.
     */
    public function recalculateTotalHours(): void
    {
        $this->total_hours = $this->entries()->sum('hours');
        $this->save();
    }

    /**
     * Check if timesheet is editable by employee.
     * Only 'draft' and 'returned' timesheets can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [TimesheetStatus::DRAFT, TimesheetStatus::RETURNED]);
    }

    /**
     * Check if timesheet is locked (submitted or approved).
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [TimesheetStatus::SUBMITTED, TimesheetStatus::APPROVED]);
    }
}
