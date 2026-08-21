<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\McpIntegrationService;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class McpOAuthPkceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function createUserWithRole(UserRole $roleEnum, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $roleEnum,
            'is_active' => true,
        ], $attributes));

        $role = Role::where('slug', $roleEnum->value)->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        return $user;
    }

    /**
     * Test 1: OAuth Authorization Server Metadata discovery endpoint (.well-known)
     */
    public function test_oauth_authorization_server_discovery_metadata()
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'response_types_supported',
            'code_challenge_methods_supported',
            'grant_types_supported',
        ]);

        $this->assertContains('S256', $response->json('code_challenge_methods_supported'));
        $this->assertContains('authorization_code', $response->json('grant_types_supported'));
    }

    /**
     * Test 2: OAuth Protected Resource Metadata discovery endpoint
     */
    public function test_oauth_protected_resource_discovery_metadata()
    {
        $response = $this->getJson('/.well-known/oauth-protected-resource');

        $response->assertStatus(200);
        $response->assertJsonStructure(['resource']);
    }

    /**
     * Test 3: First-time OAuth login page redirection when unauthenticated
     */
    public function test_unauthenticated_request_to_mcp_returns_401_with_oauth_header()
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate');
        $this->assertStringContainsString('resource_metadata', $response->headers->get('WWW-Authenticate'));
    }

    /**
     * Test 4: Valid credential authentication generates secure 8-hour token
     */
    public function test_valid_credentials_returns_session_token()
    {
        $user = $this->createUserWithRole(UserRole::EMPLOYEE, [
            'username' => 'testemployee',
            'password' => bcrypt('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/mcp/login', [
            'username' => 'testemployee',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user', 'message']);
        $this->assertStringStartsWith('mcp_', $response->json('token'));
        $this->assertEquals($user->id, $response->json('user.id'));
    }

    /**
     * Test 5: Invalid credentials fail authentication with 401
     */
    public function test_invalid_credentials_rejected()
    {
        $this->createUserWithRole(UserRole::EMPLOYEE, [
            'username' => 'testemployee',
            'password' => bcrypt('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/mcp/login', [
            'username' => 'testemployee',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthorized: Invalid username or password.']);
    }

    /**
     * Test 6: Bearer token correctly resolves actual Laravel User
     */
    public function test_bearer_token_resolves_actual_laravel_user()
    {
        $user = $this->createUserWithRole(UserRole::MANAGER);
        $token = app(\App\Services\AI\McpAuthService::class)->generateTokenForUser($user);

        $resolvedUser = app(\App\Services\AI\McpAuthService::class)->authenticateByToken($token);

        $this->assertNotNull($resolvedUser);
        $this->assertEquals($user->id, $resolvedUser->id);
        $this->assertEquals(UserRole::MANAGER, $resolvedUser->role);
    }

    /**
     * Test 7: Inactive / deactivated user is immediately denied even with valid token
     */
    public function test_inactive_user_is_denied_access()
    {
        $user = $this->createUserWithRole(UserRole::EMPLOYEE, ['is_active' => false]);
        $token = app(\App\Services\AI\McpAuthService::class)->generateTokenForUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
        $this->assertStringContainsString('Unauthorized', $response->json('error.message'));
    }

    /**
     * Test 8: Employee role is allowed for allowed tools and denied for restricted tools
     */
    public function test_employee_rbac_permissions()
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $integrationService = app(McpIntegrationService::class);

        // Employee attempting client.create -> DENIED (403)
        $deniedContext = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Forbidden Corp'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $deniedResponse = $integrationService->handleRequest($deniedContext);
        $this->assertFalse($deniedResponse->isSuccess);
        $this->assertEquals(403, $deniedResponse->error['code']);
        $this->assertStringContainsString('Unauthorized', $deniedResponse->error['message']);

        // Employee attempting timesheet.create -> ALLOWED (Not 403)
        $allowedContext = new McpRequestContext(
            user: $employee,
            toolName: 'timesheet.create',
            arguments: [
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-07',
                'period_type' => 'weekly',
                'entries' => [],
            ],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $allowedResponse = $integrationService->handleRequest($allowedContext);
        $this->assertNotEquals(403, $allowedResponse->error['code'] ?? null);
    }

    /**
     * Test 9: Admin role is allowed according to existing permissions
     */
    public function test_super_admin_rbac_permissions()
    {
        $admin = $this->createUserWithRole(UserRole::SUPER_ADMIN);
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $admin,
            toolName: 'client.create',
            arguments: ['company_name' => 'Admin Allowed Corp', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertNotEquals(403, $response->error['code'] ?? null, "Super Admin should not receive 403.");
    }

    /**
     * Test 10: Token cannot impersonate another user; user is resolved from token
     */
    public function test_token_cannot_impersonate_another_user()
    {
        $userA = $this->createUserWithRole(UserRole::EMPLOYEE);
        $userB = $this->createUserWithRole(UserRole::MANAGER);

        $tokenA = app(\App\Services\AI\McpAuthService::class)->generateTokenForUser($userA);

        // Request user context comes from token, not payload arguments
        $resolvedUser = app(\App\Services\AI\McpAuthService::class)->authenticateByToken($tokenA);
        $this->assertEquals($userA->id, $resolvedUser->id);
        $this->assertNotEquals($userB->id, $resolvedUser->id);
    }

    /**
     * Test 11: RBAC cannot be bypassed by client payload manipulation
     */
    public function test_rbac_cannot_be_bypassed_by_client_payload()
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $integrationService = app(McpIntegrationService::class);

        // Even if client injects 'role' => 'super_admin' in arguments payload
        $spoofedContext = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Hack Corp', 'role' => 'super_admin'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($spoofedContext);
        $this->assertEquals(403, $response->error['code']);
    }
}
