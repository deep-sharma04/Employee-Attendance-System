<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveTypeSlug;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveWorkingDayService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_hr_admin_can_manage_leave_types_and_allocate_quotas(): void
    {
        // 1. View leave types page
        $response = $this->actingAsHrAdmin()->get('/hr-admin/leaves/types');
        $response->assertStatus(200);
        $response->assertSee('Active Leave Types');

        // 2. Create a new custom leave type
        $storeResponse = $this->actingAsHrAdmin()->post('/hr-admin/leaves/types', [
            'name' => 'Bereavement Leave',
            'slug' => 'bereavement_leave',
            'annual_quota' => 5.0,
            'requires_document' => true,
            'is_active' => true,
        ]);
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('leave_types', [
            'name' => 'Bereavement Leave',
            'slug' => 'bereavement_leave',
        ]);

        // 3. Allocate quota to employee
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        $allocResponse = $this->actingAsHrAdmin()->post('/hr-admin/leaves/allocation', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 14.0,
        ]);
        $allocResponse->assertSessionHas('success');

        $this->assertDatabaseHas('employee_leave_balances', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 14.0,
            'remaining_days' => 14.0,
        ]);
    }

    public function test_leave_working_day_service_excludes_sundays_and_declared_holidays(): void
    {
        $service = app(LeaveWorkingDayService::class);

        // Monday 2026-08-10 to Friday 2026-08-14 (5 weekdays)
        $days = $service->calculateWorkingDays('2026-08-10', '2026-08-14');
        $this->assertEquals(5.0, $days);

        // Thursday 2026-08-20 to Monday 2026-08-24
        // Thu 20th, Fri 21st, Sat 22nd, Mon 24th = 4 working days (Sunday 23rd excluded)
        $daysWithWeekend = $service->calculateWorkingDays('2026-08-20', '2026-08-24');
        $this->assertEquals(4.0, $daysWithWeekend);

        // Declare a holiday on 2026-08-22 (Saturday)
        Holiday::create([
            'name' => 'Company Foundation Day',
            'holiday_date' => '2026-08-22',
            'is_recurring_yearly' => false,
        ]);

        // Now Thu to Mon = Thu, Fri, Mon (3 days because Sat 22nd is a holiday and Sun 23rd is Sunday)
        $daysWithHoliday = $service->calculateWorkingDays('2026-08-20', '2026-08-24');
        $this->assertEquals(3.0, $daysWithHoliday);

        // Half day single day = 0.5
        $halfDay = $service->calculateWorkingDays('2026-08-10', '2026-08-10', true);
        $this->assertEquals(0.5, $halfDay);
    }

    public function test_employee_can_apply_for_leave_within_balance(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        // Allocate 10 days
        EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 10.0,
            'used_days' => 0.0,
            'remaining_days' => 10.0,
        ]);

        // Apply for 2 days
        $response = $this->actingAs($user, 'web')->post('/employee/leaves/apply', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'reason' => 'Attending family wedding ceremony.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'total_days' => 2.0,
            'status' => LeaveStatus::PENDING->value,
        ]);

        // Audit Trail check
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.applied',
            'target_type' => LeaveRequest::class,
        ]);
    }

    public function test_employee_cannot_apply_for_leave_exceeding_balance(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        // Allocate only 1 day
        EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 1.0,
            'used_days' => 0.0,
            'remaining_days' => 1.0,
        ]);

        // Try applying for 5 days
        $response = $this->actingAs($user, 'web')->post('/employee/leaves/apply', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-14',
            'reason' => 'Vacation trip beyond my available quota.',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
        $this->assertEquals(0, LeaveRequest::count());
    }

    public function test_hr_admin_can_approve_leave_and_syncs_attendance_and_deducts_balance(): void
    {
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        // Initial 12 days balance
        $balance = EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'is_half_day' => false,
            'total_days' => 2.0,
            'reason' => 'Personal work at home town.',
            'status' => LeaveStatus::PENDING,
        ]);

        $response = $this->actingAsHrAdmin()->post("/hr-admin/leaves/{$leaveRequest->id}/approve");
        $response->assertSessionHas('success');

        $leaveRequest->refresh();
        $balance->refresh();

        // 1. Status updated
        $this->assertEquals(LeaveStatus::APPROVED, $leaveRequest->status);
        $this->assertNotNull($leaveRequest->reviewed_at);

        // 2. Balance deducted (12 - 2 = 10)
        $this->assertEquals(10.0, (float) $balance->remaining_days);
        $this->assertEquals(2.0, (float) $balance->used_days);

        // 3. Attendance synced as 'leave'
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-10',
            'status' => AttendanceStatus::LEAVE->value,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-11',
            'status' => AttendanceStatus::LEAVE->value,
        ]);

        // 4. Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.approved',
            'target_type' => LeaveRequest::class,
            'target_id' => $leaveRequest->id,
        ]);
    }

    public function test_hr_admin_can_reject_leave_with_mandatory_reason(): void
    {
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'total_days' => 2.0,
            'reason' => 'Emergency personal commitment.',
            'status' => LeaveStatus::PENDING,
        ]);

        // 1. Rejection without reason fails validation
        $failResponse = $this->actingAsHrAdmin()->post("/hr-admin/leaves/{$leaveRequest->id}/reject", [
            'rejection_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('rejection_reason');

        // 2. Rejection with valid reason
        $response = $this->actingAsHrAdmin()->post("/hr-admin/leaves/{$leaveRequest->id}/reject", [
            'rejection_reason' => 'Critical production release scheduled on these dates; team attendance required.',
        ]);
        $response->assertSessionHas('success');

        $leaveRequest->refresh();
        $this->assertEquals(LeaveStatus::REJECTED, $leaveRequest->status);
        $this->assertEquals('Critical production release scheduled on these dates; team attendance required.', $leaveRequest->rejection_reason);

        // Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.rejected',
            'target_type' => LeaveRequest::class,
            'target_id' => $leaveRequest->id,
        ]);
    }

    public function test_employee_can_cancel_pending_leave_but_cannot_cancel_approved_leave(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        // 1. Pending leave can be cancelled
        $pendingRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-21',
            'total_days' => 2.0,
            'reason' => 'Plans cancelled.',
            'status' => LeaveStatus::PENDING,
        ]);

        $cancelResponse = $this->actingAs($user, 'web')->post("/employee/leaves/{$pendingRequest->id}/cancel");
        $cancelResponse->assertSessionHas('success');

        $pendingRequest->refresh();
        $this->assertEquals(LeaveStatus::CANCELLED, $pendingRequest->status);
        $this->assertNotNull($pendingRequest->cancelled_at);

        // 2. Approved leave CANNOT be cancelled by employee (T094)
        $approvedRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-26',
            'total_days' => 2.0,
            'reason' => 'Approved vacation.',
            'status' => LeaveStatus::APPROVED,
        ]);

        $blockResponse = $this->actingAs($user, 'web')->post("/employee/leaves/{$approvedRequest->id}/cancel");
        $blockResponse->assertSessionHas('error');

        $approvedRequest->refresh();
        $this->assertEquals(LeaveStatus::APPROVED, $approvedRequest->status);
    }

    public function test_leave_expiry_resets_unused_balances_at_cycle_end(): void
    {
        $service = app(LeaveBalanceService::class);
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $leaveType = LeaveType::first();

        EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2025,
            'allocated_days' => 12.0,
            'used_days' => 4.0,
            'remaining_days' => 8.0,
        ]);

        // Expire 2025 cycle
        $updated = $service->expireUnusedBalances(2025);
        $this->assertGreaterThan(0, $updated);

        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('year', 2025)
            ->first();

        $this->assertEquals(0.0, (float) $balance->remaining_days);
    }
}
