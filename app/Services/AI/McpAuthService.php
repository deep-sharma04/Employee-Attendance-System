<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class McpAuthService
{
    /**
     * Authenticate and resolve user from incoming HTTP request.
     */
    public function resolveUser(Request $request): ?User
    {
        // 1. If user already authenticated via active session
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            return ($user && $user->is_active) ? $user : null;
        }

        // 2. HTTP Basic Authentication header (PHP_AUTH_USER / PHP_AUTH_PW or Authorization: Basic ...)
        $basicUser = $request->header('PHP_AUTH_USER');
        $basicPass = $request->header('PHP_AUTH_PW');
        if ($basicUser && $basicPass) {
            $user = $this->authenticateByCredentials($basicUser, $basicPass);
            if ($user) {
                return $user;
            }
        }

        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with(strtolower($authHeader), 'basic ')) {
            $credentials = base64_decode(substr($authHeader, 6));
            if (str_contains($credentials, ':')) {
                [$userStr, $passStr] = explode(':', $credentials, 2);
                $user = $this->authenticateByCredentials($userStr, $passStr);
                if ($user) {
                    return $user;
                }
            }
        }

        // 3. Custom Headers (X-MCP-Username / X-MCP-Password or X-HRM-Username / X-HRM-Password)
        $customUser = $request->header('X-MCP-Username') ?: $request->header('X-HRM-Username');
        $customPass = $request->header('X-MCP-Password') ?: $request->header('X-HRM-Password');
        if ($customUser && $customPass) {
            $user = $this->authenticateByCredentials($customUser, $customPass);
            if ($user) {
                return $user;
            }
        }

        // 4. Resolve from Bearer token or X-MCP-Token header
        $token = $request->bearerToken() ?: $request->header('X-MCP-Token');
        if ($token) {
            $user = $this->authenticateByToken($token);
            if ($user) {
                return $user;
            }
        }

        // 5. Resolve from JSON-RPC body parameters or _meta payload
        $meta = $request->input('_meta') ?? $request->input('params._meta') ?? [];
        if (!empty($meta['username']) && !empty($meta['password'])) {
            $user = $this->authenticateByCredentials((string) $meta['username'], (string) $meta['password']);
            if ($user) {
                return $user;
            }
        }
        if (!empty($meta['auth_token']) || !empty($meta['token'])) {
            $metaToken = (string) ($meta['auth_token'] ?? $meta['token']);
            $user = $this->authenticateByToken($metaToken);
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Authenticate user by username or email and password against existing Laravel user database.
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

    /**
     * Authenticate user by secure token.
     */
    public function authenticateByToken(string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

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
