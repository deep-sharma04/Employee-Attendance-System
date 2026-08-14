<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Http\Requests\Holiday\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayCalendarController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    /**
     * Display a listing of declared company holidays by year.
     */
    public function index(Request $request): View
    {
        $selectedYear = (int) $request->input('year', date('Y'));

        $holidays = Holiday::whereYear('holiday_date', $selectedYear)
            ->orderBy('holiday_date')
            ->get();

        // Calculate available years in calendar
        $availableYears = Holiday::selectRaw('YEAR(holiday_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array((int) date('Y'), $availableYears)) {
            $availableYears[] = (int) date('Y');
            rsort($availableYears);
        }

        // Upcoming holidays from today
        $upcomingCount = Holiday::whereDate('holiday_date', '>=', now()->toDateString())->count();

        return view('hr-admin.holidays.index', [
            'holidays' => $holidays,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'upcomingCount' => $upcomingCount,
        ]);
    }

    /**
     * Store a newly created declared holiday in database.
     */
    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_recurring_yearly'] = $request->boolean('is_recurring_yearly');

        $holiday = Holiday::create($validated);

        $this->auditLogger->log(
            action: 'holiday.created',
            targetType: 'App\Models\Holiday',
            targetId: $holiday->id,
            afterValues: $holiday->toArray(),
            description: "Declared company holiday '{$holiday->name}' on {$holiday->holiday_date->format('Y-m-d')}."
        );

        $year = $holiday->holiday_date->format('Y');
        return redirect()->route('hr-admin.holidays.index', ['year' => $year])
            ->with('success', "Declared holiday '{$holiday->name}' added successfully.");
    }

    /**
     * Update the specified declared holiday.
     */
    public function update(UpdateHolidayRequest $request, $id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);
        $validated = $request->validated();
        $validated['is_recurring_yearly'] = $request->boolean('is_recurring_yearly');

        $beforeValues = $holiday->toArray();
        $holiday->update($validated);

        $this->auditLogger->log(
            action: 'holiday.updated',
            targetType: 'App\Models\Holiday',
            targetId: $holiday->id,
            beforeValues: $beforeValues,
            afterValues: $holiday->fresh()->toArray(),
            description: "Updated declared holiday '{$holiday->name}' on {$holiday->holiday_date->format('Y-m-d')}."
        );

        $year = $holiday->holiday_date->format('Y');
        return redirect()->route('hr-admin.holidays.index', ['year' => $year])
            ->with('success', "Declared holiday '{$holiday->name}' updated successfully.");
    }

    /**
     * Remove the specified declared holiday from calendar.
     */
    public function destroy($id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);
        $holidayName = $holiday->name;
        $holidayDate = $holiday->holiday_date->format('Y-m-d');
        $year = $holiday->holiday_date->format('Y');

        $holiday->delete();

        $this->auditLogger->log(
            action: 'holiday.deleted',
            targetType: 'App\Models\Holiday',
            targetId: $id,
            description: "Removed declared holiday '{$holidayName}' on {$holidayDate}."
        );

        return redirect()->route('hr-admin.holidays.index', ['year' => $year])
            ->with('success', "Declared holiday '{$holidayName}' removed from calendar.");
    }
}
