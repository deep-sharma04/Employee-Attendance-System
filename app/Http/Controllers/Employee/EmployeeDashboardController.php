<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if ($employee) {
            $todayAttendance = AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();

            $leaveBalances = $employee->leaveBalances()->with('leaveType')->get();

            $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->count();

            $recentPayslips = Payroll::where('employee_id', $employee->id)
                ->where('status', 'finalized')
                ->latest('payroll_year')
                ->latest('payroll_month')
                ->limit(3)
                ->get();
        } else {
            $todayAttendance = null;
            $leaveBalances = collect([]);
            $pendingLeaves = 0;
            $recentPayslips = collect([]);
        }

        return view('employee.dashboard', compact('user', 'employee', 'todayAttendance', 'leaveBalances', 'pendingLeaves', 'recentPayslips'));
    }
}