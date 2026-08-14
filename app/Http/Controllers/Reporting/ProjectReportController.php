<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use App\Services\Report\ProjectReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(
        protected ProjectReportingService $reportingService
    ) {}

    /**
     * T256 & T261: Executive Project Dashboard (Super Admin & Manager).
     */
    public function executive(Request $request): View
    {
        $user = Auth::user();

        // Enforce RBAC: Only Super Admin and Manager
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized. Executive dashboard is restricted to Managers and Super Admins.');
        }

        $filters = $request->only(['team_id', 'client_id', 'status', 'health', 'start_date', 'end_date']);
        $metrics = $this->reportingService->getExecutiveDashboardMetrics($user, $filters);

        $teams = $this->reportingService->getAuthorizedTeamsQuery($user)->get();
        $clients = Client::active()->orderBy('company_name')->get();

        return view('reports.executive', compact('metrics', 'teams', 'clients', 'filters'));
    }

    /**
     * T257 & T261: Employee Productivity Metrics (Super Admin, Manager, Team Lead).
     */
    public function productivity(Request $request): View
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isManager() && !$user->isTeamLead()) {
            abort(403, 'Unauthorized to view productivity reports.');
        }

        $filters = $request->only(['department', 'employee_id']);
        $productivity = $this->reportingService->getEmployeeProductivityMetrics($user, $filters);

        $departments = Employee::whereNotNull('department')->distinct()->pluck('department');
        $employees = Employee::active()->orderBy('first_name')->get();

        return view('reports.productivity', compact('productivity', 'departments', 'employees', 'filters'));
    }

    /**
     * T258 & T261: Team Workload View (Super Admin, Manager, Team Lead).
     */
    public function workload(Request $request): View
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && !$user->isManager() && !$user->isTeamLead()) {
            abort(403, 'Unauthorized to view team workload reports.');
        }

        $filters = $request->only(['team_id']);
        $workload = $this->reportingService->getTeamWorkloadMetrics($user, $filters);
        $teams = $this->reportingService->getAuthorizedTeamsQuery($user)->get();

        return view('reports.workload', compact('workload', 'teams', 'filters'));
    }

    /**
     * T259 & T261: Project Budget & Cost Utilization (Super Admin & Manager).
     */
    public function budget(Request $request): View
    {
        $user = Auth::user();

        // Enforce RBAC: Financial reports restricted to Super Admin and Manager
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized. Project budget reports are restricted to Managers and Super Admins.');
        }

        $filters = $request->only(['project_id', 'team_id', 'status']);
        $budgetData = $this->reportingService->getProjectBudgetUtilization($user, $filters);

        $projects = $this->reportingService->getAuthorizedProjectsQuery($user)->get();
        $teams = $this->reportingService->getAuthorizedTeamsQuery($user)->get();

        return view('reports.budget', compact('budgetData', 'projects', 'teams', 'filters'));
    }

    /**
     * T260 & T261: Export Reports to CSV.
     */
    public function export(Request $request, string $type): StreamedResponse
    {
        $user = Auth::user();

        $allowedTypes = ['executive', 'productivity', 'workload', 'budget'];
        if (!in_array($type, $allowedTypes)) {
            abort(404, 'Invalid report type requested.');
        }

        // Restrict financial / executive reports for Team Lead
        if ($user->isTeamLead() && in_array($type, ['executive', 'budget'])) {
            abort(403, 'Team Leads are not authorized to export executive or budget reports.');
        }

        // Restrict other non-privileged roles
        if (!$user->isSuperAdmin() && !$user->isManager() && !$user->isTeamLead()) {
            abort(403, 'Unauthorized to export reports.');
        }

        return $this->reportingService->exportToCsv($type, $user, $request->all());
    }
}
