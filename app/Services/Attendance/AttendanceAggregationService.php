<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;

class AttendanceAggregationService
{
    public function __construct(
        protected AttendanceClassificationService $classificationService
    ) {}

    /**
     * Aggregate monthly attendance metrics for an employee.
     */
    public function aggregateMonthlyAttendance(int $employeeId, int $year, int $month): array
    {
        $employee = Employee::with('shift')->findOrFail($employeeId);
        $shift = $employee->shift;
        $workingDaysList = is_array($shift?->working_days) ? $shift->working_days : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();
        $totalDaysInMonth = $startDate->daysInMonth;

        // Fetch declared holidays in this month
        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Fetch attendance records for this employee in this month
        $records = AttendanceRecord::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->attendance_date)->toDateString());

        $presentDays = 0;
        $lateDays = 0;
        $halfDays = 0;
        $directAbsentDays = 0;
        $leaveDays = 0;
        $holidayDays = 0;
        $weekOffDays = 0;
        $totalHoursWorked = 0.0;
        $missingPunchOutCount = 0;

        for ($day = 1; $day <= $totalDaysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day);
            $dateStr = $currentDate->toDateString();
            $dayOfWeek = strtolower($currentDate->format('l'));

            $isHoliday = in_array($dateStr, $holidays);
            $isWorkingDay = in_array($dayOfWeek, $workingDaysList);

            if ($records->has($dateStr)) {
                $rec = $records->get($dateStr);
                $status = $rec->status instanceof AttendanceStatus ? $rec->status->value : (string) $rec->status;
                $totalHoursWorked += (float) ($rec->total_hours ?? 0);

                if ($rec->punch_in_at && !$rec->punch_out_at && $currentDate->isPast() && !$currentDate->isToday()) {
                    $missingPunchOutCount++;
                }

                switch ($status) {
                    case 'present':
                        $presentDays++;
                        break;
                    case 'late':
                        $lateDays++;
                        break;
                    case 'half_day':
                        $halfDays++;
                        break;
                    case 'leave':
                        $leaveDays++;
                        break;
                    case 'holiday':
                        $holidayDays++;
                        break;
                    case 'week_off':
                        $weekOffDays++;
                        break;
                    case 'absent':
                    default:
                        $directAbsentDays++;
                        break;
                }
            } else {
                if ($isHoliday) {
                    $holidayDays++;
                } elseif (!$isWorkingDay) {
                    $weekOffDays++;
                } elseif ($currentDate->isPast() && !$currentDate->isToday()) {
                    $directAbsentDays++;
                }
            }
        }

        // Apply conversion rules: 3 Late = 1 Absent, 2 Half Days = 1 Absent
        $conversions = $this->classificationService->calculateConvertedAbsences($lateDays, $halfDays);

        $totalPayableDays = max(0.0, ($presentDays + $lateDays + ($halfDays * 0.5) + $leaveDays + $holidayDays + $weekOffDays) - $conversions['total_converted_absent_days']);

        return [
            'year' => $year,
            'month' => $month,
            'total_days_in_month' => $totalDaysInMonth,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'half_days' => $halfDays,
            'direct_absent_days' => $directAbsentDays,
            'leave_days' => $leaveDays,
            'holiday_days' => $holidayDays,
            'week_off_days' => $weekOffDays,
            'total_hours_worked' => round($totalHoursWorked, 2),
            'missing_punch_out_count' => $missingPunchOutCount,
            'conversions' => $conversions,
            'total_payable_days' => round($totalPayableDays, 1),
            'records' => $records,
        ];
    }
}
