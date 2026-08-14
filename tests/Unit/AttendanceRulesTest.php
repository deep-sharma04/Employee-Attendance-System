<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\OfficeIpAllowlist;
use App\Services\Attendance\AttendanceClassificationService;
use App\Services\Attendance\IpValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRulesTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceClassificationService $classificationService;
    protected IpValidationService $ipValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classificationService = new AttendanceClassificationService();
        $this->ipValidationService = new IpValidationService();
    }

    /*
    |--------------------------------------------------------------------------
    | T179: Attendance Classification Rules (On-Time, Grace, Late, Half-Day)
    |--------------------------------------------------------------------------
    */
    public function test_punch_in_at_exact_shift_start_is_present(): void
    {
        $punch = Carbon::parse('2026-08-10 09:00:00');
        $status = $this->classificationService->classifyPunchIn($punch, '09:00:00', 15, 60);

        $this->assertEquals(AttendanceStatus::PRESENT, $status);
    }

    public function test_punch_in_within_grace_period_is_present(): void
    {
        // 09:14 is within 15 minutes grace period of 09:00 start
        $punch = Carbon::parse('2026-08-10 09:14:59');
        $status = $this->classificationService->classifyPunchIn($punch, '09:00:00', 15, 60);

        $this->assertEquals(AttendanceStatus::PRESENT, $status);

        // Exactly at 15 minutes boundary (09:15:00)
        $punchBoundary = Carbon::parse('2026-08-10 09:15:00');
        $statusBoundary = $this->classificationService->classifyPunchIn($punchBoundary, '09:00:00', 15, 60);

        $this->assertEquals(AttendanceStatus::PRESENT, $statusBoundary);
    }

    public function test_punch_in_beyond_grace_period_up_to_threshold_is_late(): void
    {
        // 09:16 is 1 minute past grace period -> LATE
        $punch1 = Carbon::parse('2026-08-10 09:16:00');
        $status1 = $this->classificationService->classifyPunchIn($punch1, '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::LATE, $status1);

        // 09:45 is well within 60 min half-day threshold -> LATE
        $punch2 = Carbon::parse('2026-08-10 09:45:00');
        $status2 = $this->classificationService->classifyPunchIn($punch2, '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::LATE, $status2);

        // 10:00:00 is exactly at 60 min threshold -> LATE
        $punchBoundary = Carbon::parse('2026-08-10 10:00:00');
        $statusBoundary = $this->classificationService->classifyPunchIn($punchBoundary, '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::LATE, $statusBoundary);
    }

    public function test_punch_in_beyond_half_day_threshold_is_half_day(): void
    {
        // 10:01:00 is beyond 60 min threshold -> HALF_DAY
        $punch = Carbon::parse('2026-08-10 10:01:00');
        $status = $this->classificationService->classifyPunchIn($punch, '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::HALF_DAY, $status);

        // 12:30:00 afternoon punch -> HALF_DAY
        $afternoonPunch = Carbon::parse('2026-08-10 12:30:00');
        $statusAfternoon = $this->classificationService->classifyPunchIn($afternoonPunch, '09:00:00', 15, 60);
        $this->assertEquals(AttendanceStatus::HALF_DAY, $statusAfternoon);
    }

    /*
    |--------------------------------------------------------------------------
    | T179: Late and Half-Day to Absent Conversion Ratios
    |--------------------------------------------------------------------------
    */
    public function test_conversion_of_late_occurrences_to_absent_days(): void
    {
        // 0 Late -> 0 Absent
        $conv0 = $this->classificationService->calculateConvertedAbsences(0, 0);
        $this->assertEquals(0, $conv0['late_to_absent_days']);
        $this->assertEquals(0, $conv0['total_converted_absent_days']);

        // 2 Late -> 0 Absent, 2 remaining
        $conv2 = $this->classificationService->calculateConvertedAbsences(2, 0);
        $this->assertEquals(0, $conv2['late_to_absent_days']);
        $this->assertEquals(2, $conv2['remaining_late_count']);

        // 3 Late -> 1 Absent, 0 remaining (3:1 ratio)
        $conv3 = $this->classificationService->calculateConvertedAbsences(3, 0);
        $this->assertEquals(1, $conv3['late_to_absent_days']);
        $this->assertEquals(0, $conv3['remaining_late_count']);

        // 7 Late -> 2 Absent, 1 remaining
        $conv7 = $this->classificationService->calculateConvertedAbsences(7, 0);
        $this->assertEquals(2, $conv7['late_to_absent_days']);
        $this->assertEquals(1, $conv7['remaining_late_count']);
    }

    public function test_conversion_of_half_day_occurrences_to_absent_days(): void
    {
        // 1 Half Day -> 0 Absent, 1 remaining
        $conv1 = $this->classificationService->calculateConvertedAbsences(0, 1);
        $this->assertEquals(0, $conv1['half_day_to_absent_days']);
        $this->assertEquals(1, $conv1['remaining_half_days']);

        // 2 Half Days -> 1 Absent, 0 remaining (2:1 ratio)
        $conv2 = $this->classificationService->calculateConvertedAbsences(0, 2);
        $this->assertEquals(1, $conv2['half_day_to_absent_days']);
        $this->assertEquals(0, $conv2['remaining_half_days']);

        // 5 Half Days -> 2 Absent, 1 remaining
        $conv5 = $this->classificationService->calculateConvertedAbsences(0, 5);
        $this->assertEquals(2, $conv5['half_day_to_absent_days']);
        $this->assertEquals(1, $conv5['remaining_half_days']);
    }

    public function test_combined_late_and_half_day_conversions(): void
    {
        // 6 Lates (2 Absents) + 3 Half Days (1 Absent) = 3 Total Converted Absents
        $combined = $this->classificationService->calculateConvertedAbsences(6, 3);
        $this->assertEquals(2, $combined['late_to_absent_days']);
        $this->assertEquals(1, $combined['half_day_to_absent_days']);
        $this->assertEquals(3, $combined['total_converted_absent_days']);
    }

    /*
    |--------------------------------------------------------------------------
    | T179: IP Allowlist Validation Logic
    |--------------------------------------------------------------------------
    */
    public function test_ip_validation_allows_configured_office_ips_and_rejects_others(): void
    {
        OfficeIpAllowlist::create([
            'ip_address' => '10.0.5.50',
            'description' => 'Headquarters Fiber',
            'is_active' => true,
        ]);

        OfficeIpAllowlist::create([
            'ip_address' => '10.0.5.99',
            'description' => 'Old Branch (Deactivated)',
            'is_active' => false,
        ]);

        // Active office IP is allowed
        $this->assertTrue($this->ipValidationService->isIpAllowed('10.0.5.50'));

        // Inactive office IP is rejected
        $this->assertFalse($this->ipValidationService->isIpAllowed('10.0.5.99'));

        // Random external IP is rejected
        $this->assertFalse($this->ipValidationService->isIpAllowed('203.0.113.42'));

        // Null / empty IP is rejected
        $this->assertFalse($this->ipValidationService->isIpAllowed(null));
        $this->assertFalse($this->ipValidationService->isIpAllowed(''));
    }
}
