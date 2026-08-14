<?php

namespace App\Services\Leave;

use App\Enums\LeaveTypeSlug;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveBalanceService
{
    /**
     * Get the remaining balance for an employee and leave type.
     */
    public function getBalance(int $employeeId, string|LeaveTypeSlug|int $leaveType, ?int $year = null): float
    {
        $year = $year ?? (int) date('Y');

        if (!Schema::hasTable('employee_leave_balances')) {
            return 0.0;
        }

        $query = DB::table('employee_leave_balances')
            ->where('employee_leave_balances.employee_id', $employeeId)
            ->where('employee_leave_balances.year', $year);

        if (is_int($leaveType)) {
            $query->where('employee_leave_balances.leave_type_id', $leaveType);
        } else {
            $slug = $leaveType instanceof LeaveTypeSlug ? $leaveType->value : $leaveType;
            $query->join('leave_types', 'employee_leave_balances.leave_type_id', '=', 'leave_types.id')
                ->where('leave_types.slug', $slug);
        }

        $balance = $query->value('remaining_days');

        return (float) ($balance ?? 0.0);
    }

    /**
     * Check if an employee has enough leave balance for a requested duration.
     */
    public function hasSufficientBalance(int $employeeId, string|LeaveTypeSlug|int $leaveType, float $daysRequested, ?int $year = null): bool
    {
        $currentBalance = $this->getBalance($employeeId, $leaveType, $year);
        return $currentBalance >= $daysRequested;
    }

    /**
     * Deduct leave days from employee balance upon approval.
     */
    public function deductBalance(int $employeeId, int $leaveTypeId, float $days, ?int $year = null): bool
    {
        $year = $year ?? (int) date('Y');

        $balance = EmployeeLeaveBalance::firstOrCreate(
            ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['allocated_days' => 0.0, 'used_days' => 0.0, 'remaining_days' => 0.0]
        );

        $newRemaining = max(0.0, (float) $balance->remaining_days - $days);
        $newUsed = (float) $balance->used_days + $days;

        return $balance->update([
            'remaining_days' => $newRemaining,
            'used_days' => $newUsed,
        ]);
    }

    /**
     * Restore leave days if previously deducted.
     */
    public function restoreBalance(int $employeeId, int $leaveTypeId, float $days, ?int $year = null): bool
    {
        $year = $year ?? (int) date('Y');

        $balance = EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            return false;
        }

        $newRemaining = (float) $balance->remaining_days + $days;
        $newUsed = max(0.0, (float) $balance->used_days - $days);

        return $balance->update([
            'remaining_days' => $newRemaining,
            'used_days' => $newUsed,
        ]);
    }

    /**
     * Get all active leave balances for an employee in a given cycle.
     */
    public function getBalancesForEmployee(int $employeeId, ?int $year = null): Collection
    {
        $year = $year ?? (int) date('Y');

        return EmployeeLeaveBalance::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();
    }

    /**
     * Expire unused leave balances at the end of the yearly cycle (No Carry-Forward Rule).
     */
    public function expireUnusedBalances(int $year): int
    {
        return EmployeeLeaveBalance::where('year', $year)
            ->where('remaining_days', '>', 0)
            ->update(['remaining_days' => 0.0]);
    }
}
