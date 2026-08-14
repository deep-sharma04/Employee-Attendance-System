<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\IpAllowlist;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use App\Services\Payroll\PayrollGenerationService;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class Phase13AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected Shift $shift;
    protected AuditLoggerService $auditLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->auditLogger = app(AuditLoggerService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value]);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value]);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value]);

        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create(['role' => UserRole::HR_ADMIN]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->employeeUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->employeeUser->roles()->sync([$empRole->id]);

        $this->shift = Shift::firstOrCreate(
            ['code' => 'GEN-001'],
            [
                'name' => 'General Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'is_active' => true,
            ]
        );

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $this->shift->id,
            'status' => 'active',
            'department' => 'Engineering',
            'monthly_salary' => 60000.00,
        ]);
    }

    public function test_audit_logger_service_and_auditable_trait_record_immutable_logs(): void
    {
        $this->actingAs($this->superAdmin);

        $this->auditLogger->log(
            action: 'employee.onboarded',
            targetType: 'Employee',
            targetId: $this->employee->id,
            beforeValues: null,
            afterValues: ['employee_code' => $this->employee->employee_code, 'department' => 'Engineering'],
            description: 'New employee onboarded by Super Admin'
        );

        $log = AuditLog::where('action', 'employee.onboarded')->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->superAdmin->id, $log->actor_id);
        $this->assertEquals('Employee', $log->target_type);
        $this->assertEquals($this->employee->id, $log->target_id);
        $this->assertIsArray($log->after_values);
        $this->assertEquals('Engineering', $log->after_values['department']);
    }

    public function test_audit_logs_are_immutable_and_cannot_be_updated_or_deleted(): void
    {
        $this->auditLogger->log(
            action: 'security.scan',
            targetType: 'System',
            description: 'Automated scan'
        );

        $log = AuditLog::where('action', 'security.scan')->first();
        $this->assertNotNull($log);

        // Attempting to update must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be modified.');
        $log->update(['description' => 'Tampered record']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $this->auditLogger->log(
            action: 'security.check',
            targetType: 'System',
            description: 'Immutability delete test'
        );

        $log = AuditLog::where('action', 'security.check')->first();
        $this->assertNotNull($log);

        // Attempting to delete must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be deleted.');
        $log->delete();
    }

    public function test_super_admin_can_view_and_filter_audit_logs(): void
    {
        $this->actingAs($this->superAdmin);

        $this->auditLogger->log(
            action: 'payroll.approved',
            targetType: 'Payroll',
            targetId: 10,
            description: 'August payroll approved'
        );

        $this->auditLogger->log(
            action: 'ip_allowlist.created',
            targetType: 'IpAllowlist',
            targetId: 2,
            description: 'Headquarters IP added'
        );

        // 1. Index view
        $response = $this->get(route('super-admin.audit-logs.index'));
        $response->assertOk();
        $response->assertSee('System Audit Trail');
        $response->assertSee('payroll.approved');
        $response->assertSee('ip_allowlist.created');

        // 2. Filter by action
        $filteredResponse = $this->get(route('super-admin.audit-logs.index', ['action' => 'payroll.approved']));
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('payroll.approved');

        // 3. Search query
        $searchResponse = $this->get(route('super-admin.audit-logs.index', ['search' => 'Headquarters']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('ip_allowlist.created');
    }

    public function test_hr_admin_can_view_limited_operational_audit_logs(): void
    {
        $this->actingAs($this->superAdmin);

        // Operational action
        $this->auditLogger->log(
            action: 'leave.approved',
            targetType: 'LeaveRequest',
            targetId: 5,
            description: 'Casual leave approved'
        );

        // Administrative action (hidden from HR Admin)
        DB::table('audit_logs')->insert([
            'actor_id' => $this->superAdmin->id,
            'actor_name' => $this->superAdmin->name,
            'actor_role' => 'super_admin',
            'action' => 'hr_admin.created',
            'target_type' => 'User',
            'target_id' => 99,
            'description' => 'Created new HR Admin user',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        // HR Admin accesses operational activity log
        $hrResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.audit-logs.index'));

        $hrResponse->assertOk();
        $hrResponse->assertSee('Operational Activity Log');
        $hrResponse->assertSee('leave.approved');
        $hrResponse->assertDontSee('Created new HR Admin user');
    }

    public function test_employee_is_forbidden_from_accessing_audit_logs(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('super-admin.audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_logging_coverage_across_lifecycle_events(): void
    {
        // 1. Employee status update
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.employees.update-status', $this->employee->id), [
                'status' => 'inactive',
                'status_change_reason' => 'Extended sabbatical leave',
            ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.status_changed']);

        // 2. Shift status toggle
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.shifts.toggle-status', $this->shift->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'shift.status_toggled']);

        // 3. IP Allowlist creation
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.ip-allowlists.store'), [
                'ip_address' => '192.168.10.50',
                'label' => 'Branch Office',
                'is_active' => true,
            ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ip_allowlist.created']);

        // 4. Holiday creation
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.holidays.store'), [
                'name' => 'Corporate Annual Gala',
                'holiday_date' => (date('Y') + 1) . '-11-20',
                'is_recurring_yearly' => false,
            ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'holiday.created']);

        // 5. Leave Request approval
        $leaveType = LeaveType::firstOrCreate(
            ['slug' => 'casual-leave'],
            ['name' => 'Casual Leave', 'annual_quota' => 12, 'is_active' => true]
        );

        \App\Models\EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => (int) date('Y'),
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'total_days' => 1.0,
            'status' => LeaveStatus::PENDING,
            'reason' => 'Personal work',
        ]);

        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.approve', $leave->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'leave.approved']);

        // 6. Payroll workflow (Generation -> Approval -> Finalization)
        $payrollService = app(PayrollGenerationService::class);
        $payroll = $payrollService->generateForEmployee($this->employee->id, 2026, 9, $this->hrAdmin->id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.generated']);

        $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.approve', $payroll->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.approved']);

        $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.finalize', $payroll->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.finalized']);
    }
}
