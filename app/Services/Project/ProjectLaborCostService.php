<?php

namespace App\Services\Project;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\TimesheetEntry;

class ProjectLaborCostService
{
    /**
     * Calculate hourly cost rate for an employee using configurable CompanySettings.
     * Hourly Rate = Monthly Basic Salary / (Monthly Working Days * Daily Working Hours).
     */
    public function getHourlyRate(Employee $employee): float
    {
        $basicSalary = (float) ($employee->monthly_salary ?? $employee->basic_salary ?? 0.0);
        if ($basicSalary <= 0) {
            return 0.0;
        }

        $workingDays = (float) (CompanySetting::where('key', 'timesheet_monthly_working_days')->value('value') ?? 22);
        $dailyHours = (float) (CompanySetting::where('key', 'timesheet_daily_working_hours')->value('value') ?? 8);

        if ($workingDays <= 0 || $dailyHours <= 0) {
            return 0.0;
        }

        $totalMonthlyHours = $workingDays * $dailyHours;
        return round($basicSalary / $totalMonthlyHours, 2);
    }

    /**
     * Calculate and return the labor cost for a given number of hours by an employee.
     */
    public function calculateEntryCost(Employee $employee, float $hours): float
    {
        $hourlyRate = $this->getHourlyRate($employee);
        return round($hours * $hourlyRate, 2);
    }

    /**
     * Compute total approved labor cost for a specific project.
     */
    public function getTotalLaborCostForProject(int $projectId): float
    {
        return (float) TimesheetEntry::where('project_id', $projectId)
            ->whereHas('timesheet', fn ($q) => $q->where('status', 'approved'))
            ->sum('calculated_cost');
    }

    /**
     * Compute total approved labor cost for a specific task.
     */
    public function getTotalLaborCostForTask(int $taskId): float
    {
        return (float) TimesheetEntry::where('task_id', $taskId)
            ->whereHas('timesheet', fn ($q) => $q->where('status', 'approved'))
            ->sum('calculated_cost');
    }
}
