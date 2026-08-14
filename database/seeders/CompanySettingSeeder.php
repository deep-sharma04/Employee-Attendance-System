<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'HRM Enterprise Inc.', 'description' => 'Legal company name for payslip headers'],
            ['key' => 'company_address', 'value' => '100 Business Tech Park, Silicon Corridor, Suite 400', 'description' => 'Office address printed on payslip and documents'],
            ['key' => 'company_email', 'value' => 'hr@hrm.local', 'description' => 'Company contact email'],
            ['key' => 'company_phone', 'value' => '+1 (555) 019-2834', 'description' => 'Company support telephone'],
            ['key' => 'salary_divisor', 'value' => '30', 'description' => 'Standard monthly salary divisor for daily salary calculation'],
            ['key' => 'late_grace_period_minutes', 'value' => '15', 'description' => 'Grace period minutes after shift start for on-time punch'],
            ['key' => 'half_day_threshold_minutes', 'value' => '60', 'description' => 'Minutes late after shift start before attendance is marked as half day'],
            ['key' => 'late_to_absent_ratio', 'value' => '3', 'description' => 'Number of Late records converted to 1 full LOP Absent day'],
            ['key' => 'half_day_to_absent_ratio', 'value' => '2', 'description' => 'Number of Half Day records converted to 1 full LOP Absent day'],
            ['key' => 'enable_sandwich_rule', 'value' => '1', 'description' => 'Whether holiday bridging sandwich rule applies for payroll LOP'],
            ['key' => 'project_health_schedule_variance_at_risk', 'value' => '15', 'description' => 'Schedule variance % threshold for marking project At Risk'],
            ['key' => 'project_health_schedule_variance_critical', 'value' => '30', 'description' => 'Schedule variance % threshold for marking project Critical'],
            ['key' => 'project_health_overdue_milestones_at_risk', 'value' => '1', 'description' => 'Count of overdue milestones threshold for marking project At Risk'],
            ['key' => 'project_health_overdue_milestones_critical', 'value' => '2', 'description' => 'Count of overdue milestones threshold for marking project Critical'],
            ['key' => 'timesheet_monthly_working_days', 'value' => '22', 'description' => 'Standard monthly working days for project labor cost hourly calculation'],
            ['key' => 'timesheet_daily_working_hours', 'value' => '8', 'description' => 'Standard daily working hours for project labor cost hourly calculation'],
        ];

        foreach ($settings as $setting) {
            CompanySetting::updateOrInsert(['key' => $setting['key']], $setting);
        }
    }
}
