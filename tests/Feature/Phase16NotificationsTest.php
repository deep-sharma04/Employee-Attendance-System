<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase16NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected NotificationService $notificationService;

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

        $shift = Shift::first();
        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $shift->id,
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 60000.00,
        ]);

        $this->notificationService = app(NotificationService::class);
    }

    public function test_in_app_notification_service_dispatches_notifications(): void
    {
        $notif = $this->notificationService->notify(
            user: $this->employeeUser,
            title: 'Welcome Alert',
            message: 'Welcome to the enterprise HRM portal.',
            type: 'general',
            data: ['source' => 'welcome_flow']
        );

        $this->assertNotNull($notif);
        $this->assertEquals($this->employeeUser->id, $notif->user_id);
        $this->assertNull($notif->read_at);

        $this->assertEquals(1, $this->notificationService->getUnreadCount($this->employeeUser));
    }

    public function test_leave_approval_and_rejection_trigger_notifications_for_employee(): void
    {
        $leaveType = LeaveType::where('slug', 'casual')->first();

        EmployeeLeaveBalance::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => (int) date('Y'),
            'allocated_days' => 15.0,
            'used_days' => 0.0,
            'remaining_days' => 15.0,
        ]);

        // 1. Create Pending Leave
        $leave = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
            'total_days' => 2.0,
            'is_half_day' => false,
            'reason' => 'Family occasion and travel',
            'status' => LeaveStatus::PENDING,
        ]);

        // 2. HR Admin approves leave
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.approve', $leave->id));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'leave_approved',
        ]);

        // 3. Create another leave and reject
        $leave2 = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(11)->format('Y-m-d'),
            'total_days' => 2.0,
            'is_half_day' => false,
            'reason' => 'Personal work',
            'status' => LeaveStatus::PENDING,
        ]);

        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.reject', $leave2->id), [
                'rejection_reason' => 'Operational staffing requirements prevent approval for these dates.',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'leave_rejected',
        ]);
    }

    public function test_payroll_finalization_triggers_payslip_notification(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employee->id,
            'payroll_month' => 3,
            'payroll_year' => 2026,
            'monthly_salary' => 60000.00,
            'daily_salary' => 2000.00,
            'salary_divisor' => 30,
            'total_days_in_month' => 31,
            'total_lop_days' => 0.0,
            'lop_deduction_amount' => 0.0,
            'total_earnings' => 60000.00,
            'total_deductions' => 0.0,
            'net_salary' => 60000.00,
            'status' => 'approved',
            'payment_status' => \App\Enums\PaymentStatus::PENDING,
            'generated_by' => $this->hrAdmin->id,
        ]);

        // Super Admin finalizes payroll
        $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.payroll.finalize', $payroll->id));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'payslip_finalized',
        ]);
    }

    public function test_document_verification_and_rejection_trigger_notifications(): void
    {
        Storage::fake('local');

        $docType = DocumentType::first();
        $doc = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $docType->id,
            'title' => 'National Identity Proof',
            'file_path' => 'documents/test_id.pdf',
            'file_name' => 'test_id.pdf',
            'file_size' => 10240,
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        // 1. Verify
        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.verify', $doc->id));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'document_verified',
        ]);

        // 2. Reject
        $doc2 = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $docType->id,
            'title' => 'Address Verification Document',
            'file_path' => 'documents/test_address.pdf',
            'file_name' => 'test_address.pdf',
            'file_size' => 10240,
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.reject', $doc2->id), [
                'rejection_reason' => 'Address proof is blurry and unreadable.',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'document_rejected',
        ]);
    }

    public function test_user_can_view_and_mark_notifications_as_read(): void
    {
        $notif = $this->notificationService->notify(
            user: $this->employeeUser,
            title: 'Action Item Required',
            message: 'Please review your employment details.',
            type: 'general'
        );

        // 1. View Notification Center
        $response = $this->actingAs($this->employeeUser)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Notifications Center');
        $response->assertSee('Action Item Required');

        // 2. Mark single as read
        $markResponse = $this->actingAs($this->employeeUser)
            ->post(route('notifications.read', $notif->id));

        $notif->refresh();
        $this->assertNotNull($notif->read_at);

        // 3. Mark all as read
        $this->notificationService->notify($this->employeeUser, 'Second Alert', 'Second message');
        $this->assertEquals(1, $this->notificationService->getUnreadCount($this->employeeUser));

        $markAllResponse = $this->actingAs($this->employeeUser)
            ->post(route('notifications.read-all'));

        $this->assertEquals(0, $this->notificationService->getUnreadCount($this->employeeUser));
    }
}
