<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::EMPLOYEE]),
            'shift_id' => Shift::factory(),
            'employee_code' => 'EMP' . fake()->unique()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'date_of_birth' => fake()->date('Y-m-d', '-22 years'),
            'joining_date' => fake()->date('Y-m-d', '-1 years'),
            'department' => fake()->randomElement(['Engineering', 'Operations', 'Finance', 'Human Resources', 'Marketing']),
            'designation' => fake()->jobTitle(),
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => fake()->numberBetween(25000, 120000),
            'bank_name' => 'HDFC Bank',
            'account_number' => fake()->numerify('##########'),
            'ifsc_code' => 'HDFC0001234',
            'pan_number' => 'ABCDE' . fake()->numerify('####') . 'F',
        ];
    }
}
