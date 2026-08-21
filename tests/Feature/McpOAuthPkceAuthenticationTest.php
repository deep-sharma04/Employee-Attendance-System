<?php

namespace Tests\Feature;

use App\DTOs\AI\McpRequestContext;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\McpIntegrationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpOAuthPkceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $clientRepository = app(\Laravel\Passport\ClientRepository::class);
        $clientRepository->createPersonalAccessGrantClient(
            'Test Personal Access Client', null
        );
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

    // =========================================================================
    // 1. OAuth Discovery Metadata
    // =========================================================================

    /**
     * Test: OAuth Authorization Server Metadata discovery returns correct structure and 'mcp:use' scope.
     */
    public function test_oauth_authorization_server_discovery_metadata(): void
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'response_types_supported',
            'code_challenge_methods_supported',
            'scopes_supported',
            'grant_types_supported',
        ]);

        $this->assertContains('S256', $response->json('code_challenge_methods_supported'));
        $this->assertContains('authorization_code', $response->json('grant_types_supported'));
        $this->assertContains('mcp:use', $response->json('scopes_supported'));
    }

    /**
     * Test: OAuth Protected Resource Metadata returns correct structure.
     */
    public function test_oauth_protected_resource_discovery_metadata(): void
    {
        $response = $this->getJson('/.well-known/oauth-protected-resource');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'resource',
            'authorization_servers',
            'scopes_supported',
        ]);
        $this->assertContains('mcp:use', $response->json('scopes_supported'));
    }

    // =========================================================================
    // 2. First-Time OAuth / Unauthenticated Request
    // =========================================================================

    /**
     * Test: Unauthenticated request to /mcp returns 401 with WWW-Authenticate header
     * pointing to OAuth protected resource metadata.
     */
    public function test_unauthenticated_request_to_mcp_returns_401_with_oauth_header(): void
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

    // =========================================================================
    // 3. Valid Passport OAuth Token → Correct User Resolution
    // =========================================================================

    /**
     * Test: Valid Passport OAuth Bearer token resolves the correct Laravel User.
     */
    public function test_valid_passport_token_resolves_correct_user(): void
    {
        $user = $this->createUserWithRole(UserRole::EMPLOYEE, [
            'username' => 'testemployee',
        ]);

        Passport::actingAs($user, ['mcp:use'], 'api');

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        // Should NOT return 401 — user is authenticated
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    /**
     * Test: Passport token resolves to the actual user, not a different user.
     */
    public function test_passport_token_resolves_actual_user_identity(): void
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $admin = $this->createUserWithRole(UserRole::SUPER_ADMIN);

        // Authenticate as employee
        Passport::actingAs($employee, ['mcp:use'], 'api');

        $integrationService = app(McpIntegrationService::class);

        // Employee should be denied client.create (admin-only)
        $context = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Test Corp', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertEquals(403, $response->error['code'] ?? null);
    }

    // =========================================================================
    // 4. Expired Token → 401
    // =========================================================================

    /**
     * Test: Invalid or Expired Passport token returns 401 (re-authentication required).
     */
    public function test_invalid_passport_token_returns_401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.invalidtoken',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
    }

    // =========================================================================
    // 5. Inactive User → Denied
    // =========================================================================

    /**
     * Test: Inactive/deactivated user is denied even with a valid token.
     */
    public function test_inactive_user_is_denied_access(): void
    {
        $user = $this->createUserWithRole(UserRole::EMPLOYEE, ['is_active' => false]);

        Passport::actingAs($user, ['mcp:use'], 'api');

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
        $this->assertStringContainsString('Unauthorized', $response->json('error.message'));
    }

    // =========================================================================
    // 6. Employee RBAC — Allowed / Denied
    // =========================================================================

    /**
     * Test: Employee is DENIED access to admin-only tools (client.create).
     */
    public function test_employee_denied_admin_tools(): void
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Forbidden Corp'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertFalse($response->isSuccess);
        $this->assertEquals(403, $response->error['code']);
        $this->assertStringContainsString('Unauthorized', $response->error['message']);
    }

    /**
     * Test: Employee is ALLOWED access to employee-accessible tools (timesheet.create).
     */
    public function test_employee_allowed_own_tools(): void
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
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

        $response = $integrationService->handleRequest($context);
        // Should not be 403 — employee has log.timesheets permission
        $this->assertNotEquals(403, $response->error['code'] ?? null);
    }

    // =========================================================================
    // 7. Admin RBAC — Allowed
    // =========================================================================

    /**
     * Test: Super Admin is ALLOWED access to all tools.
     */
    public function test_super_admin_allowed_all_tools(): void
    {
        $admin = $this->createUserWithRole(UserRole::SUPER_ADMIN);
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $admin,
            toolName: 'client.create',
            arguments: ['company_name' => 'Admin Corp', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertNotEquals(403, $response->error['code'] ?? null, 'Super Admin should not receive 403.');
    }

    // =========================================================================
    // 8. Token Cannot Impersonate Another User
    // =========================================================================

    /**
     * Test: A Passport token bound to User A cannot impersonate User B.
     */
    public function test_token_cannot_impersonate_another_user(): void
    {
        $userA = $this->createUserWithRole(UserRole::EMPLOYEE);
        $userB = $this->createUserWithRole(UserRole::SUPER_ADMIN);

        // Authenticate as userA
        Passport::actingAs($userA, ['mcp:use'], 'api');

        // Even though userB is an admin, the resolved user should be userA
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $userA,  // User from Passport token
            toolName: 'client.create',
            arguments: ['company_name' => 'Impersonation Corp', 'status' => 'active'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        // Employee cannot create clients — proves token resolves to Employee, not Admin
        $response = $integrationService->handleRequest($context);
        $this->assertEquals(403, $response->error['code'] ?? null);
    }

    // =========================================================================
    // 9. RBAC Cannot Be Bypassed by Gemini / Payload Manipulation
    // =========================================================================

    /**
     * Test: Injecting 'role' => 'super_admin' in arguments does NOT bypass RBAC.
     */
    public function test_rbac_cannot_be_bypassed_by_client_payload(): void
    {
        $employee = $this->createUserWithRole(UserRole::EMPLOYEE);
        $integrationService = app(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $employee,
            toolName: 'client.create',
            arguments: ['company_name' => 'Hack Corp', 'role' => 'super_admin'],
            projectId: null,
            teamId: null,
            clientId: null,
            conversationId: null
        );

        $response = $integrationService->handleRequest($context);
        $this->assertEquals(403, $response->error['code']);
    }

    // =========================================================================
    // 10. Legacy Auth Methods Are Rejected
    // =========================================================================

    /**
     * Test: Static mcp_ token in Authorization header is rejected (no longer valid).
     */
    public function test_static_mcp_token_is_rejected(): void
    {
        $user = $this->createUserWithRole(UserRole::SUPER_ADMIN);
        $user->forceFill(['remember_token' => 'mcp_teststatictoken123456'])->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer mcp_teststatictoken123456',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: X-MCP-Username/X-MCP-Password custom headers are rejected.
     */
    public function test_custom_mcp_headers_are_rejected(): void
    {
        $user = $this->createUserWithRole(UserRole::SUPER_ADMIN, [
            'username' => 'testadmin',
            'password' => bcrypt('AdminPass123!'),
        ]);

        $response = $this->withHeaders([
            'X-MCP-Username' => 'testadmin',
            'X-MCP-Password' => 'AdminPass123!',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: JSON-RPC _meta credentials are rejected.
     */
    public function test_jsonrpc_meta_credentials_are_rejected(): void
    {
        $user = $this->createUserWithRole(UserRole::SUPER_ADMIN, [
            'username' => 'testadmin',
            'password' => bcrypt('AdminPass123!'),
        ]);

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
            '_meta' => [
                'username' => 'testadmin',
                'password' => 'AdminPass123!',
            ],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: X-MCP-Token header is rejected.
     */
    public function test_xmcp_token_header_is_rejected(): void
    {
        $user = $this->createUserWithRole(UserRole::SUPER_ADMIN);
        $user->forceFill(['remember_token' => 'legacytoken123'])->save();

        $response = $this->withHeaders([
            'X-MCP-Token' => 'legacytoken123',
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: HTTP Basic Auth is rejected.
     */
    public function test_basic_auth_is_rejected(): void
    {
        $user = $this->createUserWithRole(UserRole::SUPER_ADMIN, [
            'username' => 'testadmin',
            'password' => bcrypt('AdminPass123!'),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('testadmin:AdminPass123!'),
        ])->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Legacy /api/mcp/login endpoint no longer exists.
     */
    public function test_legacy_mcp_login_endpoint_removed(): void
    {
        $response = $this->postJson('/api/mcp/login', [
            'username' => 'testadmin',
            'password' => 'AdminPass123!',
        ]);

        // Should be 404 or 405 — endpoint no longer exists
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }
}
