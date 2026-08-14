<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'attendance_date',
        'punch_in',
        'punch_out',
        'punch_in_at',
        'punch_out_at',
        'punch_in_ip',
        'punch_out_ip',
        'total_working_hours',
        'total_hours',
        'status',
        'late_minutes',
        'is_manually_corrected',
        'correction_reason',
        'corrected_by',
        'corrected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => AttendanceStatus::class,
            'total_working_hours' => 'decimal:2',
            'late_minutes' => 'integer',
            'is_manually_corrected' => 'boolean',
            'corrected_at' => 'datetime',
        ];
    }

    /**
     * Relationship: Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship: Shift
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Relationship: Corrector user
     */
    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    /**
     * Relationship: Attendance events for this employee
     */
    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'employee_id', 'employee_id');
    }

    public function attendanceEvents(): HasMany
    {
        return $this->events();
    }

    /**
     * Accessor & Mutator for punch_in_at / punch_in
     */
    protected function punchInAt(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (empty($attributes['punch_in'])) {
                    return null;
                }
                $date = $attributes['attendance_date'] ?? date('Y-m-d');
                return Carbon::parse("{$date} {$attributes['punch_in']}");
            },
            set: function ($value) {
                if (empty($value)) {
                    return ['punch_in' => null];
                }
                $time = Carbon::parse($value)->format('H:i:s');
                return ['punch_in' => $time];
            }
        );
    }

    /**
     * Accessor & Mutator for punch_out_at / punch_out
     */
    protected function punchOutAt(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (empty($attributes['punch_out'])) {
                    return null;
                }
                $date = $attributes['attendance_date'] ?? date('Y-m-d');
                return Carbon::parse("{$date} {$attributes['punch_out']}");
            },
            set: function ($value) {
                if (empty($value)) {
                    return ['punch_out' => null];
                }
                $time = Carbon::parse($value)->format('H:i:s');
                return ['punch_out' => $time];
            }
        );
    }

    /**
     * Accessor & Mutator for total_hours / total_working_hours
     */
    protected function totalHours(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['total_working_hours']) ? (float) $attributes['total_working_hours'] : null,
            set: fn($value) => ['total_working_hours' => $value]
        );
    }
}
