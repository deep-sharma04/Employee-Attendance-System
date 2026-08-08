<?php

namespace App\Services\Leave;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveWorkingDayService
{
    /**
     * Compute actual working days between start and end date, excluding Sundays and declared company holidays.
     * Note: Saturday is considered a working day unless configured otherwise.
     */
    public function calculateWorkingDays(string|Carbon $startDate, string|Carbon $endDate, bool $isHalfDay = false): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lessThan($start)) {
            return 0.0;
        }

        if ($isHalfDay && $start->equalTo($end)) {
            return 0.5;
        }

        $holidays = [];
        if (Schema::hasTable('holidays')) {
            $holidays = DB::table('holidays')
                ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
                ->pluck('holiday_date')
                ->all();
        }

        $period = CarbonPeriod::create($start, $end);
        $workingDays = 0;

        foreach ($period as $date) {
            // Exclude Sunday (dayOfWeek = 0)
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            // Exclude declared holidays
            if (in_array($date->toDateString(), $holidays)) {
                continue;
            }

            $workingDays++;
        }

        return (float) $workingDays;
    }
}
