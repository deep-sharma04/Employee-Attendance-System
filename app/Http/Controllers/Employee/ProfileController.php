<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the employee read-only personal profile with masked bank details.
     */
    public function show(): View
    {
        $user = Auth::user();
        $employee = $user?->employee()
            ->with([
                'shift',
                'leaveBalances.leaveType',
                'attendanceRecords' => fn($q) => $q->latest('attendance_date')->limit(5),
                'documents.documentType',
                'payslips' => fn($q) => $q->latest('year')->latest('month')->limit(3),
            ])
            ->first();

        return view('employee.profile', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }
}
