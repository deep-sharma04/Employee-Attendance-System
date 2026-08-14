<?php

namespace Tests\Feature;

use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Mail\ForgotPasswordMail;
use App\Mail\LeaveStatusMail;
use App\Mail\PayslipFinalizedMail;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected Shift $shift;
    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->superAdmin = User::where('role', UserRole::SUPER_ADMIN)->first() ?? User::factory()->create([
            'username' => 'superadmin',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::where('role', UserRole::HR_ADMIN)->first() ?? User::factory()->create([
            'username' => 'hradmin',
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->shift = Shift::first() ?? Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN_DAY',
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        $this->leaveType = LeaveType::firstOrCreate(
            ['slug' => 'casual_leave'],
            ['name' => 'Casual Leave', 'annual_quota' => 12, 'is_active' => true]
        );

        $this->employeeUser = User::create([
            'username' => 'testemployee',
            'name' => 'Alex Morgan',
            'email' => 'alex.morgan@hrm.local',
            'password' => Hash::make('Employee@12345'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $this->shift->id,
            'employee_code' => 'EMP9999',
            'first_name' => 'Alex',
            'last_name' => 'Morgan',
            'email' => 'alex.morgan@hrm.local',
            'phone' => '9123456780',
            'gender' => 'female',
            'date_of_birth' => '1995-05-15',
            'joining_date' => '2026-01-01',
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'status' => 'active',
            'monthly_salary' => 60000.00,
            'bank_name' => 'HDFC Bank',
            'account_number' => '987654321098',
            'ifsc_code' => 'HDFC0001',
            'pan_number' => 'ABCDE1234F',
        ]);

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
            'allocated_days' => 12,
            'used_days' => 0,
            'remaining_days' => 12,
        ]);
    }

    public function test_forgot_password_sends_email_with_reset_link(): void
    {
        Mail::fake();

        $response = $this->post(route('password.forgot.post'), [
            'username' => 'alex.morgan@hrm.local',
        ]);

        $response->assertSessionHas('status');

        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) {
            $this->assertEquals($this->employeeUser->email, $mail->user->email);
            $this->assertStringContainsString('password/reset', $mail->resetUrl);
            $this->assertEquals(60, $mail->expireMinutes);
            return true;
        });
    }

    public function test_leave_approval_sends_email_to_employee(): void
    {
        Mail::fake();

        $startDate = Carbon::parse('2026-08-17'); // Monday
        $endDate = Carbon::parse('2026-08-18');   // Tuesday

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_half_day' => false,
            'total_days' => 2,
            'reason' => 'Family vacation',
            'status' => LeaveStatus::PENDING,
        ]);

        $response = $this->from(route('hr-admin.leaves.index'))
            ->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.approve', $leave->id));
        $response->assertRedirect(route('hr-admin.leaves.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(LeaveStatusMail::class, function (LeaveStatusMail $mail) use ($leave) {
            return $mail->leave->id === $leave->id
                && $mail->status === 'approved'
                && $mail->employee->id === $this->employee->id;
        });
    }

    public function test_leave_rejection_sends_email_to_employee_with_reason(): void
    {
        Mail::fake();

        $startDate = Carbon::parse('2026-08-24');
        $endDate = Carbon::parse('2026-08-24');

        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_half_day' => false,
            'total_days' => 1,
            'reason' => 'Personal work',
            'status' => LeaveStatus::PENDING,
        ]);

        $rejectionReason = 'Critical sprint delivery scheduled on this date.';

        $response = $this->from(route('hr-admin.leaves.index'))
            ->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.reject', $leave->id), [
                'rejection_reason' => $rejectionReason,
            ]);
        $response->assertRedirect(route('hr-admin.leaves.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(LeaveStatusMail::class, function (LeaveStatusMail $mail) use ($leave, $rejectionReason) {
            return $mail->leave->id === $leave->id
                && $mail->status === 'rejected'
                && $mail->reason === $rejectionReason
                && $mail->employee->id === $this->employee->id;
        });
    }

    public function test_payroll_finalization_sends_payslip_email_with_pdf_attachment(): void
    {
        Storage::fake('local');
        Mail::fake();

        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_month' => 8,
            'payroll_year' => 2026,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_lop_days' => 1,
            'lop_deduction_amount' => 2000.00,
            'total_earnings' => 60000.00,
            'total_deductions' => 2000.00,
            'net_salary' => 58000.00,
            'status' => PayrollStatus::APPROVED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('hr-admin.payroll.finalize', $payroll->id));
        $response->assertSessionHas('success');

        Mail::assertSent(PayslipFinalizedMail::class, function (PayslipFinalizedMail $mail) use ($payroll) {
            $this->assertEquals($this->employee->id, $mail->employee->id);
            $this->assertEquals('August 2026', $mail->monthName);
            $this->assertEquals(58000.00, $mail->payroll->net_salary);
            return true;
        });
    }

    public function test_mailable_views_render_without_errors(): void
    {
        // 1. ForgotPasswordMail view render
        $forgotMail = new ForgotPasswordMail($this->employeeUser, 'http://localhost:8000/password/reset/test-token', 60);
        $renderedForgot = $forgotMail->render();
        $this->assertStringContainsString('Password Reset Request', $renderedForgot);
        $this->assertStringContainsString('Alex Morgan', $renderedForgot);
        $this->assertStringContainsString('http://localhost:8000/password/reset/test-token', $renderedForgot);

        // 2. LeaveStatusMail view render
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => Carbon::parse('2026-08-10'),
            'end_date' => Carbon::parse('2026-08-11'),
            'is_half_day' => false,
            'total_days' => 2,
            'reason' => 'Need rest',
            'status' => LeaveStatus::APPROVED,
        ]);
        $leaveMail = new LeaveStatusMail($leave, 'approved');
        $renderedLeave = $leaveMail->render();
        $this->assertStringContainsString('Leave Request Approved', $renderedLeave);
        $this->assertStringContainsString('Casual Leave', $renderedLeave);
        $this->assertStringContainsString('Alex Morgan', $renderedLeave);

        // 3. PayslipFinalizedMail view render
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_month' => 8,
            'payroll_year' => 2026,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_lop_days' => 0,
            'lop_deduction_amount' => 0.00,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.00,
            'net_salary' => 60000.00,
            'status' => PayrollStatus::FINALIZED,
            'generated_by' => $this->hrAdmin->id,
        ]);
        $payslipMail = new PayslipFinalizedMail($payroll);
        $renderedPayslip = $payslipMail->render();
        $this->assertStringContainsString('Your Payslip for August 2026 is Ready', $renderedPayslip);
        $this->assertStringContainsString('EMP9999', $renderedPayslip);
        $this->assertStringContainsString('60,000.00', $renderedPayslip);
    }
}
