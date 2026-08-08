<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    protected const CACHE_KEY = 'hrm_company_settings';

    /**
     * Get a setting value by key with a fallback default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /**
     * Retrieve all company and business rule settings from cache or DB.
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            if (!Schema::hasTable('company_settings')) {
                return $this->defaults();
            }

            $settings = DB::table('company_settings')->pluck('value', 'key')->all();
            return array_merge($this->defaults(), $settings);
        });
    }

    /**
     * Update settings and invalidate the cache.
     */
    public function setMany(array $keyValues): void
    {
        if (Schema::hasTable('company_settings')) {
            foreach ($keyValues as $key => $value) {
                DB::table('company_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => is_array($value) ? json_encode($value) : (string) $value, 'updated_at' => now()]
                );
            }
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * System default baseline settings.
     */
    public function defaults(): array
    {
        return [
            'company_name' => 'HRM Enterprise Inc.',
            'company_address' => '100 Business Tech Park, Silicon Corridor',
            'company_email' => 'hr@hrm.local',
            'company_phone' => '+1 (555) 019-2834',
            'company_logo' => '/images/logo.png',
            'salary_divisor' => 30,
            'late_grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'late_to_absent_ratio' => 3,
            'half_day_to_absent_ratio' => 2,
            'enable_sandwich_rule' => true,
        ];
    }
}
