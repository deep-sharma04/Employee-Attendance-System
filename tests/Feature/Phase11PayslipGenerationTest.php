<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\PayslipGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase11PayslipGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $empUser1;
    protected User $empUser2;
    protected Employee $emp1;
    protected Employee $emp2;
    protected PayrollGenerationService $payrollService;
    protected PayslipGenerationService $payslipService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('local');

        $this->payrollService = app(PayrollGenerationService::class);
        $this->payslipService = app(PayslipGenerationService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value]);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value]);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value]);

        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create(['role' => UserRole::HR_ADMIN]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->empUser1 = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->empUser1->roles()->sync([$empRole->id]);

        $this->empUser2 = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->empUser2->roles()->sync([$empRole->id]);

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

        $this->emp1 = Employee::factory()->create([
            'user_id' => $this->empUser1->id,
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 60000.00,
            'bank_name' => 'State Bank of India',
            'account_number' => '123456789012',
            'pan_number' => 'ABCDE1234F',
        ]);

        $this->emp2 = Employee::factory()->create([
            'user_id' => $this->empUser2->id,
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 50000.00,
        ]);
    }

    public function test_payslip_generation_service_generates_pdf_only_when_finalized(): void
    {
        $payroll = $this->payrollService->generateForEmployee($this->emp1->id, 2026, 8, $this->hrAdmin->id);

        // Draft payroll -> must return null
        $this->assertNull($this->payslipService->generateForPayroll($payroll));

        // Mark finalized
        $payroll->update(['status' => PayrollStatus::FINALIZED]);

        $payslip = $this->payslipService->generateForPayroll($payroll);

        $this->assertNotNull($payslip);
        $this->assertEquals($payroll->id, $payslip->payroll_id);
        $this->assertEquals($this->emp1->id, $payslip->employee_id);
        $this->assertEquals(8, $payslip->month);
        $this->assertEquals(2026, $payslip->year);
        $this->assertEquals($payroll->net_salary, $payslip->net_pay);
        $this->assertStringStartsWith('PSL-202608-', $payslip->payslip_number);

        Storage::disk('local')->assertExists($payslip->file_path);
    }

    public function test_employee_can_view_and_download_own_finalized_payslip(): void
    {
        $payroll = $this->payrollService->generateForEmployee($this->emp1->id, 2026, 8, $this->hrAdmin->id);
        $payroll->update(['status' => PayrollStatus::FINALIZED]);
        $payslip = $this->payslipService->generateForPayroll($payroll);

        // Employee 1 accesses "My Payslips" page
        $indexResponse = $this->actingAs($this->empUser1)
            ->get(route('employee.payslips.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($payslip->payslip_number);

        // Employee 1 views own payslip
        $viewResponse = $this->actingAs($this->empUser1)
            ->get(route('employee.payslips.view', $payslip->id));
        $viewResponse->assertOk();

        // Employee 1 downloads own payslip
        $downloadResponse = $this->actingAs($this->empUser1)
            ->get(route('employee.payslips.download', $payslip->id));
        $downloadResponse->assertOk();
    }

    public function test_employee_cannot_view_or_download_other_employee_payslip(): void
    {
        $payroll = $this->payrollService->generateForEmployee($this->emp1->id, 2026, 8, $this->hrAdmin->id);
        $payroll->update(['status' => PayrollStatus::FINALIZED]);
        $payslip = $this->payslipService->generateForPayroll($payroll);

        // Employee 2 attempts to view Employee 1's payslip -> 403 Forbidden
        $viewResponse = $this->actingAs($this->empUser2)
            ->get(route('employee.payslips.view', $payslip->id));
        $viewResponse->assertForbidden();

        // Employee 2 attempts to download Employee 1's payslip -> 403 Forbidden
        $downloadResponse = $this->actingAs($this->empUser2)
            ->get(route('employee.payslips.download', $payslip->id));
        $downloadResponse->assertForbidden();
    }

    public function test_hr_and_super_admin_can_view_and_download_employee_payslips(): void
    {
        $payroll = $this->payrollService->generateForEmployee($this->emp1->id, 2026, 8, $this->hrAdmin->id);
        $payroll->update(['status' => PayrollStatus::FINALIZED]);
        $payslip = $this->payslipService->generateForPayroll($payroll);

        // HR Admin view
        $hrView = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.payroll.payslip.view', $payroll->id));
        $hrView->assertOk();

        // HR Admin download
        $hrDownload = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.payroll.payslip.download', $payroll->id));
        $hrDownload->assertOk();

        // Super Admin view
        $saView = $this->actingAs($this->superAdmin)
            ->get(route('hr-admin.payroll.payslip.view', $payroll->id));
        $saView->assertOk();

        // Super Admin download
        $saDownload = $this->actingAs($this->superAdmin)
            ->get(route('hr-admin.payroll.payslip.download', $payroll->id));
        $saDownload->assertOk();
    }
}
