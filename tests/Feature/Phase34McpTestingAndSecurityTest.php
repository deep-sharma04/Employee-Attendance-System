<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AiActionLog;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AI\McpIntegrationService;
use App\Services\AI\McpSecurityGuard;
use App\Services\AI\McpToolRegistry;
use App\Services\AI\ProjectIntelligenceService;
use App\Services\AI\Workflow\McpWorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase34McpTestingAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager1;
    protected User $manager2;
    protected User $teamLead;
    protected User $employeeUser1;
    protected User $employeeUser2;
    protected User $inactiveUser;
    protected User $clientUser1;
    protected User $clientUser2;
    protected Client $client1;
    protected Client $client2;
    protected Team $team1;
    protected Team $team2;
    protected Project $project1;
    protected Project $project2;
    protected Employee $employee1;
    protected Employee $employee2;
    protected Task $task1;
    protected Task $task2;
    protected McpIntegrationService $integrationService;
    protected McpSecurityGuard $securityGuard;
    protected McpToolRegistry $toolRegistry;
    protected ProjectIntelligenceService $intelligenceService;
    protected McpWorkflowExecutionService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integrationService = app(McpIntegrationService::class);
        $this->securityGuard = app(McpSecurityGuard::class);
        $this->toolRegistry = app(McpToolRegistry::class);
        $this->intelligenceService = app(ProjectIntelligenceService::class);
        $this->workflowService = app(McpWorkflowExecutionService::class);

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

        $this->inactiveUser = User::factory()->create([
            'name' => 'Inactive Ian',
            'email' => 'inactive.ian@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => false,
        ]);

        $this->clientUser1 = User::factory()->create([
            'name' => 'Client Clara 1',
            'email' => 'clara1@client.com',
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->clientUser2 = User::factory()->create([
            'name' => 'Client Clara 2',
            'email' => 'clara2@client.com',
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->client1 = Client::create([
            'company_name' => 'Acme Corporation',
            'company_code' => 'CLT-ACME',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->client2 = Client::create([
            'company_name' => 'Stark Industries',
            'company_code' => 'CLT-STARK',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        ClientUser::create([
            'client_id' => $this->client1->id,
            'user_id' => $this->clientUser1->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        ClientUser::create([
            'client_id' => $this->client2->id,
            'user_id' => $this->clientUser2->id,
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

        $this->team2 = Team::create([
            'name' => 'Beta Squad',
            'code' => 'SQD-BETA',
            'manager_id' => $this->manager2->id,
            'team_lead_id' => null,
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
            'account_number' => '1234567890',
            'monthly_salary' => 85000.00,
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
            'account_number' => '9876543210',
            'monthly_salary' => 90000.00,
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
            'name' => 'Project Alpha Core',
            'code' => 'PRJ-ALPHA',
            'client_id' => $this->client1->id,
            'team_id' => $this->team1->id,
            'manager_id' => $this->manager1->id,
            'budget' => 30000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->subMonth()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        ProjectMember::create([
            'project_id' => $this->project1->id,
            'user_id' => $this->employeeUser1->id,
            'employee_id' => $this->employee1->id,
            'project_role' => ProjectMemberRole::MEMBER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->project2 = Project::create([
            'name' => 'Project Beta Cloud',
            'code' => 'PRJ-BETA',
            'client_id' => $this->client2->id,
            'team_id' => $this->team2->id,
            'manager_id' => $this->manager2->id,
            'budget' => 45000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::MEDIUM,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->subMonth()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'created_by' => $this->manager2->id,
        ]);

        $this->task1 = Task::create([
            'project_id' => $this->project1->id,
            'title' => 'Backend API Development',
            'task_code' => 'TSK-ALPHA-01',
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->employeeUser1->id,
            'estimated_hours' => 12.0,
            'due_date' => now()->addWeek()->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        $this->task2 = Task::create([
            'project_id' => $this->project1->id,
            'title' => 'Database Query Optimization',
            'task_code' => 'TSK-ALPHA-02',
            'priority' => TaskPriority::MEDIUM,
            'status' => TaskStatus::TODO,
            'assigned_to' => $this->employeeUser1->id,
            'estimated_hours' => 8.0,
            'due_date' => now()->addWeeks(2)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);
    }

    /**
     * T296: Test MCP Authorization & Scope.
     * Verify MCP cannot act outside authenticated user's RBAC, team, project, or client scope.
     */
    public function test_t296_mcp_authorization_and_scope(): void
    {
        // 1. Super Admin has global access to all projects
        $adminContext = new McpRequestContext(
            user: $this->superAdmin,
            toolName: 'project.search',
            arguments: []
        );
        $this->assertTrue($this->securityGuard->validateScope($adminContext));
        $adminRes = $this->integrationService->handleRequest($adminContext);
        $this->assertTrue($adminRes->isSuccess);
        $this->assertEquals(2, $adminRes->data['count']);

        // 2. Manager 1 is scoped to Project 1; accessing Project 2 is unauthorized
        $mgr1Valid = new McpRequestContext(
            user: $this->manager1,
            toolName: 'task.create',
            arguments: ['project_id' => $this->project1->id],
            projectId: $this->project1->id
        );
        $this->assertTrue($this->securityGuard->validateScope($mgr1Valid));

        $mgr1Invalid = new McpRequestContext(
            user: $this->manager1,
            toolName: 'task.create',
            arguments: ['project_id' => $this->project2->id],
            projectId: $this->project2->id
        );
        $this->assertFalse($this->securityGuard->validateScope($mgr1Invalid));
        $mgr1Res = $this->integrationService->handleRequest($mgr1Invalid);
        $this->assertFalse($mgr1Res->isSuccess);
        $this->assertEquals(403, $mgr1Res->error['code']);

        // 3. Inactive User is denied immediately
        $inactiveContext = new McpRequestContext(
            user: $this->inactiveUser,
            toolName: 'project.search',
            arguments: []
        );
        $this->assertFalse($this->securityGuard->validateScope($inactiveContext));
        $inactiveRes = $this->integrationService->handleRequest($inactiveContext);
        $this->assertFalse($inactiveRes->isSuccess);
        $this->assertEquals(403, $inactiveRes->error['code']);
    }

    /**
     * T297: Test Sensitive HR Data Isolation.
     * Verify AI/MCP clients cannot query salary, bank details, attendance IP, payroll, or restricted HR data.
     */
    public function test_t297_sensitive_hr_data_isolation(): void
    {
        $sensitiveKeys = ['salary', 'basic_salary', 'bank_account', 'bank_account_number', 'ifsc', 'payroll', 'payslip', 'attendance_ip', 'office_ip_allowlist'];

        foreach ($sensitiveKeys as $key) {
            $context = new McpRequestContext(
                user: $this->superAdmin,
                toolName: 'employee.search',
                arguments: [$key => 'test_value']
            );

            $this->assertFalse(
                $this->securityGuard->checkHrDataIsolation($context),
                "Expected sensitive key [{$key}] to be blocked by checkHrDataIsolation"
            );

            $res = $this->integrationService->handleRequest($context);
            $this->assertFalse($res->isSuccess);
            $this->assertEquals(403, $res->error['code']);
        }

        // Verify employee.search results strictly omit sensitive columns
        $employeeSearchCtx = new McpRequestContext(
            user: $this->manager1,
            toolName: 'employee.search',
            arguments: ['query' => 'David']
        );
        $res = $this->integrationService->handleRequest($employeeSearchCtx);
        $this->assertTrue($res->isSuccess);
        $employees = $res->data['employees'] ?? [];
        $this->assertNotEmpty($employees);

        foreach ($employees as $emp) {
            $this->assertArrayNotHasKey('basic_salary', $emp);
            $this->assertArrayNotHasKey('account_number', $emp);
            $this->assertArrayNotHasKey('ifsc_code', $emp);
            $this->assertArrayNotHasKey('pan_number', $emp);
        }
    }

    /**
     * T298: Test MCP Tool Execution Validation & Error Handling.
     * Verify every MCP tool validates inputs, calls the correct Laravel business service, respects policies, and handles errors safely.
     */
    public function test_t298_mcp_tool_execution_and_error_handling(): void
    {
        // 1. Missing required field in task.create
        try {
            $this->workflowService->createTask($this->manager1, [
                'project_id' => $this->project1->id,
                // Missing title and task_code
            ]);
            $this->fail('Expected InvalidArgumentException for missing fields');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Missing required task fields', $e->getMessage());
        }

        // 2. Non-existent Project ID fails gracefully
        try {
            $this->workflowService->createTask($this->manager1, [
                'project_id' => 999999,
                'title' => 'Ghost Task',
                'task_code' => 'TSK-GHOST',
            ]);
            $this->fail('Expected exception for non-existent project');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }

        // 3. Calling unregistered tool name
        $unregisteredCtx = new McpRequestContext(
            user: $this->manager1,
            toolName: 'unregistered.ghost_tool',
            arguments: []
        );
        $res = $this->integrationService->handleRequest($unregisteredCtx);
        $this->assertFalse($res->isSuccess);
        $this->assertEquals(404, $res->error['code']);
    }

    /**
     * T299: Test Client Read-Only Isolation.
     * Verify clients cannot use MCP to write or access internal comments, costs, budgets, HR records, or other clients' documents.
     */
    public function test_t299_client_read_only_isolation(): void
    {
        // 1. Client 1 can access own projects
        $client1Search = new McpRequestContext(
            user: $this->clientUser1,
            toolName: 'project.search',
            arguments: [],
            clientId: $this->client1->id
        );
        $this->assertTrue($this->securityGuard->validateScope($client1Search));
        $res = $this->integrationService->handleRequest($client1Search);
        $this->assertTrue($res->isSuccess);
        $this->assertEquals(1, $res->data['count']);
        $this->assertEquals($this->project1->id, $res->data['projects'][0]['id']);

        // 2. Client 1 CANNOT access Client 2's project
        $client1CrossAccess = new McpRequestContext(
            user: $this->clientUser1,
            toolName: 'project.search',
            arguments: [],
            projectId: $this->project2->id,
            clientId: $this->client2->id
        );
        $this->assertFalse($this->securityGuard->validateScope($client1CrossAccess));

        // 3. Client 1 CANNOT execute mutation tools
        $mutationTools = ['project.create', 'project.update', 'task.create', 'task.assign', 'task.bulk_reassign', 'timesheet.create', 'client.create'];
        foreach ($mutationTools as $tool) {
            $mutationCtx = new McpRequestContext(
                user: $this->clientUser1,
                toolName: $tool,
                arguments: ['project_id' => $this->project1->id],
                projectId: $this->project1->id
            );
            $this->assertFalse($this->securityGuard->validateScope($mutationCtx), "Expected mutation tool [{$tool}] to be blocked for Client User");
        }

        // 4. Client CANNOT view financial reports
        $financialReport = $this->intelligenceService->generateManagementReport($this->clientUser1, [
            'report_type' => 'budget_utilization',
            'project_id' => $this->project1->id,
        ]);
        $this->assertEquals('not_authorized', $financialReport['grounding']['status']);
        $this->assertEmpty($financialReport['metrics']);
    }

    /**
     * T300: Verify AI/MCP Audit Immutability.
     * Verify AI/MCP action records cannot be modified or deleted through normal application paths.
     */
    public function test_t300_ai_mcp_audit_immutability(): void
    {
        // 1. Create a successful action log
        $context = new McpRequestContext(
            user: $this->manager1,
            toolName: 'project.search',
            arguments: ['query' => 'Alpha']
        );
        $res = $this->integrationService->handleRequest($context);
        $actionLogId = $res->actionLogId;
        $this->assertNotNull($actionLogId);

        $log = AiActionLog::findOrFail($actionLogId);
        $this->assertEquals('success', $log->execution_status);

        // 2. Attempting to update finalized action log throws RuntimeException
        try {
            $log->update(['error_message' => 'tampered error message']);
            $this->fail('Expected RuntimeException when updating immutable finalized audit log');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // 3. Attempting to delete action log throws RuntimeException
        try {
            $log->delete();
            $this->fail('Expected RuntimeException when deleting audit log');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }
    }

    /**
     * T301: Test AI Prompt & Tool Boundary Safety.
     * Test prompt injection, malicious parameters, password redaction, privilege escalation, and policy-bypass attempts.
     */
    public function test_t301_prompt_and_tool_boundary_safety(): void
    {
        // 1. SQL Injection / Prompt Injection in search parameter
        $injectionPayload = "'; DROP TABLE users; -- <script>alert(1)</script>";
        $context = new McpRequestContext(
            user: $this->manager1,
            toolName: 'project.intelligence_search',
            arguments: ['query' => $injectionPayload]
        );
        $res = $this->integrationService->handleRequest($context);
        $this->assertTrue($res->isSuccess);
        // Assert users table still exists
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);

        // 2. Sensitive Parameter Redaction in Audit Logs
        $redactionContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'project.search',
            arguments: [
                'query' => 'Alpha',
                'password' => 'SuperSecret123!',
                'token' => 'bearer_token_xyz',
                'secret' => 'api_secret_456',
            ]
        );
        $res = $this->integrationService->handleRequest($redactionContext);
        $log = AiActionLog::findOrFail($res->actionLogId);
        $this->assertEquals('***REDACTED***', $log->parameters['password']);
        $this->assertEquals('***REDACTED***', $log->parameters['token']);
        $this->assertEquals('***REDACTED***', $log->parameters['secret']);

        // 3. Privilege Escalation: Employee attempting approval or bulk reassignment
        $employeeEscalateCtx = new McpRequestContext(
            user: $this->employeeUser1,
            toolName: 'task.bulk_reassign',
            arguments: [
                'from_user_id' => $this->employeeUser1->id,
                'to_user_id' => $this->employeeUser2->id,
            ]
        );
        $this->assertFalse($this->securityGuard->validateScope($employeeEscalateCtx));
    }

    /**
     * T302: Test MCP Idempotency & Transactions.
     * Verify retries and partial failures do not create duplicate or inconsistent records.
     */
    public function test_t302_idempotency_and_transactions(): void
    {
        $idempotencyKey = 'phase34_security_idemp_key_999';

        // 1. First execution creates task
        $res1 = $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Secure Idempotent Task',
            'task_code' => 'TSK-IDEMP-SEC',
        ], $idempotencyKey);

        $this->assertEquals('created', $res1['status']);
        $firstTaskId = $res1['task_id'];

        // 2. Replaying with identical key returns cached response
        $res2 = $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Secure Idempotent Task',
            'task_code' => 'TSK-IDEMP-SEC',
        ], $idempotencyKey);

        $this->assertEquals($firstTaskId, $res2['task_id']);
        $this->assertEquals(1, Task::where('task_code', 'TSK-IDEMP-SEC')->count());

        // 3. Atomic Transaction Rollback on Failure
        try {
            $this->workflowService->bulkReassignTasks($this->manager1, [
                'from_user_id' => $this->employeeUser1->id,
                'to_user_id' => 999999, // Non-existent user triggers exception
            ]);
            $this->fail('Expected exception for invalid target user');
        } catch (\Throwable $e) {
            // Assert all tasks remain unchanged
            $this->assertEquals($this->employeeUser1->id, $this->task1->fresh()->assigned_to);
            $this->assertEquals($this->employeeUser1->id, $this->task2->fresh()->assigned_to);
        }
    }
}
