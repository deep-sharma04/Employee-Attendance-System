<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'attendance_date' => fake()->date(),
            'punch_in' => '09:05:00',
            'punch_out' => '18:02:00',
            'punch_in_ip' => '127.0.0.1',
            'punch_out_ip' => '127.0.0.1',
            'total_working_hours' => 8.95,
            'status' => AttendanceStatus::PRESENT,
            'late_minutes' => 5,
            'is_manually_corrected' => false,
        ];
    }
}
