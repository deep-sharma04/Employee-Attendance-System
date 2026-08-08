<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(): View
    {
        return view('employee.payslips.index', ['payslips' => collect([])]);
    }
}
