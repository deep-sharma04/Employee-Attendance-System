<?php

namespace Tests\Feature;

use App\Models\OfficeIpAllowlist;
use App\Models\Shift;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4ShiftAndIpAllowlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_hr_admin_can_view_shifts_and_ip_allowlists(): void
    {
        $shiftResponse = $this->actingAsHrAdmin()->get('/hr-admin/shifts');
        $shiftResponse->assertStatus(200);
        $shiftResponse->assertSee('General Day Shift');
        $shiftResponse->assertSee('GEN_DAY');

        $ipResponse = $this->actingAsHrAdmin()->get('/hr-admin/ip-allowlists');
        $ipResponse->assertStatus(200);
        $ipResponse->assertSee('127.0.0.1');
        $ipResponse->assertSee('Office IP Network Security');
    }

    public function test_hr_admin_can_create_valid_shift_and_logs_audit(): void
    {
        $payload = [
            'name' => 'Night Operations Shift',
            'code' => 'NIGHT_OPS',
            'start_time' => '20:00',
            'end_time' => '05:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'grace_period_minutes' => 20,
            'half_day_threshold_minutes' => 90,
            'is_active' => '1',
        ];

        $response = $this->actingAsHrAdmin()->post('/hr-admin/shifts', $payload);
        $response->assertRedirect(route('hr-admin.shifts.index'));
        $response->assertSessionHas('success');

        $shift = Shift::where('code', 'NIGHT_OPS')->first();
        $this->assertNotNull($shift);
        $this->assertEquals('Night Operations Shift', $shift->name);
        $this->assertEquals(20, $shift->grace_period_minutes);
        $this->assertEquals(90, $shift->half_day_threshold_minutes);
        $this->assertTrue($shift->is_active);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift.created',
            'target_type' => 'App\Models\Shift',
            'target_id' => $shift->id,
        ]);
    }

    public function test_shift_validation_rules_reject_invalid_parameters(): void
    {
        // 1. Missing fields
        $emptyResponse = $this->actingAsHrAdmin()->post('/hr-admin/shifts', []);
        $emptyResponse->assertSessionHasErrors([
            'name',
            'code',
            'start_time',
            'end_time',
            'working_days',
            'grace_period_minutes',
            'half_day_threshold_minutes',
        ]);

        // 2. Duplicate code
        $dupResponse = $this->actingAsHrAdmin()->post('/hr-admin/shifts', [
            'name' => 'Duplicate Code Shift',
            'code' => 'GEN_DAY', // already in database
            'start_time' => '10:00',
            'end_time' => '19:00',
            'working_days' => ['monday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
        ]);
        $dupResponse->assertSessionHasErrors(['code']);

        // 3. Grace period exceeding half-day threshold
        $thresholdResponse = $this->actingAsHrAdmin()->post('/hr-admin/shifts', [
            'name' => 'Invalid Grace Shift',
            'code' => 'INV_GRACE',
            'start_time' => '10:00',
            'end_time' => '19:00',
            'working_days' => ['monday'],
            'grace_period_minutes' => 90,
            'half_day_threshold_minutes' => 30, // less than grace
        ]);
        $thresholdResponse->assertSessionHasErrors(['half_day_threshold_minutes']);
    }

    public function test_hr_admin_can_edit_shift_and_toggle_status(): void
    {
        $shift = Shift::first();

        // 1. Edit shift
        $updateResponse = $this->actingAsHrAdmin()->put("/hr-admin/shifts/{$shift->id}", [
            'name' => 'General Day Shift (Updated)',
            'code' => $shift->code,
            'start_time' => '09:30',
            'end_time' => '18:30',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'grace_period_minutes' => 20,
            'half_day_threshold_minutes' => 75,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('hr-admin.shifts.index'));
        $shift->refresh();
        $this->assertEquals('General Day Shift (Updated)', $shift->name);
        $this->assertEquals(20, $shift->grace_period_minutes);

        // 2. Toggle status
        $toggleResponse = $this->actingAsHrAdmin()->post("/hr-admin/shifts/{$shift->id}/toggle-status");
        $toggleResponse->assertSessionHas('success');
        $shift->refresh();
        $this->assertFalse($shift->is_active);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift.status_toggled',
            'target_type' => 'App\Models\Shift',
            'target_id' => $shift->id,
        ]);
    }

    public function test_hr_admin_can_add_valid_ip_allowlist_and_logs_audit(): void
    {
        $response = $this->actingAsHrAdmin()->post('/hr-admin/ip-allowlists', [
            'ip_address' => '192.168.10.50',
            'description' => 'Branch Office 2 Gateway',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('success');

        $ip = OfficeIpAllowlist::where('ip_address', '192.168.10.50')->first();
        $this->assertNotNull($ip);
        $this->assertEquals('Branch Office 2 Gateway', $ip->description);
        $this->assertTrue($ip->is_active);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ip_allowlist.created',
            'target_type' => 'App\Models\OfficeIpAllowlist',
            'target_id' => $ip->id,
        ]);
    }

    public function test_ip_allowlist_validation_rejects_invalid_ip_format_and_duplicates(): void
    {
        // 1. Invalid IP format
        $invalidResponse = $this->actingAsHrAdmin()->post('/hr-admin/ip-allowlists', [
            'ip_address' => '999.888.777.666',
            'description' => 'Bogus IP',
        ]);
        $invalidResponse->assertSessionHasErrors(['ip_address']);

        // 2. Duplicate IP
        $dupResponse = $this->actingAsHrAdmin()->post('/hr-admin/ip-allowlists', [
            'ip_address' => '127.0.0.1', // already seeded
            'description' => 'Duplicate Localhost',
        ]);
        $dupResponse->assertSessionHasErrors(['ip_address']);
    }

    public function test_hr_admin_can_toggle_ip_status_and_delete_entry(): void
    {
        $ip = OfficeIpAllowlist::create([
            'ip_address' => '10.5.0.1',
            'description' => 'Test LAN',
            'is_active' => true,
        ]);

        // Toggle status
        $toggleResponse = $this->actingAsHrAdmin()->post("/hr-admin/ip-allowlists/{$ip->id}/toggle-status");
        $toggleResponse->assertSessionHas('success');
        $ip->refresh();
        $this->assertFalse($ip->is_active);

        // Delete entry
        $deleteResponse = $this->actingAsHrAdmin()->delete("/hr-admin/ip-allowlists/{$ip->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('office_ip_allowlists', ['id' => $ip->id]);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ip_allowlist.deleted',
            'target_type' => 'App\Models\OfficeIpAllowlist',
            'target_id' => $ip->id,
        ]);
    }
}
