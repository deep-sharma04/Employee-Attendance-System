<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LeaveRequest;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (in_array($role, ['super_admin', 'hr_admin'])) {
            return true;
        }

        return $role === 'employee' && $user->employee && (int) $user->employee->id === (int) $leaveRequest->employee_id;
    }

    public function apply(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return $role === 'employee' && (bool) $user->is_active;
    }

    public function approve(User $user): bool
    {
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        return in_array($role, ['super_admin', 'hr_admin']);
    }
}
