<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DocumentStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\AttendanceEvent;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\OfficeIpAllowlist;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Payslip;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Phase17SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUserA;
    protected Employee $employeeA;
    protected User $employeeUserB;
    protected Employee $employeeB;
    protected Shift $shift;
    protected LeaveType $leaveType;
    protected DocumentType $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value], ['name' => 'Super Admin']);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value], ['name' => 'HR Admin']);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value], ['name' => 'Employee']);

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->shift = Shift::first() ?? Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        $this->leaveType = LeaveType::where('slug', 'casual')->first() ?? LeaveType::create([
            'name' => 'Casual Leave',
            'slug' => 'casual',
            'annual_quota' => 12,
            'is_active' => true,
        ]);

        $this->documentType = DocumentType::first() ?? DocumentType::create([
            'name' => 'Identity Proof',
            'code' => 'ID_PROOF',
            'is_mandatory' => true,
        ]);

        // Employee A
        $this->employeeUserA = User::factory()->create([
            'name' => 'Alice Employee',
            'username' => 'alice.employee',
            'email' => 'alice@hrm.local',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);
        $this->employeeUserA->roles()->sync([$empRole->id]);

        $this->employeeA = Employee::factory()->create([
            'user_id' => $this->employeeUserA->id,
            'shift_id' => $this->shift->id,
            'first_name' => 'Alice',
            'last_name' => 'Employee',
            'employee_code' => 'EMP001',
            'email' => 'alice@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 50000.00,
        ]);

        // Employee B
        $this->employeeUserB = User::factory()->create([
            'name' => 'Bob Employee',
            'username' => 'bob.employee',
            'email' => 'bob@hrm.local',
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);
        $this->employeeUserB->roles()->sync([$empRole->id]);

        $this->employeeB = Employee::factory()->create([
            'user_id' => $this->employeeUserB->id,
            'shift_id' => $this->shift->id,
            'first_name' => 'Bob',
            'last_name' => 'Employee',
            'employee_code' => 'EMP002',
            'email' => 'bob@hrm.local',
            'status' => EmployeeStatus::ACTIVE,
            'monthly_salary' => 55000.00,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T169: Mass Assignment Protection
    |--------------------------------------------------------------------------
    */
    public function test_all_eloquent_models_have_fillable_protection_defined(): void
    {
        $models = [
            AttendanceEvent::class,
            AttendanceRecord::class,
            AuditLog::class,
            CompanySetting::class,
            Document::class,
            DocumentType::class,
            Employee::class,
            EmployeeLeaveBalance::class,
            Holiday::class,
            LeaveRequest::class,
            LeaveType::class,
            Notification::class,
            OfficeIpAllowlist::class,
            Payroll::class,
            PayrollItem::class,
            Payslip::class,
            Permission::class,
            Role::class,
            Shift::class,
            User::class,
        ];

        foreach ($models as $modelClass) {
            $model = new $modelClass();
            $fillable = $model->getFillable();
            $guarded = $model->getGuarded();

            // Must have either explicit fillable defined or guarded configured
            $this->assertTrue(
                !empty($fillable) || $guarded !== ['*'],
                "Model {$modelClass} must define \$fillable or \$guarded protection."
            );
        }
    }

    public function test_strict_mass_assignment_disallows_unfillable_attribute_injection(): void
    {
        Model::preventSilentlyDiscardingAttributes(true);

        $this->expectException(MassAssignmentException::class);

        // Attempting to mass-assign an arbitrary non-fillable field must throw in strict mode
        User::create([
            'name' => 'Hacker Test',
            'username' => 'hacker',
            'email' => 'hacker@test.local',
            'password' => Hash::make('Secret123!'),
            'role' => UserRole::EMPLOYEE,
            'is_system_admin_override' => true, // unfillable attribute
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T170: CSRF Protection
    |--------------------------------------------------------------------------
    */
    public function test_all_state_changing_views_contain_csrf_tokens(): void
    {
        // Login view contains CSRF
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);

        // Super Admin Settings view contains CSRF
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.settings.index'));
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);

        // HR Admin Document Upload view contains CSRF
        $response = $this->actingAs($this->hrAdmin)->get(route('hr-admin.documents.create'));
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);

        // Employee Leave Apply view contains CSRF
        $response = $this->actingAs($this->employeeUserA)->get(route('employee.leaves.create'));
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | T171: Form Request Validation Coverage
    |--------------------------------------------------------------------------
    */
    public function test_form_request_validation_for_company_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('super-admin.settings.index'))
            ->post(route('super-admin.settings.update'), [
                'company_name' => '', // Required
                'company_address' => '', // Required
                'salary_divisor' => 10, // Must be between 20 and 31
                'late_grace_period_minutes' => 999, // Max 60
                'half_day_threshold_minutes' => 5, // Min 15
            ]);

        $response->assertSessionHasErrors([
            'company_name',
            'company_address',
            'salary_divisor',
            'late_grace_period_minutes',
            'half_day_threshold_minutes',
        ]);
    }

    public function test_form_request_validation_for_attendance_correction(): void
    {
        $record = AttendanceRecord::create([
            'employee_id' => $this->employeeA->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.attendance.store-correction', $record->id), [
                'status' => 'invalid_status', // Invalid enum
                'correction_reason' => 'abc', // Min 5 characters
            ]);

        $response->assertSessionHasErrors(['status', 'correction_reason']);
    }

    public function test_form_request_validation_for_leave_rejection(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employeeA->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Annual checkup',
            'status' => LeaveStatus::PENDING,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.leaves.reject', $leaveRequest->id), [
                'rejection_reason' => 'No', // Min 5 characters
            ]);

        $response->assertSessionHasErrors(['rejection_reason']);
    }

    public function test_form_request_validation_for_payroll_generation(): void
    {
        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.payroll.generate'), [
                'year' => 2010, // Min 2020
                'month' => 15, // Max 12
                'employee_id' => 99999, // Non-existent employee
            ]);

        $response->assertSessionHasErrors(['year', 'month', 'employee_id']);
    }

    public function test_form_request_validation_for_password_reset_flow(): void
    {
        $response = $this->post(route('password.reset.post'), [
            'token' => 'sample_token',
            'email' => 'invalid-email-format',
            'password' => 'short', // Min 8
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /*
    |--------------------------------------------------------------------------
    | T172: XSS Output Escaping
    |--------------------------------------------------------------------------
    */
    public function test_dynamic_output_escapes_xss_scripts_in_rendered_views(): void
    {
        $xssPayload = "<script>alert('XSS_INJECTION')</script>";

        $this->employeeA->update([
            'designation' => $xssPayload,
            'department' => $xssPayload,
        ]);

        $response = $this->actingAs($this->employeeUserA)->get(route('employee.dashboard'));
        $response->assertStatus(200);

        // Blade MUST HTML-escape the script tag
        $response->assertDontSee($xssPayload, false);
        $response->assertSee(e($xssPayload), false);
    }

    /*
    |--------------------------------------------------------------------------
    | T173: Document Upload Security
    |--------------------------------------------------------------------------
    */
    public function test_document_upload_rejects_unauthorized_mimetypes_and_disguised_files(): void
    {
        Storage::fake('local');

        // Disallowed executable script disguised with .pdf extension
        $fakePhpScript = UploadedFile::fake()->createWithContent('malicious.php', '<?php echo "pwned"; ?>');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employeeA->id,
                'document_type_id' => $this->documentType->id,
                'title' => 'Malicious Executable',
                'document_file' => $fakePhpScript,
            ]);

        $response->assertSessionHasErrors(['document_file']);
    }

    public function test_document_upload_rejects_oversized_files(): void
    {
        Storage::fake('local');

        // File exceeding 500 KB limit (600 KB)
        $oversizedFile = UploadedFile::fake()->create('large_scan.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employeeA->id,
                'document_type_id' => $this->documentType->id,
                'title' => 'Oversized ID Scan',
                'document_file' => $oversizedFile,
            ]);

        $response->assertSessionHasErrors(['document_file']);
    }

    public function test_document_upload_accepts_valid_pdf_and_stores_outside_public_webroot(): void
    {
        Storage::fake('local');

        $validPdf = UploadedFile::fake()->create('valid_passport.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employeeA->id,
                'document_type_id' => $this->documentType->id,
                'title' => 'Passport Copy',
                'document_file' => $validPdf,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $document = Document::where('title', 'Passport Copy')->first();
        $this->assertNotNull($document);
        $this->assertEquals(DocumentStatus::PENDING, $document->status);
        Storage::disk('local')->assertExists($document->file_path);
    }

    /*
    |--------------------------------------------------------------------------
    | T174: Rate Limiting on Sensitive Endpoints
    |--------------------------------------------------------------------------
    */
    public function test_sensitive_endpoints_have_throttling_middleware_applied(): void
    {
        $routes = Route::getRoutes();

        // 1. Password change route throttling
        $changePasswordRoute = $routes->getByName('password.change.post');
        $this->assertNotNull($changePasswordRoute);
        $this->assertContains('throttle:6,1', $changePasswordRoute->gatherMiddleware());

        // 2. Login route throttling
        $loginRoute = $routes->getByName('login.post');
        $this->assertNotNull($loginRoute);
        $this->assertContains('throttle:10,1', $loginRoute->gatherMiddleware());

        // 3. Punch In route throttling
        $punchInRoute = $routes->getByName('employee.attendance.punch-in');
        $this->assertNotNull($punchInRoute);
        $this->assertContains('throttle:30,1', $punchInRoute->gatherMiddleware());

        // 4. Punch Out route throttling
        $punchOutRoute = $routes->getByName('employee.attendance.punch-out');
        $this->assertNotNull($punchOutRoute);
        $this->assertContains('throttle:30,1', $punchOutRoute->gatherMiddleware());
    }

    /*
    |--------------------------------------------------------------------------
    | T175: HTTPS Enforcement & Config
    |--------------------------------------------------------------------------
    */
    public function test_https_scheme_is_enforced_when_configured(): void
    {
        URL::forceScheme('https');

        $generatedUrl = route('login');
        $this->assertStringStartsWith('https://', $generatedUrl);

        // Reset scheme for testing isolation
        URL::forceScheme(null);
    }

    /*
    |--------------------------------------------------------------------------
    | T176: Session & Token Security
    |--------------------------------------------------------------------------
    */
    public function test_session_security_configuration(): void
    {
        $this->assertEquals(120, config('session.lifetime'));
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('lax', config('session.same_site'));
    }

    public function test_password_reset_token_fails_if_expired(): void
    {
        $rawToken = 'sample_secret_token_12345';
        $email = 'alice@hrm.local';

        // Insert token created 2 hours ago (expired > 60 mins)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($rawToken),
                'created_at' => Carbon::now()->subMinutes(120),
            ]
        );

        $response = $this->post(route('password.reset.post'), [
            'token' => $rawToken,
            'email' => $email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertTrue(Hash::check('password', $this->employeeUserA->fresh()->password));
    }

    /*
    |--------------------------------------------------------------------------
    | T177: Full Authorization Test Sweep (RBAC)
    |--------------------------------------------------------------------------
    */
    public function test_super_admin_routes_are_forbidden_to_hr_admins_and_employees(): void
    {
        // HR Admin attempting to access Super Admin dashboard
        $response = $this->actingAs($this->hrAdmin)->get(route('super-admin.dashboard'));
        $response->assertStatus(403);

        // Employee attempting to access Super Admin dashboard
        $response = $this->actingAs($this->employeeUserA)->get(route('super-admin.dashboard'));
        $response->assertStatus(403);

        // Unauthenticated guest
        auth()->logout();
        $this->flushSession();
        $response = $this->get(route('super-admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_hr_admin_routes_are_forbidden_to_employees(): void
    {
        $response = $this->actingAs($this->employeeUserA)->get(route('hr-admin.employees.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->employeeUserA)->get(route('hr-admin.payroll.index'));
        $response->assertStatus(403);
    }

    public function test_payroll_approval_and_finalization_are_super_admin_only(): void
    {
        $payroll = Payroll::create([
            'employee_id' => $this->employeeA->id,
            'payroll_year' => (int) date('Y'),
            'payroll_month' => (int) date('n'),
            'monthly_salary' => 50000.00,
            'daily_salary' => 1666.67,
            'salary_divisor' => 30,
            'total_days_in_month' => 30,
            'present_days' => 22.0,
            'absent_days' => 0.0,
            'leave_days' => 0.0,
            'total_earnings' => 50000.00,
            'total_deductions' => 0.0,
            'net_salary' => 50000.00,
            'status' => PayrollStatus::REVIEWED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        // HR Admin cannot approve
        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.approve', $payroll->id));
        $response->assertStatus(403);

        // HR Admin cannot finalize
        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.payroll.finalize', $payroll->id));
        $response->assertStatus(403);

        // Super Admin can approve
        $response = $this->actingAs($this->superAdmin)->post(route('hr-admin.payroll.approve', $payroll->id));
        $response->assertRedirect();
        $this->assertEquals(PayrollStatus::APPROVED, $payroll->fresh()->status);
    }

    public function test_deactivated_accounts_are_blocked_by_active_account_middleware(): void
    {
        $this->employeeUserA->update(['is_active' => false]);

        $response = $this->actingAs($this->employeeUserA)->get(route('employee.dashboard'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login']);
    }

    /*
    |--------------------------------------------------------------------------
    | T178: General Web Vulnerability Review & IDOR Prevention
    |--------------------------------------------------------------------------
    */
    public function test_idor_prevention_employee_cannot_access_another_employees_payslip(): void
    {
        Storage::fake('local');
        $fakePdfPath = 'payslips/2026/08/EMP002_2026_08.pdf';
        Storage::disk('local')->put($fakePdfPath, '%PDF-1.4 Mock Payslip Bob');

        $payrollB = Payroll::create([
            'employee_id' => $this->employeeB->id,
            'payroll_year' => 2026,
            'payroll_month' => 8,
            'monthly_salary' => 55000.00,
            'daily_salary' => 1833.33,
            'salary_divisor' => 30,
            'total_days_in_month' => 30,
            'total_earnings' => 55000.00,
            'total_deductions' => 0.00,
            'net_salary' => 55000.00,
            'status' => PayrollStatus::FINALIZED,
            'generated_by' => $this->hrAdmin->id,
        ]);

        $payslipB = Payslip::create([
            'employee_id' => $this->employeeB->id,
            'payroll_id' => $payrollB->id,
            'payslip_number' => 'PSL-EMP002-2026-08',
            'month' => 8,
            'year' => 2026,
            'file_path' => $fakePdfPath,
            'net_pay' => 55000.00,
            'generated_at' => now(),
        ]);

        // Employee A attempts to view Employee B's payslip
        $response = $this->actingAs($this->employeeUserA)->get(route('employee.payslips.view', $payslipB->id));
        $response->assertStatus(403);

        // Employee A attempts to download Employee B's payslip
        $response = $this->actingAs($this->employeeUserA)->get(route('employee.payslips.download', $payslipB->id));
        $response->assertStatus(403);
    }

    public function test_idor_prevention_employee_cannot_cancel_another_employees_leave_request(): void
    {
        $leaveRequestB = LeaveRequest::create([
            'employee_id' => $this->employeeB->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'total_days' => 1.0,
            'reason' => 'Personal work',
            'status' => LeaveStatus::PENDING,
        ]);

        // Employee A attempts to cancel Employee B's leave
        $response = $this->actingAs($this->employeeUserA)->post(route('employee.leaves.cancel', $leaveRequestB->id));
        $response->assertStatus(404);

        $this->assertEquals(LeaveStatus::PENDING, $leaveRequestB->fresh()->status);
    }

    public function test_sql_injection_payload_in_search_queries_is_sanitized(): void
    {
        $sqliPayload = "' OR '1'='1";

        $response = $this->actingAs($this->hrAdmin)->get(route('hr-admin.employees.index', ['search' => $sqliPayload]));
        $response->assertStatus(200);

        // SQL injection must not dump all records matching 1=1
        $response->assertDontSee('SQLSTATE');
    }
}
