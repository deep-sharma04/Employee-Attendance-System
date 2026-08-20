<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RemoteMcpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $employeeUser;
    protected User $clientUser;
    protected User $inactiveUser;

    protected Client $client;
    protected Team $team;
    protected Project $project;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Remote',
            'username' => 'superadmin_remote',
            'email' => 'superadmin_remote@hrm.local',
            'password' => Hash::make('SecretPass123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // Create Manager
        $this->manager = User::factory()->create([
            'name' => 'Manager Remote',
            'username' => 'manager_remote',
            'email' => 'manager_remote@hrm.local',
            'password' => Hash::make('ManagerPass123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        // Create Employee
        $this->employeeUser = User::factory()->create([
            'name' => 'Employee Remote',
            'username' => 'employee_remote',
            'email' => 'employee_remote@hrm.local',
            'password' => Hash::make('EmployeePass123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        // Create Client User
        $this->clientUser = User::factory()->create([
            'name' => 'Client Remote User',
            'username' => 'client_remote',
            'email' => 'client_remote@hrm.local',
            'password' => Hash::make('ClientPass123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        // Create Inactive User
        $this->inactiveUser = User::factory()->create([
            'name' => 'Inactive Remote User',
            'username' => 'inactive_remote',
            'email' => 'inactive_remote@hrm.local',
            'password' => Hash::make('InactivePass123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => false,
        ]);

        // Client setup
        $this->client = Client::create([
            'company_name' => 'Acme Corp Remote',
            'company_code' => 'CLT-ACME-REMOTE',
            'status' => ClientStatus::ACTIVE->value,
            'created_by' => $this->superAdmin->id,
        ]);

        ClientUser::create([
            'client_id' => $this->client->id,
            'user_id' => $this->clientUser->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->team = Team::create([
            'name' => 'Remote Alpha Squad',
            'code' => 'SQUAD-R',
            'manager_id' => $this->manager->id,
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'client_id' => $this->client->id,
            'team_id' => $this->team->id,
            'name' => 'Remote Portal Redesign',
            'code' => 'PRJ-REMOTE-01',
            'status' => ProjectStatus::ACTIVE->value,
            'budget' => 50000.00,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-REMOTE-01',
            'first_name' => 'Remote',
            'last_name' => 'Engineer',
            'email' => 'employee_remote@hrm.local',
            'department' => 'Engineering',
            'designation' => 'Software Engineer',
            'status' => EmployeeStatus::ACTIVE->value,
            'joining_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test unauthenticated MCP request is rejected with 401 Unauthorized.
     */
    public function test_unauthenticated_mcp_request_is_rejected(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', -32001);
    }

    /**
     * Test valid username and password authenticates via HTTP Basic Auth.
     */
    public function test_valid_username_password_authenticates_via_basic_auth(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('manager_remote:ManagerPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 10,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test valid username and password authenticates via custom headers (X-MCP-Username / X-MCP-Password).
     */
    public function test_valid_username_password_authenticates_via_custom_headers(): void
    {
        $response = $this->withHeaders([
            'X-MCP-Username' => 'manager_remote',
            'X-MCP-Password' => 'ManagerPass123!',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 11,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test invalid username is rejected.
     */
    public function test_invalid_username_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('nonexistent_user:ManagerPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 12,
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', -32001);
    }

    /**
     * Test invalid password is rejected.
     */
    public function test_invalid_password_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('manager_remote:WrongPassword999!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 13,
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', -32001);
    }

    /**
     * Test inactive user is rejected.
     */
    public function test_inactive_user_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('inactive_remote:InactivePass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 14,
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', -32001);
    }

    /**
     * Test manager can execute manager-permitted tool (e.g. project.create).
     */
    public function test_manager_can_execute_allowed_project_create_tool(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('manager_remote:ManagerPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'project.create',
                'arguments' => [
                    'client_id' => $this->client->id,
                    'team_id' => $this->team->id,
                    'name' => 'Remote Portal Mobile App',
                    'code' => 'PRJ-REMOTE-MOB',
                    'status' => 'active',
                    'priority' => 'high',
                    'start_date' => '2026-09-01',
                    'deadline' => '2026-12-31',
                    'budget' => 25000.00,
                ],
            ],
            'id' => 20,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-REMOTE-MOB']);
    }

    /**
     * Test employee role CANNOT execute manager-only mutation tools (e.g. project.create).
     */
    public function test_employee_cannot_execute_unauthorized_mutation_tool(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('employee_remote:EmployeePass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'project.create',
                'arguments' => [
                    'client_id' => $this->client->id,
                    'team_id' => $this->team->id,
                    'name' => 'Unauthorized Project Create',
                    'code' => 'PRJ-UNAUTH-01',
                    'status' => 'active',
                    'priority' => 'high',
                    'start_date' => '2026-09-01',
                    'deadline' => '2026-12-31',
                ],
            ],
            'id' => 21,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', true);
        $this->assertDatabaseMissing('projects', ['code' => 'PRJ-UNAUTH-01']);
    }

    /**
     * Test client role remains strictly read-only.
     */
    public function test_client_role_remains_strictly_read_only(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('client_remote:ClientPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'task.create',
                'arguments' => [
                    'project_id' => $this->project->id,
                    'title' => 'Client Unauthorized Task',
                    'task_code' => 'TSK-CLIENT-01',
                ],
            ],
            'id' => 22,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', true);
        $this->assertDatabaseMissing('tasks', ['task_code' => 'TSK-CLIENT-01']);
    }

    /**
     * Test HR/payroll restrictions: employee.search excludes sensitive financial columns.
     */
    public function test_employee_search_excludes_sensitive_financial_information(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('manager_remote:ManagerPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'employee.search',
                'arguments' => [
                    'search' => 'Remote',
                ],
            ],
            'id' => 23,
        ]);

        $response->assertStatus(200);
        $content = json_encode($response->json());
        $this->assertStringNotContainsString('salary', strtolower($content));
        $this->assertStringNotContainsString('bank_account', strtolower($content));
        $this->assertStringNotContainsString('ifsc', strtolower($content));
    }

    /**
     * Test password is never exposed in logs or MCP responses.
     */
    public function test_password_is_never_exposed_in_responses(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('manager_remote:ManagerPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 24,
        ]);

        $content = json_encode($response->json());
        $this->assertStringNotContainsString('ManagerPass123!', $content);
        $this->assertStringNotContainsString('password', strtolower($content));
    }

    /**
     * Test authentication via Bearer token works seamlessly.
     */
    public function test_authentication_via_bearer_token_works(): void
    {
        $token = 'mcp_test_token_manager_' . time();
        $this->manager->forceFill(['remember_token' => $token])->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 25,
        ]);

        $response->assertStatus(200);
    }
}
