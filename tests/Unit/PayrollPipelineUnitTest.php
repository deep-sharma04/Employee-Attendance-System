<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendanceAggregationService;
use App\Services\Attendance\AttendanceClassificationService;
use App\Services\Payroll\HolidayBridgingService;
use App\Services\Payroll\PayrollGenerationService;
use App\Services\Payroll\SalaryCalculationService;
use App\Services\Audit\AuditLoggerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPipelineUnitTest extends TestCase
{
    use RefreshDatabase;

    protected SalaryCalculationService $salaryCalculationService;
    protected HolidayBridgingService $holidayBridgingService;
    protected PayrollGenerationService $payrollGenerationService;
    protected Employee $employee;
    protected User $user;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salaryCalculationService = new SalaryCalculationService();
        $this->holidayBridgingService = new HolidayBridgingService();

        $classificationService = new AttendanceClassificationService();
        $aggregationService = new AttendanceAggregationService($classificationService);
        $auditLogger = app(AuditLoggerService::class);

        $this->payrollGenerationService = new PayrollGenerationService(
            $aggregationService,
            $this->holidayBridgingService,
            $this->salaryCalculationService,
            $auditLogger
        );

        $this->user = User::factory()->create();
        $this->shift = Shift::create([
            'name' => 'Standard Day Shift',
            'code' => 'STD01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'shift_id' => $this->shift->id,
            'monthly_salary' => 60000.00,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T181: Salary Calculation Formulas
    |--------------------------------------------------------------------------
    */
    public function test_salary_calculation_formulas_and_edge_cases(): void
    {
        // 1. Daily salary with standard 30 divisor
        $this->assertEquals(2000.00, $this->salaryCalculationService->calculateDailySalary(60000.00, 30));

        // 2. Custom 26 working days divisor
        $this->assertEquals(2307.69, $this->salaryCalculationService->calculateDailySalary(60000.00, 26));

        // 3. LOP deduction calculation with integer and fractional days
        $daily = 2000.00;
        $this->assertEquals(0.00, $this->salaryCalculationService->calculateLopDeduction($daily, 0.0));
        $this->assertEquals(2000.00, $this->salaryCalculationService->calculateLopDeduction($daily, 1.0));
        $this->assertEquals(3000.00, $this->salaryCalculationService->calculateLopDeduction($daily, 1.5));
        $this->assertEquals(5000.00, $this->salaryCalculationService->calculateLopDeduction($daily, 2.5));

        // 4. Net salary with deductions and floor at 0.00
        $this->assertEquals(57000.00, $this->salaryCalculationService->calculateNetSalary(60000.00, 3000.00, 0.00));
        $this->assertEquals(56800.00, $this->salaryCalculationService->calculateNetSalary(60000.00, 3000.00, 200.00));
        $this->assertEquals(0.00, $this->salaryCalculationService->calculateNetSalary(60000.00, 70000.00, 0.00));
    }

    /*
    |--------------------------------------------------------------------------
    | T181: Holiday Bridging / Sandwich Rule Unit Logic
    |--------------------------------------------------------------------------
    */
    public function test_sandwich_rule_triggers_only_when_both_adjacent_days_are_unapproved_absences(): void
    {
        // Both days absent -> Bridged
        $this->assertTrue($this->holidayBridgingService->isHolidayBridged('2026-08-15', 'absent', 'absent', false, false));

        // Friday present, Monday absent -> NOT bridged
        $this->assertFalse($this->holidayBridgingService->isHolidayBridged('2026-08-15', 'present', 'absent', false, false));

        // Friday absent, Monday present -> NOT bridged
        $this->assertFalse($this->holidayBridgingService->isHolidayBridged('2026-08-15', 'absent', 'present', false, false));

        // Friday approved leave, Monday absent -> NOT bridged
        $this->assertFalse($this->holidayBridgingService->isHolidayBridged('2026-08-15', 'absent', 'absent', true, false));

        // Friday absent, Monday approved leave -> NOT bridged
        $this->assertFalse($this->holidayBridgingService->isHolidayBridged('2026-08-15', 'absent', 'absent', false, true));
    }

    /*
    |--------------------------------------------------------------------------
    | T181: Full End-to-End Payroll Generation Pipeline
    |--------------------------------------------------------------------------
    */
    public function test_full_payroll_pipeline_generates_accurate_earnings_and_lop_deductions(): void
    {
        // Declare a holiday on 2026-08-15 (Saturday)
        Holiday::create([
            'name' => 'Independence Day',
            'holiday_date' => '2026-08-15',
            'is_recurring_yearly' => true,
        ]);

        // Mark all working days in August 2026 as PRESENT except Aug 14 and Aug 17
        for ($day = 1; $day <= 31; $day++) {
            $d = Carbon::create(2026, 8, $day);
            $dateStr = $d->toDateString();

            if ($dateStr === '2026-08-14' || $dateStr === '2026-08-17') {
                AttendanceRecord::create([
                    'employee_id' => $this->employee->id,
                    'attendance_date' => $dateStr,
                    'status' => AttendanceStatus::ABSENT,
                ]);
            } elseif ($dateStr !== '2026-08-15' && $d->dayOfWeek !== Carbon::SUNDAY) {
                AttendanceRecord::create([
                    'employee_id' => $this->employee->id,
                    'attendance_date' => $dateStr,
                    'status' => AttendanceStatus::PRESENT,
                    'punch_in_at' => $dateStr . ' 09:00:00',
                    'punch_out_at' => $dateStr . ' 18:00:00',
                    'total_hours' => 9.0,
                ]);
            }
        }

        // Generate Payroll for August 2026
        $payroll = $this->payrollGenerationService->generateForEmployee(
            $this->employee->id,
            2026,
            8,
            $this->user->id
        );

        $this->assertInstanceOf(\App\Models\Payroll::class, $payroll);
        $this->assertEquals(PayrollStatus::DRAFT, $payroll->status);
        $this->assertEquals(60000.00, (float) $payroll->monthly_salary);
        $this->assertEquals(2000.00, (float) $payroll->daily_salary);

        // LOP Days: 2 Direct Absents (Aug 14, 17) + 1 Bridged Holiday (Aug 15) = 3 LOP days
        $this->assertEquals(3.0, (float) $payroll->total_lop_days);
        $this->assertEquals(6000.00, (float) $payroll->lop_deduction_amount);

        // Net salary: 60,000 - 6,000 = 54,000
        $this->assertEquals(54000.00, (float) $payroll->net_salary);
    }
}
