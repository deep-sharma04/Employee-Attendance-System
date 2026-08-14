<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\EmployeeProjectProfile;
use App\Models\ProjectMilestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AI\HrmMcpServer;
use App\Services\AI\McpToolRegistry;
use App\Services\AI\ProjectIntelligenceService;
use App\Services\AI\Tools\IntelligenceMcpTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Transport\StdioTransport;
use Tests\TestCase;

class Phase32ProjectIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager1;
    protected User $manager2;
    protected User $teamLead;
    protected User $employeeUser1;
    protected User $employeeUser2;
    protected User $clientUser;
    protected Client $client;
    protected Team $team1;
    protected Project $project1;
    protected Project $project2;
    protected Employee $employee1;
    protected Employee $employee2;
    protected Task $overdueTask;
    protected Task $upcomingTask;
    protected Task $completedTask;
    protected ProjectMilestone $overdueMilestone;
    protected ProjectIntelligenceService $intelligenceService;
    protected IntelligenceMcpTools $intelligenceTools;
    protected McpToolRegistry $toolRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intelligenceService = app(ProjectIntelligenceService::class);
        $this->intelligenceTools = app(IntelligenceMcpTools::class);
        $this->toolRegistry = app(McpToolRegistry::class);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@hrm.local',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager1 = User::factory()->create([
            'name' => 'Manager Alpha',
            'email' => 'manager.alpha@test.com',
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->manager2 = User::factory()->create([
            'name' => 'Manager Beta',
            'email' => 'manager.beta@test.com',
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::factory()->create([
            'name' => 'Lead Charlie',
            'email' => 'lead.charlie@test.com',
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->employeeUser1 = User::factory()->create([
            'name' => 'David Developer',
            'email' => 'david.dev@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employeeUser2 = User::factory()->create([
            'name' => 'Emma Engineer',
            'email' => 'emma.eng@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->clientUser = User::factory()->create([
            'name' => 'Client Clara',
            'email' => 'clara@client.com',
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'company_name' => 'Acme Corporation',
            'company_code' => 'CLT-ACME',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        ClientUser::create([
            'client_id' => $this->client->id,
            'user_id' => $this->clientUser->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->team1 = Team::create([
            'name' => 'Alpha Squad',
            'code' => 'SQD-ALPHA',
            'manager_id' => $this->manager1->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $this->employee1 = Employee::create([
            'user_id' => $this->employeeUser1->id,
            'employee_code' => 'EMP-DAVID-001',
            'first_name' => 'David',
            'last_name' => 'Developer',
            'email' => $this->employeeUser1->email,
            'department' => 'Engineering',
            'designation' => 'Backend Engineer',
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 5500.00,
            'bank_name' => 'Secret Bank',
            'account_number' => '1234567890',
            'joining_date' => now()->subYear(),
        ]);

        EmployeeProjectProfile::create([
            'employee_id' => $this->employee1->id,
            'user_id' => $this->employeeUser1->id,
            'skills' => ['PHP', 'Laravel', 'MySQL', 'Docker'],
            'availability_status' => 'available',
            'weekly_capacity_hours' => 40,
            'experience_years' => 4.5,
        ]);

        $this->employee2 = Employee::create([
            'user_id' => $this->employeeUser2->id,
            'employee_code' => 'EMP-EMMA-002',
            'first_name' => 'Emma',
            'last_name' => 'Engineer',
            'email' => $this->employeeUser2->email,
            'department' => 'Engineering',
            'designation' => 'Frontend Engineer',
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 5200.00,
            'bank_name' => 'Secret Bank',
            'account_number' => '9876543210',
            'joining_date' => now()->subYear(),
        ]);

        EmployeeProjectProfile::create([
            'employee_id' => $this->employee2->id,
            'user_id' => $this->employeeUser2->id,
            'skills' => ['Vue', 'JavaScript', 'TypeScript', 'Tailwind'],
            'availability_status' => 'partially_available',
            'weekly_capacity_hours' => 20,
            'experience_years' => 3.0,
        ]);

        TeamMember::create([
            'team_id' => $this->team1->id,
            'user_id' => $this->employeeUser1->id,
            'employee_id' => $this->employee1->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        TeamMember::create([
            'team_id' => $this->team1->id,
            'user_id' => $this->employeeUser2->id,
            'employee_id' => $this->employee2->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->project1 = Project::create([
            'name' => 'Project Alpha Core',
            'code' => 'PRJ-ALPHA',
            'client_id' => $this->client->id,
            'team_id' => $this->team1->id,
            'manager_id' => $this->manager1->id,
            'budget' => 50000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->subMonth()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        ProjectMember::create([
            'project_id' => $this->project1->id,
            'user_id' => $this->employeeUser1->id,
            'employee_id' => $this->employee1->id,
            'project_role' => \App\Enums\ProjectMemberRole::MEMBER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ProjectMember::create([
            'project_id' => $this->project1->id,
            'user_id' => $this->employeeUser2->id,
            'employee_id' => $this->employee2->id,
            'project_role' => \App\Enums\ProjectMemberRole::MEMBER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->project2 = Project::create([
            'name' => 'Project Beta Cloud',
            'code' => 'PRJ-BETA',
            'client_id' => null,
            'team_id' => null,
            'manager_id' => $this->manager2->id,
            'budget' => 80000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::MEDIUM,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->subMonth()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'created_by' => $this->manager2->id,
        ]);

        $this->overdueMilestone = ProjectMilestone::create([
            'project_id' => $this->project1->id,
            'title' => 'Milestone 1: Database Setup',
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'pending',
            'created_by' => $this->manager1->id,
        ]);

        $this->overdueTask = Task::create([
            'project_id' => $this->project1->id,
            'milestone_id' => $this->overdueMilestone->id,
            'title' => 'Database Schema Optimization',
            'task_code' => 'TSK-ALPHA-01',
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->employeeUser1->id,
            'estimated_hours' => 10.0,
            'due_date' => now()->subDays(3)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        $this->upcomingTask = Task::create([
            'project_id' => $this->project1->id,
            'title' => 'Frontend Dashboard Integration',
            'task_code' => 'TSK-ALPHA-02',
            'priority' => TaskPriority::MEDIUM,
            'status' => TaskStatus::TODO,
            'assigned_to' => $this->employeeUser2->id,
            'estimated_hours' => 15.0,
            'due_date' => now()->addDays(4)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        $this->completedTask = Task::create([
            'project_id' => $this->project1->id,
            'title' => 'Requirements Analysis',
            'task_code' => 'TSK-ALPHA-00',
            'priority' => TaskPriority::LOW,
            'status' => TaskStatus::DONE,
            'assigned_to' => $this->employeeUser1->id,
            'estimated_hours' => 5.0,
            'due_date' => now()->subWeek()->toDateString(),
            'completed_at' => now()->subDays(6),
            'created_by' => $this->manager1->id,
        ]);
    }

    /**
     * T285: Natural-Language Project Search & Scoping.
     */
    public function test_t285_natural_language_project_search(): void
    {
        // 1. Natural Language search for overdue tasks
        $res = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'Show all overdue tasks in project',
        ]);
        $this->assertEquals('overdue_tasks', $res['intent']);
        $this->assertEquals('confirmed', $res['grounding']['status']);
        $this->assertTrue($res['grounding']['is_factual']);
        $this->assertEquals(1, $res['count']);
        $this->assertEquals($this->overdueTask->id, $res['results'][0]['id']);

        // 2. Upcoming tasks
        $upcomingRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'Show tasks due this week',
        ]);
        $this->assertEquals('upcoming_tasks', $upcomingRes['intent']);
        $this->assertEquals(1, $upcomingRes['count']);
        $this->assertEquals($this->upcomingTask->id, $upcomingRes['results'][0]['id']);

        // 3. Project workload
        $workloadRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'Which projects have the highest workload?',
        ]);
        $this->assertEquals('project_workload', $workloadRes['intent']);
        $this->assertGreaterThanOrEqual(1, $workloadRes['count']);

        // 4. Authorization isolation: Manager 1 cannot query Project 2
        $unauthRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'project_id' => $this->project2->id,
            'query' => 'Show tasks',
        ]);
        $this->assertEquals('not_authorized', $unauthRes['grounding']['status']);
        $this->assertEquals(0, $unauthRes['count']);

        // 5. Unsupported query returns safe insufficient_data response
        $unsupportedRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'What is the stock price of Apple?',
        ]);
        $this->assertEquals('insufficient_data', $unsupportedRes['grounding']['status']);
        $this->assertFalse($unsupportedRes['grounding']['is_factual']);
        $this->assertStringContainsString('Insufficient/unsupported', $unsupportedRes['grounding']['message']);
    }

    /**
     * T286: Project Health Explanation with Grounded Evidence.
     */
    public function test_t286_project_health_explanation(): void
    {
        $explanation = $this->intelligenceService->explainProjectHealth($this->manager1, $this->project1->id);

        $this->assertEquals($this->project1->id, $explanation['project_id']);
        $this->assertEquals('confirmed', $explanation['grounding']['status']);
        $this->assertTrue($explanation['grounding']['is_factual']);
        $this->assertNotEmpty($explanation['evidence']);
        $this->assertEquals(1, $explanation['overdue_tasks_count']);
        $this->assertEquals(1, $explanation['overdue_milestones_count']);
        $this->assertContains("project:{$this->project1->id}", $explanation['grounding']['evidence_sources']);

        // Unauthorized health request
        $unauthExplanation = $this->intelligenceService->explainProjectHealth($this->manager1, $this->project2->id);
        $this->assertEquals('not_authorized', $unauthExplanation['grounding']['status']);
    }

    /**
     * T287: Task Allocation Recommendations (Read-Only, Skill Matching, Workload).
     */
    public function test_t287_task_allocation_recommendations(): void
    {
        // 1. Recommend for a PHP/Laravel backend task
        $rec = $this->intelligenceService->recommendTaskAllocation($this->manager1, [
            'project_id' => $this->project1->id,
            'required_skills' => ['PHP', 'Laravel'],
            'estimated_hours' => 8.0,
        ]);

        $this->assertEquals('confirmed', $rec['grounding']['status']);
        $this->assertTrue($rec['grounding']['is_factual']);
        $this->assertGreaterThanOrEqual(1, $rec['recommendations_count']);

        // David should be top recommendation for PHP/Laravel
        $topCandidate = $rec['recommendations'][0];
        $this->assertEquals($this->employee1->id, $topCandidate['employee_id']);
        $this->assertContains('PHP', $topCandidate['skills']);
        $this->assertContains('php', array_map('strtolower', $topCandidate['matched_skills']));

        // Verify task was NOT assigned (Strict Read-Only)
        $this->assertDatabaseMissing('tasks', ['title' => 'Recommendation Assigned']);

        // CRITICAL DATA ISOLATION: Verify sensitive HR data is NOT present in candidate data
        $this->assertArrayNotHasKey('monthly_salary', $topCandidate);
        $this->assertArrayNotHasKey('bank_name', $topCandidate);
        $this->assertArrayNotHasKey('account_number', $topCandidate);
        $this->assertArrayNotHasKey('ifsc_code', $topCandidate);

        // 2. Recommend for a Vue frontend task
        $vueRec = $this->intelligenceService->recommendTaskAllocation($this->manager1, [
            'project_id' => $this->project1->id,
            'required_skills' => ['Vue', 'JavaScript'],
        ]);
        $topVueCandidate = $vueRec['recommendations'][0];
        $this->assertEquals($this->employee2->id, $topVueCandidate['employee_id']);
        $this->assertContains('Vue', $topVueCandidate['skills']);

        // 3. Regular employee cannot request task allocation recommendations
        $employeeRec = $this->intelligenceService->recommendTaskAllocation($this->employeeUser1, [
            'project_id' => $this->project1->id,
        ]);
        $this->assertEquals('not_authorized', $employeeRec['grounding']['status']);
    }

    /**
     * T288: Management Reports (Productivity, Workload, Financial Authorization).
     */
    public function test_t288_management_reports(): void
    {
        // 1. Productivity Report
        $prodReport = $this->intelligenceService->generateManagementReport($this->manager1, [
            'report_type' => 'productivity',
            'project_id' => $this->project1->id,
        ]);
        $this->assertEquals('project_productivity', $prodReport['report_type']);
        $this->assertEquals('confirmed', $prodReport['grounding']['status']);
        $this->assertArrayHasKey('summary', $prodReport);
        $this->assertArrayHasKey('metrics', $prodReport);

        // 2. Workload Report
        $workloadReport = $this->intelligenceService->generateManagementReport($this->manager1, [
            'report_type' => 'workload',
            'team_id' => $this->team1->id,
        ]);
        $this->assertEquals('team_workload', $workloadReport['report_type']);
        $this->assertEquals('confirmed', $workloadReport['grounding']['status']);
        $this->assertGreaterThanOrEqual(1, $workloadReport['summary']['total_teams']);

        // 3. Budget / Financial Report for Manager (Authorized)
        $budgetReport = $this->intelligenceService->generateManagementReport($this->manager1, [
            'report_type' => 'budget_utilization',
            'project_id' => $this->project1->id,
        ]);
        $this->assertEquals('budget_utilization', $budgetReport['report_type']);
        $this->assertEquals('confirmed', $budgetReport['grounding']['status']);
        $this->assertNotEmpty($budgetReport['metrics']);
        $this->assertEquals(50000.00, $budgetReport['metrics'][0]['budget']);

        // 4. Budget / Financial Report for Employee (Unauthorized to view financials)
        $employeeBudget = $this->intelligenceService->generateManagementReport($this->employeeUser1, [
            'report_type' => 'budget_utilization',
            'project_id' => $this->project1->id,
        ]);
        $this->assertEquals('not_authorized', $employeeBudget['grounding']['status']);
        $this->assertEmpty($employeeBudget['metrics']);
    }

    /**
     * T289: Ground AI Responses in Authorized Project Data.
     */
    public function test_t289_grounding_metadata_contract(): void
    {
        // Confirmed grounding on factual search
        $searchRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'Show overdue tasks',
        ]);
        $this->assertEquals('confirmed', $searchRes['grounding']['status']);
        $this->assertTrue($searchRes['grounding']['is_factual']);
        $this->assertContains("task:{$this->overdueTask->id}", $searchRes['grounding']['evidence_sources']);

        // Insufficient data on unmapped query
        $insufficientRes = $this->intelligenceService->searchProjectIntelligence($this->manager1, [
            'query' => 'What is the weather tomorrow?',
        ]);
        $this->assertEquals('insufficient_data', $insufficientRes['grounding']['status']);
        $this->assertFalse($insufficientRes['grounding']['is_factual']);
        $this->assertNotEmpty($insufficientRes['grounding']['missing_information']);
    }

    /**
     * MCP Server Tool Discovery of Phase 32 Tools.
     */
    public function test_mcp_server_discovers_phase_32_tools(): void
    {
        $server = new HrmMcpServer(new StdioTransport('test-intelligence-session'));
        $context = $server->createContext();

        $tools = $context->tools();
        $this->assertGreaterThanOrEqual(17, $tools->count());

        $toolNames = $tools->map(fn ($t) => $t->name())->all();
        $this->assertContains('project.intelligence_search', $toolNames);
        $this->assertContains('project.explain_health', $toolNames);
        $this->assertContains('task.recommend_allocation', $toolNames);
        $this->assertContains('project.management_report', $toolNames);
    }
}
