<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create and return a persistent Super Admin user.
     */
    protected function createSuperAdmin(array $attributes = []): User
    {
        return User::firstOrCreate(
            ['username' => $attributes['username'] ?? 'superadmin'],
            array_merge([
                'name' => 'Super Administrator',
                'email' => 'superadmin@hrm.local',
                'password' => Hash::make('Admin@12345'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
            ], $attributes)
        );
    }

    /**
     * Authenticate as a Super Admin.
     */
    protected function actingAsSuperAdmin(array $attributes = []): static
    {
        $user = $this->createSuperAdmin($attributes);
        return $this->actingAs($user);
    }

    /**
     * Create and return a persistent HR Admin user.
     */
    protected function createHrAdmin(array $attributes = []): User
    {
        return User::firstOrCreate(
            ['username' => $attributes['username'] ?? 'hradmin'],
            array_merge([
                'name' => 'HR Administrator',
                'email' => 'hradmin@hrm.local',
                'password' => Hash::make('HrAdmin@12345'),
                'role' => UserRole::HR_ADMIN,
                'is_active' => true,
            ], $attributes)
        );
    }

    /**
     * Authenticate as an HR Admin.
     */
    protected function actingAsHrAdmin(array $attributes = []): static
    {
        $user = $this->createHrAdmin($attributes);
        return $this->actingAs($user);
    }

    /**
     * Create and return a persistent Employee user.
     */
    protected function createEmployeeUser(array $attributes = []): User
    {
        return User::firstOrCreate(
            ['username' => $attributes['username'] ?? 'john.doe'],
            array_merge([
                'name' => 'John Employee',
                'email' => 'john.doe@hrm.local',
                'password' => Hash::make('Employee@12345'),
                'role' => UserRole::EMPLOYEE,
                'is_active' => true,
            ], $attributes)
        );
    }

    /**
     * Authenticate as an Employee.
     */
    protected function actingAsEmployee(array $attributes = []): static
    {
        $user = $this->createEmployeeUser($attributes);
        return $this->actingAs($user);
    }
}
