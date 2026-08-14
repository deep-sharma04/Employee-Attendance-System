<?php

namespace App\Console\Commands;

use App\Services\AI\HrmMcpServer;
use App\Services\AI\McpAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server\Registrar;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve {--token= : Secure authentication token}';
    protected $description = 'Start the internal HRM MCP server over STDIO with token authentication';

    public function handle(McpAuthService $authService, Registrar $registrar): int
    {
        $token = $this->option('token') ?: env('MCP_AUTH_TOKEN');

        if (empty($token)) {
            $this->error('Authentication failed: Missing MCP authentication token.');
            $this->line('Please provide a valid token via --token=<token> or MCP_AUTH_TOKEN environment variable.');
            $this->line('Generate a token using: php artisan mcp:token <user_email>');
            return self::FAILURE;
        }

        $user = $authService->authenticateByToken((string) $token);

        if (!$user) {
            $this->error('Authentication failed: Invalid or expired MCP authentication token.');
            return self::FAILURE;
        }

        if (!$user->is_active) {
            $this->error("Authentication failed: User account [{$user->email}] is inactive.");
            return self::FAILURE;
        }

        // Set authenticated user in session and container
        Auth::setUser($user);
        app()->instance('mcp.authenticated_user', $user);

        $server = $registrar->getLocalServer('hrm');

        if ($server === null) {
            $this->error('MCP Server [hrm] not registered.');
            return self::FAILURE;
        }

        $server();

        return self::SUCCESS;
    }
}
