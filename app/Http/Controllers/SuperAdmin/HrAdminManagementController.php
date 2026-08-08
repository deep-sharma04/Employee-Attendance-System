<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrAdminManagementController extends Controller
{
    public function index(): View
    {
        return view('super-admin.hr-admins.index', ['hrAdmins' => collect([])]);
    }

    public function create(): View
    {
        return view('super-admin.hr-admins.create');
    }
}
