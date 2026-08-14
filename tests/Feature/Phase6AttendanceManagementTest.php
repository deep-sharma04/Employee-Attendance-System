<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OfficeIpAllowlist;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\AttendanceClassificationService;
use App\Services\Attendance\IpValidationService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ip_validation_service_accepts_allowlisted_ips_and_rejects_unauthorized(): void
    {
        $service = app(IpValidationService::class);

        // Seeded localhost
        $this->assertTrue($service->isIpAllowed('127.0.0.1'));

        // Custom allowlisted IP
        OfficeIpAllowlist::create([
            'ip_address' => '10.20.30.40',
            'description' => 'Office Branch Wi-Fi',
            'is_active' => true,
        ]);
        $this->assertTrue($service->isIpAllowed('10.20.30.40'));

        // Deactivated IP
        OfficeIpAllowlist::create([
            'ip_address' => '10.20.30.50',
            'description' => 'Deactivated Gateway',
            'is_active' => false,
        ]);
        $this->assertFalse($service->isIpAllowed('10.20.30.50'));

        // Unknown external IP
        $this->assertFalse($service->isIpAllowed('198.51.100.254'));
    }

    public function test_employee_can_punch_in_on_time_and_records_event_and_ip(): void
    {
        $shift = Shift::first();
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        // Freeze time at 09:10 (within 15 min grace of 09:00 shift start)
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:10:00'));

        $response = $this->actingAs($user, 'web')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/employee/attendance/punch-in');

        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertEquals('127.0.0.1', $record->punch_in_ip);

        // Verify attendance event recorded
        $this->assertDatabaseHas('attendance_events', [
            'employee_id' => $employee->id,
            'action' => 'punch_in',
            'ip_address' => '127.0.0.1',
        ]);

        // Duplicate punch-in on same day is rejected
        $dupResponse = $this->actingAs($user, 'web')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/employee/attendance/punch-in');
        $dupResponse->assertSessionHas('warning');

        Carbon::setTestNow();
    }

    public function test_punch_in_classifies_late_and_half_day_accurately(): void
    {
        $shift = Shift::first(); // 09:00 start, 15m grace, 60m half-day threshold
        $classifier = app(AttendanceClassificationService::class);

        // 1. 09:10 (<= 15 mins) -> PRESENT
        $status1 = $classifier->classifyPunchIn(
            '2026-08-10 09:10:00',
            $shift->start_time,
            $shift->grace_period_minutes,
            $shift->half_day_threshold_minutes
        );
        $this->assertEquals(AttendanceStatus::PRESENT, $status1);

        // 2. 09:30 (> 15 mins but <= 60 mins) -> LATE
        $status2 = $classifier->classifyPunchIn(
            '2026-08-10 09:30:00',
            $shift->start_time,
            $shift->grace_period_minutes,
            $shift->half_day_threshold_minutes
        );
        $this->assertEquals(AttendanceStatus::LATE, $status2);

        // 3. 10:15 (> 60 mins) -> HALF_DAY
        $status3 = $classifier->classifyPunchIn(
            '2026-08-10 10:15:00',
            $shift->start_time,
            $shift->grace_period_minutes,
            $shift->half_day_threshold_minutes
        );
        $this->assertEquals(AttendanceStatus::HALF_DAY, $status3);
    }

    public function test_employee_can_punch_out_and_calculates_total_hours(): void
    {
        $shift = Shift::first();
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        // 1. Punch In at 09:00 on 2026-08-10
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));
        $inResponse = $this->actingAs($user, 'web')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/employee/attendance/punch-in');
        $inResponse->assertSessionHas('success');

        // 2. Punch Out at 18:00 on same day (9.0 hours duration)
        Carbon::setTestNow(Carbon::parse('2026-08-10 18:00:00'));
        $outResponse = $this->actingAs($user, 'web')
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/employee/attendance/punch-out');

        $outResponse->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-08-10')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(9.0, (float) $record->total_working_hours);
        $this->assertNotNull($record->punch_out);
        $this->assertEquals('127.0.0.1', $record->punch_out_ip);

        // Verify punch_out event logged
        $this->assertDatabaseHas('attendance_events', [
            'employee_id' => $employee->id,
            'action' => 'punch_out',
            'ip_address' => '127.0.0.1',
        ]);

        Carbon::setTestNow();
    }

    public function test_unauthorized_ip_punch_is_rejected_with_friendly_message(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]);

        $response = $this->actingAs($user, 'web')
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.88'])
            ->post('/employee/attendance/punch-in');

        $response->assertSessionHas('error');
        $this->assertStringContainsString('unauthorized network', session('error'));
    }

    public function test_late_and_half_day_conversions_apply_3_late_and_2_half_days_rules(): void
    {
        $classifier = app(AttendanceClassificationService::class);

        // 7 Late occurrences -> 2 absent days, 1 remaining late
        // 5 Half Days -> 2 absent days, 1 remaining half day
        $conversions = $classifier->calculateConvertedAbsences(7, 5);

        $this->assertEquals(2, $conversions['late_to_absent_days']);
        $this->assertEquals(1, $conversions['remaining_late_count']);
        $this->assertEquals(2, $conversions['half_day_to_absent_days']);
        $this->assertEquals(1, $conversions['remaining_half_days']);
        $this->assertEquals(4, $conversions['total_converted_absent_days']);
    }

    public function test_employee_can_view_own_attendance_history(): void
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'status' => EmployeeStatus::ACTIVE]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'shift_id' => $employee->shift_id,
            'attendance_date' => date('Y-m-01'),
            'punch_in' => '09:00:00',
            'punch_out' => '18:00:00',
            'total_working_hours' => 9.0,
            'status' => AttendanceStatus::PRESENT,
            'punch_in_ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user, 'web')->get('/employee/attendance/history');
        $response->assertStatus(200);
        $response->assertSee('My Attendance Records');
        $response->assertSee('On-Time Punches');
        $response->assertSee('127.0.0.1');
    }

    public function test_hr_admin_can_view_monitoring_and_manually_correct_attendance(): void
    {
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'shift_id' => $employee->shift_id,
            'attendance_date' => '2026-08-01',
            'status' => AttendanceStatus::ABSENT,
            'total_working_hours' => 0.0,
        ]);

        // 1. HR views monitoring dashboard
        $response = $this->actingAsHrAdmin()->get('/hr-admin/attendance?date=2026-08-01');
        $response->assertStatus(200);
        $response->assertSee('Company Attendance Monitoring');
        $response->assertSee($employee->full_name);

        // 2. HR submits manual correction with reason
        $correctResponse = $this->actingAsHrAdmin()->post("/hr-admin/attendance/correct/{$record->id}", [
            'status' => 'present',
            'punch_in_at' => '09:05',
            'punch_out_at' => '18:00',
            'total_hours' => 8.9,
            'correction_reason' => 'Employee worked on-site client audit and biometric terminal was unavailable.',
        ]);

        $correctResponse->assertRedirect(route('hr-admin.attendance.index', ['date' => '2026-08-01']));
        $record->refresh();

        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertTrue($record->is_manually_corrected);
        $this->assertEquals('Employee worked on-site client audit and biometric terminal was unavailable.', $record->correction_reason);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.corrected',
            'target_type' => 'App\Models\AttendanceRecord',
            'target_id' => $record->id,
        ]);
    }

    public function test_hr_admin_can_add_past_attendance_entry(): void
    {
        $employee = Employee::factory()->create(['status' => EmployeeStatus::ACTIVE]);

        $response = $this->actingAsHrAdmin()->post('/hr-admin/attendance/manual', [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-03',
            'status' => 'present',
            'punch_in_at' => '09:00',
            'punch_out_at' => '18:00',
            'total_hours' => 9.0,
            'correction_reason' => 'Official external technical summit attendance approved by management.',
        ]);

        $response->assertSessionHas('success');

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-08-03')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(AttendanceStatus::PRESENT, $record->status);
        $this->assertTrue($record->is_manually_corrected);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.manual_entry',
            'target_type' => 'App\Models\AttendanceRecord',
            'target_id' => $record->id,
        ]);
    }
}
