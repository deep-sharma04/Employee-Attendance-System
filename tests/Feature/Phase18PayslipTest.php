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

class Phase18PayslipTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUserA;
    protected Employee $employeeA;
    protected User $employeeUserB;
    protected Employee $employeeB;
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

        $this->shift = Shift::create([
            'name' => 'Day Shift',
            'code' => 'DAY01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        // Employee A
        $this->employeeUserA = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $this->employeeA = Employee::factory()->create([
            'user_id' => $this->employeeUserA->id,
            'shift_id' => $this->shift->id,
            'employee_code' => 'EMP001',
            'monthly_salary' => 60000.00,
        ]);

        // Employee B
        $this->employeeUserB = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $this->employeeB = Employee::factory()->create([
            'user_id' => $this->employeeUserB->id,
            'shift_id' => $this->shift->id,
            'employee_code' => 'EMP002',
            'monthly_salary' => 50000.00,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T189: Payslip Auto-Generation
    |--------------------------------------------------------------------------
    */
    public function test_payslip_auto_generated_when_payroll_finalized(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employeeA->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.00,
            'net_salary' => 60000.00,
            'status' => PayrollStatus::APPROVED,
            'payment_status' => PaymentStatus::PENDING,
            'generated_by' => $this->hrAdmin->id,
        ]);

        // Finalize payroll by Super Admin
        $response = $this->actingAs($this->superAdmin)->post(route('hr-admin.payroll.finalize', $payroll->id));
        $response->assertRedirect();

        $payslip = Payslip::where('payroll_id', $payroll->id)->first();
        $this->assertNotNull($payslip);
        $this->assertEquals($this->employeeA->id, $payslip->employee_id);
        $this->assertEquals(60000.00, (float) $payslip->net_pay);
        $this->assertEquals(8, $payslip->month);
        $this->assertEquals(2026, $payslip->year);

        Storage::disk('local')->assertExists($payslip->file_path);
    }

    /*
    |--------------------------------------------------------------------------
    | T189: Employee Portal Payslip Access
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_view_payslip_index_and_download_own_payslip(): void
    {
        $fakePath = 'payslips/2026/08/EMP001_2026_08.pdf';
        Storage::disk('local')->put($fakePath, '%PDF-1.4 Mock Payslip Alice');

        $payroll = Payroll::create([
            'employee_id' => $this->employeeA->id,
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
            'payment_status' => PaymentStatus::CLEARED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $payslip = Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employeeA->id,
            'payslip_number' => 'PSL-EMP001-2026-08',
            'month' => 8,
            'year' => 2026,
            'net_pay' => 60000.00,
            'file_path' => $fakePath,
            'generated_at' => now(),
        ]);

        // 1. Employee A views payslips list
        $indexResponse = $this->actingAs($this->employeeUserA)->get(route('employee.payslips.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('PSL-EMP001-2026-08');

        // 2. Employee A views own payslip inline
        $viewResponse = $this->actingAs($this->employeeUserA)->get(route('employee.payslips.view', $payslip->id));
        $viewResponse->assertOk();

        // 3. Employee A downloads own payslip
        $downloadResponse = $this->actingAs($this->employeeUserA)->get(route('employee.payslips.download', $payslip->id));
        $downloadResponse->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | T189: IDOR Cross-Employee Access Rejection
    |--------------------------------------------------------------------------
    */
    public function test_employee_cannot_access_another_employees_payslip(): void
    {
        $fakePathB = 'payslips/2026/08/EMP002_2026_08.pdf';
        Storage::disk('local')->put($fakePathB, '%PDF-1.4 Mock Payslip Bob');

        $payrollB = Payroll::create([
            'employee_id' => $this->employeeB->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 50000.00,
            'daily_salary' => 1666.67,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_earnings' => 50000.00,
            'total_deductions' => 0.00,
            'net_salary' => 50000.00,
            'status' => PayrollStatus::FINALIZED,
            'payment_status' => PaymentStatus::CLEARED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $payslipB = Payslip::create([
            'payroll_id' => $payrollB->id,
            'employee_id' => $this->employeeB->id,
            'payslip_number' => 'PSL-EMP002-2026-08',
            'month' => 8,
            'year' => 2026,
            'net_pay' => 50000.00,
            'file_path' => $fakePathB,
            'generated_at' => now(),
        ]);

        // Employee A attempts to view Employee B's payslip -> 403 Forbidden
        $this->actingAs($this->employeeUserA)
            ->get(route('employee.payslips.view', $payslipB->id))
            ->assertForbidden();

        // Employee A attempts to download Employee B's payslip -> 403 Forbidden
        $this->actingAs($this->employeeUserA)
            ->get(route('employee.payslips.download', $payslipB->id))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | T189: HR Admin / Super Admin Payslip Access
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_and_super_admin_can_access_any_payslip(): void
    {
        $fakePath = 'payslips/2026/08/EMP001_2026_08.pdf';
        Storage::disk('local')->put($fakePath, '%PDF-1.4 Mock Content');

        $payroll = Payroll::create([
            'employee_id' => $this->employeeA->id,
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
            'payment_status' => PaymentStatus::CLEARED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $payslip = Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employeeA->id,
            'payslip_number' => 'PSL-EMP001-2026-08',
            'month' => 8,
            'year' => 2026,
            'net_pay' => 60000.00,
            'file_path' => $fakePath,
            'generated_at' => now(),
        ]);

        // HR Admin view & download
        $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.payroll.payslip.view', $payroll->id))
            ->assertOk();

        $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.payroll.payslip.download', $payroll->id))
            ->assertOk();

        // Super Admin view & download
        $this->actingAs($this->superAdmin)
            ->get(route('hr-admin.payroll.payslip.view', $payroll->id))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('hr-admin.payroll.payslip.download', $payroll->id))
            ->assertOk();
    }
}
