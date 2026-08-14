<?php

namespace App\Services\Payroll;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class HolidayBridgingService
{
    /**
     * Determine if a holiday is sandwiched/bridged by unapproved absence on both preceding and following working days.
     *
     * Rule:
     * If the working day before a holiday is ABSENT (and NOT approved leave),
     * AND the working day after a holiday is ABSENT (and NOT approved leave),
     * the holiday itself is marked as salary-deductible LOP.
     */
    public function isHolidayBridged(
        string|Carbon $holidayDate,
        string $statusDayBefore,
        string $statusDayAfter,
        bool $isApprovedLeaveBefore = false,
        bool $isApprovedLeaveAfter = false
    ): bool {
        // If either day is approved leave, sandwich rule is strictly NOT triggered
        if ($isApprovedLeaveBefore || $isApprovedLeaveAfter) {
            return false;
        }

        // Check if both adjacent working days are unapproved absences
        $isAbsentBefore = in_array(strtolower($statusDayBefore), ['absent', 'unapproved_absence']);
        $isAbsentAfter = in_array(strtolower($statusDayAfter), ['absent', 'unapproved_absence']);

        return $isAbsentBefore && $isAbsentAfter;
    }

    /**
     * Detect all bridged holidays for an employee in a given month/period.
     *
     * @return array{bridged_holidays: array<string>, bridged_count: int}
     */
    public function detectBridgedHolidaysForEmployee(int $employeeId, int $year, int $month): array
    {
        $employee = Employee::with('shift')->findOrFail($employeeId);
        $shift = $employee->shift;
        $workingDaysList = is_array($shift?->working_days)
            ? $shift->working_days
            : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->orderBy('holiday_date')
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        if (empty($holidays)) {
            return [
                'bridged_holidays' => [],
                'bridged_count' => 0,
            ];
        }

        // All holidays in the system (for crossing month boundaries)
        $allHolidays = Holiday::pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // Fetch attendance records and approved leave requests
        $records = AttendanceRecord::where('employee_id', $employeeId)
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->attendance_date)->toDateString());

        $approvedLeaves = LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->get();

        $bridgedHolidays = [];

        foreach ($holidays as $holidayDateStr) {
            $holidayDate = Carbon::parse($holidayDateStr);

            // Find preceding working day (skipping other holidays and non-working days)
            $prevDate = $holidayDate->copy()->subDay();
            while (
                in_array($prevDate->toDateString(), $allHolidays) ||
                !in_array(strtolower($prevDate->format('l')), $workingDaysList)
            ) {
                $prevDate->subDay();
            }

            // Find succeeding working day (skipping other holidays and non-working days)
            $nextDate = $holidayDate->copy()->addDay();
            while (
                in_array($nextDate->toDateString(), $allHolidays) ||
                !in_array(strtolower($nextDate->format('l')), $workingDaysList)
            ) {
                $nextDate->addDay();
            }

            $prevDateStr = $prevDate->toDateString();
            $nextDateStr = $nextDate->toDateString();

            // Determine status for prevDate
            $isApprovedLeaveBefore = $this->isDateCoveredByApprovedLeave($prevDateStr, $approvedLeaves);
            $statusBefore = $this->getWorkingDayStatus($prevDateStr, $records, $isApprovedLeaveBefore);

            // Determine status for nextDate
            $isApprovedLeaveAfter = $this->isDateCoveredByApprovedLeave($nextDateStr, $approvedLeaves);
            $statusAfter = $this->getWorkingDayStatus($nextDateStr, $records, $isApprovedLeaveAfter);

            if ($this->isHolidayBridged($holidayDateStr, $statusBefore, $statusAfter, $isApprovedLeaveBefore, $isApprovedLeaveAfter)) {
                $bridgedHolidays[] = $holidayDateStr;
            }
        }

        $uniqueBridged = array_values(array_unique($bridgedHolidays));

        return [
            'bridged_holidays' => $uniqueBridged,
            'bridged_count' => count($uniqueBridged),
        ];
    }

    /**
     * Check whether a specific date falls within any approved leave request for the employee.
     */
    public function isDateCoveredByApprovedLeave(string $dateStr, $approvedLeaves): bool
    {
        $target = Carbon::parse($dateStr);
        foreach ($approvedLeaves as $leave) {
            $from = Carbon::parse($leave->start_date);
            $to = Carbon::parse($leave->end_date);
            if ($target->betweenIncluded($from, $to)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve working day status from attendance records or approved leave.
     */
    public function getWorkingDayStatus(string $dateStr, $records, bool $isApprovedLeave): string
    {
        if ($isApprovedLeave) {
            return 'leave';
        }

        if ($records->has($dateStr)) {
            $rec = $records->get($dateStr);
            $status = $rec->status instanceof AttendanceStatus ? $rec->status->value : (string) $rec->status;
            return $status;
        }

        // If no attendance record exists and it's a past working day, it is considered absent
        $date = Carbon::parse($dateStr);
        if ($date->isPast() && !$date->isToday()) {
            return 'absent';
        }

        return 'present';
    }
}
