<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    /**
     * Display the Super Admin high-level operations dashboard.
     */
    public function index(): View
    {
        $stats = [
            'total_employees' => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
            'active_employees' => Schema::hasTable('employees') ? DB::table('employees')->where('status', 'active')->count() : 0,
            'hr_admins' => Schema::hasTable('users') ? DB::table('users')->where('role', 'hr_admin')->count() : 0,
            'today_attendance_count' => Schema::hasTable('attendance_records') ? DB::table('attendance_records')->whereDate('attendance_date', today())->count() : 0,
            'pending_leaves' => Schema::hasTable('leave_requests') ? DB::table('leave_requests')->where('status', 'pending')->count() : 0,
            'pending_documents' => Schema::hasTable('documents') ? DB::table('documents')->where('status', 'pending')->count() : 0,
            'current_payroll_status' => 'Not Generated',
        ];

        $recentActivity = Schema::hasTable('audit_logs')
            ? DB::table('audit_logs')->latest('created_at')->limit(8)->get()
            : collect([]);

        return view('super-admin.dashboard', compact('stats', 'recentActivity'));
    }
}
