<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('hr-admin.employees.index', ['employees' => collect([])]);
    }

    public function create(): View
    {
        return view('hr-admin.employees.create');
    }

    public function show($id): View
    {
        return view('hr-admin.employees.show', ['id' => $id]);
    }

    public function edit($id): View
    {
        return view('hr-admin.employees.edit', ['id' => $id]);
    }
}
