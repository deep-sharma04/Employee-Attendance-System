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

            $raw = DB::table('company_settings')->pluck('value', 'key');
            $settings = is_array($raw) ? $raw : ($raw instanceof \Illuminate\Support\Collection ? $raw->all() : (array) $raw);
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
            // SMTP & Email Configuration
            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'mail_port' => (int) env('MAIL_PORT', 465),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_password' => env('MAIL_PASSWORD', ''),
            'mail_encryption' => env('MAIL_ENCRYPTION', 'ssl'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', 'noreply@hrm.local'),
            'mail_from_name' => env('MAIL_FROM_NAME', 'HRM System'),
        ];
    }

    /**
     * Dynamically apply mail settings to runtime Laravel mail configuration.
     */
    public function applyMailConfiguration(): void
    {
        try {
            $settings = $this->all();

            $mailer = $settings['mail_mailer'] ?? config('mail.default', 'smtp');
            $host = $settings['mail_host'] ?? config('mail.mailers.smtp.host', '127.0.0.1');
            $port = (int) ($settings['mail_port'] ?? config('mail.mailers.smtp.port', 587));
            $username = $settings['mail_username'] ?? config('mail.mailers.smtp.username');
            $password = $settings['mail_password'] ?? config('mail.mailers.smtp.password');
            $encryption = $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'tls');
            if (empty($encryption) || $encryption === 'none' || $encryption === 'null') {
                $encryption = null;
            }

            $fromAddress = $settings['mail_from_address'] ?? config('mail.from.address', 'noreply@hrm.local');
            $fromName = $settings['mail_from_name'] ?? config('mail.from.name', config('app.name', 'HRM System'));

            config([
                'mail.default' => $mailer,
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);
        } catch (\Throwable $e) {
            // Silently fallback to static config if DB is not ready during early bootstrap
        }
    }
}
