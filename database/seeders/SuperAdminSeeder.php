<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', UserRole::SUPER_ADMIN->value)->first();

        $user = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'partha.mcr@gmail.com',
                'password' => Hash::make('Admin@12345'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($superAdminRole) {
            $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Also create a sample HR Admin account for easy verification
        $hrAdminRole = Role::where('slug', UserRole::HR_ADMIN->value)->first();
        $hrUser = User::firstOrCreate(
            ['username' => 'hradmin'],
            [
                'name' => 'HR Administrator',
                'email' => 'hradmin@hrm.local',
                'password' => Hash::make('HrAdmin@12345'),
                'role' => UserRole::HR_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($hrAdminRole) {
            $hrUser->roles()->syncWithoutDetaching([$hrAdminRole->id]);
        }
    }
}
