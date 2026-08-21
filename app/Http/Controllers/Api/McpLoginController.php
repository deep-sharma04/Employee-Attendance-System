<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\McpAuthService;
use Illuminate\Http\Request;

class McpLoginController extends Controller
{
    public function login(Request $request, McpAuthService $authService)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = $authService->authenticateByCredentials(
            $request->input('username'),
            $request->input('password')
        );

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized: Invalid username or password.'
            ], 401);
        }

        $token = $authService->generateTokenForUser($user);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
            ],
            'message' => 'Use this token in the Authorization Header (Bearer) or X-MCP-Token for future requests.'
        ]);
    }
}
