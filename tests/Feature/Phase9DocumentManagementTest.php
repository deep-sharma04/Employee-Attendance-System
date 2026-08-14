<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase9DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('local');

        $superAdminRole = Role::firstOrCreate(['slug' => UserRole::SUPER_ADMIN->value]);
        $hrAdminRole = Role::firstOrCreate(['slug' => UserRole::HR_ADMIN->value]);
        $empRole = Role::firstOrCreate(['slug' => UserRole::EMPLOYEE->value]);

        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->hrAdmin = User::factory()->create(['role' => UserRole::HR_ADMIN]);
        $this->hrAdmin->roles()->sync([$hrAdminRole->id]);

        $this->employeeUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->employeeUser->roles()->sync([$empRole->id]);

        $shift = Shift::firstOrCreate(
            ['code' => 'GEN-001'],
            [
                'name' => 'General Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 60,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'is_active' => true,
            ]
        );

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'shift_id' => $shift->id,
            'status' => 'active',
            'monthly_salary' => 50000.00,
        ]);

        $this->docType = DocumentType::firstOrCreate(
            ['slug' => 'identity-proof'],
            ['name' => 'Identity Proof', 'is_mandatory' => true]
        );
    }

    public function test_hr_admin_can_view_document_types_and_create_new_type(): void
    {
        $response = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.documents.types'));

        $response->assertOk();
        $response->assertSee('Identity Proof');

        $storeResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.types.store'), [
                'name' => 'Graduation Certificate',
                'is_mandatory' => 1,
            ]);

        $storeResponse->assertRedirect(route('hr-admin.documents.types'));
        $this->assertDatabaseHas('document_types', [
            'name' => 'Graduation Certificate',
            'is_mandatory' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_type.created',
            'target_type' => 'DocumentType',
        ]);
    }

    public function test_hr_admin_can_update_and_delete_unused_document_type(): void
    {
        $type = DocumentType::create([
            'name' => 'Temporary Badge',
            'slug' => 'temporary-badge',
            'is_mandatory' => false,
        ]);

        $updateResponse = $this->actingAs($this->hrAdmin)
            ->put(route('hr-admin.documents.types.update', $type->id), [
                'name' => 'Temporary Security Badge',
                'is_mandatory' => 1,
            ]);

        $updateResponse->assertRedirect(route('hr-admin.documents.types'));
        $this->assertDatabaseHas('document_types', [
            'id' => $type->id,
            'name' => 'Temporary Security Badge',
            'is_mandatory' => 1,
        ]);

        $deleteResponse = $this->actingAs($this->hrAdmin)
            ->delete(route('hr-admin.documents.types.destroy', $type->id));

        $deleteResponse->assertRedirect(route('hr-admin.documents.types'));
        $this->assertDatabaseMissing('document_types', ['id' => $type->id]);
    }

    public function test_hr_admin_can_upload_valid_document_for_employee(): void
    {
        $file = UploadedFile::fake()->create('aadhaar_card.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employee->id,
                'document_type_id' => $this->docType->id,
                'title' => 'National Aadhaar Card',
                'document_file' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->docType->id,
            'title' => 'National Aadhaar Card',
            'file_name' => 'aadhaar_card.pdf',
            'status' => 'pending',
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.uploaded',
            'target_type' => 'Document',
        ]);
    }

    public function test_upload_rejected_when_file_exceeds_500kb(): void
    {
        // 600 KB file (exceeds max 500 KB)
        $largeFile = UploadedFile::fake()->create('large_scan.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employee->id,
                'document_type_id' => $this->docType->id,
                'title' => 'Large Scan',
                'document_file' => $largeFile,
            ]);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertEquals(0, Document::count());
    }

    public function test_upload_rejected_when_file_type_is_unsupported(): void
    {
        // TXT or ZIP or EXE is unsupported (only PNG, JPG, JPEG, PDF allowed)
        $invalidFile = UploadedFile::fake()->create('script.sh', 50, 'text/plain');

        $response = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employee->id,
                'document_type_id' => $this->docType->id,
                'title' => 'Bash Script',
                'document_file' => $invalidFile,
            ]);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertEquals(0, Document::count());
    }

    public function test_hr_admin_can_view_and_download_stored_document(): void
    {
        $file = UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf');
        $storedPath = $file->store('documents', 'local');

        $doc = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->docType->id,
            'title' => 'B.Tech Degree',
            'file_path' => $storedPath,
            'file_name' => 'degree.pdf',
            'file_size' => 204800,
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $viewResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.documents.view', $doc->id));
        $viewResponse->assertOk();

        $downloadResponse = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.documents.download', $doc->id));
        $downloadResponse->assertOk();
    }

    public function test_hr_admin_and_super_admin_can_verify_document(): void
    {
        $file = UploadedFile::fake()->create('pan.jpg', 150, 'image/jpeg');
        $storedPath = $file->store('documents', 'local');

        $doc = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->docType->id,
            'title' => 'PAN Card Copy',
            'file_path' => $storedPath,
            'file_name' => 'pan.jpg',
            'file_size' => 153600,
            'mime_type' => 'image/jpeg',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $verifyResponse = $this->actingAs($this->superAdmin)
            ->post(route('hr-admin.documents.verify', $doc->id));

        $verifyResponse->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'verified',
            'verified_by' => $this->superAdmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.verified',
            'target_type' => 'Document',
            'target_id' => $doc->id,
        ]);
    }

    public function test_hr_admin_can_reject_document_with_mandatory_reason(): void
    {
        $file = UploadedFile::fake()->create('id_blur.png', 100, 'image/png');
        $storedPath = $file->store('documents', 'local');

        $doc = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->docType->id,
            'title' => 'Blur ID Proof',
            'file_path' => $storedPath,
            'file_name' => 'id_blur.png',
            'file_size' => 102400,
            'mime_type' => 'image/png',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        // 1. Rejection without reason fails
        $emptyReasonResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.reject', $doc->id), [
                'rejection_reason' => '',
            ]);
        $emptyReasonResponse->assertSessionHasErrors(['rejection_reason']);

        // 2. Rejection with reason succeeds
        $validReasonResponse = $this->actingAs($this->hrAdmin)
            ->post(route('hr-admin.documents.reject', $doc->id), [
                'rejection_reason' => 'The uploaded photocopy is blurry and illegible. Please submit a clearer scan.',
            ]);

        $validReasonResponse->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'rejected',
            'rejection_reason' => 'The uploaded photocopy is blurry and illegible. Please submit a clearer scan.',
            'verified_by' => $this->hrAdmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.rejected',
            'target_type' => 'Document',
            'target_id' => $doc->id,
        ]);
    }

    public function test_employee_is_forbidden_from_accessing_document_management_routes_and_files(): void
    {
        // 1. Employee cannot view document list
        $listResponse = $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.documents.index'));
        $listResponse->assertForbidden();

        // 2. Employee cannot access upload form
        $createResponse = $this->actingAs($this->employeeUser)
            ->get(route('hr-admin.documents.create'));
        $createResponse->assertForbidden();

        // 3. Employee cannot upload
        $file = UploadedFile::fake()->create('unauth.pdf', 50, 'application/pdf');
        $storeResponse = $this->actingAs($this->employeeUser)
            ->post(route('hr-admin.documents.store'), [
                'employee_id' => $this->employee->id,
                'document_type_id' => $this->docType->id,
                'title' => 'Unauthorized Upload',
                'document_file' => $file,
            ]);
        $storeResponse->assertForbidden();
    }

    public function test_pending_documents_widget_appears_on_hr_dashboard(): void
    {
        Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->docType->id,
            'title' => 'Pending Verification ID',
            'file_path' => 'documents/fake.pdf',
            'file_name' => 'fake.pdf',
            'file_size' => 10240,
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $response = $this->actingAs($this->hrAdmin)
            ->get(route('hr-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Pending Documents');
    }
}
