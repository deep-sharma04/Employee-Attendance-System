<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PayrollManagementController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.payroll.index', ['payrolls' => collect([])]);
    }
}
