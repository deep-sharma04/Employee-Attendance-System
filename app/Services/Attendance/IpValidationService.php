<?php

namespace App\Services\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IpValidationService
{
    /**
     * Determine whether an IP is in the approved office allowlist.
     */
    public function isIpAllowed(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        // Allow localhost and local loopbacks for development and testing environments
        if (app()->environment('local', 'testing') && in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }

        // If database table exists, check active office IP allowlist records
        if (Schema::hasTable('office_ip_allowlists')) {
            $matched = DB::table('office_ip_allowlists')
                ->where('is_active', true)
                ->where('ip_address', $ip)
                ->exists();

            if ($matched) {
                return true;
            }
        }

        return false;
    }
}
