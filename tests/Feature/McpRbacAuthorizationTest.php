<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Role;
use App\Services\AI\McpIntegrationService;
use App\Services\AI\McpToolRegistry;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpRbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function createUserWithRole(UserRole $roleEnum): User
    {
        $user = User::factory()->create(['role' => $roleEnum, 'is_active' => true]);
        $role = Role::where('slug', $roleEnum->value)->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }
        return $user;
    }

    public function test_super_admin_can_access_all_tools()
    {
        $superAdmin = $this->createUserWithRole(UserRole::SUPER_ADMIN);
        $this->actingAs($superAdmin);

        $integrationService = app(McpIntegrationService::class);
        $registry = app(McpToolRegistry::class);

        // Test client.create
        $context = new McpRequestContext(
            user: $superAdmin,
            toolName: 'client.create',
            arguments: ['company_name' => 'Test', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertNotEquals(403, $response->error['code'] ?? null, "Super Admin should not receive 403.");
    }

    public function test_manager_can_access_client_tools()
    {
        $manager = $this->createUserWithRole(UserRole::MANAGER);
        $this->actingAs($manager);

        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $manager,
            toolName: 'client.create',
            arguments: ['company_name' => 'Test', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertNotEquals(403, $response->error['code'] ?? null, "Manager should not receive 403 for client.create.");
    }

    public function test_employee_cannot_access_client_tools()
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $this->actingAs($employee);

        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Test', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertEquals(403, $response->error['code'] ?? null, "Employee should receive 403 for client.create.");
        $this->assertStringContainsString('Unauthorized', $response->error['message'] ?? '');
    }
    
    public function test_employee_can_access_timesheet_create()
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $this->actingAs($employee);

        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $employee,
            toolName: 'timesheet.create',
            arguments: [
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-07',
                'period_type' => 'weekly',
                'entries' => []
            ],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertNotEquals(403, $response->error['code'] ?? null, "Employee should not receive 403 for timesheet.create.");
    }

    public function test_login_endpoint_returns_token()
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $employee->update([
            'username' => 'johndoe',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/mcp/login', [
            'username' => 'johndoe',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user', 'message']);
        
        $this->assertStringStartsWith('mcp_', $response->json('token'));
    }
}
