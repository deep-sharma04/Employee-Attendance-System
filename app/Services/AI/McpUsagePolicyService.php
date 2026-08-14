<?php

namespace App\Services\AI;

use App\DTOs\AI\McpRequestContext;
use App\Models\CompanySetting;

class McpUsagePolicyService
{
    /**
     * T275: Check whether AI rate limiting is enforced (V1 default: false).
     */
    public function isRateLimitEnforced(): bool
    {
        $setting = CompanySetting::where('key', 'ai_mcp_rate_limit_enabled')->first();
        return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * T275: Check if request conforms to usage policy.
     */
    public function checkUsagePolicy(McpRequestContext $context): bool
    {
        if (!$this->isRateLimitEnforced()) {
            return true;
        }

        // Future rate limit policy logic placeholder for subsequent versions
        return true;
    }

    /**
     * Record usage metrics for analytics.
     */
    public function recordUsage(McpRequestContext $context): void
    {
        // No-op or analytics hook for future versions
    }
}
