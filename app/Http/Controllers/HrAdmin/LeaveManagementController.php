<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LeaveManagementController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.leaves.index', ['leaves' => collect([])]);
    }
}
