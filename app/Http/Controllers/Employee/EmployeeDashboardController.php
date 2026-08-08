<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee personal self-service dashboard.
     */
    public function index(): View
    {
        $user = Auth::user();
        $employee = $user?->employee;

        $todayAttendance = null;
        if ($employee && Schema::hasTable('attendance_records')) {
            $todayAttendance = DB::table('attendance_records')
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();
        }

        $leaveBalances = collect([]);
        if ($employee && Schema::hasTable('employee_leave_balances')) {
            $leaveBalances = DB::table('employee_leave_balances')
                ->join('leave_types', 'employee_leave_balances.leave_type_id', '=', 'leave_types.id')
                ->where('employee_leave_balances.employee_id', $employee->id)
                ->select('leave_types.name', 'leave_types.slug', 'employee_leave_balances.remaining_days')
                ->get();
        }

        $recentPayslips = collect([]);
        if ($employee && Schema::hasTable('payrolls')) {
            $recentPayslips = DB::table('payrolls')
                ->where('employee_id', $employee->id)
                ->where('status', 'finalized')
                ->latest('payroll_year')
                ->latest('payroll_month')
                ->limit(3)
                ->get();
        }

        return view('employee.dashboard', compact('user', 'employee', 'todayAttendance', 'leaveBalances', 'recentPayslips'));
    }
}
