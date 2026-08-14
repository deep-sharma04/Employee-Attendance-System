<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'holiday_date' => fake()->unique()->date(),
            'name' => fake()->words(3, true) . ' Holiday',
            'description' => 'Official declared company holiday',
            'is_recurring_yearly' => false,
        ];
    }
}
