<?php

namespace Tests\Feature;

use Tests\TestCase;

class Phase0EnvironmentSetupTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_root(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('HRM Enterprise Portal');
        $response->assertSee('Username');
        $response->assertSee('Password');
    }

    public function test_forgot_password_page_renders_successfully(): void
    {
        $response = $this->get('/password/forgot');
        $response->assertStatus(200);
        $response->assertSee('Reset Account Password');
    }

    public function test_super_admin_can_access_super_admin_dashboard(): void
    {
        $response = $this->actingAsSuperAdmin()->get('/super-admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Super Admin Overview');
    }

    public function test_hr_admin_can_access_hr_admin_dashboard(): void
    {
        $response = $this->actingAsHrAdmin()->get('/hr-admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('HR Operations Workspace');
    }

    public function test_employee_can_access_employee_dashboard(): void
    {
        $response = $this->actingAsEmployee()->get('/employee/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Daily Attendance Punch');
    }

    public function test_employee_cannot_access_super_admin_dashboard(): void
    {
        $response = $this->actingAsEmployee()->get('/super-admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_employee_cannot_access_hr_admin_dashboard(): void
    {
        $response = $this->actingAsEmployee()->get('/hr-admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_custom_404_error_page_renders_properly(): void
    {
        $response = $this->get('/non-existent-hrm-route-path');
        $response->assertStatus(404);
        $response->assertSee('404 Page Not Found');
    }
}
