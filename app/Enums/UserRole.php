<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case HR_ADMIN = 'hr_admin';
    case EMPLOYEE = 'employee';
    case MANAGER = 'manager';
    case TEAM_LEAD = 'team_lead';
    case CLIENT = 'client';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::HR_ADMIN => 'HR Admin',
            self::EMPLOYEE => 'Employee',
            self::MANAGER => 'Manager',
            self::TEAM_LEAD => 'Team Lead',
            self::CLIENT => 'Client',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'super-admin.dashboard',
            self::HR_ADMIN => 'hr-admin.dashboard',
            self::EMPLOYEE => 'employee.dashboard',
            self::MANAGER => 'manager.dashboard',
            self::TEAM_LEAD => 'team-lead.dashboard',
            self::CLIENT => 'client-portal.dashboard',
        };
    }
}
