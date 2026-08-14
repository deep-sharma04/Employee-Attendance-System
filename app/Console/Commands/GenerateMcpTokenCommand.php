<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AI\McpAuthService;
use Illuminate\Console\Command;

class GenerateMcpTokenCommand extends Command
{
    protected $signature = 'mcp:token {email : The email address of the user}';
    protected $description = 'Generate a secure local MCP authentication token for an active user';

    public function handle(McpAuthService $authService): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return self::FAILURE;
        }

        if (!$user->is_active) {
            $this->error("User [{$email}] is inactive and cannot be authenticated.");
            return self::FAILURE;
        }

        $token = $authService->generateTokenForUser($user);

        $this->info("Generated secure MCP token for {$user->name} ({$user->role->value}):");
        $this->line("<comment>{$token}</comment>");
        $this->newLine();
        $this->line("Set this in your local MCP client configuration as an environment variable:");
        $this->line("  MCP_AUTH_TOKEN={$token}");

        return self::SUCCESS;
    }
}
