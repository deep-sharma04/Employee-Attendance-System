<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12ReportsAndDashboardsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

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

    public function test_super_admin_dashboard_renders_metrics_and_attendance_breakdown(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => today()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
            'punch_in_at' => today()->toDateString() . ' 08:50:00',
            'punch_out_at' => today()->toDateString() . ' 18:00:00',
            'total_hours' => 9.1,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Employees');
        $response->assertSee('Attendance Breakdown');
        $response->assertSee('Recent System Audit Trail');
    }

    public function test_hr_admin_dashboard_renders_shortcuts_and_breakdown(): void
    {
        $response = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Active Employees');
        $response->assertSee('Attendance Breakdown');
        $response->assertSee('Attendance Monitoring');
        $response->assertSee('Generate Monthly Payroll');
        $response->assertSee('Quick Reports Access');
    }

    public function test_employee_dashboard_renders_self_service_widgets(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.dashboard'));

        $response->assertOk();
        $response->assertSee('Daily Attendance Punch');
        $response->assertSee('Leave Balances');
        $response->assertSee('Recent Payslips');
        $response->assertSee('System Notifications');
    }

    public function test_attendance_report_suite_filtering_and_csv_export(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id,
            'attendance_date' => today()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
            'punch_in_at' => today()->toDateString() . ' 08:50:00',
            'punch_out_at' => today()->toDateString() . ' 18:00:00',
            'total_hours' => 9.1,
        ]);

        // 1. View Report Page
        $viewResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.attendance', [
                'department' => 'Engineering',
                'status' => 'present',
            ]));
        $viewResponse->assertOk();
        $viewResponse->assertSee($this->employee->first_name);

        // 2. Export CSV
        $csvResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.attendance.export', [
                'department' => 'Engineering',
            ]));
        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_leave_report_suite_filtering_and_csv_export(): void
    {
        $leaveType = LeaveType::firstOrCreate(
            ['slug' => 'casual-leave'],
            ['name' => 'Casual Leave', 'annual_quota' => 12, 'is_active' => true]
        );

        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'total_days' => 1.0,
            'status' => LeaveStatus::APPROVED,
            'reason' => 'Personal work',
            'reviewed_at' => now(),
        ]);

        // 1. View Report Page
        $viewResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.leave', [
                'status' => 'approved',
            ]));
        $viewResponse->assertOk();
        $viewResponse->assertSee($this->employee->first_name);
        $viewResponse->assertSee('Casual Leave');

        // 2. Export CSV
        $csvResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.leave.export'));
        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_payroll_report_suite_filtering_and_csv_export(): void
    {
        $payrollService = app(PayrollGenerationService::class);
        $payroll = $payrollService->generateForEmployee($this->employee->id, (int) date('Y'), (int) date('n'), $this->hrAdmin->id);

        // 1. View Report Page
        $viewResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.payroll', [
                'year' => date('Y'),
                'month' => date('n'),
            ]));
        $viewResponse->assertOk();
        $viewResponse->assertSee($this->employee->first_name);
        $viewResponse->assertSee('Total Gross');

        // 2. Export CSV
        $csvResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.reports.payroll.export', [
                'year' => date('Y'),
                'month' => date('n'),
            ]));
        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_employee_is_forbidden_from_accessing_hr_reports(): void
    {
        $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.reports.attendance'))
            ->assertForbidden();

        $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.reports.leave'))
            ->assertForbidden();

        $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.reports.payroll'))
            ->assertForbidden();
    }
}
