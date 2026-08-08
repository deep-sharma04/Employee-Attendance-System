<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(): View
    {
        return view('employee.leaves.index', ['requests' => collect([])]);
    }

    public function create(): View
    {
        return view('employee.leaves.create');
    }
}
