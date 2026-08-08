<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the employee read-only personal profile.
     */
    public function show(): View
    {
        $user = Auth::user();
        $employee = $user?->employee;

        return view('employee.profile', compact('user', 'employee'));
    }
}
