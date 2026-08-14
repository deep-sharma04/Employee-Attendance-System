<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Project;
use App\Services\Audit\AuditLoggerService;
use App\Services\Project\ProjectHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectHealthSettingController extends Controller
{
    public function __construct(
        protected AuditLoggerService $auditLogger,
        protected ProjectHealthService $healthService
    ) {}

    /**
     * Display the Project Health configuration screen (Task T224).
     */
    public function index(): View
    {
        $thresholds = $this->healthService->getThresholds();

        return view('super-admin.settings.project-health', compact('thresholds'));
    }

    /**
     * Update health engine configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_health_schedule_variance_at_risk' => ['required', 'integer', 'min:1', 'max:100'],
            'project_health_schedule_variance_critical' => ['required', 'integer', 'min:1', 'max:100', 'gt:project_health_schedule_variance_at_risk'],
            'project_health_overdue_milestones_at_risk' => ['required', 'integer', 'min:1', 'max:50'],
            'project_health_overdue_milestones_critical' => ['required', 'integer', 'min:1', 'max:50', 'gte:project_health_overdue_milestones_at_risk'],
        ]);

        $before = $this->healthService->getThresholds();

        foreach ($validated as $key => $val) {
            CompanySetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $val]
            );
        }

        // Recalculate health for all non-completed projects with new thresholds
        $projects = Project::whereNotIn('status', ['completed', 'cancelled'])->get();
        foreach ($projects as $project) {
            $this->healthService->recalculateAndSave($project);
        }

        $this->auditLogger->log(
            action: 'project_health_settings.updated',
            targetType: 'CompanySettings',
            targetId: 0,
            beforeValues: $before,
            afterValues: $validated,
            description: 'Project health engine thresholds were updated.'
        );

        return redirect()->route('super-admin.settings.project-health')
            ->with('success', 'Project health thresholds updated and applied to active projects.');
    }
}
