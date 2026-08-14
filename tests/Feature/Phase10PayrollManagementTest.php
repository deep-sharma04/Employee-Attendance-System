<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\SalaryCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected SalaryCalculationService $salaryCalculationService;
    protected PayrollGenerationService $payrollGenerationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->salaryCalculationService = new SalaryCalculationService();
        $this->payrollGenerationService = app(PayrollGenerationService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value]);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value]);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value]);

        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create(['role' => UserRole::HR_ADMIN]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->employeeUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->employeeUser->roles()->sync([$empRole->id]);

        $shift = Shift::firstOrCreate(
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
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 60000.00, // Daily salary = 60,000 / 30 = 2,000
        ]);
    }

    public function test_daily_salary_calculation_service_uses_30_divisor(): void
    {
        $daily = $this->salaryCalculationService->calculateDailySalary(60000.00, 30);
        $this->assertEquals(2000.00, $daily);

        $dailyOdd = $this->salaryCalculationService->calculateDailySalary(45500.00, 30);
        $this->assertEquals(1516.67, $dailyOdd);
    }

    public function test_lop_deduction_and_net_salary_calculation(): void
    {
        $dailySalary = 2000.00;
        $lopDays = 3.5;

        $lopDeduction = $this->salaryCalculationService->calculateLopDeduction($dailySalary, $lopDays);
        $this->assertEquals(7000.00, $lopDeduction);

        $netSalary = $this->salaryCalculationService->calculateNetSalary(60000.00, $lopDeduction, 200.00);
        $this->assertEquals(52800.00, $netSalary);
    }

    public function test_hr_admin_can_generate_draft_payroll_with_accurate_lop_and_bridging(): void
    {
        // 2026-08: 14 Aug Absent, 15 Aug Holiday, 17 Aug Absent -> Bridged Holiday = 1 day, Direct Absences = 2 days
        Holiday::firstOrCreate(
            ['holiday_date' => '2026-08-15'],
            ['name' => 'Independence Day', 'description' => 'National Holiday']
        );

        // Populate present records for all working days except Aug 14 and Aug 17
        for ($d = 1; $d <= 31; $d++) {
            $dt = Carbon::create(2026, 8, $d);
            $dtStr = $dt->toDateString();
            if ($dt->isSunday() || $dtStr === '2026-08-15') {
                continue;
            }

            if ($dtStr === '2026-08-14' || $dtStr === '2026-08-17') {
                AttendanceRecord::create([
                    'employee_id' => $this->employee->id,
                    'attendance_date' => $dtStr,
                    'status' => AttendanceStatus::ABSENT,
                ]);
            } else {
                AttendanceRecord::create([
                    'employee_id' => $this->employee->id,
                    'attendance_date' => $dtStr,
                    'status' => AttendanceStatus::PRESENT,
                    'punch_in_at' => "{$dtStr} 08:55:00",
                    'punch_out_at' => "{$dtStr} 18:05:00",
                    'total_hours' => 9.1,
                ]);
            }
        }

        // Generate payroll via controller
        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.generate'), [
                'year' => 2026,
                'month' => 8,
                'employee_id' => $this->employee->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'bridged_holiday_days' => 1,
            'status' => 'draft',
        ]);

        $payroll = Payroll::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($payroll);
        // Total LOP = 2 direct absences + 1 bridged holiday = 3 days
        $this->assertEquals(3.0, (float) $payroll->total_lop_days);
        $this->assertEquals(6000.00, (float) $payroll->lop_deduction_amount);
        $this->assertEquals(54000.00, (float) $payroll->net_salary);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.generated',
            'target_type' => 'Payroll',
            'target_id' => $payroll->id,
        ]);
    }

    public function test_payroll_review_approval_and_finalization_workflow(): void
    {
        $payroll = $this->payrollGenerationService->generateForEmployee($this->employee->id, 2026, 8, $this->hrAdmin->id);
        $this->assertEquals(PayrollStatus::DRAFT, $payroll->status);

        // 1. HR Admin marks as Reviewed
        $reviewResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.review', $payroll->id));
        $reviewResponse->assertRedirect();
        $payroll->refresh();
        $this->assertEquals(PayrollStatus::REVIEWED, $payroll->status);

        // 2. HR Admin attempts to approve (Must fail with 403 - Super Admin only)
        $unauthApprove = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.approve', $payroll->id));
        $unauthApprove->assertForbidden();

        // 3. Super Admin approves payroll
        $superApprove = $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.approve', $payroll->id));
        $superApprove->assertRedirect();
        $payroll->refresh();
        $this->assertEquals(PayrollStatus::APPROVED, $payroll->status);
        $this->assertEquals($this->superAdmin->id, $payroll->approved_by);
        $this->assertNotNull($payroll->approved_at);

        // 4. Super Admin finalizes and locks payroll
        $finalizeResponse = $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.finalize', $payroll->id));
        $finalizeResponse->assertRedirect();
        $payroll->refresh();
        $this->assertEquals(PayrollStatus::FINALIZED, $payroll->status);
        $this->assertEquals($this->superAdmin->id, $payroll->finalizer_by ?? $payroll->finalized_by);
        $this->assertNotNull($payroll->finalized_at);
    }

    public function test_duplicate_finalized_payroll_is_blocked_without_revision(): void
    {
        $payroll = $this->payrollGenerationService->generateForEmployee($this->employee->id, 2026, 8, $this->hrAdmin->id);
        $payroll->update(['status' => PayrollStatus::FINALIZED]);

        $this->expectException(\InvalidArgumentException::class);
        $this->payrollGenerationService->generateForEmployee($this->employee->id, 2026, 8, $this->hrAdmin->id);
    }

    public function test_controlled_revision_process_for_finalized_payroll(): void
    {
        $payroll = $this->payrollGenerationService->generateForEmployee($this->employee->id, 2026, 8, $this->hrAdmin->id);
        $payroll->update(['status' => PayrollStatus::FINALIZED]);

        // 1. Revision fails without mandatory justification reason
        $emptyReasonResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.revision', $payroll->id), [
                'revision_reason' => '',
            ]);
        $emptyReasonResponse->assertSessionHasErrors(['revision_reason']);

        // 2. Revision succeeds with authorized justification
        $revisionResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.revision', $payroll->id), [
                'revision_reason' => 'Approved attendance correction on 14 Aug adjusted LOP days from 3 to 1.',
            ]);

        $revisionResponse->assertRedirect();
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'revision_number' => 2,
            'revision_reason' => 'Approved attendance correction on 14 Aug adjusted LOP days from 3 to 1.',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.revised',
            'target_type' => 'Payroll',
        ]);
    }

    public function test_payment_status_management(): void
    {
        $payroll = $this->payrollGenerationService->generateForEmployee($this->employee->id, 2026, 8, $this->hrAdmin->id);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.payment-status', $payroll->id), [
                'payment_status' => 'cleared',
            ]);

        $response->assertRedirect();
        $payroll->refresh();
        $this->assertEquals(PaymentStatus::CLEARED, $payroll->payment_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.payment_status_updated',
            'target_type' => 'Payroll',
            'target_id' => $payroll->id,
        ]);
    }

    public function test_current_payroll_status_widget_on_dashboards(): void
    {
        $this->payrollGenerationService->generateForEmployee($this->employee->id, (int) date('Y'), (int) date('n'), $this->hrAdmin->id);

        // HR Admin dashboard
        $hrResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.dashboard'));
        $hrResponse->assertOk();
        $hrResponse->assertSee('Draft Generated');

        // Super Admin dashboard
        $saResponse = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.dashboard'));
        $saResponse->assertOk();
        $saResponse->assertSee('Draft Generated');
    }
}
