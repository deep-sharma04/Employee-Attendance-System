<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::first()?->id ?? 1,
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'is_half_day' => false,
            'total_days' => 1.0,
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::PENDING,
        ];
    }
}
