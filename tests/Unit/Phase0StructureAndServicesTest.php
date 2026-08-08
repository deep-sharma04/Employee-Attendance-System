<?php

namespace Tests\Unit;

use App\Enums\AttendanceAction;
use App\Enums\AttendanceStatus;
use App\Enums\DocumentStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveTypeSlug;
use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Services\Attendance\AttendanceClassificationService;
use App\Services\Attendance\IpValidationService;
use App\Services\Leave\LeaveWorkingDayService;
use App\Services\Payroll\HolidayBridgingService;
use App\Services\Payroll\SalaryCalculationService;
use App\Services\Settings\SettingsService;
use Tests\TestCase;

class Phase0StructureAndServicesTest extends TestCase
{
    public function test_all_core_enums_exist_and_have_valid_values(): void
    {
        $this->assertEquals('super_admin', UserRole::SUPER_ADMIN->value);
        $this->assertEquals('hr_admin', UserRole::HR_ADMIN->value);
        $this->assertEquals('employee', UserRole::EMPLOYEE->value);

        $this->assertEquals('active', EmployeeStatus::ACTIVE->value);
        $this->assertEquals('present', AttendanceStatus::PRESENT->value);
        $this->assertEquals('late', AttendanceStatus::LATE->value);
        $this->assertEquals('half_day', AttendanceStatus::HALF_DAY->value);
        $this->assertEquals('absent', AttendanceStatus::ABSENT->value);

        $this->assertEquals('punch_in', AttendanceAction::PUNCH_IN->value);
        $this->assertEquals('punch_out', AttendanceAction::PUNCH_OUT->value);

        $this->assertEquals('casual', LeaveTypeSlug::CASUAL->value);
        $this->assertEquals('medical', LeaveTypeSlug::MEDICAL->value);

        $this->assertEquals('pending', LeaveStatus::PENDING->value);
        $this->assertEquals('verified', DocumentStatus::VERIFIED->value);
        $this->assertEquals('finalized', PayrollStatus::FINALIZED->value);
        $this->assertEquals('cleared', PaymentStatus::CLEARED->value);
    }

    public function test_salary_calculation_service_daily_rate_and_lop_math(): void
    {
        $service = new SalaryCalculationService();

        // 30,000 monthly / 30 = 1,000 daily
        $daily = $service->calculateDailySalary(30000, 30);
        $this->assertEquals(1000.0, $daily);

        // 2 LOP days = 2,000 deduction
        $lopDeduction = $service->calculateLopDeduction($daily, 2);
        $this->assertEquals(2000.0, $lopDeduction);

        // Net salary = 30,000 - 2,000 = 28,000
        $net = $service->calculateNetSalary(30000, $lopDeduction, 0);
        $this->assertEquals(28000.0, $net);
    }

    public function test_attendance_classification_service_rules(): void
    {
        $service = new AttendanceClassificationService();

        // Punch at 09:10 for a 09:00 shift (within 15 min grace period) -> Present
        $status = $service->classifyPunchIn('2026-08-08 09:10:00', '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::PRESENT, $status);

        // Punch at 09:25 for a 09:00 shift (beyond 15 mins, within 60 mins) -> Late
        $statusLate = $service->classifyPunchIn('2026-08-08 09:25:00', '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::LATE, $statusLate);

        // Punch at 10:15 for a 09:00 shift (beyond 60 mins) -> Half Day
        $statusHalf = $service->classifyPunchIn('2026-08-08 10:15:00', '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::HALF_DAY, $statusHalf);
    }

    public function test_late_and_half_day_conversions_to_absent(): void
    {
        $service = new AttendanceClassificationService();

        // 3 Late = 1 Absent, 2 Half Day = 1 Absent -> Total 2 Absent Days
        $converted = $service->calculateConvertedAbsences(3, 2);
        $this->assertEquals(1, $converted['late_to_absent_days']);
        $this->assertEquals(1, $converted['half_day_to_absent_days']);
        $this->assertEquals(2, $converted['total_converted_absent_days']);

        // 7 Late = 2 Absent (1 leftover), 5 Half Days = 2 Absent (1 leftover)
        $convertedComplex = $service->calculateConvertedAbsences(7, 5);
        $this->assertEquals(2, $convertedComplex['late_to_absent_days']);
        $this->assertEquals(1, $convertedComplex['remaining_late_count']);
        $this->assertEquals(2, $convertedComplex['half_day_to_absent_days']);
        $this->assertEquals(1, $convertedComplex['remaining_half_days']);
        $this->assertEquals(4, $convertedComplex['total_converted_absent_days']);
    }

    public function test_holiday_bridging_sandwich_rule_detection(): void
    {
        $service = new HolidayBridgingService();

        // Absent before and absent after -> Bridged (LOP)
        $isBridged = $service->isHolidayBridged('2026-08-15', 'absent', 'absent', false, false);
        $this->assertTrue($isBridged);

        // Approved leave before -> Not bridged
        $notBridgedLeaveBefore = $service->isHolidayBridged('2026-08-15', 'leave', 'absent', true, false);
        $this->assertFalse($notBridgedLeaveBefore);

        // Present before -> Not bridged
        $notBridgedPresent = $service->isHolidayBridged('2026-08-15', 'present', 'absent', false, false);
        $this->assertFalse($notBridgedPresent);
    }

    public function test_leave_working_day_service_excludes_sundays(): void
    {
        $service = new LeaveWorkingDayService();

        // 2026-08-07 (Friday) to 2026-08-10 (Monday): Friday + Saturday + Monday = 3 working days (Sunday excluded)
        $workingDays = $service->calculateWorkingDays('2026-08-07', '2026-08-10');
        $this->assertEquals(3.0, $workingDays);
    }

    public function test_ip_validation_service_accepts_localhost_in_local_env(): void
    {
        $service = new IpValidationService();
        $this->assertTrue($service->isIpAllowed('127.0.0.1'));
        $this->assertTrue($service->isIpAllowed('::1'));
    }

    public function test_settings_service_returns_expected_defaults(): void
    {
        $service = new SettingsService();
        $defaults = $service->defaults();

        $this->assertEquals(30, $defaults['salary_divisor']);
        $this->assertEquals(15, $defaults['late_grace_period_minutes']);
        $this->assertEquals(60, $defaults['half_day_threshold_minutes']);
        $this->assertEquals(3, $defaults['late_to_absent_ratio']);
        $this->assertEquals(2, $defaults['half_day_to_absent_ratio']);
    }
}
