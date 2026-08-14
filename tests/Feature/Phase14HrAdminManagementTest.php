<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase14HrAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;

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
    }

    public function test_super_admin_can_view_hr_admins_list(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.hr-admins.index'));

        $response->assertOk();
        $response->assertSee('HR Admin Management');
        $response->assertSee($this->hrAdmin->name);
        $response->assertSee($this->hrAdmin->username);
    }

    public function test_super_admin_can_create_new_hr_admin_with_audit_log(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.hr-admins.store'), [
                'name' => 'Monica Geller',
                'username' => 'monica.geller',
                'email' => 'monica@company.com',
                'password' => 'SecurePass123!',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('super-admin.hr-admins.index'));

        $newUser = User::where('username', 'monica.geller')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Monica Geller', $newUser->name);
        $this->assertEquals(UserRole::HR_ADMIN, $newUser->role);
        $this->assertTrue($newUser->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hr_admin.created',
            'target_type' => 'User',
            'target_id' => $newUser->id,
        ]);
    }

    public function test_super_admin_can_edit_hr_admin_details(): void
    {
        // Edit page
        $editPage = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.hr-admins.edit', $this->hrAdmin->id));
        $editPage->assertOk();
        $editPage->assertSee($this->hrAdmin->name);

        // Update action
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('super-admin.hr-admins.update', $this->hrAdmin->id), [
                'name' => 'Updated HR Admin Name',
                'email' => 'updated.hr@company.com',
                'phone' => '+91 9123456789',
                'password' => 'NewSecurePassword123!',
                'is_active' => 1,
            ]);

        $updateResponse->assertRedirect(route('super-admin.hr-admins.index'));

        $this->hrAdmin->refresh();
        $this->assertEquals('Updated HR Admin Name', $this->hrAdmin->name);
        $this->assertEquals('updated.hr@company.com', $this->hrAdmin->email);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->hrAdmin->password));

        // Audit Log verification
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'hr_admin.updated',
            'target_type' => 'User',
            'target_id' => $this->hrAdmin->id,
        ]);
    }

    public function test_super_admin_can_toggle_hr_admin_status_suspend_and_activate(): void
    {
        $this->assertTrue($this->hrAdmin->is_active);

        // 1. Suspend
        $suspendResponse = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.hr-admins.toggle-status', $this->hrAdmin->id));

        $suspendResponse->assertRedirect(route('super-admin.hr-admins.index'));
        $this->hrAdmin->refresh();
        $this->assertFalse($this->hrAdmin->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'hr_admin.suspended']);

        // 2. Activate
        $activateResponse = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.hr-admins.toggle-status', $this->hrAdmin->id));

        $activateResponse->assertRedirect(route('super-admin.hr-admins.index'));
        $this->hrAdmin->refresh();
        $this->assertTrue($this->hrAdmin->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'hr_admin.activated']);
    }

    public function test_non_super_admin_cannot_access_hr_admin_management(): void
    {
        // HR Admin cannot access
        $this->actingAs($this->hrAdmin)
            ->get(route('super-admin.hr-admins.index'))
            ->assertForbidden();

        $this->actingAs($this->hrAdmin)
            ->get(route('super-admin.hr-admins.create'))
            ->assertForbidden();

        $this->actingAs($this->hrAdmin)
            ->post(route('super-admin.hr-admins.store'), [
                'name' => 'Unauthorized Admin',
                'username' => 'unauth.admin',
                'email' => 'unauth@test.com',
            ])->assertForbidden();

        // Employee cannot access
        $this->actingAs($this->employeeUser)
            ->get(route('super-admin.hr-admins.index'))
            ->assertForbidden();

        $this->actingAs($this->employeeUser)
            ->post(route('super-admin.hr-admins.toggle-status', $this->hrAdmin->id))
            ->assertForbidden();
    }
}
