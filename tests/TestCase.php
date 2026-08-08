<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create and return a mock or persistent Super Admin user.
     */
    protected function createSuperAdmin(array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'id' => 1,
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@hrm.local',
            'password' => Hash::make('Admin@12345'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ], $attributes));

        return $user;
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
     * Create and return a mock or persistent HR Admin user.
     */
    protected function createHrAdmin(array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'id' => 2,
            'name' => 'HR Administrator',
            'username' => 'hradmin',
            'email' => 'hradmin@hrm.local',
            'password' => Hash::make('HrAdmin@12345'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ], $attributes));

        return $user;
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
     * Create and return a mock or persistent Employee user.
     */
    protected function createEmployeeUser(array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'id' => 3,
            'name' => 'John Employee',
            'username' => 'john.doe',
            'email' => 'john.doe@hrm.local',
            'password' => Hash::make('Employee@12345'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ], $attributes));

        return $user;
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
