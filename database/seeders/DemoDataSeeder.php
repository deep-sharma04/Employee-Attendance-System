<?php

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\ClientContact;
use App\Models\ClientDocument;
use App\Models\ClientUser;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskDependency;
use App\Models\TaskHistory;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding comprehensive HRM Demo Data...');

        // 1. Ensure Shifts exist
        $generalShift = Shift::firstOrCreate(
            ['code' => 'GEN_DAY'],
            [
                'name' => 'General Day Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
                'is_active' => true,
            ]
        );

        // 2. Ensure Roles exist
        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value], ['name' => 'Super Administrator']);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value], ['name' => 'HR Administrator']);
        $managerRole = Role::firstOrCreate(['slug' => UserRole::MANAGER->value], ['name' => 'Engineering Manager']);
        $teamLeadRole = Role::firstOrCreate(['slug' => UserRole::TEAM_LEAD->value], ['name' => 'Team Lead']);
        $employeeRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value], ['name' => 'Employee']);
        $clientRole = Role::firstOrCreate(['slug' => UserRole::CLIENT->value], ['name' => 'Client']);

        // 3. User Accounts: Admin & HR Admin
        $superAdmin = User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@hrm.local',
                'password' => Hash::make('Admin@12345'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // Secondary alias 'admin'
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Admin',
                'email' => 'admin@hrm.local',
                'password' => Hash::make('Admin@12345'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $hrAdmin = User::updateOrCreate(
            ['username' => 'hradmin'],
            [
                'name' => 'Rachel Hayes (HR Lead)',
                'email' => 'hradmin@hrm.local',
                'password' => Hash::make('HrAdmin@12345'),
                'role' => UserRole::HR_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $hrAdmin->roles()->syncWithoutDetaching([$hrAdminRole->id]);

        // 4. Employee Section Users & Employee Profiles: Manager, Team Lead, Employee

        // Manager: Marcus Vance
        $managerUser = User::updateOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'Marcus Vance',
                'email' => 'manager@hrm.local',
                'password' => Hash::make('Manager@12345'),
                'role' => UserRole::MANAGER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $managerUser->roles()->syncWithoutDetaching([$managerRole->id]);

        $managerEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-MGR-001'],
            [
                'user_id' => $managerUser->id,
                'shift_id' => $generalShift->id,
                'first_name' => 'Marcus',
                'last_name' => 'Vance',
                'email' => 'manager@hrm.local',
                'phone' => '+1 (555) 234-5678',
                'gender' => 'male',
                'date_of_birth' => '1988-05-14',
                'joining_date' => '2022-03-01',
                'department' => 'Engineering',
                'designation' => 'Director of Engineering',
                'status' => EmployeeStatus::ACTIVE,
                'monthly_salary' => 7040.00, // 7040 / 176 = $40.00/hr
            ]
        );

        EmployeeProjectProfile::updateOrCreate(
            ['employee_id' => $managerEmployee->id],
            [
                'user_id' => $managerUser->id,
                'experience_years' => 12.0,
                'weekly_capacity_hours' => 40,
                'availability_status' => 'available',
                'skills' => ['System Architecture', 'Agile Leadership', 'Kubernetes', 'Cloud Infrastructure', 'Budget Optimization'],
                'bio' => 'Experienced engineering manager specialized in high-scale distributed systems and enterprise software architecture.',
            ]
        );

        // Team Lead: Sarah Jenkins
        $teamLeadUser = User::updateOrCreate(
            ['username' => 'teamlead'],
            [
                'name' => 'Sarah Jenkins',
                'email' => 'teamlead@hrm.local',
                'password' => Hash::make('TeamLead@12345'),
                'role' => UserRole::TEAM_LEAD,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $teamLeadUser->roles()->syncWithoutDetaching([$teamLeadRole->id]);

        $teamLeadEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-TL-001'],
            [
                'user_id' => $teamLeadUser->id,
                'shift_id' => $generalShift->id,
                'first_name' => 'Sarah',
                'last_name' => 'Jenkins',
                'email' => 'teamlead@hrm.local',
                'phone' => '+1 (555) 345-6789',
                'gender' => 'female',
                'date_of_birth' => '1992-09-20',
                'joining_date' => '2022-08-15',
                'department' => 'Engineering',
                'designation' => 'Lead Full-Stack Architect',
                'status' => EmployeeStatus::ACTIVE,
                'monthly_salary' => 5280.00, // 5280 / 176 = $30.00/hr
            ]
        );

        EmployeeProjectProfile::updateOrCreate(
            ['employee_id' => $teamLeadEmployee->id],
            [
                'user_id' => $teamLeadUser->id,
                'experience_years' => 8.0,
                'weekly_capacity_hours' => 40,
                'availability_status' => 'available',
                'skills' => ['Laravel', 'PHP 8.3', 'Vue.js', 'PostgreSQL', 'Microservices', 'GraphQL', 'CI/CD Pipelines'],
                'bio' => 'Technical lead with a focus on resilient backend architectures, clean code principles, and sprint delivery.',
            ]
        );

        // Employee: Alex Rivera
        $employeeUser = User::updateOrCreate(
            ['username' => 'employee'],
            [
                'name' => 'Alex Rivera',
                'email' => 'employee@hrm.local',
                'password' => Hash::make('Employee@12345'),
                'role' => UserRole::EMPLOYEE,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $employeeUser->roles()->syncWithoutDetaching([$employeeRole->id]);

        $devEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-DEV-001'],
            [
                'user_id' => $employeeUser->id,
                'shift_id' => $generalShift->id,
                'first_name' => 'Alex',
                'last_name' => 'Rivera',
                'email' => 'employee@hrm.local',
                'phone' => '+1 (555) 456-7890',
                'gender' => 'male',
                'date_of_birth' => '1995-11-08',
                'joining_date' => '2023-02-01',
                'department' => 'Engineering',
                'designation' => 'Senior Backend Engineer',
                'status' => EmployeeStatus::ACTIVE,
                'monthly_salary' => 4400.00, // 4400 / 176 = $25.00/hr
            ]
        );

        EmployeeProjectProfile::updateOrCreate(
            ['employee_id' => $devEmployee->id],
            [
                'user_id' => $employeeUser->id,
                'experience_years' => 5.0,
                'weekly_capacity_hours' => 40,
                'availability_status' => 'available',
                'skills' => ['Laravel', 'MySQL', 'Redis', 'Docker', 'RESTful APIs', 'ElasticSearch', 'PHPUnit'],
                'bio' => 'Passionate backend developer specializing in performance optimization, caching strategies, and robust data models.',
            ]
        );

        // Additional Team Members
        $designerUser = User::updateOrCreate(
            ['username' => 'emily.chen'],
            [
                'name' => 'Emily Chen',
                'email' => 'emily@hrm.local',
                'password' => Hash::make('Employee@12345'),
                'role' => UserRole::EMPLOYEE,
                'is_active' => true,
            ]
        );
        $designerEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-DSN-002'],
            [
                'user_id' => $designerUser->id,
                'shift_id' => $generalShift->id,
                'first_name' => 'Emily',
                'last_name' => 'Chen',
                'email' => 'emily@hrm.local',
                'phone' => '+1 (555) 567-8901',
                'gender' => 'female',
                'date_of_birth' => '1997-04-18',
                'joining_date' => '2023-05-10',
                'department' => 'Design',
                'designation' => 'Senior Product UI/UX Designer',
                'status' => EmployeeStatus::ACTIVE,
                'monthly_salary' => 3872.00, // $22.00/hr
            ]
        );

        $qaUser = User::updateOrCreate(
            ['username' => 'michael.qa'],
            [
                'name' => 'Michael Brown',
                'email' => 'michael@hrm.local',
                'password' => Hash::make('Employee@12345'),
                'role' => UserRole::EMPLOYEE,
                'is_active' => true,
            ]
        );
        $qaEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-QA-003'],
            [
                'user_id' => $qaUser->id,
                'shift_id' => $generalShift->id,
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael@hrm.local',
                'phone' => '+1 (555) 678-9012',
                'gender' => 'male',
                'date_of_birth' => '1996-07-25',
                'joining_date' => '2023-06-01',
                'department' => 'QA',
                'designation' => 'Lead QA Automation Engineer',
                'status' => EmployeeStatus::ACTIVE,
                'monthly_salary' => 3520.00, // $20.00/hr
            ]
        );

        // 5. Client Account & Client Portal
        $clientUser = User::updateOrCreate(
            ['username' => 'client'],
            [
                'name' => 'Robert Sterling (Apex Tech)',
                'email' => 'client@apex.com',
                'password' => Hash::make('Client@12345'),
                'role' => UserRole::CLIENT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $clientUser->roles()->syncWithoutDetaching([$clientRole->id]);

        $client = Client::updateOrCreate(
            ['company_code' => 'CLI-APEX'],
            [
                'company_name' => 'Apex Global Enterprises',
                'email' => 'contact@apex.com',
                'phone' => '+1 (800) 555-APEX',
                'website' => 'https://apex.example.com',
                'address' => '500 Technology Square, Suite 800, San Francisco, CA 94107',
                'status' => ClientStatus::ACTIVE,
            ]
        );

        ClientUser::updateOrCreate(
            ['client_id' => $client->id, 'user_id' => $clientUser->id],
            ['is_primary' => true]
        );

        ClientContact::updateOrCreate(
            ['client_id' => $client->id, 'email' => 'rsterling@apex.com'],
            [
                'name' => 'Robert Sterling',
                'position' => 'Chief Information Officer',
                'phone' => '+1 (800) 555-2739',
                'is_primary' => true,
            ]
        );

        // 6. Teams & Squads (Phase 22)
        $squad = Team::updateOrCreate(
            ['code' => 'SQUAD-CORE'],
            [
                'name' => 'Core Engineering Squad',
                'description' => 'Cross-functional engineering squad dedicated to core banking and high-scale cloud platforms.',
                'manager_id' => $managerUser->id,
                'team_lead_id' => $teamLeadUser->id,
                'is_active' => true,
            ]
        );

        TeamMember::updateOrCreate(
            ['team_id' => $squad->id, 'employee_id' => $teamLeadEmployee->id],
            ['user_id' => $teamLeadUser->id, 'is_primary' => true, 'joined_at' => now()->subMonths(12)]
        );
        TeamMember::updateOrCreate(
            ['team_id' => $squad->id, 'employee_id' => $devEmployee->id],
            ['user_id' => $employeeUser->id, 'is_primary' => true, 'joined_at' => now()->subMonths(10)]
        );
        TeamMember::updateOrCreate(
            ['team_id' => $squad->id, 'employee_id' => $designerEmployee->id],
            ['user_id' => $designerUser->id, 'is_primary' => true, 'joined_at' => now()->subMonths(8)]
        );
        TeamMember::updateOrCreate(
            ['team_id' => $squad->id, 'employee_id' => $qaEmployee->id],
            ['user_id' => $qaUser->id, 'is_primary' => true, 'joined_at' => now()->subMonths(6)]
        );

        // 7. Projects & Milestones (Phase 23)
        $bankingProject = Project::updateOrCreate(
            ['code' => 'PROJ-BANK-01'],
            [
                'name' => 'Apex Cloud Banking Platform',
                'client_id' => $client->id,
                'team_id' => $squad->id,
                'manager_id' => $managerUser->id,
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::HIGH->value,
                'health' => ProjectHealth::GOOD->value,
                'budget' => 125000.00,
                'start_date' => Carbon::now()->subMonths(1)->startOfMonth(),
                'deadline' => Carbon::now()->addMonths(3)->endOfMonth(),
                'description' => 'Secure next-generation omnichannel cloud banking suite with automated compliance, real-time ledger synchronization, and fraud detection.',
            ]
        );

        ProjectMember::updateOrCreate(
            ['project_id' => $bankingProject->id, 'user_id' => $managerUser->id],
            ['employee_id' => $managerEmployee->id, 'project_role' => \App\Enums\ProjectMemberRole::MANAGER->value, 'is_active' => true, 'joined_at' => now()->subMonths(1)]
        );
        ProjectMember::updateOrCreate(
            ['project_id' => $bankingProject->id, 'user_id' => $teamLeadUser->id],
            ['employee_id' => $teamLeadEmployee->id, 'project_role' => \App\Enums\ProjectMemberRole::TEAM_LEAD->value, 'is_active' => true, 'joined_at' => now()->subMonths(1)]
        );
        ProjectMember::updateOrCreate(
            ['project_id' => $bankingProject->id, 'user_id' => $employeeUser->id],
            ['employee_id' => $devEmployee->id, 'project_role' => \App\Enums\ProjectMemberRole::MEMBER->value, 'is_active' => true, 'joined_at' => now()->subMonths(1)]
        );

        // Milestones
        $m1 = ProjectMilestone::updateOrCreate(
            ['project_id' => $bankingProject->id, 'title' => 'Phase 1: High-Level Architecture & API Design'],
            [
                'description' => 'Completion of technical RFCs, data models, OpenAPI 3.0 specs, and security audit sign-offs.',
                'due_date' => Carbon::now()->subDays(10),
                'status' => 'completed',
                'completed_at' => Carbon::now()->subDays(12),
                'order' => 1,
            ]
        );

        $m2 = ProjectMilestone::updateOrCreate(
            ['project_id' => $bankingProject->id, 'title' => 'Phase 2: Core Banking Transaction Engine'],
            [
                'description' => 'Ledger processing, double-entry bookkeeping engine, and high-concurrency wallet transfers.',
                'due_date' => Carbon::now()->addDays(20),
                'status' => 'in_progress',
                'order' => 2,
            ]
        );

        $m3 = ProjectMilestone::updateOrCreate(
            ['project_id' => $bankingProject->id, 'title' => 'Phase 3: Omnichannel Mobile Client Delivery & Pentest'],
            [
                'description' => 'End-to-end integration testing, client UAT, penetration testing, and production rollout.',
                'due_date' => Carbon::now()->addMonths(2),
                'status' => 'pending',
                'order' => 3,
            ]
        );

        // Secondary Project
        $logisticsProject = Project::updateOrCreate(
            ['code' => 'PROJ-LOG-02'],
            [
                'name' => 'NextGen Supply Chain & Fleet Telematics',
                'client_id' => $client->id,
                'team_id' => $squad->id,
                'manager_id' => $managerUser->id,
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => ProjectPriority::MEDIUM->value,
                'health' => ProjectHealth::GOOD->value,
                'budget' => 85000.00,
                'start_date' => Carbon::now()->subDays(15),
                'deadline' => Carbon::now()->addMonths(4),
                'description' => 'IoT telematics tracking for multimodal fleet shipments with automated ETA prediction.',
            ]
        );

        // 8. Tasks & Work Items (Phase 24)
        $t1 = Task::updateOrCreate(
            ['task_code' => 'TSK-BANK-01'],
            [
                'project_id' => $bankingProject->id,
                'title' => 'Implement OAuth2 MFA & Biometric Auth Gateway',
                'description' => 'Implement secure authorization flow with TOTP, biometric credentials, and Redis token revocation.',
                'assigned_to' => $employeeUser->id,
                'created_by' => $teamLeadUser->id,
                'status' => TaskStatus::IN_PROGRESS->value,
                'priority' => TaskPriority::HIGH->value,
                'estimated_hours' => 24.0,
                'actual_hours' => 14.0,
                'due_date' => Carbon::now()->addDays(3),
            ]
        );

        TaskChecklist::firstOrCreate(
            ['task_id' => $t1->id, 'title' => 'Implement TOTP QR Code generation and validation'],
            ['is_completed' => true, 'order' => 1]
        );
        TaskChecklist::firstOrCreate(
            ['task_id' => $t1->id, 'title' => 'Configure Redis Token Blacklist with TTL expiration'],
            ['is_completed' => true, 'order' => 2]
        );
        TaskChecklist::firstOrCreate(
            ['task_id' => $t1->id, 'title' => 'Add rate-limiting and device fingerprint anomaly guard'],
            ['is_completed' => false, 'order' => 3]
        );

        TaskComment::firstOrCreate(
            ['task_id' => $t1->id, 'user_id' => $teamLeadUser->id],
            [
                'comment' => 'Please verify token rotation handling on mobile reconnect scenarios.',
                'is_internal' => true,
            ]
        );

        $t2 = Task::updateOrCreate(
            ['task_code' => 'TSK-BANK-02'],
            [
                'project_id' => $bankingProject->id,
                'title' => 'Database Schema Migration & Replication Cluster Setup',
                'description' => 'Setup PostgreSQL master-replica cluster with automatic failover and read pooling.',
                'assigned_to' => $employeeUser->id,
                'created_by' => $managerUser->id,
                'status' => TaskStatus::DONE->value,
                'priority' => TaskPriority::URGENT->value,
                'estimated_hours' => 16.0,
                'actual_hours' => 16.0,
                'due_date' => Carbon::now()->subDays(6),
                'completed_at' => Carbon::now()->subDays(6),
            ]
        );

        $t3 = Task::updateOrCreate(
            ['task_code' => 'TSK-BANK-03'],
            [
                'project_id' => $bankingProject->id,
                'title' => 'Double-Entry Ledger Engine Core Service',
                'description' => 'Create immutable ledger transaction dispatching service with idempotency keys.',
                'assigned_to' => $employeeUser->id,
                'created_by' => $teamLeadUser->id,
                'status' => TaskStatus::TODO->value,
                'priority' => TaskPriority::HIGH->value,
                'estimated_hours' => 32.0,
                'actual_hours' => 0.0,
                'due_date' => Carbon::now()->addDays(12),
            ]
        );

        // 9. Timesheets (Phase 25)
        // Approved Timesheet for last week
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $approvedTimesheet = Timesheet::updateOrCreate(
            ['employee_id' => $devEmployee->id, 'start_date' => $lastWeekStart->toDateString()],
            [
                'user_id' => $employeeUser->id,
                'end_date' => $lastWeekEnd->toDateString(),
                'status' => TimesheetStatus::APPROVED->value,
                'total_hours' => 38.0,
                'submitted_at' => $lastWeekEnd,
                'approved_at' => $lastWeekEnd->copy()->addHours(4),
                'approved_by' => $managerUser->id,
            ]
        );

        TimesheetEntry::updateOrCreate(
            ['timesheet_id' => $approvedTimesheet->id, 'entry_date' => $lastWeekStart->toDateString()],
            [
                'project_id' => $bankingProject->id,
                'task_id' => $t2->id,
                'hours' => 8.0,
                'is_billable' => true,
                'description' => 'PostgreSQL high-availability cluster setup and replication testing.',
                'calculated_cost' => 200.00, // 8 * 25.00
            ]
        );

        TimesheetEntry::updateOrCreate(
            ['timesheet_id' => $approvedTimesheet->id, 'entry_date' => $lastWeekStart->copy()->addDay()->toDateString()],
            [
                'project_id' => $bankingProject->id,
                'task_id' => $t2->id,
                'hours' => 8.0,
                'is_billable' => true,
                'description' => 'Automated failover fail-safe checks with PgBouncer connection pooling.',
                'calculated_cost' => 200.00,
            ]
        );

        // Current Week Submitted Timesheet for review
        $currWeekStart = Carbon::now()->startOfWeek();
        $currWeekEnd = Carbon::now()->endOfWeek();

        $pendingTimesheet = Timesheet::updateOrCreate(
            ['employee_id' => $devEmployee->id, 'start_date' => $currWeekStart->toDateString()],
            [
                'user_id' => $employeeUser->id,
                'end_date' => $currWeekEnd->toDateString(),
                'status' => TimesheetStatus::SUBMITTED->value,
                'total_hours' => 14.0,
                'submitted_at' => Carbon::now()->subHours(2),
            ]
        );

        TimesheetEntry::updateOrCreate(
            ['timesheet_id' => $pendingTimesheet->id, 'entry_date' => $currWeekStart->toDateString()],
            [
                'project_id' => $bankingProject->id,
                'task_id' => $t1->id,
                'hours' => 7.0,
                'is_billable' => true,
                'description' => 'OAuth2 PKCE flow and TOTP key generation handlers.',
                'calculated_cost' => 175.00,
            ]
        );

        TimesheetEntry::updateOrCreate(
            ['timesheet_id' => $pendingTimesheet->id, 'entry_date' => $currWeekStart->copy()->addDay()->toDateString()],
            [
                'project_id' => $bankingProject->id,
                'task_id' => $t1->id,
                'hours' => 7.0,
                'is_billable' => true,
                'description' => 'Redis token blacklist middleware with unit test suites.',
                'calculated_cost' => 175.00,
            ]
        );

        // 10. Client Documents & Communications (Phase 21 & Phase 26)
        Storage::disk('local')->put("clients/{$client->id}/documents/apex_architecture_spec_2026.pdf", "Dummy PDF content for Apex Architecture");
        Storage::disk('local')->put("clients/{$client->id}/documents/internal_profitability_model.pdf", "Dummy confidential financial model");

        ClientDocument::updateOrCreate(
            ['client_id' => $client->id, 'file_name' => 'apex_architecture_spec_2026.pdf'],
            [
                'uploaded_by' => $managerUser->id,
                'title' => 'Apex Cloud Banking — Technical Architecture Blueprint',
                'file_path' => "clients/{$client->id}/documents/apex_architecture_spec_2026.pdf",
                'file_size' => 450560,
                'mime_type' => 'application/pdf',
                'is_shared_with_client' => true,
                'notes' => 'Official architecture specification with system sequence diagrams and API schemas.',
            ]
        );

        ClientDocument::updateOrCreate(
            ['client_id' => $client->id, 'file_name' => 'internal_profitability_model.pdf'],
            [
                'uploaded_by' => $managerUser->id,
                'title' => 'Internal Labor Cost & Resource Margin Estimates',
                'file_path' => "clients/{$client->id}/documents/internal_profitability_model.pdf",
                'file_size' => 204800,
                'mime_type' => 'application/pdf',
                'is_shared_with_client' => false,
                'notes' => 'Confidential resource cost allocation model. Strictly internal.',
            ]
        );

        ClientCommunication::updateOrCreate(
            ['client_id' => $client->id, 'subject' => 'Sprint 2 Architecture Sign-off & Milestone Review'],
            [
                'user_id' => $managerUser->id,
                'type' => 'meeting',
                'details' => 'Met with Robert Sterling (CIO) to present Phase 1 milestone deliverables. Architecture approved without reservations. Action item: Proceed with Phase 2 Core Ledger implementation.',
                'communication_date' => Carbon::now()->subDays(5),
            ]
        );

        // 11. Attendance records for demo employee
        for ($i = 1; $i <= 5; $i++) {
            $date = Carbon::now()->subDays($i);
            if (!$date->isWeekend()) {
                \App\Models\AttendanceRecord::updateOrCreate(
                    ['employee_id' => $devEmployee->id, 'attendance_date' => $date->toDateString()],
                    [
                        'shift_id' => $generalShift->id,
                        'punch_in_at' => $date->copy()->setTime(9, 5, 0),
                        'punch_out_at' => $date->copy()->setTime(18, 10, 0),
                        'total_working_hours' => 9.08,
                        'status' => \App\Enums\AttendanceStatus::PRESENT,
                    ]
                );
            }
        }

        $this->command?->info('Comprehensive HRM Demo Data seeded successfully!');
    }
}
