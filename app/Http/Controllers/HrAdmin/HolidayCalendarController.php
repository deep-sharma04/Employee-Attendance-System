<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HolidayCalendarController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.holidays.index', ['holidays' => collect([])]);
    }
}
