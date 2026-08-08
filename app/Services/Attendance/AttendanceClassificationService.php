<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use Carbon\Carbon;

class AttendanceClassificationService
{
    /**
     * Classify punch-in status against shift timings.
     *
     * Rules:
     * - Within shift start + grace period (e.g. 15 mins): Present
     * - Beyond grace period up to half-day threshold (e.g. 60 mins): Late
     * - Beyond half-day threshold: Half Day
     */
    public function classifyPunchIn(
        Carbon|string $punchTime,
        string $shiftStartTime,
        int $graceMinutes = 15,
        int $halfDayThresholdMinutes = 60
    ): AttendanceStatus {
        $punch = Carbon::parse($punchTime);
        $shiftStart = Carbon::parse($punch->toDateString() . ' ' . $shiftStartTime);

        if ($punch->lessThanOrEqualTo($shiftStart->copy()->addMinutes($graceMinutes))) {
            return AttendanceStatus::PRESENT;
        }

        if ($punch->lessThanOrEqualTo($shiftStart->copy()->addMinutes($halfDayThresholdMinutes))) {
            return AttendanceStatus::LATE;
        }

        return AttendanceStatus::HALF_DAY;
    }

    /**
     * Compute converted LOP absent days from late and half-day occurrences.
     *
     * Rules:
     * - 3 Late = 1 Absent
     * - 2 Half Days = 1 Absent
     */
    public function calculateConvertedAbsences(int $lateCount, int $halfDayCount): array
    {
        $lateToAbsent = intdiv($lateCount, 3);
        $remainingLate = $lateCount % 3;

        $halfDayToAbsent = intdiv($halfDayCount, 2);
        $remainingHalfDays = $halfDayCount % 2;

        return [
            'late_count' => $lateCount,
            'late_to_absent_days' => $lateToAbsent,
            'remaining_late_count' => $remainingLate,
            'half_day_count' => $halfDayCount,
            'half_day_to_absent_days' => $halfDayToAbsent,
            'remaining_half_days' => $remainingHalfDays,
            'total_converted_absent_days' => $lateToAbsent + $halfDayToAbsent,
        ];
    }
}
