<?php

namespace Tests\Feature;

use App\Models\Holiday;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5HolidayCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_hr_admin_can_view_holiday_calendar_and_create_holiday(): void
    {
        $response = $this->actingAsHrAdmin()->get('/hr-admin/holidays');
        $response->assertStatus(200);
        $response->assertSee('Company Holiday Calendar');
        $response->assertSee('Republic Day');

        // Create new holiday
        $createResponse = $this->actingAsHrAdmin()->post('/hr-admin/holidays', [
            'holiday_date' => '2026-11-04',
            'name' => 'Diwali (Deepavali)',
            'description' => 'Festival of Lights',
            'is_recurring_yearly' => '1',
        ]);

        $createResponse->assertRedirect(route('hr-admin.holidays.index', ['year' => 2026]));
        $createResponse->assertSessionHas('success');

        $holiday = Holiday::where('name', 'Diwali (Deepavali)')->first();
        $this->assertNotNull($holiday);
        $this->assertEquals('2026-11-04', $holiday->holiday_date->format('Y-m-d'));
        $this->assertTrue($holiday->is_recurring_yearly);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'holiday.created',
            'target_type' => 'App\Models\Holiday',
            'target_id' => $holiday->id,
        ]);
    }

    public function test_holiday_validation_rejects_duplicate_dates_within_same_year(): void
    {
        // 1. Missing fields
        $emptyResponse = $this->actingAsHrAdmin()->post('/hr-admin/holidays', []);
        $emptyResponse->assertSessionHasErrors(['holiday_date', 'name']);

        // 2. Duplicate holiday on 2026-01-26 (already seeded)
        $dupResponse = $this->actingAsHrAdmin()->post('/hr-admin/holidays', [
            'holiday_date' => '2026-01-26',
            'name' => 'Another Republic Day',
        ]);
        $dupResponse->assertSessionHasErrors(['holiday_date']);
    }

    public function test_hr_admin_can_update_and_delete_holiday_with_audit_trail(): void
    {
        $holiday = Holiday::where('name', 'Independence Day')->first();
        $this->assertNotNull($holiday);

        // 1. Update holiday
        $updateResponse = $this->actingAsHrAdmin()->put("/hr-admin/holidays/{$holiday->id}", [
            'holiday_date' => $holiday->holiday_date->format('Y-m-d'),
            'name' => 'Independence Day (National Holiday)',
            'description' => 'Updated national holiday description',
            'is_recurring_yearly' => '1',
        ]);

        $updateResponse->assertSessionHas('success');
        $holiday->refresh();
        $this->assertEquals('Independence Day (National Holiday)', $holiday->name);

        // Audit update log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'holiday.updated',
            'target_type' => 'App\Models\Holiday',
            'target_id' => $holiday->id,
        ]);

        // 2. Delete holiday
        $deleteResponse = $this->actingAsHrAdmin()->delete("/hr-admin/holidays/{$holiday->id}");
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);

        // Audit delete log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'holiday.deleted',
            'target_type' => 'App\Models\Holiday',
            'target_id' => $holiday->id,
        ]);
    }

    public function test_employee_can_view_read_only_holiday_calendar(): void
    {
        $response = $this->actingAsEmployee()->get('/employee/holidays');
        $response->assertStatus(200);
        $response->assertSee('Official Holidays for ' . date('Y'));
        $response->assertSee('Republic Day');
        $response->assertSee('Independence Day');

        // Cannot post to HR Admin holiday create endpoint
        $forbiddenResponse = $this->actingAsEmployee()->post('/hr-admin/holidays', [
            'holiday_date' => '2026-12-31',
            'name' => 'New Year Eve',
        ]);
        $forbiddenResponse->assertStatus(403);
    }
}
