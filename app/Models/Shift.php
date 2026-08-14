<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'working_days',
        'grace_period_minutes',
        'half_day_threshold_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'grace_period_minutes' => 'integer',
            'half_day_threshold_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
