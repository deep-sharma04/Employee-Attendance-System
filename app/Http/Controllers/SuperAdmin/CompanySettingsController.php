<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateCompanySettingsRequest;
use App\Services\Audit\AuditLoggerService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected AuditLoggerService $auditLogger
    ) {}

    public function index(): View
    {
        $settings = $this->settingsService->all();
        return view('super-admin.settings.index', compact('settings'));
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $beforeValues = $this->settingsService->all();

        $saveData = [
            'company_name' => $validated['company_name'],
            'company_address' => $validated['company_address'],
            'company_email' => $validated['company_email'] ?? 'hr@hrm.local',
            'company_phone' => $validated['company_phone'] ?? '',
            'salary_divisor' => (int) $validated['salary_divisor'],
            'late_grace_period_minutes' => (int) $validated['late_grace_period_minutes'],
            'half_day_threshold_minutes' => (int) $validated['half_day_threshold_minutes'],
            'late_to_absent_ratio' => (int) ($validated['late_to_absent_ratio'] ?? 3),
            'half_day_to_absent_ratio' => (int) ($validated['half_day_to_absent_ratio'] ?? 2),
            'enable_sandwich_rule' => $request->has('enable_sandwich_rule') ? $request->boolean('enable_sandwich_rule') : true,
        ];

        $this->settingsService->setMany($saveData);

        $this->auditLogger->log(
            action: 'system_settings.updated',
            targetType: 'CompanySettings',
            targetId: null,
            beforeValues: $beforeValues,
            afterValues: $saveData,
            description: 'Super Admin updated company profile and business rules configuration.'
        );

        return back()->with('success', 'System business rules and company profile updated successfully.');
    }
}
