<?php

namespace App\Services\Leave;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveAttendanceSyncService
{
    /**
     * Sync approved leave dates to the attendance records table.
     */
    public function syncApprovedLeave(LeaveRequest $leaveRequest): int
    {
        $employee = $leaveRequest->employee;
        if (!$employee) {
            return 0;
        }

        $startDate = Carbon::parse($leaveRequest->start_date)->startOfDay();
        $endDate = Carbon::parse($leaveRequest->end_date)->startOfDay();

        $holidays = Holiday::whereBetween('holiday_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('holiday_date')
            ->toArray();

        $period = CarbonPeriod::create($startDate, $endDate);
        $recordsSynced = 0;

        foreach ($period as $date) {
            // Skip non-working Sundays
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            // Skip company holidays
            if (in_array($date->toDateString(), $holidays)) {
                continue;
            }

            $dateStr = $date->toDateString();
            $status = $leaveRequest->is_half_day ? AttendanceStatus::HALF_DAY : AttendanceStatus::LEAVE;

            $record = AttendanceRecord::firstOrNew([
                'employee_id' => $employee->id,
                'attendance_date' => $dateStr,
            ]);

            $record->shift_id = $employee->shift_id;
            $record->status = $status;
            $record->notes = "Approved Leave: {$leaveRequest->leaveType->name}" . ($leaveRequest->is_half_day ? " ({$leaveRequest->half_day_type})" : "");
            $record->total_working_hours = $leaveRequest->is_half_day ? 4.0 : 0.0;
            $record->save();

            $recordsSynced++;
        }

        return $recordsSynced;
    }
}
