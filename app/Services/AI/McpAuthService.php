<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class McpAuthService
{
    /**
     * Resolve the authenticated user from an incoming MCP HTTP request.
     *
     * SECURITY: Only Passport OAuth Bearer tokens are accepted for remote MCP.
     * All legacy fallback methods (web session, Basic auth, custom headers,
     * static remember_token, JSON-RPC _meta credentials) have been removed.
     *
     * The OAuth flow works as follows:
     * 1. Gemini calls POST /mcp without a token → 401 with WWW-Authenticate header.
     * 2. Gemini discovers /.well-known/oauth-protected-resource → authorization server.
     * 3. Gemini calls POST /oauth/register → creates a Passport client.
     * 4. Gemini redirects user to GET /oauth/authorize → user logs in with HRM credentials.
     * 5. Passport issues authorization code → Gemini exchanges for Bearer token.
     * 6. Gemini sends Bearer token on all subsequent /mcp requests.
     * 7. This method resolves the Bearer token to the actual Laravel User via Passport.
     */
    public function resolveUser(Request $request): ?User
    {
        try {
            /** @var User|null $user */
            $user = Auth::guard('api')->user();

            if ($user && $user->is_active) {
                return $user;
            }

            return null;
        } catch (\Throwable $e) {
            // Token parsing failure (expired, revoked, malformed) → null
            return null;
        }
    }

    /**
     * Authenticate user by username or email and password.
     *
     * Used by Passport's authorization page login form (standard Laravel web auth).
     * NOT used for direct MCP authentication — MCP only accepts OAuth Bearer tokens.
     */
    public function authenticateByCredentials(string $usernameOrEmail, string $password): ?User
    {
        if (empty($usernameOrEmail) || empty($password)) {
            return null;
        }

        $user = User::where('username', $usernameOrEmail)
            ->orWhere('email', $usernameOrEmail)
            ->first();

        if ($user && $user->is_active && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }
}
