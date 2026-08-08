<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    public function index(): View
    {
        $settings = $this->settingsService->all();
        return view('super-admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'company_address' => 'required|string|max:255',
            'salary_divisor' => 'required|integer|min:20|max:31',
            'late_grace_period_minutes' => 'required|integer|min:0|max:60',
            'half_day_threshold_minutes' => 'required|integer|min:15|max:180',
        ]);

        $this->settingsService->setMany($validated);

        return back()->with('success', 'System business rules and company settings updated successfully.');
    }
}
