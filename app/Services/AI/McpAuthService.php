<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class McpAuthService
{
    /**
     * Authenticate and resolve user from incoming request.
     */
    public function resolveUser(Request $request): ?User
    {
        // 1. If user already authenticated via session
        if (Auth::check()) {
            $user = Auth::user();
            return $user->is_active ? $user : null;
        }

        // 2. Resolve from Bearer token or X-MCP-Token header
        $token = $request->bearerToken() ?: $request->header('X-MCP-Token');
        if ($token) {
            return $this->authenticateByToken($token);
        }

        return null;
    }

    /**
     * Authenticate user by secure token.
     */
    public function authenticateByToken(string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

        // Check against users remember_token or custom hashed MCP tokens
        $user = User::where('remember_token', $token)->first();

        if ($user && $user->is_active) {
            return $user;
        }

        return null;
    }

    /**
     * Generate or rotate a secure local MCP token for a user.
     */
    public function generateTokenForUser(User $user): string
    {
        $token = 'mcp_' . Str::random(40);
        $user->forceFill(['remember_token' => $token])->save();
        return $token;
    }
}
