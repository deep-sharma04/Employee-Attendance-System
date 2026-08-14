<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase28ProductivityAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected Employee $employee;
    protected User $clientUser;
    protected Client $client;
    protected Team $team;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        CompanySetting::create(['key' => 'timesheet_monthly_working_days', 'value' => '22']);
        CompanySetting::create(['key' => 'timesheet_daily_working_hours', 'value' => '8']);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Manager Marcus',
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::factory()->create([
            'name' => 'Lead Lucas',
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'name' => 'Dev Dave',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-DEV-001',
            'first_name' => 'Dave',
            'last_name' => 'Developer',
            'email' => $this->employeeUser->email,
            'department' => 'Engineering',
            'designation' => 'Senior Backend Engineer',
            'status' => EmployeeStatus::ACTIVE,
            'joining_date' => now()->subYear(),
            'monthly_salary' => 3520.00, // 3520 / (22 * 8 = 176) = $20.00 / hr
        ]);

        $this->client = Client::create([
            'company_name' => 'Apex Global Solutions',
            'company_code' => 'CLT-APEX',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->clientUser = User::factory()->create([
            'name' => 'Apex Client Contact',
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->team = Team::create([
            'name' => 'Cloud Platform Squad',
            'code' => 'SQD-CLOUD',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employee->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->project = Project::create([
            'name' => 'Enterprise API Gateway',
            'code' => 'PRJ-API-GW',
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 10000.00,
            'estimated_hours' => 200.00,
            'start_date' => now()->subMonth()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'created_by' => $this->manager->id,
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $this->employee->id,
            'project_role' => ProjectMemberRole::MEMBER,
            'joined_at' => now(),
        ]);
    }

    /**
     * T256: Executive Project Dashboard (Manager / Super Admin).
     */
    public function test_t256_executive_project_dashboard_metrics_and_view(): void
    {
        // Add a second project with CRITICAL health
        $criticalProject = Project::create([
            'name' => 'Legacy Migration Service',
            'code' => 'PRJ-LEGACY',
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'budget' => 5000.00,
            'estimated_hours' => 100.00,
            'start_date' => now()->subMonths(2)->toDateString(),
            'deadline' => now()->subDays(5)->toDateString(), // Overdue
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::URGENT,
            'health' => ProjectHealth::CRITICAL,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)->get(route('manager.reports.executive'));

        $response->assertOk();
        $response->assertViewIs('reports.executive');
        $response->assertViewHas('metrics');
        $response->assertSee('Enterprise API Gateway');
        $response->assertSee('Legacy Migration Service');
        $response->assertSee('Good Health');
        $response->assertSee('Critical');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['statusCounts']['active']);
        $this->assertEquals(1, $metrics['healthCounts']['good']);
        $this->assertEquals(1, $metrics['healthCounts']['critical']);
        $this->assertGreaterThanOrEqual(1, $metrics['overdueCount']);
    }

    /**
     * T257: Employee Productivity Metrics calculation.
     */
    public function test_t257_employee_productivity_metrics_calculation(): void
    {
        // 1. Task completed on time
        Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-ONTIME-01',
            'title' => 'Implement Auth Middleware',
            'status' => TaskStatus::DONE,
            'priority' => TaskPriority::HIGH,
            'estimated_hours' => 10.00,
            'due_date' => now()->addDays(5)->toDateString(),
            'completed_at' => now()->addDays(2),
        ]);

        // 2. Overdue task
        Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-OVERDUE-01',
            'title' => 'Fix Memory Leak in WebSockets',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::URGENT,
            'estimated_hours' => 15.00,
            'due_date' => now()->subDays(3)->toDateString(), // Overdue
        ]);

        // 3. Timesheet with approved hours
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'period_type' => 'weekly',
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'total_hours' => 8.00,
            'status' => TimesheetStatus::APPROVED,
            'submitted_at' => now(),
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'entry_date' => now()->startOfWeek()->toDateString(),
            'hours' => 8.00,
            'is_billable' => true,
            'calculated_cost' => 160.00, // 8h * $20/h
        ]);

        $response = $this->actingAs($this->manager)->get(route('manager.reports.productivity'));

        $response->assertOk();
        $response->assertViewIs('reports.productivity');
        $response->assertViewHas('productivity');

        $productivity = $response->viewData('productivity');
        $employeeData = $productivity->firstWhere('employee.id', $this->employee->id);

        $this->assertNotNull($employeeData);
        $this->assertEquals(2, $employeeData['total_assigned']);
        $this->assertEquals(1, $employeeData['total_completed']);
        $this->assertEquals(1, $employeeData['overdue_count']);
        $this->assertEquals(100.0, $employeeData['on_time_percentage']); // 1 completed and on-time
        $this->assertEquals(25.0, $employeeData['estimated_hours']); // 10 + 15
        $this->assertEquals(8.0, $employeeData['logged_approved_hours']);
    }

    /**
     * T258: Team Workload View (Manager & Team Lead).
     */
    public function test_t258_team_workload_and_capacity_view(): void
    {
        // Assign an active task due in 3 days
        Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employeeUser->id,
            'task_code' => 'TSK-SOON-01',
            'title' => 'Setup CI Pipeline',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::MEDIUM,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        // Manager view
        $managerResponse = $this->actingAs($this->manager)->get(route('manager.reports.workload'));
        $managerResponse->assertOk();
        $managerResponse->assertViewIs('reports.workload');
        $managerResponse->assertSee('Cloud Platform Squad');
        $managerResponse->assertSee('Dev Dave');

        // Team Lead view
        $leadResponse = $this->actingAs($this->teamLead)->get(route('team-lead.reports.workload'));
        $leadResponse->assertOk();
        $leadResponse->assertViewIs('reports.workload');
        $leadResponse->assertSee('Cloud Platform Squad');

        $workload = $leadResponse->viewData('workload');
        $teamWorkload = $workload->firstWhere('team.id', $this->team->id);
        $this->assertNotNull($teamWorkload);
        $this->assertEquals(1, $teamWorkload['total_active_tasks']);
        $this->assertEquals(1, $teamWorkload['total_due_soon']);
    }

    /**
     * T259: Project Budget & Cost Utilization Report (Manager / Super Admin).
     */
    public function test_t259_project_budget_utilization_and_cost_report(): void
    {
        // Log 40 approved hours on project
        // Employee rate = $20/hr, Cost = $800.00
        // Project Budget = $10,000.00 -> Consumed: 8.0%, Remaining: $9,200.00
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'period_type' => 'weekly',
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'total_hours' => 40.00,
            'status' => TimesheetStatus::APPROVED,
            'submitted_at' => now(),
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'entry_date' => now()->startOfWeek()->toDateString(),
            'hours' => 40.00,
            'is_billable' => true,
            'calculated_cost' => 800.00,
        ]);

        $response = $this->actingAs($this->manager)->get(route('manager.reports.budget'));

        $response->assertOk();
        $response->assertViewIs('reports.budget');
        $response->assertViewHas('budgetData');

        $budgetData = $response->viewData('budgetData');
        $projectBudget = $budgetData->firstWhere('project.id', $this->project->id);

        $this->assertNotNull($projectBudget);
        $this->assertEquals(10000.00, $projectBudget['budget']);
        $this->assertEquals(800.00, $projectBudget['labor_cost']);
        $this->assertEquals(8.0, $projectBudget['consumed_percent']);
        $this->assertEquals(9200.00, $projectBudget['budget_remaining']);
        $this->assertEquals('under_budget', $projectBudget['utilization_status']);
    }

    /**
     * T260: Report Exports to CSV across types.
     */
    public function test_t260_report_csv_exports(): void
    {
        // 1. Executive CSV Export
        $execResponse = $this->actingAs($this->manager)->get(route('manager.reports.export', ['type' => 'executive']));
        $execResponse->assertOk();
        $this->assertTrue(str_contains($execResponse->headers->get('Content-Type'), 'text/csv'));
        $this->assertTrue(str_contains($execResponse->headers->get('Content-Disposition'), 'executive_report_'));

        // 2. Productivity CSV Export
        $prodResponse = $this->actingAs($this->manager)->get(route('manager.reports.export', ['type' => 'productivity']));
        $prodResponse->assertOk();
        $this->assertTrue(str_contains($prodResponse->headers->get('Content-Disposition'), 'productivity_report_'));

        // 3. Workload CSV Export
        $workloadResponse = $this->actingAs($this->teamLead)->get(route('team-lead.reports.export', ['type' => 'workload']));
        $workloadResponse->assertOk();
        $this->assertTrue(str_contains($workloadResponse->headers->get('Content-Disposition'), 'team_workload_report_'));

        // 4. Budget CSV Export
        $budgetResponse = $this->actingAs($this->superAdmin)->get(route('manager.reports.export', ['type' => 'budget']));
        $budgetResponse->assertOk();
        $this->assertTrue(str_contains($budgetResponse->headers->get('Content-Disposition'), 'budget_utilization_report_'));
    }

    /**
     * T261: Access Control & Scope Enforcement.
     */
    public function test_t261_role_based_access_control_and_scope_enforcement(): void
    {
        // 1. Team Lead is FORBIDDEN from Executive Dashboard and Budget Reports (No salary/budget visibility)
        $tlExecResponse = $this->actingAs($this->teamLead)->get(route('manager.reports.executive'));
        $tlExecResponse->assertStatus(403);

        $tlBudgetResponse = $this->actingAs($this->teamLead)->get(route('manager.reports.budget'));
        $tlBudgetResponse->assertStatus(403);

        $tlBudgetExportResponse = $this->actingAs($this->teamLead)->get(route('team-lead.reports.export', ['type' => 'budget']));
        $tlBudgetExportResponse->assertStatus(403);

        // 2. Team Lead CAN access Productivity and Workload
        $tlProdResponse = $this->actingAs($this->teamLead)->get(route('team-lead.reports.productivity'));
        $tlProdResponse->assertOk();

        // 3. Standard Employee is FORBIDDEN from all manager/team-lead report endpoints
        $empExecResponse = $this->actingAs($this->employeeUser)->get(route('manager.reports.executive'));
        $empExecResponse->assertStatus(403);

        $empProdResponse = $this->actingAs($this->employeeUser)->get(route('manager.reports.productivity'));
        $empProdResponse->assertStatus(403);

        // 4. Client is FORBIDDEN from all report endpoints
        $clientExecResponse = $this->actingAs($this->clientUser)->get(route('manager.reports.executive'));
        $clientExecResponse->assertStatus(403);

        $clientProdResponse = $this->actingAs($this->clientUser)->get(route('manager.reports.productivity'));
        $clientProdResponse->assertStatus(403);
    }
}
