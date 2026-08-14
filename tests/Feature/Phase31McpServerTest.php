<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\ClientStatus;
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
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AI\HrmMcpServer;
use App\Services\AI\McpAuthService;
use App\Services\AI\McpIntegrationService;
use App\Services\AI\McpToolRegistry;
use App\Services\AI\Tools\ClientMcpTools;
use App\Services\AI\Tools\EmployeeMcpTools;
use App\Services\AI\Tools\ProjectMcpTools;
use App\Services\AI\Tools\TaskMcpTools;
use App\Services\AI\Tools\TimesheetMcpTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Server\Transport\StdioTransport;
use Tests\TestCase;

class Phase31McpServerTest extends TestCase
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
    protected McpToolRegistry $toolRegistry;
    protected McpIntegrationService $integrationService;
    protected McpAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = app(McpAuthService::class);
        $this->integrationService = app(McpIntegrationService::class);
        $this->toolRegistry = app(McpToolRegistry::class);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@hrm.local',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
            'remember_token' => 'mcp_token_admin_123',
        ]);

        $this->manager1 = User::factory()->create([
            'name' => 'Manager Alpha',
            'email' => 'manager.alpha@test.com',
            'role' => UserRole::MANAGER,
            'is_active' => true,
            'remember_token' => 'mcp_token_manager_alpha',
        ]);

        $this->manager2 = User::factory()->create([
            'name' => 'Manager Beta',
            'email' => 'manager.beta@test.com',
            'role' => UserRole::MANAGER,
            'is_active' => true,
            'remember_token' => 'mcp_token_manager_beta',
        ]);

        $this->teamLead = User::factory()->create([
            'name' => 'Lead Charlie',
            'email' => 'lead.charlie@test.com',
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
            'remember_token' => 'mcp_token_lead_charlie',
        ]);

        $this->employeeUser1 = User::factory()->create([
            'name' => 'Dev David',
            'email' => 'dev.david@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
            'remember_token' => 'mcp_token_dev_david',
        ]);

        $this->employeeUser2 = User::factory()->create([
            'name' => 'Dev Emma',
            'email' => 'dev.emma@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
            'remember_token' => 'mcp_token_dev_emma',
        ]);

        $this->clientUser = User::factory()->create([
            'name' => 'Client Clara',
            'email' => 'client.clara@test.com',
            'role' => UserRole::CLIENT,
            'is_active' => true,
            'remember_token' => 'mcp_token_client_clara',
        ]);

        $this->client = Client::create([
            'company_name' => 'Acme Corp',
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
            'monthly_salary' => 5000.00,
            'bank_name' => 'Secret Bank',
            'account_number' => '1234567890',
            'joining_date' => now()->subYear(),
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
            'monthly_salary' => 4800.00,
            'bank_name' => 'Secret Bank',
            'account_number' => '9876543210',
            'joining_date' => now()->subYear(),
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
            'name' => 'Alpha Core Redesign',
            'code' => 'PRJ-ALPHA-01',
            'client_id' => $this->client->id,
            'team_id' => $this->team1->id,
            'manager_id' => $this->manager1->id,
            'budget' => 25000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        $this->project2 = Project::create([
            'name' => 'Beta Cloud Architecture',
            'code' => 'PRJ-BETA-02',
            'client_id' => null,
            'team_id' => null,
            'manager_id' => $this->manager2->id,
            'budget' => 45000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::MEDIUM,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(4)->toDateString(),
            'created_by' => $this->manager2->id,
        ]);
    }

    /**
     * T276: Internal MCP Server Initialization & Tool Discovery.
     */
    public function test_t276_internal_mcp_server_initialization_and_tool_discovery(): void
    {
        $server = new HrmMcpServer(new StdioTransport('test-session'));
        $context = $server->createContext();

        $tools = $context->tools();
        $this->assertGreaterThanOrEqual(13, $tools->count());

        $toolNames = $tools->map(fn ($t) => $t->name())->all();
        $this->assertContains('client.search', $toolNames);
        $this->assertContains('client.create', $toolNames);
        $this->assertContains('client.update', $toolNames);
        $this->assertContains('project.search', $toolNames);
        $this->assertContains('project.create', $toolNames);
        $this->assertContains('project.update', $toolNames);
        $this->assertContains('task.create', $toolNames);
        $this->assertContains('task.update', $toolNames);
        $this->assertContains('task.assign', $toolNames);
        $this->assertContains('task.complete', $toolNames);
        $this->assertContains('timesheet.search', $toolNames);
        $this->assertContains('timesheet.create', $toolNames);
        $this->assertContains('employee.search', $toolNames);
    }

    /**
     * T277: Secure Internal MCP Transport & Authentication.
     */
    public function test_t277_secure_mcp_authentication(): void
    {
        // 1. Unauthenticated token returns null
        $this->assertNull($this->authService->authenticateByToken('invalid_token'));

        // 2. Valid token returns user
        $resolved = $this->authService->authenticateByToken('mcp_token_manager_alpha');
        $this->assertNotNull($resolved);
        $this->assertEquals($this->manager1->id, $resolved->id);

        // 3. Inactive user token is rejected
        $this->manager1->update(['is_active' => false]);
        $this->assertNull($this->authService->authenticateByToken('mcp_token_manager_alpha'));
    }

    /**
     * T278: MCP Central Tool Registry.
     */
    public function test_t278_mcp_tool_registry(): void
    {
        $allTools = $this->toolRegistry->getAllTools();
        $this->assertGreaterThanOrEqual(13, count($allTools));

        $clientTool = $this->toolRegistry->getTool('client.search');
        $this->assertNotNull($clientTool);
        $this->assertEquals('client', $clientTool['category']);
        $this->assertEquals('read', $clientTool['type']);
    }

    /**
     * T279: Client MCP Tools (client.search, client.create, client.update).
     */
    public function test_t279_client_mcp_tools(): void
    {
        $clientTools = app(ClientMcpTools::class);

        // 1. client.search (Super Admin)
        $searchRes = $clientTools->search($this->superAdmin, ['search' => 'Acme']);
        $this->assertEquals(1, $searchRes['count']);
        $this->assertEquals('Acme Corp', $searchRes['clients'][0]['company_name']);

        // 2. client.create (Manager)
        $createRes = $clientTools->create($this->manager1, [
            'company_name' => 'Stark Industries',
            'company_code' => 'CLT-STARK',
            'status' => ClientStatus::ACTIVE->value,
            'email' => 'tony@stark.com',
        ]);
        $this->assertEquals('Stark Industries', $createRes['company_name']);
        $this->assertDatabaseHas('clients', ['company_code' => 'CLT-STARK']);

        // 3. client.update (Manager)
        $updateRes = $clientTools->update($this->manager1, [
            'client_id' => $createRes['client_id'],
            'company_name' => 'Stark Industries Global',
        ]);
        $this->assertEquals('Stark Industries Global', $updateRes['company_name']);
    }

    /**
     * T280: Project MCP Tools (project.search, project.create, project.update).
     */
    public function test_t280_project_mcp_tools(): void
    {
        $projectTools = app(ProjectMcpTools::class);

        // 1. project.search (Manager only sees their own project)
        $managerRes = $projectTools->search($this->manager1, []);
        $this->assertEquals(1, $managerRes['count']);
        $this->assertEquals($this->project1->id, $managerRes['projects'][0]['id']);

        // 2. project.create (Manager)
        $createRes = $projectTools->create($this->manager1, [
            'name' => 'Project Gamma',
            'code' => 'PRJ-GAMMA-01',
            'client_id' => $this->client->id,
            'team_id' => $this->team1->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
        ]);
        $this->assertEquals('Project Gamma', $createRes['name']);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-GAMMA-01']);

        // 3. project.update (Manager)
        $updateRes = $projectTools->update($this->manager1, [
            'project_id' => $createRes['project_id'],
            'priority' => ProjectPriority::URGENT->value,
        ]);
        $this->assertDatabaseHas('projects', ['id' => $createRes['project_id'], 'priority' => 'urgent']);
    }

    /**
     * T281: Task MCP Tools (task.create, task.update, task.assign, task.complete).
     */
    public function test_t281_task_mcp_tools(): void
    {
        $taskTools = app(TaskMcpTools::class);

        // 1. task.create
        $createRes = $taskTools->create($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Build Authentication Module',
            'task_code' => 'TSK-AUTH-01',
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::TODO->value,
            'estimated_hours' => 12.5,
        ]);
        $this->assertEquals('Build Authentication Module', $createRes['title']);
        $taskId = $createRes['task_id'];

        // 2. task.assign
        $assignRes = $taskTools->assign($this->manager1, [
            'task_id' => $taskId,
            'assigned_to' => $this->employeeUser1->id,
        ]);
        $this->assertEquals($this->employeeUser1->id, $assignRes['assigned_to']);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'assigned_to' => $this->employeeUser1->id]);

        // 3. task.update
        $updateRes = $taskTools->update($this->manager1, [
            'task_id' => $taskId,
            'title' => 'Build Authentication Module & MFA',
        ]);
        $this->assertEquals('Build Authentication Module & MFA', $updateRes['title']);

        // 4. task.complete
        $completeRes = $taskTools->complete($this->employeeUser1, [
            'task_id' => $taskId,
        ]);
        $this->assertEquals('done', $completeRes['status']);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'status' => 'done']);
    }

    /**
     * T282: Timesheet MCP Tools (timesheet.create, timesheet.search).
     */
    public function test_t282_timesheet_mcp_tools(): void
    {
        $timesheetTools = app(TimesheetMcpTools::class);

        // 1. timesheet.create
        $createRes = $timesheetTools->create($this->employeeUser1, [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'period_type' => 'weekly',
            'entries' => [
                [
                    'project_id' => $this->project1->id,
                    'entry_date' => now()->toDateString(),
                    'hours' => 6.5,
                    'is_billable' => true,
                    'description' => 'Developed MCP endpoints',
                ],
            ],
        ]);
        $this->assertEquals(6.5, $createRes['total_hours']);
        $this->assertEquals('draft', $createRes['status']);

        // 2. timesheet.search
        $searchRes = $timesheetTools->search($this->employeeUser1, []);
        $this->assertEquals(1, $searchRes['count']);
        $this->assertEquals($createRes['timesheet_id'], $searchRes['timesheets'][0]['id']);
    }

    /**
     * T283: Restricted Employee Search (Read-Only).
     */
    public function test_t283_restricted_employee_search(): void
    {
        $employeeTools = app(EmployeeMcpTools::class);

        $res = $employeeTools->search($this->manager1, ['search' => 'David']);
        $this->assertEquals(1, $res['count']);

        $employeeData = $res['employees'][0];
        $this->assertEquals('David Developer', $employeeData['name']);
        $this->assertEquals('Engineering', $employeeData['department']);
        $this->assertEquals('Backend Engineer', $employeeData['designation']);

        // CRITICAL: Ensure sensitive fields are NOT present
        $this->assertArrayNotHasKey('monthly_salary', $employeeData);
        $this->assertArrayNotHasKey('daily_salary', $employeeData);
        $this->assertArrayNotHasKey('bank_name', $employeeData);
        $this->assertArrayNotHasKey('bank_account_number', $employeeData);
        $this->assertArrayNotHasKey('ifsc_code', $employeeData);
        $this->assertArrayNotHasKey('attendance_ip', $employeeData);
        $this->assertArrayNotHasKey('leaves', $employeeData);
    }

    /**
     * T284: Schema Validation & Error Resilience.
     */
    public function test_t284_schema_validation_and_error_handling(): void
    {
        $clientTools = app(ClientMcpTools::class);

        // Missing required company_name throws validation error
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $clientTools->create($this->manager1, [
            'status' => 'active',
        ]);
    }
}
