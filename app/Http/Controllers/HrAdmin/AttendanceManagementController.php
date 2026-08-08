<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('hr-admin.attendance.index', ['records' => collect([])]);
    }

    public function createCorrection(): View
    {
        return view('hr-admin.attendance.correct');
    }
}
