<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveWorkingDayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRulesTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveBalanceService $balanceService;
    protected LeaveWorkingDayService $workingDayService;
    protected Employee $employee;
    protected LeaveType $casualLeave;
    protected LeaveType $sickLeave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->balanceService = new LeaveBalanceService();
        $this->workingDayService = new LeaveWorkingDayService();

        $user = User::factory()->create();
        $shift = Shift::create([
            'name' => 'General Shift',
            'code' => 'GEN',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
        ]);

        $this->casualLeave = LeaveType::create([
            'name' => 'Casual Leave',
            'slug' => 'casual-leave',
            'annual_quota' => 12.0,
            'is_active' => true,
        ]);

        $this->sickLeave = LeaveType::create([
            'name' => 'Sick Leave',
            'slug' => 'sick-leave',
            'annual_quota' => 8.0,
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T180: Leave Balance Validation & Calculations
    |--------------------------------------------------------------------------
    */
    public function test_leave_balance_lookup_and_sufficiency_check(): void
    {
        $currentYear = (int) date('Y');

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year' => $currentYear,
            'allocated_days' => 12.0,
            'used_days' => 2.0,
            'remaining_days' => 10.0,
        ]);

        // Balance lookup returns remaining days
        $balance = $this->balanceService->getBalance($this->employee->id, $this->casualLeave->id, $currentYear);
        $this->assertEquals(10.0, $balance);

        // Sufficiency check returns true when requested <= balance
        $this->assertTrue($this->balanceService->hasSufficientBalance($this->employee->id, $this->casualLeave->id, 5.0, $currentYear));
        $this->assertTrue($this->balanceService->hasSufficientBalance($this->employee->id, $this->casualLeave->id, 10.0, $currentYear));

        // Sufficiency check returns false when requested > balance
        $this->assertFalse($this->balanceService->hasSufficientBalance($this->employee->id, $this->casualLeave->id, 10.5, $currentYear));
    }

    public function test_leave_balance_deduction_and_restoration(): void
    {
        $currentYear = (int) date('Y');

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year' => $currentYear,
            'allocated_days' => 12.0,
            'used_days' => 0.0,
            'remaining_days' => 12.0,
        ]);

        // Deduct 3 days
        $this->balanceService->deductBalance($this->employee->id, $this->casualLeave->id, 3.0, $currentYear);
        $this->assertEquals(9.0, $this->balanceService->getBalance($this->employee->id, $this->casualLeave->id, $currentYear));

        // Restore 1 day
        $this->balanceService->restoreBalance($this->employee->id, $this->casualLeave->id, 1.0, $currentYear);
        $this->assertEquals(10.0, $this->balanceService->getBalance($this->employee->id, $this->casualLeave->id, $currentYear));
    }

    public function test_no_carry_forward_balance_expiration(): void
    {
        $pastYear = 2025;

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'year' => $pastYear,
            'allocated_days' => 12.0,
            'used_days' => 5.0,
            'remaining_days' => 7.0,
        ]);

        $affected = $this->balanceService->expireUnusedBalances($pastYear);
        $this->assertEquals(1, $affected);

        $this->assertEquals(0.0, $this->balanceService->getBalance($this->employee->id, $this->casualLeave->id, $pastYear));
    }

    /*
    |--------------------------------------------------------------------------
    | T180: Working Day Calculations (Excluding Sundays & Declared Holidays)
    |--------------------------------------------------------------------------
    */
    public function test_working_days_calculation_within_standard_workweek(): void
    {
        // Monday 2026-08-10 to Friday 2026-08-14 (5 weekdays)
        $days = $this->workingDayService->calculateWorkingDays('2026-08-10', '2026-08-14');
        $this->assertEquals(5.0, $days);

        // Single weekday (Tuesday) = 1 day
        $singleDay = $this->workingDayService->calculateWorkingDays('2026-08-11', '2026-08-11');
        $this->assertEquals(1.0, $singleDay);
    }

    public function test_working_days_calculation_excludes_sundays(): void
    {
        // Friday 2026-08-14 to Tuesday 2026-08-18 (5 calendar days: Fri, Sat, Sun, Mon, Tue)
        // Sunday 2026-08-16 is excluded, so working days = 4 (Fri, Sat, Mon, Tue)
        $days = $this->workingDayService->calculateWorkingDays('2026-08-14', '2026-08-18');
        $this->assertEquals(4.0, $days);

        // Entire weekend span: Saturday to Sunday -> Only Saturday is counted (1 day)
        $weekendSpan = $this->workingDayService->calculateWorkingDays('2026-08-15', '2026-08-16');
        $this->assertEquals(1.0, $weekendSpan);

        // Only Sunday -> 0 working days
        $sundayOnly = $this->workingDayService->calculateWorkingDays('2026-08-16', '2026-08-16');
        $this->assertEquals(0.0, $sundayOnly);
    }

    public function test_working_days_calculation_excludes_declared_holidays(): void
    {
        Holiday::create([
            'name' => 'Independence Day',
            'holiday_date' => '2026-08-15', // Saturday
            'is_recurring_yearly' => true,
        ]);

        Holiday::create([
            'name' => 'Company Day',
            'holiday_date' => '2026-08-17', // Monday
            'is_recurring_yearly' => false,
        ]);

        // Friday 2026-08-14 to Tuesday 2026-08-18 (Fri, Sat(Holiday), Sun(Weekend), Mon(Holiday), Tue)
        // Expected: Fri (1) + Tue (1) = 2.0 days
        $days = $this->workingDayService->calculateWorkingDays('2026-08-14', '2026-08-18');
        $this->assertEquals(2.0, $days);
    }

    public function test_half_day_leave_calculation(): void
    {
        // Half day on single date returns 0.5
        $halfDay = $this->workingDayService->calculateWorkingDays('2026-08-10', '2026-08-10', true);
        $this->assertEquals(0.5, $halfDay);
    }

    public function test_invalid_date_range_returns_zero(): void
    {
        // End date before start date
        $invalid = $this->workingDayService->calculateWorkingDays('2026-08-20', '2026-08-10');
        $this->assertEquals(0.0, $invalid);
    }
}
