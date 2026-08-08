<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function attendance(Request $request): View
    {
        return view('hr-admin.reports.attendance');
    }

    public function leave(Request $request): View
    {
        return view('hr-admin.reports.leave');
    }

    public function payroll(Request $request): View
    {
        return view('hr-admin.reports.payroll');
    }
}
