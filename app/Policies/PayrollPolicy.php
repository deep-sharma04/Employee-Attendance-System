<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        return $role === 'employee' && $user->employee && (int) $user->employee->id === (int) $payroll->employee_id;
    }

    public function generate(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function approve(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return $role === 'super_admin';
    }

    public function finalize(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return $role === 'super_admin';
    }
}
