<?php

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18LeaveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected Shift $shift;
    protected LeaveType $casualLeave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'General Shift',
            'code' => 'GEN01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $this->shift->id,
        ]);

        $this->casualLeave = LeaveType::create([
            'name' => 'Casual Leave',
            'slug' => 'casual-leave',
            'annual_quota' => 12.0,
            'is_active' => true,
        ]);

        // Allocate 12 days balance for 2026
        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year' => 2026,
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T186: Leave Application Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_apply_for_leave_within_allocated_balance(): void
    {
        $response = $this->actingAs($this->employeeUser)->post(route('employee.leaves.store'), [
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-10', // Monday
            'end_date' => '2026-08-12',   // Wednesday (3 working days)
            'is_half_day' => false,
            'reason' => 'Family travel commitments.',
        ]);

        $response->assertRedirect(route('employee.leaves.index'));
        $response->assertSessionHas('success');

        $leave = LeaveRequest::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($leave);
        $this->assertEquals(LeaveStatus::PENDING, $leave->status);
        $this->assertEquals(3.0, (float) $leave->total_days);

        // Audit Trail recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.applied',
            'target_type' => 'App\Models\LeaveRequest',
            'target_id' => $leave->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T186: Leave Approval Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_approve_leave_and_deduct_balance(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'total_days' => 2.0,
            'status' => LeaveStatus::PENDING,
            'reason' => 'Attending medical checkup.',
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.leaves.approve', $leave->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals(LeaveStatus::APPROVED, $leave->status);
        $this->assertEquals($this->hrAdmin->id, $leave->reviewed_by);

        // Balance deducted: 12 - 2 = 10 remaining, 2 used
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->where('year', 2026)
            ->first();

        $this->assertEquals(10.0, (float) $balance->remaining_days);
        $this->assertEquals(2.0, (float) $balance->used_days);

        // In-app notification dispatched
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'leave_approved',
        ]);

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.approved',
            'target_type' => 'App\Models\LeaveRequest',
            'target_id' => $leave->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T186: Leave Rejection Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_reject_leave_with_reason_without_deducting_balance(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-12',
            'total_days' => 3.0,
            'status' => LeaveStatus::PENDING,
            'reason' => 'Vacation request.',
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.leaves.reject', $leave->id), [
            'rejection_reason' => 'Critical sprint delivery scheduled during these dates.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals(LeaveStatus::REJECTED, $leave->status);
        $this->assertEquals('Critical sprint delivery scheduled during these dates.', $leave->rejection_reason);

        // Balance untouched: 12 remaining
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->where('year', 2026)
            ->first();

        $this->assertEquals(12.0, (float) $balance->remaining_days);
        $this->assertEquals(0.0, (float) $balance->used_days);

        // In-app notification dispatched
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'leave_rejected',
        ]);

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.rejected',
            'target_type' => 'App\Models\LeaveRequest',
            'target_id' => $leave->id,
        ]);
    }

    public function test_hr_admin_rejection_requires_rejection_reason(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'total_days' => 1.0,
            'status' => LeaveStatus::PENDING,
            'reason' => 'Personal work.',
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.leaves.reject', $leave->id), [
            'rejection_reason' => '', // Missing required reason
        ]);

        $response->assertSessionHasErrors(['rejection_reason']);
        $this->assertEquals(LeaveStatus::PENDING, $leave->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | T186: Leave Cancellation Flow Tests
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_cancel_pending_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-21',
            'total_days' => 2.0,
            'status' => LeaveStatus::PENDING,
            'reason' => 'Planned personal trip.',
        ]);

        $response = $this->actingAs($this->employeeUser)->post(route('employee.leaves.cancel', $leave->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals(LeaveStatus::CANCELLED, $leave->status);
        $this->assertNotNull($leave->cancelled_at);

        // Audit Trail recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave.cancelled',
            'target_type' => 'App\Models\LeaveRequest',
            'target_id' => $leave->id,
        ]);
    }

    public function test_employee_cannot_cancel_approved_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-21',
            'total_days' => 2.0,
            'status' => LeaveStatus::APPROVED,
            'reason' => 'Approved vacation.',
        ]);

        $response = $this->actingAs($this->employeeUser)->post(route('employee.leaves.cancel', $leave->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(LeaveStatus::APPROVED, $leave->fresh()->status);
    }
}
