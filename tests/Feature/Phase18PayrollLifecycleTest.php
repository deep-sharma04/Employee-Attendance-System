<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase18PayrollLifecycleTest extends TestCase
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

        Storage::fake('local');

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

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
            'monthly_salary' => 60000.00,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T188: Payroll Generation Flows
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_generate_individual_payroll(): void
    {
        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.generate'), [
            'year' => 2026,
            'month' => 8,
            'employee_id' => $this->employee->id,
        ]);

        $payroll = Payroll::where('employee_id', $this->employee->id)
            ->where('payroll_year', 2026)
            ->where('payroll_month', 8)
            ->first();

        $this->assertNotNull($payroll);
        $response->assertRedirect(route('hr-admin.payroll.show', $payroll->id));
        $this->assertEquals(PayrollStatus::DRAFT, $payroll->status);
        $this->assertEquals(60000.00, (float) $payroll->monthly_salary);
    }

    public function test_hr_admin_can_batch_generate_payroll_for_all_active_employees(): void
    {
        // Second active employee
        $user2 = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $emp2 = Employee::factory()->create([
            'user_id' => $user2->id,
            'shift_id' => $this->shift->id,
            'monthly_salary' => 45000.00,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.generate'), [
            'year' => 2026,
            'month' => 9,
        ]);

        $response->assertRedirect(route('hr-admin.payroll.index', ['year' => 2026, 'month' => 9]));
        $response->assertSessionHas('success');

        $this->assertEquals(2, Payroll::where('payroll_year', 2026)->where('payroll_month', 9)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | T188: Review, Approval & Finalization Lifecycle
    |--------------------------------------------------------------------------
    */
    public function test_full_payroll_lifecycle_review_approve_finalize_and_payslip_generation(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'present_days' => 26.0,
            'absent_days' => 0.0,
            'leave_days' => 0.0,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.00,
            'net_salary' => 60000.00,
            'status' => PayrollStatus::DRAFT,
            'payment_status' => PaymentStatus::PENDING,
            'generated_by' => $this->hrAdmin->id,
        ]);

        // 1. HR Admin marks payroll as REVIEWED
        $reviewResponse = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.review', $payroll->id));
        $reviewResponse->assertRedirect();
        $this->assertEquals(PayrollStatus::REVIEWED, $payroll->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.reviewed', 'target_id' => $payroll->id]);

        // 2. Super Admin approves payroll
        $approveResponse = $this->actingAs($this->superAdmin)->post(route('hr-admin.payroll.approve', $payroll->id));
        $approveResponse->assertRedirect();
        $this->assertEquals(PayrollStatus::APPROVED, $payroll->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.approved', 'target_id' => $payroll->id]);

        // 3. Super Admin finalizes and locks payroll
        $finalizeResponse = $this->actingAs($this->superAdmin)->post(route('hr-admin.payroll.finalize', $payroll->id));
        $finalizeResponse->assertRedirect();
        $this->assertEquals(PayrollStatus::FINALIZED, $payroll->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.finalized', 'target_id' => $payroll->id]);

        // 4. Payslip record and PDF generated
        $payslip = Payslip::where('payroll_id', $payroll->id)->first();
        $this->assertNotNull($payslip);
        $this->assertEquals(60000.00, (float) $payslip->net_pay);

        // 5. In-App Notification dispatched to employee
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'payslip_finalized',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T188: Revision Creation & Payment Status Updates
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_create_payroll_revision_for_finalized_payroll(): void
    {
        $finalizedPayroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 7,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'present_days' => 26.0,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.00,
            'net_salary' => 60000.00,
            'status' => PayrollStatus::FINALIZED,
            'payment_status' => PaymentStatus::CLEARED,
            'generated_by' => $this->hrAdmin->id,
            'revision_number' => 1,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.revision', $finalizedPayroll->id), [
            'revision_reason' => 'Adjustment for retroactive attendance correction approval.',
        ]);

        $newRevision = Payroll::where('employee_id', $this->employee->id)
            ->where('payroll_year', 2026)
            ->where('payroll_month', 7)
            ->where('revision_number', 2)
            ->first();

        $this->assertNotNull($newRevision);
        $response->assertRedirect(route('hr-admin.payroll.show', $newRevision->id));
        $this->assertEquals(PayrollStatus::DRAFT, $newRevision->status);
        $this->assertEquals('Adjustment for retroactive attendance correction approval.', $newRevision->revision_reason);

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.revised',
            'target_id' => $newRevision->id,
        ]);
    }

    public function test_hr_admin_can_update_payment_status(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.00,
            'net_salary' => 60000.00,
            'status' => PayrollStatus::FINALIZED,
            'payment_status' => PaymentStatus::PENDING,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.payment-status', $payroll->id), [
            'payment_status' => 'cleared',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(PaymentStatus::CLEARED, $payroll->fresh()->payment_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.payment_status_updated',
            'target_id' => $payroll->id,
        ]);
    }
}
