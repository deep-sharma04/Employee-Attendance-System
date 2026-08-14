<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\AiActionLog;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AI\McpIntegrationService;
use App\Services\AI\McpSecurityGuard;
use App\Services\AI\McpUsagePolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase30AiMcpFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager1;
    protected User $manager2;
    protected User $teamLead;
    protected User $employeeUser;
    protected User $clientUser;
    protected Client $client;
    protected Project $project1;
    protected Project $project2;
    protected Team $team1;
    protected McpIntegrationService $mcpService;
    protected McpSecurityGuard $securityGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityGuard = new McpSecurityGuard();
        $this->mcpService = new McpIntegrationService($this->securityGuard, new McpUsagePolicyService());

        $this->superAdmin = User::factory()->create([
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

        $this->employeeUser = User::factory()->create([
            'name' => 'Dev David',
            'email' => 'dev.david@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->clientUser = User::factory()->create([
            'name' => 'Client Clara',
            'email' => 'client.clara@test.com',
            'role' => UserRole::CLIENT,
            'is_active' => true,
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
            'code' => 'SQD-A',
            'manager_id' => $this->manager1->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-DEV-001',
            'first_name' => 'David',
            'last_name' => 'Developer',
            'email' => $this->employeeUser->email,
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'status' => EmployeeStatus::ACTIVE,
            'joining_date' => now()->subYear(),
            'monthly_salary' => 4000.00,
        ]);

        TeamMember::create([
            'team_id' => $this->team1->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => $employee->id,
            'is_primary' => true,
            'joined_at' => now(),
        ]);

        $this->project1 = Project::create([
            'name' => 'Alpha Core Redesign',
            'code' => 'PRJ-ALPHA',
            'client_id' => $this->client->id,
            'team_id' => $this->team1->id,
            'manager_id' => $this->manager1->id,
            'budget' => 20000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'created_by' => $this->manager1->id,
        ]);

        $this->project2 = Project::create([
            'name' => 'Beta Cloud Migration',
            'code' => 'PRJ-BETA',
            'client_id' => null,
            'team_id' => null,
            'manager_id' => $this->manager2->id,
            'budget' => 35000.00,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::MEDIUM,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(4)->toDateString(),
            'created_by' => $this->manager2->id,
        ]);
    }

    /**
     * T267: Database Schema and Eloquent Relationships.
     */
    public function test_t267_ai_mcp_database_schema_and_model_relations(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->manager1->id,
            'project_id' => $this->project1->id,
            'client_id' => $this->client->id,
            'title' => 'Project Timeline Analysis',
            'metadata' => ['ide' => 'vscode', 'agent' => 'copilot'],
        ]);

        $this->assertDatabaseHas('ai_conversations', [
            'id' => $conversation->id,
            'title' => 'Project Timeline Analysis',
            'user_id' => $this->manager1->id,
        ]);

        $message = AiMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $this->manager1->id,
            'role' => 'user',
            'content' => 'What is the current health status of Alpha Core Redesign?',
        ]);

        $this->assertEquals($conversation->id, $message->conversation->id);
        $this->assertCount(1, $conversation->messages);

        $actionLog = AiActionLog::create([
            'conversation_id' => $conversation->id,
            'user_id' => $this->manager1->id,
            'project_id' => $this->project1->id,
            'tool_name' => 'project.get_health',
            'action_type' => 'query',
            'parameters' => ['project_id' => $this->project1->id],
            'approval_state' => 'not_required',
            'execution_status' => 'success',
            'execution_result' => ['health' => 'good'],
        ]);

        $this->assertEquals($this->manager1->id, $actionLog->user->id);
        $this->assertEquals($this->project1->id, $actionLog->project->id);
        $this->assertCount(1, $conversation->actionLogs);
    }

    /**
     * T268 & T269: MCP Integration Abstraction without External LLM Calls.
     */
    public function test_t268_t269_mcp_integration_abstraction_without_llm_calls(): void
    {
        // Register a mock MCP tool handler
        $this->mcpService->registerToolHandler('mock.get_project_summary', function (McpRequestContext $ctx) {
            return [
                'project_name' => 'Alpha Core Redesign',
                'status' => 'active',
                'executed_by' => $ctx->user->name,
            ];
        });

        $context = new McpRequestContext(
            user: $this->manager1,
            toolName: 'mock.get_project_summary',
            arguments: ['project_id' => $this->project1->id],
            projectId: $this->project1->id
        );

        $response = $this->mcpService->handleRequest($context);

        $this->assertTrue($response->isSuccess);
        $this->assertEquals('Alpha Core Redesign', $response->data['project_name']);
        $this->assertEquals('Manager Alpha', $response->data['executed_by']);

        // Assert unregistered tool returns structured 404 error safely
        $unknownContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'unknown.nonexistent_tool',
            arguments: []
        );

        $unknownResponse = $this->mcpService->handleRequest($unknownContext);
        $this->assertFalse($unknownResponse->isSuccess);
        $this->assertEquals(404, $unknownResponse->error['code']);
    }

    /**
     * T270: Strict User, Project, Team, and Client Scope Enforcement (Fail-Closed).
     */
    public function test_t270_strict_user_project_team_client_scope_enforcement(): void
    {
        $this->mcpService->registerToolHandler('project.inspect', fn ($ctx) => ['ok' => true]);

        // 1. Manager1 attempting to inspect Manager2's project -> MUST BE REJECTED
        $outOfScopeContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'project.inspect',
            arguments: ['project_id' => $this->project2->id],
            projectId: $this->project2->id
        );

        $response = $this->mcpService->handleRequest($outOfScopeContext);
        $this->assertFalse($response->isSuccess);
        $this->assertEquals(403, $response->error['code']);

        // 2. Client attempting to execute an internal mutation -> MUST BE REJECTED
        $clientMutationContext = new McpRequestContext(
            user: $this->clientUser,
            toolName: 'task.create',
            arguments: ['title' => 'Illegal Task'],
            clientId: $this->client->id
        );

        $clientResponse = $this->mcpService->handleRequest($clientMutationContext);
        $this->assertFalse($clientResponse->isSuccess);
        $this->assertEquals(403, $clientResponse->error['code']);

        // 3. Super Admin has global scope -> ALLOWED
        $adminContext = new McpRequestContext(
            user: $this->superAdmin,
            toolName: 'project.inspect',
            arguments: ['project_id' => $this->project2->id],
            projectId: $this->project2->id
        );

        $adminResponse = $this->mcpService->handleRequest($adminContext);
        $this->assertTrue($adminResponse->isSuccess);
    }

    /**
     * T271: Strict HR and Payroll Data Isolation.
     */
    public function test_t271_strict_hr_and_payroll_data_isolation(): void
    {
        $this->mcpService->registerToolHandler('employee.get_details', fn ($ctx) => ['details' => 'ok']);

        // Prohibited keywords in tool name
        $salaryToolContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'payroll.get_salary',
            arguments: []
        );
        $response = $this->mcpService->handleRequest($salaryToolContext);
        $this->assertFalse($response->isSuccess);
        $this->assertEquals(403, $response->error['code']);
        $this->assertStringContainsString('sensitive HR/Payroll data is strictly prohibited', $response->error['message']);

        // Prohibited keywords in arguments
        $bankDetailsContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'employee.get_details',
            arguments: ['include_fields' => ['bank_account_number', 'ifsc_code']]
        );
        $bankResponse = $this->mcpService->handleRequest($bankDetailsContext);
        $this->assertFalse($bankResponse->isSuccess);
        $this->assertEquals(403, $bankResponse->error['code']);
    }

    /**
     * T272: Approval Flow Foundation (Pending Approval, Approve, Reject).
     */
    public function test_t272_server_side_approval_state_machine(): void
    {
        $this->mcpService->registerToolHandler('task.bulk_reassign', function (McpRequestContext $ctx) {
            return ['reassigned_count' => 5];
        });

        // 1. Sensitive tool execution requests approval instead of immediately executing
        $requestContext = new McpRequestContext(
            user: $this->manager1,
            toolName: 'task.bulk_reassign',
            arguments: ['from_user' => 10, 'to_user' => 12],
            projectId: $this->project1->id
        );

        $response = $this->mcpService->handleRequest($requestContext);
        $this->assertTrue($response->requiresApproval);
        $this->assertNotNull($response->actionLogId);

        $actionLog = AiActionLog::find($response->actionLogId);
        $this->assertTrue($actionLog->isPendingApproval());

        // 2. Team Lead cannot approve -> MUST BE REJECTED
        $teamLeadApproval = $this->mcpService->approveAction($actionLog->id, $this->teamLead);
        $this->assertFalse($teamLeadApproval->isSuccess);
        $this->assertEquals(403, $teamLeadApproval->error['code']);

        // 3. Authorized Manager approves -> PROCEEDS TO SUCCESS
        $managerApproval = $this->mcpService->approveAction($actionLog->id, $this->manager1);
        $this->assertTrue($managerApproval->isSuccess);
        $this->assertEquals(5, $managerApproval->data['reassigned_count']);

        $actionLog->refresh();
        $this->assertTrue($actionLog->isApproved());
        $this->assertEquals('success', $actionLog->execution_status);
        $this->assertEquals($this->manager1->id, $actionLog->approved_by);
    }

    /**
     * T273: Immutable Action Audit Logging and Sanitization.
     */
    public function test_t273_immutable_audit_logging_and_sanitization(): void
    {
        $this->mcpService->registerToolHandler('user.test_action', fn ($ctx) => ['status' => 'done']);

        $context = new McpRequestContext(
            user: $this->manager1,
            toolName: 'user.test_action',
            arguments: [
                'name' => 'John',
                'password' => 'SuperSecret123',
                'token' => 'bearer-abc-token',
            ],
            projectId: $this->project1->id
        );

        $response = $this->mcpService->handleRequest($context);
        $this->assertTrue($response->isSuccess);

        $log = AiActionLog::find($response->actionLogId);
        $this->assertNotNull($log);
        $this->assertEquals('John', $log->parameters['name']);
        $this->assertEquals('***REDACTED***', $log->parameters['password']);
        $this->assertEquals('***REDACTED***', $log->parameters['token']);

        // Verify immutability: attempting to delete throws RuntimeException
        $this->expectException(\RuntimeException::class);
        $log->delete();
    }

    /**
     * T274: Failure / Retry Foundation and Idempotency.
     */
    public function test_t274_failure_retry_and_idempotency(): void
    {
        $executionCount = 0;

        $this->mcpService->registerToolHandler('counter.increment', function ($ctx) use (&$executionCount) {
            $executionCount++;
            return ['count' => $executionCount];
        });

        $context1 = new McpRequestContext(
            user: $this->manager1,
            toolName: 'counter.increment',
            arguments: [],
            projectId: $this->project1->id,
            idempotencyKey: 'IDEMP-KEY-TEST-001'
        );

        // First execution: should execute handler
        $resp1 = $this->mcpService->handleRequest($context1);
        $this->assertTrue($resp1->isSuccess);
        $this->assertEquals(1, $resp1->data['count']);
        $this->assertEquals(1, $executionCount);

        // Duplicate execution with same idempotency key: returns cached result without re-executing
        $resp2 = $this->mcpService->handleRequest($context1);
        $this->assertTrue($resp2->isSuccess);
        $this->assertEquals(1, $resp2->data['count']);
        $this->assertEquals(1, $executionCount); // Handler was NOT run a second time
    }

    /**
     * T275: V1 Usage Policy (No rate limiting blocking normal operation).
     */
    public function test_t275_v1_usage_policy_configuration(): void
    {
        $policyService = new McpUsagePolicyService();
        $this->assertFalse($policyService->isRateLimitEnforced());

        $context = new McpRequestContext(user: $this->manager1, toolName: 'test.tool');
        $this->assertTrue($policyService->checkUsagePolicy($context));
    }
}
