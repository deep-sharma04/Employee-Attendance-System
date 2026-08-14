<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['slug' => UserRole::SUPER_ADMIN->value],
            ['name' => 'Super Admin', 'description' => 'Complete administrative access over all HR, system, and employee records']
        );

        $hrAdminRole = Role::firstOrCreate(
            ['slug' => UserRole::HR_ADMIN->value],
            ['name' => 'HR Admin', 'description' => 'Human resources manager handling employee lifecycle, attendance, leaves, and payroll draft generation']
        );

        $employeeRole = Role::firstOrCreate(
            ['slug' => UserRole::EMPLOYEE->value],
            ['name' => 'Employee', 'description' => 'Standard employee with self-service punch-in/out, leave applications, and payslip downloads']
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => UserRole::MANAGER->value],
            ['name' => 'Manager', 'description' => 'Project manager overseeing assigned teams, clients, projects, task assignments, and timesheet approvals']
        );

        $teamLeadRole = Role::firstOrCreate(
            ['slug' => UserRole::TEAM_LEAD->value],
            ['name' => 'Team Lead', 'description' => 'Team lead overseeing team task allocation, reviews, and timesheet validations']
        );

        $clientRole = Role::firstOrCreate(
            ['slug' => UserRole::CLIENT->value],
            ['name' => 'Client', 'description' => 'External client with strictly read-only portal access to linked project progress and milestones']
        );

        // Define permissions
        $permissions = [
            // Super Admin Permissions
            ['slug' => 'manage.hr_admins', 'name' => 'Manage HR Admin Accounts', 'module' => 'Administration'],
            ['slug' => 'manage.settings', 'name' => 'Manage System Settings', 'module' => 'Settings'],
            ['slug' => 'view.audit_logs', 'name' => 'View System Audit Logs', 'module' => 'Audit'],
            ['slug' => 'approve.payroll', 'name' => 'Approve & Finalize Payroll', 'module' => 'Payroll'],

            // HR Admin Permissions
            ['slug' => 'manage.employees', 'name' => 'Create & Edit Employees', 'module' => 'Employees'],
            ['slug' => 'manage.shifts', 'name' => 'Manage Shifts & Schedules', 'module' => 'Shifts'],
            ['slug' => 'manage.ip_allowlist', 'name' => 'Manage Office IP Allowlist', 'module' => 'Security'],
            ['slug' => 'manage.holidays', 'name' => 'Manage Holiday Calendar', 'module' => 'Calendar'],
            ['slug' => 'correct.attendance', 'name' => 'Manual Attendance Correction', 'module' => 'Attendance'],
            ['slug' => 'approve.leaves', 'name' => 'Approve / Reject Leave Requests', 'module' => 'Leaves'],
            ['slug' => 'manage.documents', 'name' => 'Upload & Verify Documents', 'module' => 'Documents'],
            ['slug' => 'generate.payroll', 'name' => 'Generate Monthly Payroll Batch', 'module' => 'Payroll'],
            ['slug' => 'view.reports', 'name' => 'View Operational Reports', 'module' => 'Reports'],

            // Employee Permissions
            ['slug' => 'punch.attendance', 'name' => 'Record Office Attendance', 'module' => 'SelfService'],
            ['slug' => 'apply.leave', 'name' => 'Submit Leave Application', 'module' => 'SelfService'],
            ['slug' => 'view.payslips', 'name' => 'View Finalized Payslips', 'module' => 'SelfService'],

            // Project Module Permissions (Phase 20+)
            ['slug' => 'manage.clients', 'name' => 'Manage Clients & Contacts', 'module' => 'Projects'],
            ['slug' => 'manage.teams', 'name' => 'Manage Teams & Memberships', 'module' => 'Projects'],
            ['slug' => 'manage.projects', 'name' => 'Manage Projects & Milestones', 'module' => 'Projects'],
            ['slug' => 'assign.tasks', 'name' => 'Assign & Manage Project Tasks', 'module' => 'Projects'],
            ['slug' => 'log.timesheets', 'name' => 'Log Project Task Hours', 'module' => 'Projects'],
            ['slug' => 'approve.timesheets', 'name' => 'Review & Approve Timesheets', 'module' => 'Projects'],
            ['slug' => 'view.project_reports', 'name' => 'View Project Productivity & Reports', 'module' => 'Projects'],
            ['slug' => 'view.client_portal', 'name' => 'Access Client Read-Only Portal', 'module' => 'ClientPortal'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // Attach all to super admin
        $allPermissionIds = Permission::pluck('id');
        $superAdminRole->permissions()->sync($allPermissionIds);

        // Attach HR permissions
        $hrPermissionIds = Permission::whereIn('slug', [
            'manage.employees', 'manage.shifts', 'manage.ip_allowlist', 'manage.holidays',
            'correct.attendance', 'approve.leaves', 'manage.documents', 'generate.payroll', 'view.reports'
        ])->pluck('id');
        $hrAdminRole->permissions()->sync($hrPermissionIds);

        // Attach Employee permissions
        $empPermissionIds = Permission::whereIn('slug', [
            'punch.attendance', 'apply.leave', 'view.payslips', 'log.timesheets'
        ])->pluck('id');
        $employeeRole->permissions()->sync($empPermissionIds);

        // Attach Manager permissions
        $managerPermissionIds = Permission::whereIn('slug', [
            'manage.clients', 'manage.teams', 'manage.projects', 'assign.tasks',
            'log.timesheets', 'approve.timesheets', 'view.project_reports'
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPermissionIds);

        // Attach Team Lead permissions
        $teamLeadPermissionIds = Permission::whereIn('slug', [
            'assign.tasks', 'log.timesheets', 'approve.timesheets', 'view.project_reports'
        ])->pluck('id');
        $teamLeadRole->permissions()->sync($teamLeadPermissionIds);

        // Attach Client permissions
        $clientPermissionIds = Permission::whereIn('slug', [
            'view.client_portal'
        ])->pluck('id');
        $clientRole->permissions()->sync($clientPermissionIds);
    }
}
