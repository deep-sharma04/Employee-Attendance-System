<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SuperAdminSeeder::class,
            LeaveTypeSeeder::class,
            DocumentTypeSeeder::class,
            CompanySettingSeeder::class,
            ShiftSeeder::class,
            OfficeIpAllowlistSeeder::class,
            HolidaySeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
