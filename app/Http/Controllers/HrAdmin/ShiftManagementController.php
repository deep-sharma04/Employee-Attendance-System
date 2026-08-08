<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ShiftManagementController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.shifts.index', ['shifts' => collect([])]);
    }
}
