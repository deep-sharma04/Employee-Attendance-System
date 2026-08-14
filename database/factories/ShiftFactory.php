<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => 'General Day Shift',
            'code' => 'SHIFT_' . fake()->unique()->numerify('###'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ];
    }
}
