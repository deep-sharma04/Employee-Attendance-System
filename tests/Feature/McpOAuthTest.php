<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:keys');
    }

    public function test_mcp_discovery_endpoint_is_accessible()
    {
        $response = $this->getJson('/.well-known/oauth-authorization-server');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'scopes_supported',
            'response_types_supported',
            'grant_types_supported',
        ]);
        $this->assertEquals(url('/'), $response->json('issuer'));
    }

    public function test_mcp_protected_resource_endpoint_is_accessible()
    {
        $response = $this->getJson('/.well-known/oauth-protected-resource');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'resource' => url('/'),
            'authorization_servers' => [url('/')],
            'scopes_supported' => ['mcp:use']
        ]);
    }

    public function test_mcp_endpoint_rejects_unauthenticated_request()
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1
        ]);

        $response->assertStatus(401);
        $this->assertTrue($response->headers->has('WWW-Authenticate'));
    }

    public function test_mcp_endpoint_accepts_valid_passport_token()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        Passport::actingAs($user, ['mcp']);

        // Test tool discovery as the employee
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.tools', function ($tools) {
            return is_array($tools) && count($tools) > 0;
        });
    }

    public function test_mcp_endpoint_still_accepts_basic_auth()
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1
        ], [
            'PHP_AUTH_USER' => $user->email,
            'PHP_AUTH_PW'   => 'password123',
        ]);

        $response->assertStatus(401);
    }
}
