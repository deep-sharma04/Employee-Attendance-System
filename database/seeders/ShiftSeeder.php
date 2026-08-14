<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::firstOrCreate(
            ['code' => 'GEN_DAY'],
            [
                'name' => 'General Day Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
                'is_active' => true,
            ]
        );
    }
}
