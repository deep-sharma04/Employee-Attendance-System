<?php

namespace App\Models;

use App\Enums\AttendanceAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_record_id',
        'action',
        'event_timestamp',
        'ip_address',
        'user_agent',
        'is_valid',
        'invalidation_reason',
    ];

    protected function casts(): array
    {
        return [
            'action' => AttendanceAction::class,
            'event_timestamp' => 'datetime',
            'is_valid' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    /**
     * Accessor & Mutator for event_type / action
     */
    protected function eventType(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $attributes['action'] ?? null,
            set: fn($value) => ['action' => $value instanceof AttendanceAction ? $value->value : $value]
        );
    }

    /**
     * Accessor & Mutator for event_time / event_timestamp
     */
    protected function eventTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['event_timestamp']) ? Carbon::parse($attributes['event_timestamp']) : null,
            set: fn($value) => ['event_timestamp' => $value]
        );
    }
}
