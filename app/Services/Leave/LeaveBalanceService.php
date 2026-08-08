<?php

namespace App\Services\Leave;

use App\Enums\LeaveTypeSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveBalanceService
{
    /**
     * Get the remaining balance for an employee and leave type.
     */
    public function getBalance(int $employeeId, string|LeaveTypeSlug $leaveTypeSlug): float
    {
        $slug = $leaveTypeSlug instanceof LeaveTypeSlug ? $leaveTypeSlug->value : $leaveTypeSlug;

        if (!Schema::hasTable('employee_leave_balances')) {
            return 0.0;
        }

        $balance = DB::table('employee_leave_balances')
            ->join('leave_types', 'employee_leave_balances.leave_type_id', '=', 'leave_types.id')
            ->where('employee_leave_balances.employee_id', $employeeId)
            ->where('leave_types.slug', $slug)
            ->value('remaining_days');

        return (float) ($balance ?? 0.0);
    }

    /**
     * Check if an employee has enough leave balance for a requested duration.
     */
    public function hasSufficientBalance(int $employeeId, string|LeaveTypeSlug $leaveTypeSlug, float $daysRequested): bool
    {
        $currentBalance = $this->getBalance($employeeId, $leaveTypeSlug);
        return $currentBalance >= $daysRequested;
    }
}
