<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HrDashboardController extends Controller
{
    /**
     * Display the HR Admin operational dashboard.
     */
    public function index(): View
    {
        $stats = [
            'total_employees' => Schema::hasTable('employees') ? DB::table('employees')->count() : 0,
            'active_employees' => Schema::hasTable('employees') ? DB::table('employees')->where('status', 'active')->count() : 0,
            'today_attendance_count' => Schema::hasTable('attendance_records') ? DB::table('attendance_records')->whereDate('attendance_date', today())->count() : 0,
            'today_late_count' => Schema::hasTable('attendance_records') ? DB::table('attendance_records')->whereDate('attendance_date', today())->where('status', 'late')->count() : 0,
            'pending_leaves' => Schema::hasTable('leave_requests') ? DB::table('leave_requests')->where('status', 'pending')->count() : 0,
            'pending_documents' => Schema::hasTable('documents') ? DB::table('documents')->where('status', 'pending')->count() : 0,
        ];

        return view('hr-admin.dashboard', compact('stats'));
    }
}
