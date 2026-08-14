<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'timesheet_id',
        'project_id',
        'task_id',
        'entry_date',
        'hours',
        'is_billable',
        'description',
        'calculated_cost',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hours' => 'decimal:2',
            'is_billable' => 'boolean',
            'calculated_cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
