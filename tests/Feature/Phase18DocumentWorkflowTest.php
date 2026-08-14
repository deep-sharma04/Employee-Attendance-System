<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase18DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrAdmin;
    protected User $employeeUser;
    protected Employee $employee;
    protected DocumentType $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
        ]);

        $this->documentType = DocumentType::create([
            'name' => 'Government ID / Passport',
            'slug' => 'gov-id',
            'is_mandatory' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T187: Document Upload Flows
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_upload_valid_document_for_employee(): void
    {
        $fakePdf = UploadedFile::fake()->create('passport_copy.pdf', 250, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.documents.store'), [
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->documentType->id,
            'title' => 'Employee Passport Copy',
            'document_file' => $fakePdf,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = Document::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($document);
        $this->assertEquals(DocumentStatus::PENDING, $document->status);
        $this->assertEquals('passport_copy.pdf', $document->file_name);

        Storage::disk('local')->assertExists($document->file_path);

        // Audit Trail recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.uploaded',
            'target_type' => 'Document',
            'target_id' => $document->id,
        ]);
    }

    public function test_document_upload_rejects_oversized_files(): void
    {
        // 600 KB file (exceeds 500 KB limit)
        $oversizedPdf = UploadedFile::fake()->create('huge_document.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.documents.store'), [
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->documentType->id,
            'title' => 'Oversized Document',
            'document_file' => $oversizedPdf,
        ]);

        $response->assertSessionHasErrors(['document_file']);
        $this->assertDatabaseMissing('documents', ['title' => 'Oversized Document']);
    }

    /*
    |--------------------------------------------------------------------------
    | T187: Document Verification Flows
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_verify_uploaded_document(): void
    {
        $fakePath = 'documents/passport_sample.pdf';
        Storage::disk('local')->put($fakePath, '%PDF-1.4 Mock Document');

        $document = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->documentType->id,
            'title' => 'National ID',
            'file_path' => $fakePath,
            'file_name' => 'national_id.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.documents.verify', $document->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals(DocumentStatus::VERIFIED, $document->status);
        $this->assertEquals($this->hrAdmin->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);

        // Employee notification dispatched
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'document_verified',
        ]);

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.verified',
            'target_type' => 'Document',
            'target_id' => $document->id,
        ]);
    }

    public function test_hr_admin_can_reject_uploaded_document_with_reason(): void
    {
        $fakePath = 'documents/id_sample.pdf';
        Storage::disk('local')->put($fakePath, '%PDF-1.4 Mock Document');

        $document = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->documentType->id,
            'title' => 'Expired Driving License',
            'file_path' => $fakePath,
            'file_name' => 'license.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::PENDING,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('hr-admin.documents.reject', $document->id), [
            'rejection_reason' => 'Document expired in 2024. Please upload an active identity card.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals(DocumentStatus::REJECTED, $document->status);
        $this->assertEquals('Document expired in 2024. Please upload an active identity card.', $document->rejection_reason);

        // Employee notification dispatched
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'document_rejected',
        ]);

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.rejected',
            'target_type' => 'Document',
            'target_id' => $document->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | T187: Document Access & Download Flows
    |--------------------------------------------------------------------------
    */
    public function test_hr_admin_can_view_and_download_document_file(): void
    {
        $fakePath = 'documents/viewable_doc.pdf';
        Storage::disk('local')->put($fakePath, '%PDF-1.4 Content To View');

        $document = Document::create([
            'employee_id' => $this->employee->id,
            'document_type_id' => $this->documentType->id,
            'title' => 'Official Certificate',
            'file_path' => $fakePath,
            'file_name' => 'certificate.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::VERIFIED,
            'uploaded_by' => $this->hrAdmin->id,
        ]);

        // 1. View file inline
        $viewResponse = $this->actingAs($this->hrAdmin)->get(route('hr-admin.documents.view', $document->id));
        $viewResponse->assertOk();
        $viewResponse->assertHeader('Content-Type', 'application/pdf');

        // 2. Download file
        $downloadResponse = $this->actingAs($this->hrAdmin)->get(route('hr-admin.documents.download', $document->id));
        $downloadResponse->assertOk();
    }
}
