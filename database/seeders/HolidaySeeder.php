<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = (int) date('Y');

        $holidays = [
            [
                'name' => 'Republic Day',
                'holiday_date' => "{$currentYear}-01-26",
                'description' => 'National Gazetted Holiday celebrating the Constitution of India.',
                'is_recurring_yearly' => true,
            ],
            [
                'name' => 'Independence Day',
                'holiday_date' => "{$currentYear}-08-15",
                'description' => 'National Holiday celebrating Indian Independence.',
                'is_recurring_yearly' => true,
            ],
            [
                'name' => 'Gandhi Jayanti',
                'holiday_date' => "{$currentYear}-10-02",
                'description' => 'National Gazetted Holiday celebrating Mahatma Gandhi birthday.',
                'is_recurring_yearly' => true,
            ],
            [
                'name' => 'Christmas Day',
                'holiday_date' => "{$currentYear}-12-25",
                'description' => 'Festival Holiday celebrating Christmas.',
                'is_recurring_yearly' => true,
            ],
        ];

        foreach ($holidays as $data) {
            Holiday::firstOrCreate(
                [
                    'holiday_date' => $data['holiday_date'],
                ],
                $data
            );
        }
    }
}
