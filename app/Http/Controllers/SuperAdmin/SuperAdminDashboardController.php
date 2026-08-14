<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Document;
use App\Models\Payroll;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    public function index(): View
    {
        $today = today();
        
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'hr_admins' => DB::table('users')->where('role', 'hr_admin')->count(),
            'pending_leaves' => LeaveRequest::where('status', 'pending')->count(),
            'pending_documents' => Document::where('status', 'pending')->count(),
        ];

        // Today's Attendance Breakdown
        $attendanceBreakdown = AttendanceRecord::whereDate('attendance_date', $today)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats['present_today'] = $attendanceBreakdown->get('present', 0);
        $stats['late_today'] = $attendanceBreakdown->get('late', 0);
        $stats['half_day_today'] = $attendanceBreakdown->get('half_day', 0);
        $stats['absent_today'] = $attendanceBreakdown->get('absent', 0);
        $stats['leave_today'] = $attendanceBreakdown->get('leave', 0);

        // Payroll Status (Current Month)
        $currentMonthPayrolls = Payroll::where('payroll_year', $today->year)->where('payroll_month', $today->month)->get();
        if ($currentMonthPayrolls->isEmpty()) {
            $payrollStatus = 'Not Generated';
        } elseif ($currentMonthPayrolls->every(fn($p) => $p->status === 'finalized')) {
            $payrollStatus = 'Finalized & Locked';
        } elseif ($currentMonthPayrolls->every(fn($p) => in_array($p->status->value ?? $p->status, ['approved', 'finalized']))) {
            $payrollStatus = 'Super Admin Approved';
        } elseif ($currentMonthPayrolls->contains(fn($p) => ($p->status->value ?? $p->status) === 'reviewed')) {
            $payrollStatus = 'Under Review';
        } else {
            $payrollStatus = 'Draft Generated';
        }

        $recentActivity = AuditLog::with('user')->latest()->limit(10)->get();

        return view('super-admin.dashboard', compact('stats', 'payrollStatus', 'recentActivity'));
    }
}