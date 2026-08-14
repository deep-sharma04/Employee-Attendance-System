<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T183: Super Admin Access Tests
    |--------------------------------------------------------------------------
    */
    public function test_super_admin_can_access_all_super_admin_endpoints(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get(route('super-admin.dashboard'))->assertOk();
        $this->get(route('super-admin.hr-admins.index'))->assertOk();
        $this->get(route('super-admin.hr-admins.create'))->assertOk();
        $this->get(route('super-admin.settings.index'))->assertOk();
        $this->get(route('super-admin.audit-logs.index'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | T183: HR Admin Access & Boundaries Tests
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_access_hr_modules(): void
    {
        $this->actingAs($this->hrAdmin);

        $this->get(route('hr-admin.dashboard'))->assertOk();
        $this->get(route('hr-admin.employees.index'))->assertOk();
        $this->get(route('hr-admin.shifts.index'))->assertOk();
        $this->get(route('hr-admin.ip-allowlists.index'))->assertOk();
        $this->get(route('hr-admin.holidays.index'))->assertOk();
        $this->get(route('hr-admin.attendance.index'))->assertOk();
        $this->get(route('hr-admin.leaves.index'))->assertOk();
        $this->get(route('hr-admin.documents.index'))->assertOk();
        $this->get(route('hr-admin.payroll.index'))->assertOk();
        $this->get(route('hr-admin.reports.attendance'))->assertOk();
    }

    public function test_hr_admin_is_forbidden_from_super_admin_routes(): void
    {
        $this->actingAs($this->hrAdmin);

        $this->get(route('super-admin.dashboard'))->assertForbidden();
        $this->get(route('super-admin.hr-admins.index'))->assertForbidden();
        $this->get(route('super-admin.settings.index'))->assertForbidden();
        $this->get(route('super-admin.audit-logs.index'))->assertForbidden();
    }

    public function test_hr_admin_cannot_approve_or_finalize_payroll(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 50000.00,
            'daily_salary' => 1666.67,
            'salary_divisor' => 30,
            'total_days_in_month' => 30,
            'total_earnings' => 50000.00,
            'total_deductions' => 0.00,
            'net_salary' => 50000.00,
            'status' => PayrollStatus::REVIEWED,
            'payment_status' => PaymentStatus::PENDING,
            'generated_by' => $this->hrAdmin->id,
        ]);

        // HR Admin cannot approve
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.approve', $payroll->id))
            ->assertForbidden();

        // HR Admin cannot finalize
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.finalize', $payroll->id))
            ->assertForbidden();

        // Super Admin CAN approve
        $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.approve', $payroll->id))
            ->assertRedirect();
        $this->assertEquals(PayrollStatus::APPROVED, $payroll->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | T183: Employee Access & Boundaries Tests
    |--------------------------------------------------------------------------
    */
    public function test_employee_can_access_employee_portal(): void
    {
        $this->actingAs($this->employeeUser);

        $this->get(route('employee.dashboard'))->assertOk();
        $this->get(route('employee.profile'))->assertOk();
        $this->get(route('employee.holidays.index'))->assertOk();
        $this->get(route('employee.attendance.history'))->assertOk();
        $this->get(route('employee.leaves.index'))->assertOk();
        $this->get(route('employee.leaves.create'))->assertOk();
        $this->get(route('employee.payslips.index'))->assertOk();
    }

    public function test_employee_is_forbidden_from_hr_admin_and_super_admin_routes(): void
    {
        $this->actingAs($this->employeeUser);

        // HR Admin areas
        $this->get(route('hr-admin.dashboard'))->assertForbidden();
        $this->get(route('hr-admin.employees.index'))->assertForbidden();
        $this->get(route('hr-admin.payroll.index'))->assertForbidden();
        $this->get(route('hr-admin.leaves.index'))->assertForbidden();

        // Super Admin areas
        $this->get(route('super-admin.dashboard'))->assertForbidden();
        $this->get(route('super-admin.hr-admins.index'))->assertForbidden();
        $this->get(route('super-admin.settings.index'))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | T183: Unauthenticated Guest Redirection Tests
    |--------------------------------------------------------------------------
    */
    public function test_guest_is_redirected_to_login_on_protected_routes(): void
    {
        $this->get(route('super-admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('hr-admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('employee.dashboard'))->assertRedirect(route('login'));
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }
}
