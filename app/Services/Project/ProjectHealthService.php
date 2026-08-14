<?php

namespace App\Services\Project;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Models\CompanySetting;
use App\Models\Project;

class ProjectHealthService
{
    /**
     * Get configured health thresholds.
     */
    public function getThresholds(): array
    {
        return [
            'schedule_variance_at_risk' => (int) (CompanySetting::where('key', 'project_health_schedule_variance_at_risk')->value('value') ?? 15),
            'schedule_variance_critical' => (int) (CompanySetting::where('key', 'project_health_schedule_variance_critical')->value('value') ?? 30),
            'overdue_milestones_at_risk' => (int) (CompanySetting::where('key', 'project_health_overdue_milestones_at_risk')->value('value') ?? 1),
            'overdue_milestones_critical' => (int) (CompanySetting::where('key', 'project_health_overdue_milestones_critical')->value('value') ?? 2),
        ];
    }

    /**
     * Deterministically calculate the health of a project (Task T223).
     */
    public function calculateHealth(Project $project): ProjectHealth
    {
        // 1. Completed projects are always in GOOD health
        if ($project->status === ProjectStatus::COMPLETED) {
            return ProjectHealth::GOOD;
        }

        // 2. Cancelled projects are marked CRITICAL
        if ($project->status === ProjectStatus::CANCELLED) {
            return ProjectHealth::CRITICAL;
        }

        // 3. Not started / Planning with future or no start date
        if ($project->status === ProjectStatus::PLANNING && (!$project->start_date || $project->start_date->isFuture())) {
            return ProjectHealth::NOT_STARTED;
        }

        $thresholds = $this->getThresholds();

        // 4. Overdue Milestones Count
        $overdueMilestones = $project->milestones()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        // 5. Schedule Variance Calculation
        $actualProgress = $project->progressPercentage();
        $expectedProgress = 0;

        if ($project->start_date && $project->deadline) {
            $totalDays = $project->start_date->diffInDays($project->deadline, false);
            if ($totalDays > 0) {
                if (now()->lt($project->start_date)) {
                    $expectedProgress = 0;
                } elseif (now()->gte($project->deadline)) {
                    $expectedProgress = 100;
                } else {
                    $daysElapsed = $project->start_date->diffInDays(now(), false);
                    $expectedProgress = ($daysElapsed / $totalDays) * 100;
                }
            }
        } elseif ($project->status === ProjectStatus::ACTIVE) {
            $expectedProgress = 50;
        }

        $scheduleVariance = max(0, $expectedProgress - $actualProgress);
        $isDeadlinePassed = $project->deadline && $project->deadline->isPast() && $actualProgress < 100;

        // Critical Condition
        if (
            $isDeadlinePassed ||
            $scheduleVariance >= $thresholds['schedule_variance_critical'] ||
            $overdueMilestones >= $thresholds['overdue_milestones_critical']
        ) {
            return ProjectHealth::CRITICAL;
        }

        // At Risk Condition
        if (
            $scheduleVariance >= $thresholds['schedule_variance_at_risk'] ||
            $overdueMilestones >= $thresholds['overdue_milestones_at_risk'] ||
            $project->status === ProjectStatus::ON_HOLD
        ) {
            return ProjectHealth::AT_RISK;
        }

        if ($project->status === ProjectStatus::PLANNING) {
            return ProjectHealth::NOT_STARTED;
        }

        return ProjectHealth::GOOD;
    }

    /**
     * Recalculate and persist the project health.
     */
    public function recalculateAndSave(Project $project): ProjectHealth
    {
        $health = $this->calculateHealth($project);

        if ($project->health !== $health) {
            $project->health = $health;
            $project->save();
        }

        return $health;
    }
}
