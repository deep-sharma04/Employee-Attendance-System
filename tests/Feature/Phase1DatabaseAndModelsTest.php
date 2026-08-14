<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DocumentStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\CompanySetting;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OfficeIpAllowlist;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1DatabaseAndModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeders_populate_default_roles_and_super_admin(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => 'super_admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'hr_admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'employee']);

        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'hradmin',
            'role' => UserRole::HR_ADMIN->value,
            'is_active' => true,
        ]);
    }

    public function test_seeders_populate_default_leave_and_document_types(): void
    {
        $this->assertDatabaseHas('leave_types', ['slug' => 'casual']);
        $this->assertDatabaseHas('leave_types', ['slug' => 'medical']);

        $this->assertDatabaseHas('document_types', ['slug' => 'identity_proof']);
        $this->assertDatabaseHas('document_types', ['slug' => 'offer_letter']);
        $this->assertDatabaseHas('document_types', ['slug' => 'bank_passbook']);
    }

    public function test_seeders_populate_company_settings_and_default_shift(): void
    {
        $this->assertDatabaseHas('company_settings', ['key' => 'salary_divisor', 'value' => '30']);
        $this->assertDatabaseHas('company_settings', ['key' => 'late_grace_period_minutes', 'value' => '15']);
        $this->assertDatabaseHas('company_settings', ['key' => 'half_day_threshold_minutes', 'value' => '60']);

        $this->assertDatabaseHas('shifts', ['code' => 'GEN_DAY', 'is_active' => true]);
        $this->assertDatabaseHas('office_ip_allowlists', ['ip_address' => '127.0.0.1', 'is_active' => true]);
    }

    public function test_employee_and_user_eloquent_relationship(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'username' => 'test.employee',
        ]);

        $shift = Shift::first();

        $employee = Employee::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'employee_code' => 'EMP9999',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@hrm.local',
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'joining_date' => '2026-01-15',
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 75000.00,
        ]);

        $this->assertEquals('Jane Doe', $employee->full_name);
        $this->assertEquals($user->id, $employee->user->id);
        $this->assertEquals($employee->id, $user->employee->id);
        $this->assertEquals('GEN_DAY', $employee->shift->code);
    }

    public function test_attendance_record_and_event_relationships(): void
    {
        $employee = Employee::factory()->create();

        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'shift_id' => $employee->shift_id,
            'attendance_date' => '2026-08-08',
            'punch_in' => '09:05:00',
            'punch_out' => '18:00:00',
            'punch_in_ip' => '127.0.0.1',
            'punch_out_ip' => '127.0.0.1',
            'total_working_hours' => 8.92,
            'status' => AttendanceStatus::PRESENT,
            'late_minutes' => 5,
        ]);

        $this->assertEquals($employee->id, $record->employee->id);
        $this->assertCount(1, $employee->attendanceRecords);
    }

    public function test_leave_balances_and_requests_relationships(): void
    {
        $employee = Employee::factory()->create();
        $casualType = LeaveType::where('slug', 'casual')->first();

        $balance = EmployeeLeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $casualType->id,
            'year' => 2026,
            'allocated_days' => 12.0,
            'used_days' => 2.0,
            'remaining_days' => 10.0,
        ]);

        $request = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $casualType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'is_half_day' => false,
            'total_days' => 2.0,
            'reason' => 'Family vacation',
            'status' => LeaveStatus::PENDING,
        ]);

        $this->assertEquals($casualType->id, $request->leaveType->id);
        $this->assertEquals(10.0, $balance->remaining_days);
        $this->assertCount(1, $employee->leaveRequests);
    }

    public function test_payroll_and_payslip_complete_lifecycle_relationships(): void
    {
        $employee = Employee::factory()->create(['monthly_salary' => 60000.00]);
        $superAdmin = User::where('role', UserRole::SUPER_ADMIN->value)->first();

        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'payroll_month' => 8,
            'payroll_year' => 2026,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'present_days' => 25.0,
            'absent_days' => 1.0,
            'total_lop_days' => 1.0,
            'lop_deduction_amount' => 2000.00,
            'total_earnings' => 60000.00,
            'total_deductions' => 2000.00,
            'net_salary' => 58000.00,
            'status' => PayrollStatus::FINALIZED,
            'payment_status' => PaymentStatus::PENDING,
            'generated_by' => $superAdmin->id,
            'approved_by' => $superAdmin->id,
            'finalized_by' => $superAdmin->id,
            'approved_at' => now(),
            'finalized_at' => now(),
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => 'earning',
            'category' => 'basic',
            'label' => 'Basic Salary',
            'amount' => 30000.00,
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => 'deduction',
            'category' => 'lop_deduction',
            'label' => 'Loss of Pay (1 Day)',
            'amount' => 2000.00,
        ]);

        $payslip = Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'payslip_number' => 'PAY-202608-EMP999',
            'month' => 8,
            'year' => 2026,
            'net_pay' => 58000.00,
            'generated_at' => now(),
        ]);

        $this->assertCount(2, $payroll->items);
        $this->assertEquals($payslip->id, $payroll->payslip->id);
        $this->assertEquals(58000.00, $payroll->net_salary);
    }
}
