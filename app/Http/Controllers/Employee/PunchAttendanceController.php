<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PunchAttendanceController extends Controller
{
    public function history(Request $request): View
    {
        return view('employee.attendance.history', ['records' => collect([])]);
    }
}
