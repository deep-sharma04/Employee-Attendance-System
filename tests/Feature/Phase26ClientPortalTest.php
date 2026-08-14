<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientDocument;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Shift;
use App\Models\Task;
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

class Phase26ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $clientUser;
    protected Client $client;
    protected User $otherClientUser;
    protected Client $otherClient;
    protected Project $clientProject;
    protected Project $otherClientProject;
    protected ClientDocument $sharedDocument;
    protected ClientDocument $unsharedDocument;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        Storage::fake('local');

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Project Manager',
            'username' => 'projmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        // Client 1: Apex Enterprises
        $this->client = Client::create([
            'company_name' => 'Apex Enterprises',
            'company_code' => 'CLI-APEX',
            'email' => 'contact@apex.com',
            'status' => ClientStatus::ACTIVE,
        ]);

        $this->clientUser = User::create([
            'name' => 'Alice Client',
            'username' => 'aliceclient',
            'email' => 'alice@apex.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        ClientUser::create([
            'client_id' => $this->client->id,
            'user_id' => $this->clientUser->id,
            'is_primary' => true,
        ]);

        // Client 2: Beta Corp
        $this->otherClient = Client::create([
            'company_name' => 'Beta Corp',
            'company_code' => 'CLI-BETA',
            'email' => 'info@beta.com',
            'status' => ClientStatus::ACTIVE,
        ]);

        $this->otherClientUser = User::create([
            'name' => 'Bob Beta',
            'username' => 'bobbeta',
            'email' => 'bob@beta.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        ClientUser::create([
            'client_id' => $this->otherClient->id,
            'user_id' => $this->otherClientUser->id,
            'is_primary' => true,
        ]);

        // Projects
        $this->clientProject = Project::create([
            'name' => 'Apex Mobile Banking App',
            'code' => 'PROJ-APEX-01',
            'client_id' => $this->client->id,
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'health' => ProjectHealth::GOOD->value,
            'start_date' => '2026-08-01',
            'deadline' => '2026-12-31',
            'description' => 'Secure omnichannel mobile banking application for Apex banking customers.',
        ]);

        ProjectMilestone::create([
            'project_id' => $this->clientProject->id,
            'title' => 'Phase 1: Architecture & UX',
            'due_date' => '2026-08-30',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        ProjectMilestone::create([
            'project_id' => $this->clientProject->id,
            'title' => 'Phase 2: Core Banking API Integration',
            'due_date' => '2026-10-31',
            'status' => 'in_progress',
        ]);

        Task::create([
            'project_id' => $this->clientProject->id,
            'title' => 'Biometric Authentication SDK',
            'task_code' => 'TSK-APEX-01',
            'status' => TaskStatus::DONE->value,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => '2026-08-25',
        ]);

        $this->otherClientProject = Project::create([
            'name' => 'Beta Logistics Portal',
            'code' => 'PROJ-BETA-01',
            'client_id' => $this->otherClient->id,
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::MEDIUM->value,
            'health' => ProjectHealth::GOOD->value,
        ]);

        // Documents
        $file1 = UploadedFile::fake()->create('apex_specifications.pdf', 200, 'application/pdf');
        $path1 = $file1->store('clients/' . $this->client->id . '/documents', 'local');

        $this->sharedDocument = ClientDocument::create([
            'client_id' => $this->client->id,
            'uploaded_by' => $this->manager->id,
            'title' => 'Apex Architecture Specification',
            'file_path' => $path1,
            'file_name' => 'apex_specifications.pdf',
            'file_size' => 204800,
            'mime_type' => 'application/pdf',
            'is_shared_with_client' => true,
            'notes' => 'Final sign-off technical architecture design document.',
        ]);

        $file2 = UploadedFile::fake()->create('apex_internal_audit.pdf', 150, 'application/pdf');
        $path2 = $file2->store('clients/' . $this->client->id . '/documents', 'local');

        $this->unsharedDocument = ClientDocument::create([
            'client_id' => $this->client->id,
            'uploaded_by' => $this->manager->id,
            'title' => 'Apex Internal Cost & Margin Audit',
            'file_path' => $path2,
            'file_name' => 'apex_internal_audit.pdf',
            'file_size' => 153600,
            'mime_type' => 'application/pdf',
            'is_shared_with_client' => false,
            'notes' => 'Internal margin estimates. Strictly confidential.',
        ]);
    }

    /**
     * T243: Client Login & Dashboard with Permitted Projects Only.
     */
    public function test_t243_client_dashboard_displays_only_permitted_projects_and_kpis(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.dashboard'));
        $response->assertOk();
        $response->assertViewIs('client-portal.dashboard');

        // Permitted project is visible
        $response->assertSee('Apex Mobile Banking App');
        $response->assertSee('PROJ-APEX-01');

        // Other client's project is strictly hidden
        $response->assertDontSee('Beta Logistics Portal');
        $response->assertDontSee('PROJ-BETA-01');

        // Shared document is visible in preview
        $response->assertSee('Apex Architecture Specification');
        // Unshared internal document is hidden
        $response->assertDontSee('Apex Internal Cost & Margin Audit');
    }

    /**
     * T244: Client Views Read-Only Project Progress & Milestones.
     */
    public function test_t244_client_views_project_progress_and_milestones_read_only(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.projects.show', $this->clientProject));
        $response->assertOk();
        $response->assertViewIs('client-portal.project-show');

        // Verify milestone & deliverable details
        $response->assertSee('Phase 1: Architecture & UX');
        $response->assertSee('Phase 2: Core Banking API Integration');
        $response->assertSee('Biometric Authentication SDK');
        $response->assertSee('TSK-APEX-01');

        // Verify strictly read-only: no edit or creation actions
        $response->assertDontSee('Edit Project');
        $response->assertDontSee('Delete Project');
        $response->assertDontSee('Create Milestone');
        $response->assertDontSee('Add Task');

        // Verify Audit Log entry created (T248)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->clientUser->id,
            'target_type' => 'Project',
            'action' => 'client.project_viewed',
            'target_id' => $this->clientProject->id,
        ]);
    }

    /**
     * T245: Client Views and Downloads Only Shared Documents.
     */
    public function test_t245_client_views_and_downloads_shared_documents(): void
    {
        // 1. Documents list view
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.documents.index'));
        $response->assertOk();
        $response->assertViewIs('client-portal.documents');

        $response->assertSee('Apex Architecture Specification');
        $response->assertDontSee('Apex Internal Cost & Margin Audit');

        // 2. Download shared document
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.documents.download', $this->sharedDocument));
        $response->assertOk();

        // 3. Attempt downloading unshared document -> 403 Forbidden
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.documents.download', $this->unsharedDocument));
        $response->assertForbidden();

        // 4. Verify Audit Log entry created (T248)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->clientUser->id,
            'action' => 'client.document_downloaded',
        ]);
    }

    /**
     * T246: Enforce Strict Read-Only Data Isolation & Cross-Tenant Protection.
     */
    public function test_t246_cross_tenant_isolation_and_route_blocking(): void
    {
        // 1. Client user cannot view another client's project -> 403 Forbidden
        $response = $this->actingAs($this->clientUser)->get(route('client-portal.projects.show', $this->otherClientProject));
        $response->assertForbidden();

        // Verify access denied audit logged (T248)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->clientUser->id,
            'action' => 'client.access_denied',
            'target_id' => $this->otherClientProject->id,
        ]);

        // 2. Client user is blocked from internal operational portals -> 403 Forbidden
        $this->actingAs($this->clientUser)->get(route('manager.dashboard'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('manager.projects.index'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('hr-admin.dashboard'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('hr-admin.employees.index'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('super-admin.dashboard'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('team-lead.dashboard'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('employee.dashboard'))->assertForbidden();
    }

    /**
     * T247: Reject Client Write Operations.
     */
    public function test_t247_client_cannot_perform_write_mutations(): void
    {
        // 1. Client cannot create projects
        $this->actingAs($this->clientUser)->post(route('manager.projects.store'), [
            'name' => 'Illegal Client Project',
            'client_id' => $this->client->id,
        ])->assertForbidden();

        // 2. Client cannot create tasks
        $this->actingAs($this->clientUser)->post(route('manager.tasks.store'), [
            'project_id' => $this->clientProject->id,
            'title' => 'Illegal Task',
        ])->assertForbidden();

        // 3. Client cannot submit timesheets
        $this->actingAs($this->clientUser)->post('/employee/timesheets', [
            'period_type' => 'weekly',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
        ])->assertForbidden();
    }
}
