<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case HR_ADMIN = 'hr_admin';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::HR_ADMIN => 'HR Admin',
            self::EMPLOYEE => 'Employee',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'super-admin.dashboard',
            self::HR_ADMIN => 'hr-admin.dashboard',
            self::EMPLOYEE => 'employee.dashboard',
        };
    }
}
