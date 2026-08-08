<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine if user can view employee list.
     */
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if user can view a specific employee profile.
     */
    public function view(User $user, $employee): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        // Employee can only view their own record (own-data guard)
        return $role === 'employee' && (int) $user->id === (int) ($employee->user_id ?? 0);
    }

    /**
     * Determine if user can create employees.
     */
    public function create(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if user can update employees.
     */
    public function update(User $user, $employee): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    /**
     * Determine if user can delete/soft-delete employees.
     */
    public function delete(User $user, $employee): bool
    {
        // Hard deletion is disabled across all roles
        return false;
    }
}
