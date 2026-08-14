<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeHolidayController extends Controller
{
    /**
     * Display a read-only list/calendar view of declared company holidays.
     */
    public function index(Request $request): View
    {
        $selectedYear = (int) $request->input('year', date('Y'));

        $holidays = Holiday::whereYear('holiday_date', $selectedYear)
            ->orderBy('holiday_date')
            ->get();

        $availableYears = Holiday::selectRaw('YEAR(holiday_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array((int) date('Y'), $availableYears)) {
            $availableYears[] = (int) date('Y');
            rsort($availableYears);
        }

        return view('employee.holidays.index', [
            'holidays' => $holidays,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
        ]);
    }
}
