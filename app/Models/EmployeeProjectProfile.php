<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProjectProfile extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'employee_id',
        'user_id',
        'skills',
        'availability_status',
        'weekly_capacity_hours',
        'experience_years',
        'bio',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'weekly_capacity_hours' => 'integer',
            'experience_years' => 'decimal:1',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    /**
     * Human-readable availability badge and label.
     */
    public function availabilityLabel(): string
    {
        return match ($this->availability_status) {
            'available' => 'Fully Available',
            'partially_available' => 'Partially Available',
            'allocated' => 'Fully Allocated',
            'on_leave' => 'On Leave',
            default => ucfirst(str_replace('_', ' ', $this->availability_status)),
        };
    }

    public function availabilityBadgeClass(): string
    {
        return match ($this->availability_status) {
            'available' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'partially_available' => 'bg-amber-50 text-amber-700 border-amber-200',
            'allocated' => 'bg-blue-50 text-blue-700 border-blue-200',
            'on_leave' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
