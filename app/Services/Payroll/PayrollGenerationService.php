<?php

namespace App\Services\Payroll;

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\Attendance\AttendanceAggregationService;
use App\Services\Audit\AuditLoggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollGenerationService
{
    public function __construct(
        protected AttendanceAggregationService $attendanceAggregationService,
        protected HolidayBridgingService $holidayBridgingService,
        protected SalaryCalculationService $salaryCalculationService,
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Generate payroll for a single employee for a specific month and year.
     */
    public function generateForEmployee(int $employeeId, int $year, int $month, ?int $generatedById = null): Payroll
    {
        $employee = Employee::with('shift')->findOrFail($employeeId);
        $generatedById = $generatedById ?? Auth::id() ?? 1;

        // Check if there is an existing finalized payroll (Duplicate guard T119)
        $existingPayroll = Payroll::where('employee_id', $employeeId)
            ->where('payroll_year', $year)
            ->where('payroll_month', $month)
            ->latest('revision_number')
            ->first();

        if ($existingPayroll && $existingPayroll->status === PayrollStatus::FINALIZED) {
            throw new \InvalidArgumentException("Payroll for {$employee->first_name} {$employee->last_name} ({$year}-{$month}) has already been finalized and locked. Please use the revision workflow to apply adjustments.");
        }

        // 1. Fetch attendance metrics
        $attendance = $this->attendanceAggregationService->aggregateMonthlyAttendance($employeeId, $year, $month);

        // 2. Fetch bridged holidays (Sandwich rule T100-T102, T115)
        $bridgedResult = $this->holidayBridgingService->detectBridgedHolidaysForEmployee($employeeId, $year, $month);
        $bridgedCount = (int) ($bridgedResult['bridged_count'] ?? 0);

        // 3. Aggregate LOP Days (T115)
        // Direct absent days + Converted late absent days + Converted half-day absent days + remaining half-days (0.5) + bridged holidays
        $conversions = $attendance['conversions'] ?? ['late_to_absent_days' => 0, 'half_day_to_absent_days' => 0, 'remaining_half_days' => 0];
        $convertedLateAbsent = (int) ($conversions['late_to_absent_days'] ?? 0);
        $convertedHalfDayAbsent = (int) ($conversions['half_day_to_absent_days'] ?? 0);
        $remainingHalfDays = (int) ($conversions['remaining_half_days'] ?? 0);
        $directAbsentDays = (float) ($attendance['direct_absent_days'] ?? 0.0);

        $totalLopDays = round($directAbsentDays + $convertedLateAbsent + $convertedHalfDayAbsent + ($remainingHalfDays * 0.5) + $bridgedCount, 1);

        // 4. Daily Salary Calculation (T114)
        $salaryDivisorSetting = CompanySetting::where('key', 'salary_divisor')->value('value');
        $salaryDivisor = ($salaryDivisorSetting && is_numeric($salaryDivisorSetting)) ? (int) $salaryDivisorSetting : 30;

        $monthlySalary = (float) $employee->monthly_salary;
        $dailySalary = $this->salaryCalculationService->calculateDailySalary($monthlySalary, $salaryDivisor);

        // 5. LOP Deduction Calculation (T116)
        $lopDeduction = $this->salaryCalculationService->calculateLopDeduction($dailySalary, $totalLopDays);

        // 6. Professional Tax Provision (T126)
        $profTaxSetting = CompanySetting::where('key', 'professional_tax_default')->value('value');
        $profTaxAmount = ($profTaxSetting && is_numeric($profTaxSetting)) ? (float) $profTaxSetting : 0.00;

        // 7. Net Salary Calculation (T117)
        $totalEarnings = $monthlySalary;
        $totalDeductions = round($lopDeduction + $profTaxAmount, 2);
        $netSalary = $this->salaryCalculationService->calculateNetSalary($monthlySalary, $lopDeduction, $profTaxAmount);

        return DB::transaction(function () use (
            $employee, $year, $month, $monthlySalary, $dailySalary, $salaryDivisor,
            $attendance, $bridgedCount, $convertedLateAbsent, $convertedHalfDayAbsent,
            $totalLopDays, $lopDeduction, $profTaxAmount, $totalEarnings, $totalDeductions,
            $netSalary, $generatedById, $existingPayroll
        ) {
            $payrollData = [
                'employee_id' => $employee->id,
                'payroll_month' => $month,
                'payroll_year' => $year,
                'monthly_salary' => $monthlySalary,
                'daily_salary' => $dailySalary,
                'salary_divisor' => $salaryDivisor,
                'total_days_in_month' => $attendance['total_days_in_month'] ?? 30,
                'present_days' => $attendance['present_days'] ?? 0,
                'late_days' => $attendance['late_days'] ?? 0,
                'half_days' => $attendance['half_days'] ?? 0,
                'absent_days' => $attendance['direct_absent_days'] ?? 0,
                'leave_days' => $attendance['leave_days'] ?? 0,
                'holiday_days' => $attendance['holiday_days'] ?? 0,
                'weekend_days' => $attendance['week_off_days'] ?? 0,
                'bridged_holiday_days' => $bridgedCount,
                'converted_late_absent_days' => $convertedLateAbsent,
                'converted_half_day_absent_days' => $convertedHalfDayAbsent,
                'total_lop_days' => $totalLopDays,
                'lop_deduction_amount' => $lopDeduction,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'status' => PayrollStatus::DRAFT,
                'payment_status' => PaymentStatus::PENDING,
                'revision_number' => $existingPayroll ? $existingPayroll->revision_number : 1,
                'generated_by' => $generatedById,
            ];

            if ($existingPayroll && $existingPayroll->status !== PayrollStatus::FINALIZED) {
                $existingPayroll->update($payrollData);
                $payroll = $existingPayroll;
            } else {
                $payroll = Payroll::create($payrollData);
            }

            // Sync itemized line items
            $payroll->items()->delete();

            // Earnings items
            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'type' => 'earning',
                'category' => 'basic',
                'label' => 'Basic / Monthly Gross Salary',
                'amount' => $monthlySalary,
            ]);

            // Deductions items
            if ($lopDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => 'deduction',
                    'category' => 'lop_deduction',
                    'label' => "Loss of Pay ({$totalLopDays} days @ ₹{$dailySalary}/day)",
                    'amount' => $lopDeduction,
                ]);
            }

            if ($profTaxAmount > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type' => 'deduction',
                    'category' => 'professional_tax',
                    'label' => 'Professional Tax Provision',
                    'amount' => $profTaxAmount,
                ]);
            }

            $this->auditLogger->log(
                action: 'payroll.generated',
                targetType: 'Payroll',
                targetId: $payroll->id,
                beforeValues: null,
                afterValues: [
                    'employee_id' => $payroll->employee_id,
                    'year' => $year,
                    'month' => $month,
                    'monthly_salary' => $monthlySalary,
                    'lop_days' => $totalLopDays,
                    'lop_deduction' => $lopDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'draft',
                ],
                description: "Generated draft payroll for {$employee->first_name} {$employee->last_name} ({$year}-{$month})"
            );

            return $payroll;
        });
    }

    /**
     * Batch generate payroll for all active employees for a given month and year.
     *
     * @return array{total_generated: int, skipped: int, errors: array<string>}
     */
    public function generateBatch(int $year, int $month, ?int $generatedById = null): array
    {
        $activeEmployees = Employee::where('status', 'active')->get();
        $generatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($activeEmployees as $emp) {
            try {
                $this->generateForEmployee($emp->id, $year, $month, $generatedById);
                $generatedCount++;
            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = "Employee {$emp->employee_code}: " . $e->getMessage();
            }
        }

        return [
            'total_generated' => $generatedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Create an authorized tracked revision for a finalized payroll.
     */
    public function createRevision(int $payrollId, string $reason, ?int $revisedById = null): Payroll
    {
        $original = Payroll::with('items')->findOrFail($payrollId);
        $revisedById = $revisedById ?? Auth::id() ?? 1;

        $newRevisionNumber = $original->revision_number + 1;

        return DB::transaction(function () use ($original, $newRevisionNumber, $reason, $revisedById) {
            $newPayroll = $original->replicate([
                'approved_by', 'approved_at', 'finalized_by', 'finalized_at'
            ]);

            $newPayroll->revision_number = $newRevisionNumber;
            $newPayroll->revision_reason = $reason;
            $newPayroll->status = PayrollStatus::DRAFT;
            $newPayroll->payment_status = PaymentStatus::PENDING;
            $newPayroll->generated_by = $revisedById;
            $newPayroll->approved_by = null;
            $newPayroll->approved_at = null;
            $newPayroll->finalized_by = null;
            $newPayroll->finalized_at = null;
            $newPayroll->save();

            foreach ($original->items as $item) {
                PayrollItem::create([
                    'payroll_id' => $newPayroll->id,
                    'type' => $item->type,
                    'category' => $item->category,
                    'label' => $item->label,
                    'amount' => $item->amount,
                ]);
            }

            $this->auditLogger->log(
                action: 'payroll.revised',
                targetType: 'Payroll',
                targetId: $newPayroll->id,
                beforeValues: ['id' => $original->id, 'revision_number' => $original->revision_number, 'status' => $original->status->value],
                afterValues: ['id' => $newPayroll->id, 'revision_number' => $newRevisionNumber, 'reason' => $reason],
                description: "Created Revision #{$newRevisionNumber} for Payroll ID {$original->id}: {$reason}"
            );

            return $newPayroll;
        });
    }
}
