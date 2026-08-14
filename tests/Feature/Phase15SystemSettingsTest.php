<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Phase15SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value]);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value]);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value]);

        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create(['role' => UserRole::HR_ADMIN]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->employeeUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->employeeUser->roles()->sync([$empRole->id]);

        $this->settingsService = app(SettingsService::class);
    }

    public function test_super_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Company Branding');
        $response->assertSee('Company Legal Name');
        $response->assertSee('Salary Monthly Divisor');
    }

    public function test_super_admin_can_update_settings_and_invalidates_cache_and_audit_logs(): void
    {
        // 1. Initial cached value
        $initialDivisor = $this->settingsService->get('salary_divisor', 30);
        $this->assertEquals(30, (int) $initialDivisor);

        // 2. Update settings
        $payload = [
            'company_name' => 'Acme Global Corporation',
            'company_address' => '456 Innovation Boulevard, Suite 800, Tech City',
            'company_email' => 'contact@acmeglobal.local',
            'company_phone' => '+1 (800) 555-0199',
            'salary_divisor' => 28,
            'late_grace_period_minutes' => 20,
            'half_day_threshold_minutes' => 75,
            'late_to_absent_ratio' => 4,
            'half_day_to_absent_ratio' => 3,
            'enable_sandwich_rule' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. Verify Cache Invalidation & Persistence
        $updatedDivisor = $this->settingsService->get('salary_divisor');
        $this->assertEquals(28, (int) $updatedDivisor);
        $this->assertEquals('Acme Global Corporation', $this->settingsService->get('company_name'));
        $this->assertEquals(20, (int) $this->settingsService->get('late_grace_period_minutes'));

        // 4. Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_settings.updated',
            'target_type' => 'CompanySettings',
        ]);
    }

    public function test_non_super_admin_cannot_access_settings(): void
    {
        // HR Admin cannot access
        $this->actingAs($this->hrAdmin)
            ->get(route('super-admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($this->hrAdmin)
            ->post(route('super-admin.settings.update'), [
                'company_name' => 'Unauthorized Hack',
                'company_address' => 'Fake Address',
                'salary_divisor' => 30,
                'late_grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
            ])->assertForbidden();

        // Employee cannot access
        $this->actingAs($this->employeeUser)
            ->get(route('super-admin.settings.index'))
            ->assertForbidden();
    }
}
