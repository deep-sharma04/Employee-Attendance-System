<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectDocumentVersion;
use App\Models\ProjectMember;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase27ProjectDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employee;
    protected User $unauthorizedEmployee;
    protected User $clientUser;
    protected User $otherClientUser;
    protected Client $client;
    protected Client $otherClient;
    protected Team $team;
    protected Project $project;
    protected Project $otherProject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        Storage::fake('local');

        // 1. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // 2. Manager
        $this->manager = User::create([
            'name' => 'Project Manager',
            'username' => 'projmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        // 3. Team Lead
        $this->teamLead = User::create([
            'name' => 'Team Lead',
            'username' => 'teamlead',
            'email' => 'lead@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        // 4. Employee (Project Member)
        $this->employee = User::create([
            'name' => 'Dev Employee',
            'username' => 'devemployee',
            'email' => 'dev@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        // 5. Unauthorized Employee
        $this->unauthorizedEmployee = User::create([
            'name' => 'Other Employee',
            'username' => 'otheremp',
            'email' => 'other@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        // 6. Clients
        $this->client = Client::create([
            'company_name' => 'Apex Enterprises',
            'company_code' => 'CLI-APEX',
            'email' => 'contact@apex.com',
            'status' => ClientStatus::ACTIVE,
        ]);

        $this->clientUser = User::create([
            'name' => 'Apex Client User',
            'username' => 'apexclient',
            'email' => 'client@apex.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        ClientUser::create([
            'client_id' => $this->client->id,
            'user_id' => $this->clientUser->id,
        ]);

        $this->otherClient = Client::create([
            'company_name' => 'Beta Corp',
            'company_code' => 'CLI-BETA',
            'email' => 'contact@beta.com',
            'status' => ClientStatus::ACTIVE,
        ]);

        $this->otherClientUser = User::create([
            'name' => 'Beta Client User',
            'username' => 'betaclient',
            'email' => 'client@beta.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        ClientUser::create([
            'client_id' => $this->otherClient->id,
            'user_id' => $this->otherClientUser->id,
        ]);

        // 7. Team & Members
        $this->team = Team::create([
            'name' => 'Alpha Engineering',
            'code' => 'TEAM-ALPHA',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->employee->id,
            'is_primary' => true,
        ]);

        // 8. Projects
        $this->project = Project::create([
            'name' => 'Apex Cloud Migration',
            'code' => 'PRJ-APEX-01',
            'client_id' => $this->client->id,
            'manager_id' => $this->manager->id,
            'team_id' => $this->team->id,
            'created_by' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
        ]);

        ProjectMember::create([
            'project_id' => $this->project->id,
            'user_id' => $this->employee->id,
            'project_role' => \App\Enums\ProjectMemberRole::MEMBER,
            'joined_at' => now(),
        ]);

        $this->otherProject = Project::create([
            'name' => 'Beta ERP Implementation',
            'code' => 'PRJ-BETA-01',
            'client_id' => $this->otherClient->id,
            'manager_id' => $this->superAdmin->id,
            'created_by' => $this->superAdmin->id,
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::MEDIUM,
            'health' => ProjectHealth::GOOD,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(6)->toDateString(),
        ]);
    }

    /**
     * T249 & T250: Storage folder isolation and valid file upload CRUD
     */
    public function test_t249_t250_upload_and_manage_project_documents_with_isolated_storage(): void
    {
        $file = UploadedFile::fake()->create('Architecture_Design.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Architecture Specification',
            'description' => 'System architectural diagram and module breakdown.',
            'is_client_visible' => false,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = ProjectDocument::where('project_id', $this->project->id)->where('name', 'Architecture Specification')->first();
        $this->assertNotNull($document);
        $this->assertEquals(1, $document->current_version);
        $this->assertFalse($document->is_client_visible);

        $version = $document->latestVersion;
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
        $this->assertStringStartsWith("projects/{$this->project->id}/documents/", $version->file_path);
        $this->assertTrue(Storage::disk('local')->exists($version->file_path));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'project_document.uploaded',
            'target_type' => 'ProjectDocument',
            'target_id' => $document->id,
        ]);
    }

    /**
     * T250: Upload validation (2MB limit, restricted extensions/mimes)
     */
    public function test_t250_upload_validation_limits_and_disallowed_mime_types(): void
    {
        // 1. Oversized file (> 2048 KB)
        $oversizedFile = UploadedFile::fake()->create('big_file.pdf', 3000, 'application/pdf');

        $response = $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Oversized Doc',
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors('file');

        // 2. Disallowed file type (.exe / application/x-msdownload)
        $executableFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Malicious Script',
            'file' => $executableFile,
        ]);

        $response->assertSessionHasErrors('file');

        // 3. Disallowed script file (.php)
        $phpFile = UploadedFile::fake()->create('backdoor.php', 50, 'application/x-php');

        $response = $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'PHP Script',
            'file' => $phpFile,
        ]);

        $response->assertSessionHasErrors('file');
    }

    /**
     * T251: Project Documents listing and search UI
     */
    public function test_t251_documents_index_view_and_search_filter(): void
    {
        $file1 = UploadedFile::fake()->create('Spec1.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->create('Budget.xlsx', 300, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Backend API Specification',
            'description' => 'Endpoints and payload schemas.',
            'file' => $file1,
        ]);

        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Cost Estimation Model',
            'description' => 'Financial projection spreadsheet.',
            'file' => $file2,
        ]);

        // Access index page
        $response = $this->actingAs($this->manager)->get(route('manager.projects.documents.index', $this->project));
        $response->assertOk();
        $response->assertSee('Backend API Specification');
        $response->assertSee('Cost Estimation Model');

        // Search for specific document
        $searchResponse = $this->actingAs($this->manager)->get(route('manager.projects.documents.index', [
            'project' => $this->project,
            'search' => 'Backend API',
        ]));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Backend API Specification');
        $searchResponse->assertDontSee('Cost Estimation Model');
    }

    /**
     * T252: Document Versioning retaining exactly latest 10 versions and purging older versions automatically
     */
    public function test_t252_document_versioning_and_10_version_auto_purge(): void
    {
        $docName = 'Living System Requirements';
        $uploadedPaths = [];

        // Upload 12 successive versions with the same name
        for ($i = 1; $i <= 12; $i++) {
            $file = UploadedFile::fake()->create("req_v{$i}.docx", 100 + $i, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

            $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
                'name' => $docName,
                'description' => "Requirements update iteration {$i}",
                'file' => $file,
            ]);
        }

        $document = ProjectDocument::where('project_id', $this->project->id)->where('name', $docName)->first();
        $this->assertNotNull($document);
        $this->assertEquals(12, $document->current_version);

        // Verify exactly 10 latest versions are retained in the database (versions 3 through 12)
        $versions = ProjectDocumentVersion::where('project_document_id', $document->id)
            ->orderBy('version_number', 'asc')
            ->get();

        $this->assertCount(10, $versions);
        $this->assertEquals(3, $versions->first()->version_number);
        $this->assertEquals(12, $versions->last()->version_number);

        // Verify versions 1 and 2 are purged from DB
        $this->assertFalse(ProjectDocumentVersion::where('project_document_id', $document->id)->where('version_number', 1)->exists());
        $this->assertFalse(ProjectDocumentVersion::where('project_document_id', $document->id)->where('version_number', 2)->exists());

        // Verify all 10 retained version files physically exist on disk
        foreach ($versions as $ver) {
            $this->assertTrue(Storage::disk('local')->exists($ver->file_path));
        }

        // Verify audit log for purging
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'project_document.version_purged',
            'target_type' => 'ProjectDocument',
            'target_id' => $document->id,
        ]);
    }

    /**
     * T253: Client Document Sharing management & default privacy
     */
    public function test_t253_client_document_sharing_toggle_and_privacy_boundaries(): void
    {
        $file = UploadedFile::fake()->create('Client_Deliverable.pdf', 350, 'application/pdf');

        // 1. Upload by default is not shared with client
        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Milestone Deliverable 1',
            'description' => 'Signed off milestone document.',
            'is_client_visible' => false,
            'file' => $file,
        ]);

        $document = ProjectDocument::where('project_id', $this->project->id)->where('name', 'Milestone Deliverable 1')->first();
        $this->assertFalse($document->is_client_visible);

        // Client cannot view or download unshared document
        $clientResponse = $this->actingAs($this->clientUser)->get(route('client-portal.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]));
        $clientResponse->assertStatus(403);

        // 2. Manager toggles share
        $toggleResponse = $this->actingAs($this->manager)->post(route('manager.projects.documents.toggle-share', [
            'project' => $this->project,
            'document' => $document,
        ]));
        $toggleResponse->assertRedirect();

        $document->refresh();
        $this->assertTrue($document->is_client_visible);

        // 3. Client can now download shared document
        $clientDownloadResponse = $this->actingAs($this->clientUser)->get(route('client-portal.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]));
        $clientDownloadResponse->assertOk();
    }

    /**
     * T254: Access Control across Roles and IDOR protection
     */
    public function test_t254_role_based_access_and_idor_protection(): void
    {
        $file = UploadedFile::fake()->create('Confidential_Spec.pdf', 400, 'application/pdf');

        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Confidential Spec',
            'is_client_visible' => true,
            'file' => $file,
        ]);

        $document = ProjectDocument::where('project_id', $this->project->id)->where('name', 'Confidential Spec')->first();

        // 1. Super Admin can download
        $this->actingAs($this->superAdmin)->get(route('manager.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]))->assertOk();

        // 2. Team Lead can download & upload
        $this->actingAs($this->teamLead)->get(route('team-lead.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]))->assertOk();

        $tlFile = UploadedFile::fake()->create('Sprint_Plan.pdf', 200, 'application/pdf');
        $this->actingAs($this->teamLead)->post(route('team-lead.projects.documents.store', $this->project), [
            'name' => 'Sprint 1 Plan',
            'file' => $tlFile,
        ])->assertRedirect();

        // 3. Project Member Employee can download
        $this->actingAs($this->employee)->get(route('employee.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]))->assertOk();

        // 4. Unauthorized Employee (not on project) CANNOT access
        $this->actingAs($this->unauthorizedEmployee)->get(route('employee.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]))->assertStatus(403);

        // 5. Client from different company CANNOT access
        $this->actingAs($this->otherClientUser)->get(route('client-portal.projects.documents.download', [
            'project' => $this->project,
            'document' => $document,
        ]))->assertStatus(403);

        // 6. IDOR Protection: Accessing document belonging to Project A with Project B route param returns 404
        $this->actingAs($this->manager)->get(route('manager.projects.documents.download', [
            'project' => $this->otherProject,
            'document' => $document,
        ]))->assertStatus(404);
    }

    /**
     * T255: Project Knowledge Base Search across documents, tasks, and internal comments with strict client isolation
     */
    public function test_t255_project_knowledge_search_and_visibility_boundaries(): void
    {
        // 1. Create a project document
        $docFile = UploadedFile::fake()->create('OAuth_Guide.pdf', 150, 'application/pdf');
        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'OAuth2 Security Guidelines',
            'description' => 'Standard protocol configuration for JWT authentication.',
            'is_client_visible' => true,
            'file' => $docFile,
        ]);

        // 2. Create a task with description
        $task = Task::create([
            'project_id' => $this->project->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->employee->id,
            'task_code' => 'TSK-OAUTH-01',
            'title' => 'Implement OAuth Token Refresh Endpoint',
            'description' => 'Ensure JWT tokens are rotated periodically according to security guidelines.',
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::IN_PROGRESS,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        // 3. Create an internal comment
        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->manager->id,
            'comment' => 'Please review the JWT expiration threshold before deployment.',
            'is_internal' => true,
        ]);

        // Manager searches "OAuth"
        $managerSearch = $this->actingAs($this->manager)->get(route('manager.knowledge.index', [
            'q' => 'OAuth',
        ]));
        $managerSearch->assertOk();
        $managerSearch->assertSee('OAuth2 Security Guidelines');
        $managerSearch->assertSee('Implement OAuth Token Refresh Endpoint');

        // Manager searches comment keyword "JWT"
        $commentSearch = $this->actingAs($this->manager)->get(route('manager.knowledge.index', [
            'q' => 'JWT',
        ]));
        $commentSearch->assertOk();
        $commentSearch->assertSee('Please review the JWT expiration threshold');

        // Client searches "OAuth" -> can see document and task
        $clientSearch = $this->actingAs($this->clientUser)->get(route('client-portal.knowledge.index', [
            'q' => 'OAuth',
        ]));
        $clientSearch->assertOk();
        $clientSearch->assertSee('OAuth2 Security Guidelines');
        $clientSearch->assertSee('Implement OAuth Token Refresh Endpoint');

        // Client searches "JWT" -> CANNOT see internal task comment (Strict Isolation)
        $clientCommentSearch = $this->actingAs($this->clientUser)->get(route('client-portal.knowledge.index', [
            'q' => 'threshold',
        ]));
        $clientCommentSearch->assertOk();
        $clientCommentSearch->assertDontSee('Please review the JWT expiration threshold');
    }

    /**
     * T250 & T254: Delete document removes all files from disk and records audit log
     */
    public function test_t250_delete_project_document_removes_all_versions_and_storage(): void
    {
        $file1 = UploadedFile::fake()->create('Spec_v1.pdf', 100, 'application/pdf');
        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Delete Target Doc',
            'file' => $file1,
        ]);

        $file2 = UploadedFile::fake()->create('Spec_v2.pdf', 150, 'application/pdf');
        $this->actingAs($this->manager)->post(route('manager.projects.documents.store', $this->project), [
            'name' => 'Delete Target Doc',
            'file' => $file2,
        ]);

        $document = ProjectDocument::where('project_id', $this->project->id)->where('name', 'Delete Target Doc')->first();
        $this->assertNotNull($document);
        $v1Path = $document->versions->where('version_number', 1)->first()->file_path;
        $v2Path = $document->versions->where('version_number', 2)->first()->file_path;

        $this->assertTrue(Storage::disk('local')->exists($v1Path));
        $this->assertTrue(Storage::disk('local')->exists($v2Path));

        // Delete document
        $deleteResponse = $this->actingAs($this->manager)->delete(route('manager.projects.documents.destroy', [
            'project' => $this->project,
            'document' => $document,
        ]));
        $deleteResponse->assertRedirect();

        // Verify document and versions are deleted from DB
        $this->assertDatabaseMissing('project_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('project_document_versions', ['project_document_id' => $document->id]);

        // Verify physical files are cleaned up from storage
        $this->assertFalse(Storage::disk('local')->exists($v1Path));
        $this->assertFalse(Storage::disk('local')->exists($v2Path));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'project_document.deleted',
            'target_type' => 'ProjectDocument',
            'target_id' => $document->id,
        ]);
    }
}
