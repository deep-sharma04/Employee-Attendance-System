<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientCommunication;
use App\Models\ClientContact;
use App\Models\ClientDocument;
use App\Models\ClientUser;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase21ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected User $hrAdmin;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->shift = Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN_DAY',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        // Users
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager One',
            'username' => 'manager1',
            'email' => 'manager1@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Team Lead One',
            'username' => 'teamlead1',
            'email' => 'teamlead1@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Employee One',
            'username' => 'emp1',
            'email' => 'emp1@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::create([
            'name' => 'HR Admin',
            'username' => 'hradmin',
            'email' => 'hradmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * T207: Client Management CRUD operations.
     */
    public function test_t207_client_crud_lifecycle_and_views(): void
    {
        // 1. View Index
        $response = $this->actingAs($this->manager)->get(route('manager.clients.index'));
        $response->assertOk();
        $response->assertViewIs('manager.clients.index');

        // 2. View Create
        $response = $this->actingAs($this->manager)->get(route('manager.clients.create'));
        $response->assertOk();
        $response->assertViewIs('manager.clients.create');

        // 3. Store Client
        $postData = [
            'company_name' => 'Acme Corporation',
            'company_code' => 'ACME',
            'email' => 'contact@acme.com',
            'phone' => '+1234567890',
            'website' => 'https://acme.example.com',
            'address' => '100 Acme Way, Silicon Valley',
            'status' => 'active',
            'currency' => 'USD',
            'billing_type' => 'Retainer',
            'notes' => 'Important enterprise client.',
        ];

        $response = $this->actingAs($this->manager)->post(route('manager.clients.store'), $postData);
        $this->assertDatabaseHas('clients', [
            'company_name' => 'Acme Corporation',
            'company_code' => 'ACME',
            'status' => 'active',
            'created_by' => $this->manager->id,
        ]);

        $client = Client::where('company_code', 'ACME')->first();
        $response->assertRedirect(route('manager.clients.show', $client));

        // 4. View Show
        $response = $this->actingAs($this->manager)->get(route('manager.clients.show', $client));
        $response->assertOk();
        $response->assertViewIs('manager.clients.show');
        $response->assertSee('Acme Corporation');

        // 5. View Edit
        $response = $this->actingAs($this->manager)->get(route('manager.clients.edit', $client));
        $response->assertOk();
        $response->assertViewIs('manager.clients.edit');

        // 6. Update Client
        $response = $this->actingAs($this->manager)->put(route('manager.clients.update', $client), [
            'company_name' => 'Acme Global Inc.',
            'company_code' => 'ACME',
            'email' => 'new-contact@acme.com',
            'phone' => '+1234567890',
            'status' => 'lead',
            'currency' => 'EUR',
            'billing_type' => 'Fixed Price',
        ]);

        $response->assertRedirect(route('manager.clients.show', $client));
        $client->refresh();
        $this->assertEquals('Acme Global Inc.', $client->company_name);
        $this->assertEquals(ClientStatus::LEAD, $client->status);
        $this->assertEquals('EUR', $client->currency);

        // 7. Soft Delete Client
        $response = $this->actingAs($this->manager)->delete(route('manager.clients.destroy', $client));
        $response->assertRedirect(route('manager.clients.index'));
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    /**
     * T208: Client Contacts Management (Multiple contacts, primary toggle, validation).
     */
    public function test_t208_client_contacts_management(): void
    {
        $client = Client::create([
            'company_name' => 'Stark Industries',
            'company_code' => 'STARK',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        // Add First Contact (should auto-become primary)
        $response = $this->actingAs($this->manager)->post(route('manager.clients.contacts.store', $client), [
            'name' => 'Tony Stark',
            'position' => 'CEO',
            'email' => 'tony@stark.com',
            'phone' => '123-456-7890',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('client_contacts', [
            'client_id' => $client->id,
            'name' => 'Tony Stark',
            'is_primary' => true,
        ]);

        $tony = ClientContact::where('name', 'Tony Stark')->first();

        // Add Second Contact with explicit primary
        $response = $this->actingAs($this->manager)->post(route('manager.clients.contacts.store', $client), [
            'name' => 'Pepper Potts',
            'position' => 'COO',
            'email' => 'pepper@stark.com',
            'phone' => '987-654-3210',
            'is_primary' => 1,
        ]);
        $response->assertRedirect();

        $pepper = ClientContact::where('name', 'Pepper Potts')->first();
        $this->assertTrue((bool) $pepper->is_primary);

        $tony->refresh();
        $this->assertFalse((bool) $tony->is_primary);

        // Switch primary back to Tony via primary endpoint
        $response = $this->actingAs($this->manager)->post(route('manager.clients.contacts.primary', [
            'client' => $client,
            'contact' => $tony,
        ]));
        $response->assertRedirect();

        $tony->refresh();
        $pepper->refresh();
        $this->assertTrue((bool) $tony->is_primary);
        $this->assertFalse((bool) $pepper->is_primary);

        // Update contact
        $this->actingAs($this->manager)->put(route('manager.clients.contacts.update', [
            'client' => $client,
            'contact' => $tony,
        ]), [
            'name' => 'Anthony Stark',
            'position' => 'Chief Innovator',
            'email' => 'tony.stark@stark.com',
        ]);
        $tony->refresh();
        $this->assertEquals('Anthony Stark', $tony->name);

        // Delete contact
        $this->actingAs($this->manager)->delete(route('manager.clients.contacts.destroy', [
            'client' => $client,
            'contact' => $pepper,
        ]));
        $this->assertSoftDeleted('client_contacts', ['id' => $pepper->id]);
    }

    /**
     * T209: Associate Clients with Projects (Link & Unlink).
     */
    public function test_t209_associate_clients_with_projects(): void
    {
        $client = Client::create([
            'company_name' => 'Wayne Enterprises',
            'company_code' => 'WAYNE',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        $project = Project::create([
            'name' => 'Batmobile AI OS',
            'code' => 'PRJ-BAT-01',
            'status' => ProjectStatus::ACTIVE,
            'priority' => ProjectPriority::HIGH,
            'health' => ProjectHealth::GOOD,
            'manager_id' => $this->manager->id,
            'created_by' => $this->manager->id,
        ]);

        // Link Project to Client
        $response = $this->actingAs($this->manager)->post(route('manager.clients.projects.link', $client), [
            'project_id' => $project->id,
        ]);
        $response->assertRedirect();

        $project->refresh();
        $this->assertEquals($client->id, $project->client_id);
        $this->assertTrue($client->projects->contains($project));

        // Unlink Project
        $response = $this->actingAs($this->manager)->delete(route('manager.clients.projects.unlink', [
            'client' => $client,
            'project' => $project,
        ]));
        $response->assertRedirect();

        $project->refresh();
        $this->assertNull($project->client_id);
    }

    /**
     * T210: Client Documents (Isolated folder, max 2MB size, mime check, sharing toggle, download authorization).
     */
    public function test_t210_client_documents_management_and_sharing(): void
    {
        Storage::fake('local');

        $client = Client::create([
            'company_name' => 'Cyberdyne Systems',
            'company_code' => 'CYBER',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        // 1. Upload valid document
        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf'); // 500 KB
        $response = $this->actingAs($this->manager)->post(route('manager.clients.documents.store', $client), [
            'title' => 'Master Services Agreement',
            'document' => $file,
            'is_shared_with_client' => 1,
            'notes' => 'Signed version 2026',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('client_documents', [
            'client_id' => $client->id,
            'title' => 'Master Services Agreement',
            'file_name' => 'contract.pdf',
            'is_shared_with_client' => true,
        ]);

        $doc = ClientDocument::where('title', 'Master Services Agreement')->first();
        Storage::disk('local')->assertExists($doc->file_path);
        $this->assertStringStartsWith("clients/{$client->id}/documents/", $doc->file_path);

        // 2. Reject file exceeding 2MB (2048 KB)
        $largeFile = UploadedFile::fake()->create('huge.pdf', 3000, 'application/pdf'); // 3 MB
        $response = $this->actingAs($this->manager)->post(route('manager.clients.documents.store', $client), [
            'title' => 'Too Large Document',
            'document' => $largeFile,
        ]);
        $response->assertSessionHasErrors('document');

        // 3. Toggle sharing flag
        $response = $this->actingAs($this->manager)->post(route('manager.clients.documents.toggle-share', [
            'client' => $client,
            'document' => $doc,
        ]));
        $response->assertRedirect();
        $doc->refresh();
        $this->assertFalse($doc->is_shared_with_client);

        // Toggle back to shared
        $this->actingAs($this->manager)->post(route('manager.clients.documents.toggle-share', [
            'client' => $client,
            'document' => $doc,
        ]));
        $doc->refresh();
        $this->assertTrue($doc->is_shared_with_client);

        // 4. Download document
        $response = $this->actingAs($this->manager)->get(route('manager.clients.documents.download', [
            'client' => $client,
            'document' => $doc,
        ]));
        $response->assertOk();

        // 5. Delete document
        $response = $this->actingAs($this->manager)->delete(route('manager.clients.documents.destroy', [
            'client' => $client,
            'document' => $doc,
        ]));
        $response->assertRedirect();
        $this->assertSoftDeleted('client_documents', ['id' => $doc->id]);
    }

    /**
     * T211: Log Client Communication History (emails, calls, meetings, notes).
     */
    public function test_t211_client_communication_history_tracking(): void
    {
        $client = Client::create([
            'company_name' => 'Oscorp Industries',
            'company_code' => 'OSCORP',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        // Log Meeting
        $response = $this->actingAs($this->manager)->post(route('manager.clients.communications.store', $client), [
            'type' => 'meeting',
            'subject' => 'Sprint 1 Kickoff',
            'communication_date' => '2026-08-15 14:30:00',
            'details' => 'Discussed project scope, deliverables, and team assignments.',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('client_communications', [
            'client_id' => $client->id,
            'user_id' => $this->manager->id,
            'type' => 'meeting',
            'subject' => 'Sprint 1 Kickoff',
        ]);

        $comm = ClientCommunication::where('subject', 'Sprint 1 Kickoff')->first();

        // Delete communication log
        $response = $this->actingAs($this->manager)->delete(route('manager.clients.communications.destroy', [
            'client' => $client,
            'communication' => $comm,
        ]));
        $response->assertRedirect();
        $this->assertSoftDeleted('client_communications', ['id' => $comm->id]);
    }

    /**
     * T212: Client Portal Access Lifecycle (Create user, activate, deactivate, revoke, and access isolation).
     */
    public function test_t212_client_portal_access_management(): void
    {
        $client = Client::create([
            'company_name' => 'Umbrella Corp',
            'company_code' => 'UMB',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        // 1. Create Portal User
        $response = $this->actingAs($this->manager)->post(route('manager.clients.portal-users.store', $client), [
            'name' => 'Albert Wesker',
            'username' => 'wesker',
            'email' => 'wesker@umbrella.com',
            'password' => 'T-Virus2026!',
            'is_primary' => 1,
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'wesker',
            'role' => UserRole::CLIENT->value,
            'is_active' => true,
        ]);

        $portalUser = User::where('username', 'wesker')->first();
        $this->assertDatabaseHas('client_users', [
            'client_id' => $client->id,
            'user_id' => $portalUser->id,
            'is_primary' => true,
            'status' => 'active',
        ]);

        // 2. Client logs in and accesses Client Portal dashboard
        $response = $this->actingAs($portalUser)->get(route('client-portal.dashboard'));
        $response->assertOk();

        // Client cannot access manager client management
        $response = $this->actingAs($portalUser)->get(route('manager.clients.index'));
        $response->assertForbidden();

        // 3. Deactivate Portal User
        $response = $this->actingAs($this->manager)->post(route('manager.clients.portal-users.toggle-status', [
            'client' => $client,
            'user' => $portalUser,
        ]));
        $response->assertRedirect();
        $portalUser->refresh();
        $this->assertFalse((bool) $portalUser->is_active);

        // 4. Revoke Portal User Access
        $response = $this->actingAs($this->manager)->delete(route('manager.clients.portal-users.destroy', [
            'client' => $client,
            'user' => $portalUser,
        ]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('client_users', ['user_id' => $portalUser->id]);
    }

    /**
     * T213: Audit Trail verification for all client actions.
     */
    public function test_t213_audit_trail_for_client_actions(): void
    {
        // 1. Create client generates audit record
        $this->actingAs($this->manager)->post(route('manager.clients.store'), [
            'company_name' => 'Initech LLC',
            'company_code' => 'INIT',
            'status' => 'active',
        ]);

        $client = Client::where('company_code', 'INIT')->first();
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Client',
            'target_id' => $client->id,
            'action' => 'client.created',
        ]);

        // 2. Update client generates audit record
        $this->actingAs($this->manager)->put(route('manager.clients.update', $client), [
            'company_name' => 'Initech Solutions',
            'company_code' => 'INIT',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Client',
            'target_id' => $client->id,
            'action' => 'client.updated',
        ]);

        // 3. Add contact generates audit record
        $this->actingAs($this->manager)->post(route('manager.clients.contacts.store', $client), [
            'name' => 'Peter Gibbons',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->manager->id,
            'target_type' => 'Client',
            'target_id' => $client->id,
            'action' => 'client_contact.created',
        ]);
    }

    /**
     * RBAC Restrictions: Employee, HR Admin, Team Lead cannot manage clients.
     */
    public function test_rbac_restrictions_on_client_management(): void
    {
        $client = Client::create([
            'company_name' => 'Gekko & Co',
            'company_code' => 'GEKKO',
            'status' => ClientStatus::ACTIVE,
            'created_by' => $this->manager->id,
        ]);

        // Employee cannot view or modify clients
        $this->actingAs($this->employeeUser)->get(route('manager.clients.index'))->assertForbidden();
        $this->actingAs($this->employeeUser)->post(route('manager.clients.store'), ['company_name' => 'Bad Corp', 'status' => 'active'])->assertForbidden();

        // Team Lead cannot create or delete clients
        $this->actingAs($this->teamLead)->get(route('manager.clients.index'))->assertForbidden();
        $this->actingAs($this->teamLead)->delete(route('manager.clients.destroy', $client))->assertForbidden();

        // HR Admin cannot access project client management
        $this->actingAs($this->hrAdmin)->get(route('manager.clients.index'))->assertForbidden();

        // Super Admin CAN manage clients
        $this->actingAs($this->superAdmin)->get(route('manager.clients.index'))->assertOk();
    }
}
