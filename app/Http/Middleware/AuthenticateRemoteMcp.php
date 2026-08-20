<?php

namespace App\Http\Middleware;

use App\Services\AI\McpAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRemoteMcp
{
    public function __construct(protected McpAuthService $authService) {}

    /**
     * Handle an incoming HTTP request for remote MCP connection.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authService->resolveUser($request);

        if (!$user) {
            $requestId = $request->input('id');
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32001,
                    'message' => 'Unauthorized: Invalid or missing HRM authentication credentials. Please provide valid username and password (via HTTP Basic Auth or X-MCP-Username / X-MCP-Password headers) or a valid MCP Bearer token.',
                ],
                'id' => $requestId,
            ], 401, [
                'WWW-Authenticate' => 'Bearer realm="HRM Remote MCP Server", Basic realm="HRM Remote MCP Server"',
            ]);
        }

        if (!$user->is_active) {
            $requestId = $request->input('id');
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32001,
                    'message' => 'Unauthorized: Account is inactive.',
                ],
                'id' => $requestId,
            ], 401);
        }

        // Establish authenticated user context across Laravel Auth guard, container, and request
        Auth::setUser($user);
        app()->instance('mcp.authenticated_user', $user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
