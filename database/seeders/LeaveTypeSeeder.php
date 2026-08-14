<?php

namespace Database\Seeders;

use App\Enums\LeaveTypeSlug;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::firstOrCreate(
            ['slug' => LeaveTypeSlug::CASUAL->value],
            [
                'name' => 'Casual Leave',
                'annual_quota' => 12.0,
                'requires_document' => false,
                'is_active' => true,
            ]
        );

        LeaveType::firstOrCreate(
            ['slug' => LeaveTypeSlug::MEDICAL->value],
            [
                'name' => 'Medical Leave',
                'annual_quota' => 12.0,
                'requires_document' => true,
                'is_active' => true,
            ]
        );
    }
}
