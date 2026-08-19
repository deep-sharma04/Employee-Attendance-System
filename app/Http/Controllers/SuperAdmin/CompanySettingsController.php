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
            // SMTP Settings
            'mail_mailer' => $validated['mail_mailer'] ?? 'smtp',
            'mail_host' => $validated['mail_host'] ?? 'smtp.gmail.com',
            'mail_port' => isset($validated['mail_port']) ? (int) $validated['mail_port'] : 465,
            'mail_username' => $validated['mail_username'] ?? '',
            'mail_encryption' => $validated['mail_encryption'] ?? 'ssl',
            'mail_from_address' => $validated['mail_from_address'] ?? 'noreply@hrm.local',
            'mail_from_name' => $validated['mail_from_name'] ?? 'HRM System',
        ];

        // Only update password if a new one is supplied
        if (!empty($validated['mail_password'])) {
            $saveData['mail_password'] = $validated['mail_password'];
        }

        $this->settingsService->setMany($saveData);
        $this->settingsService->applyMailConfiguration();

        $this->auditLogger->log(
            action: 'system_settings.updated',
            targetType: 'CompanySettings',
            targetId: null,
            beforeValues: $beforeValues,
            afterValues: $saveData,
            description: 'Super Admin updated company profile, business rules, and SMTP configuration.'
        );

        return back()->with('success', 'System business rules, company profile, and SMTP email settings updated successfully.');
    }

    /**
     * Test the configured SMTP mail connection.
     */
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $testEmail = $request->input('test_email');

        try {
            $this->settingsService->applyMailConfiguration();

            \Illuminate\Support\Facades\Mail::to($testEmail)->send(
                new \App\Mail\TestSmtpMail($testEmail, now()->toDayDateTimeString())
            );

            return back()->with('success', "Test email sent successfully to {$testEmail}. Please check the inbox / spam folder.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("SMTP Test failed for {$testEmail}: " . $e->getMessage());

            return back()->withErrors([
                'test_email' => 'SMTP connection failed: ' . $e->getMessage(),
            ]);
        }
    }
}
