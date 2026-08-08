<?php

namespace App\Services\Payroll;

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
}
