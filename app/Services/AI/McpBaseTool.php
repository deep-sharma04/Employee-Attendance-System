<?php

namespace App\Services\AI;

use App\DTOs\AI\McpRequestContext;
use App\Models\User;
use Illuminate\Container\Container;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class McpBaseTool extends Tool
{
    abstract public function toolName(): string;
    abstract public function toolDescription(): string;

    public function name(): string
    {
        return $this->toolName();
    }

    public function description(): string
    {
        return $this->toolDescription();
    }

    /**
     * Resolve the authenticated user from the active request context.
     */
    protected function resolveUser(McpRequest $request): ?User
    {
        // 1. Direct Laravel Auth
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            return $user->is_active ? $user : null;
        }

        // 2. Resolve via McpAuthService from HttpRequest or meta
        $container = Container::getInstance();
        $authService = $container->make(McpAuthService::class);

        // Check if token was passed in meta or environment
        $meta = $request->meta();
        $token = $meta['auth_token'] ?? $meta['token'] ?? null;

        if (!$token && $container->bound('request')) {
            /** @var HttpRequest $httpRequest */
            $httpRequest = $container->make('request');
            $user = $authService->resolveUser($httpRequest);
            if ($user) {
                return $user;
            }
        }

        if ($token) {
            return $authService->authenticateByToken((string) $token);
        }

        // 3. Fallback to bound MCP authenticated user if set by stdio server
        if ($container->bound('mcp.authenticated_user')) {
            return $container->make('mcp.authenticated_user');
        }

        return null;
    }

    /**
     * Handle the incoming MCP tool invocation.
     */
    public function handle(McpRequest $request): Response|ResponseFactory
    {
        $user = $this->resolveUser($request);

        if (!$user) {
            return Response::error('Unauthorized: Unauthenticated MCP request. Please provide valid authentication credentials.');
        }

        if (!$user->is_active) {
            return Response::error('Unauthorized: Account is inactive.');
        }

        $container = Container::getInstance();
        $registry = $container->make(McpToolRegistry::class);
        $integrationService = $container->make(McpIntegrationService::class);

        $context = new McpRequestContext(
            user: $user,
            toolName: $this->toolName(),
            arguments: $request->all(),
            projectId: $request->get('project_id') ? (int) $request->get('project_id') : null,
            teamId: $request->get('team_id') ? (int) $request->get('team_id') : null,
            clientId: $request->get('client_id') ? (int) $request->get('client_id') : null,
            conversationId: $request->get('conversation_id') ? (int) $request->get('conversation_id') : null,
            idempotencyKey: $request->get('idempotency_key') ? (string) $request->get('idempotency_key') : null
        );

        $response = $integrationService->handleRequest($context);

        if (!$response->isSuccess) {
            $msg = $response->error['message'] ?? 'Tool execution failed.';
            return Response::error($msg);
        }

        if ($response->requiresApproval) {
            return Response::structured($response->toArray());
        }

        return Response::structured($response->toArray());
    }
}
