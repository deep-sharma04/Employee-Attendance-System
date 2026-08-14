<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $monthly = 60000.00;
        $daily = 2000.00;

        return [
            'employee_id' => Employee::factory(),
            'payroll_month' => fake()->numberBetween(1, 12),
            'payroll_year' => 2026,
            'monthly_salary' => $monthly,
            'daily_salary' => $daily,
            'salary_divisor' => 30,
            'total_days_in_month' => 30,
            'present_days' => 26.0,
            'late_days' => 1,
            'half_days' => 0,
            'absent_days' => 0.0,
            'leave_days' => 0.0,
            'holiday_days' => 0,
            'weekend_days' => 4,
            'bridged_holiday_days' => 0,
            'converted_late_absent_days' => 0,
            'converted_half_day_absent_days' => 0,
            'total_lop_days' => 0.0,
            'lop_deduction_amount' => 0.00,
            'total_earnings' => $monthly,
            'total_deductions' => 0.00,
            'net_salary' => $monthly,
            'status' => PayrollStatus::DRAFT,
            'payment_status' => PaymentStatus::PENDING,
            'revision_number' => 1,
            'generated_by' => User::first()?->id ?? 1,
        ];
    }
}
