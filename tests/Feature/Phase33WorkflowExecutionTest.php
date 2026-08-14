<?php

namespace Tests\Feature;

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
use App\Services\AI\HrmMcpServer;
use App\Services\AI\McpToolRegistry;
use App\Services\AI\Workflow\McpWorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Transport\StdioTransport;
use Tests\TestCase;

class Phase33WorkflowExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager1;
    protected User $manager2;
    protected User $teamLead;
    protected User $employeeUser1;
    protected User $employeeUser2;
    protected User $inactiveUser;
    protected User $clientUser;
    protected Client $client;
    protected Team $team1;
    protected Project $project1;
    protected Project $project2;
    protected Employee $employee1;
    protected Employee $employee2;
    protected Task $task1;
    protected Task $task2;
    protected McpWorkflowExecutionService $workflowService;
    protected McpToolRegistry $toolRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflowService = app(McpWorkflowExecutionService::class);
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

        $this->inactiveUser = User::factory()->create([
            'name' => 'Inactive Ian',
            'email' => 'inactive.ian@test.com',
            'role' => UserRole::EMPLOYEE,
            'is_active' => false,
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
            'name' => 'Project Alpha Core',
            'code' => 'PRJ-ALPHA',
            'client_id' => $this->client->id,
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

        ProjectMember::create([
            'project_id' => $this->project1->id,
            'user_id' => $this->employeeUser2->id,
            'employee_id' => $this->employee2->id,
            'project_role' => ProjectMemberRole::MEMBER,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->project2 = Project::create([
            'name' => 'Project Beta Cloud',
            'code' => 'PRJ-BETA',
            'client_id' => null,
            'team_id' => null,
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
     * T290: AI-Assisted Project & Task Creation.
     */
    public function test_t290_ai_project_and_task_creation(): void
    {
        // 1. Standard Project Creation (Manager)
        $res = $this->workflowService->createProject($this->manager1, [
            'name' => 'Project Gamma AI',
            'code' => 'PRJ-GAMMA-01',
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'budget' => 20000.00,
            'client_id' => $this->client->id,
            'team_id' => $this->team1->id,
        ]);
        $this->assertEquals('created', $res['status']);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-GAMMA-01']);

        // 2. High Budget Project Creation triggers Approval Gate (T292)
        $highBudgetRes = $this->workflowService->createProject($this->manager1, [
            'name' => 'Project Enterprise Delta',
            'code' => 'PRJ-DELTA-01',
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(6)->toDateString(),
            'budget' => 150000.00, // Exceeds 50k threshold
        ]);
        $this->assertEquals('pending_approval', $highBudgetRes['status']);
        $this->assertNotNull($highBudgetRes['action_log_id']);
        $this->assertDatabaseHas('ai_action_logs', [
            'id' => $highBudgetRes['action_log_id'],
            'approval_state' => 'pending_approval',
        ]);

        // 3. Task Creation
        $taskRes = $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Implement Webhooks',
            'task_code' => 'TSK-ALPHA-03',
            'priority' => 'high',
            'status' => 'todo',
            'assigned_to' => $this->employeeUser2->id,
        ]);
        $this->assertEquals('created', $taskRes['status']);
        $this->assertDatabaseHas('tasks', ['task_code' => 'TSK-ALPHA-03']);

        // 4. Unauthorized Task Creation (Manager 1 cannot create tasks under Manager 2's project)
        $this->expectException(\RuntimeException::class);
        $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project2->id,
            'title' => 'Unauthorized Task',
            'task_code' => 'TSK-BETA-01',
        ]);
    }

    /**
     * T291: AI-Assisted Task Assignment (Scope & Eligibility Enforced).
     */
    public function test_t291_ai_task_assignment(): void
    {
        // 1. Assign to valid team member
        $res = $this->workflowService->assignTask($this->manager1, [
            'task_id' => $this->task1->id,
            'assigned_to' => $this->employeeUser2->id,
        ]);
        $this->assertEquals('assigned', $res['status']);
        $this->assertEquals($this->employeeUser2->id, $res['new_assignee_id']);
        $this->assertDatabaseHas('tasks', ['id' => $this->task1->id, 'assigned_to' => $this->employeeUser2->id]);

        // 2. Assigning to inactive user is rejected
        $this->expectException(\InvalidArgumentException::class);
        $this->workflowService->assignTask($this->manager1, [
            'task_id' => $this->task1->id,
            'assigned_to' => $this->inactiveUser->id,
        ]);
    }

    /**
     * T292: Server-Side MCP Approval Gates (List, Approve, Reject).
     */
    public function test_t292_server_side_approval_gates(): void
    {
        // 1. Create a proposal pending approval
        $proposalRes = $this->workflowService->createProject($this->manager1, [
            'name' => 'Project Pending Appr',
            'code' => 'PRJ-APPR-01',
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'requires_approval' => true,
        ]);
        $actionLogId = $proposalRes['action_log_id'];

        // 2. Pending list for Super Admin
        $pendingList = $this->workflowService->getPendingApprovals($this->superAdmin);
        $this->assertGreaterThanOrEqual(1, $pendingList['pending_count']);

        // 3. Super Admin approves proposal
        $approvalRes = $this->workflowService->approveAction($actionLogId, $this->superAdmin);
        $this->assertEquals('approved_and_executed', $approvalRes['status']);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-APPR-01']);
        $this->assertDatabaseHas('ai_action_logs', [
            'id' => $actionLogId,
            'approval_state' => 'approved',
            'execution_status' => 'success',
        ]);

        // 4. Create another proposal and reject it
        $proposal2 = $this->workflowService->createProject($this->manager1, [
            'name' => 'Project To Reject',
            'code' => 'PRJ-REJ-01',
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(2)->toDateString(),
            'requires_approval' => true,
        ]);
        $rejectLogId = $proposal2['action_log_id'];

        $rejectRes = $this->workflowService->rejectAction($rejectLogId, $this->superAdmin, 'Budget constraints for Q3.');
        $this->assertEquals('rejected', $rejectRes['status']);
        $this->assertDatabaseHas('ai_action_logs', [
            'id' => $rejectLogId,
            'approval_state' => 'rejected',
            'rejection_reason' => 'Budget constraints for Q3.',
        ]);

        // 5. Employee CANNOT approve actions
        $this->expectException(\RuntimeException::class);
        $this->workflowService->approveAction($actionLogId, $this->employeeUser1);
    }

    /**
     * T293: Execute Destructive / Bulk MCP Actions (task.bulk_reassign).
     */
    public function test_t293_bulk_task_reassign(): void
    {
        // Manager executes bulk reassign for all active tasks from employee 1 to employee 2
        $res = $this->workflowService->bulkReassignTasks($this->manager1, [
            'from_user_id' => $this->employeeUser1->id,
            'to_user_id' => $this->employeeUser2->id,
            'project_id' => $this->project1->id,
        ]);

        $this->assertEquals('reassigned', $res['status']);
        $this->assertEquals(2, $res['reassigned_count']);

        // Verify tasks in database
        $this->assertEquals($this->employeeUser2->id, $this->task1->fresh()->assigned_to);
        $this->assertEquals($this->employeeUser2->id, $this->task2->fresh()->assigned_to);
    }

    /**
     * T294: Prevent Duplicate MCP Mutations (Idempotency Key).
     */
    public function test_t294_mutation_idempotency(): void
    {
        $idempotencyKey = 'unique_idempotency_key_test_123';

        // 1. Initial creation with idempotency key
        $res1 = $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Idempotent Task',
            'task_code' => 'TSK-IDEMP-01',
        ], $idempotencyKey);

        $this->assertEquals('created', $res1['status']);
        $taskId = $res1['task_id'];

        // 2. Re-invoking with identical idempotency key returns cached result without creating second task
        $res2 = $this->workflowService->createTask($this->manager1, [
            'project_id' => $this->project1->id,
            'title' => 'Idempotent Task',
            'task_code' => 'TSK-IDEMP-01',
        ], $idempotencyKey);

        $this->assertEquals($taskId, $res2['task_id']);
        $this->assertEquals(1, Task::where('task_code', 'TSK-IDEMP-01')->count());
    }

    /**
     * T295: Transaction Safety with Rollback on Error.
     */
    public function test_t295_transactional_safety_and_rollback(): void
    {
        // Attempting to assign to an invalid/inactive user in bulk reassignment rolls back
        try {
            $this->workflowService->bulkReassignTasks($this->manager1, [
                'from_user_id' => $this->employeeUser1->id,
                'to_user_id' => 999999, // Non-existent user
            ]);
            $this->fail('Expected exception for non-existent target user');
        } catch (\Throwable $e) {
            // Assert that none of the tasks were partially updated
            $this->assertEquals($this->employeeUser1->id, $this->task1->fresh()->assigned_to);
            $this->assertEquals($this->employeeUser1->id, $this->task2->fresh()->assigned_to);
        }
    }

    /**
     * MCP Server Tool Discovery for Phase 33.
     */
    public function test_mcp_server_discovers_phase_33_tools(): void
    {
        $server = new HrmMcpServer(new StdioTransport('test-workflow-session'));
        $context = $server->createContext();

        $tools = $context->tools();
        $this->assertGreaterThanOrEqual(21, $tools->count());

        $toolNames = $tools->map(fn ($t) => $t->name())->all();
        $this->assertContains('task.bulk_reassign', $toolNames);
        $this->assertContains('ai.action.pending_list', $toolNames);
        $this->assertContains('ai.action.approve', $toolNames);
        $this->assertContains('ai.action.reject', $toolNames);
    }
}
