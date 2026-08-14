<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TimesheetStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use App\Services\Project\ProjectLaborCostService;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase25ManualTimesheetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $teamLead;
    protected User $employeeUser;
    protected Employee $employee;
    protected User $otherEmployeeUser;
    protected Employee $otherEmployee;
    protected User $clientUser;
    protected User $hrAdmin;
    protected Project $project;
    protected Task $task;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $shift = Shift::create([
            'name' => 'General Day Shift',
            'code' => 'GEN_DAY',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'grace_period_minutes' => 15,
            'half_day_threshold_minutes' => 60,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Engineering Manager',
            'username' => 'engmanager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $this->teamLead = User::create([
            'name' => 'Tech Lead',
            'username' => 'techlead',
            'email' => 'lead@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::TEAM_LEAD,
            'is_active' => true,
        ]);

        $this->hrAdmin = User::create([
            'name' => 'HR Admin',
            'username' => 'hradmin',
            'email' => 'hr@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::HR_ADMIN,
            'is_active' => true,
        ]);

        $this->clientUser = User::create([
            'name' => 'Client Portal User',
            'username' => 'clientuser',
            'email' => 'client@acme.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'John Developer',
            'username' => 'johndev',
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-101',
            'first_name' => 'John',
            'last_name' => 'Developer',
            'email' => 'john@example.com',
            'gender' => 'male',
            'date_of_birth' => '1994-04-12',
            'joining_date' => '2023-01-15',
            'department' => 'Engineering',
            'designation' => 'Senior Backend Developer',
            'monthly_salary' => 5280.00, // 5280 / (22 * 8 = 176) = 30.00 / hr
            'shift_id' => $shift->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $this->otherEmployeeUser = User::create([
            'name' => 'Jane Engineer',
            'username' => 'janeeng',
            'email' => 'jane@example.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $this->otherEmployee = Employee::create([
            'user_id' => $this->otherEmployeeUser->id,
            'employee_code' => 'EMP-102',
            'first_name' => 'Jane',
            'last_name' => 'Engineer',
            'email' => 'jane@example.com',
            'gender' => 'female',
            'date_of_birth' => '1996-08-22',
            'joining_date' => '2023-03-01',
            'department' => 'Engineering',
            'designation' => 'Frontend Engineer',
            'monthly_salary' => 3520.00, // 3520 / 176 = 20.00 / hr
            'shift_id' => $shift->id,
            'status' => EmployeeStatus::ACTIVE,
        ]);

        $client = Client::create([
            'company_name' => 'Apex Enterprises',
            'company_code' => 'CLI-APEX',
            'email' => 'info@apex.com',
            'status' => \App\Enums\ClientStatus::ACTIVE,
        ]);

        $this->team = Team::create([
            'name' => 'Alpha Squad',
            'code' => 'SQUAD-ALPHA',
            'manager_id' => $this->manager->id,
            'team_lead_id' => $this->teamLead->id,
            'is_active' => true,
        ]);

        TeamMember::create([
            'team_id' => $this->team->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'is_primary' => true,
        ]);

        $this->project = Project::create([
            'name' => 'Cloud Migration System',
            'code' => 'PROJ-CLOUD-01',
            'client_id' => $client->id,
            'team_id' => $this->team->id,
            'manager_id' => $this->manager->id,
            'status' => ProjectStatus::ACTIVE->value,
            'priority' => ProjectPriority::HIGH->value,
            'health' => ProjectHealth::GOOD->value,
        ]);

        $this->task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Kubernetes Helm Chart Deployment',
            'task_code' => 'TSK-K8S-01',
            'assigned_to' => $this->employeeUser->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'estimated_hours' => 16.0,
            'actual_hours' => 0.0,
        ]);
    }

    /**
     * T236 & T237: Timesheets Creation, Work Log Entry, and Views.
     */
    public function test_t236_t237_employee_timesheet_creation_and_work_logging(): void
    {
        // 1. View Timesheets List
        $response = $this->actingAs($this->employeeUser)->get(route('employee.timesheets.index'));
        $response->assertOk();
        $response->assertViewIs('employee.timesheets.index');

        // 2. View Create Form
        $response = $this->actingAs($this->employeeUser)->get(route('employee.timesheets.create'));
        $response->assertOk();
        $response->assertViewIs('employee.timesheets.create');

        // 3. Create Draft Timesheet
        $response = $this->actingAs($this->employeeUser)->post(route('employee.timesheets.store'), [
            'period_type' => 'weekly',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
        ]);

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'status' => TimesheetStatus::DRAFT->value,
        ]);

        $timesheet = Timesheet::where('employee_id', $this->employee->id)->first();
        $response->assertRedirect(route('employee.timesheets.show', $timesheet));

        // 4. Add Work Log Entry
        $response = $this->actingAs($this->employeeUser)->post(route('employee.timesheets.entries.store', $timesheet), [
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => '2026-08-11',
            'hours' => 6.5,
            'is_billable' => 1,
            'description' => 'Configured ingress controllers and SSL cert-manager.',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('timesheet_entries', [
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => '2026-08-11',
            'hours' => 6.5,
            'is_billable' => true,
        ]);

        $timesheet->refresh();
        $this->assertEquals(6.5, (float) $timesheet->total_hours);

        // 5. Add second work log entry
        $this->actingAs($this->employeeUser)->post(route('employee.timesheets.entries.store', $timesheet), [
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => '2026-08-12',
            'hours' => 4.0,
            'is_billable' => 1,
            'description' => 'Load testing helm charts on staging cluster.',
        ]);

        $timesheet->refresh();
        $this->assertEquals(10.5, (float) $timesheet->total_hours);
        $this->assertEquals(2, $timesheet->entries()->count());

        // 6. Delete an entry
        $entryToDelete = $timesheet->entries()->where('entry_date', '2026-08-12')->first();
        $response = $this->actingAs($this->employeeUser)->delete(route('employee.timesheets.entries.destroy', ['timesheet' => $timesheet, 'entry' => $entryToDelete]));
        $response->assertRedirect();

        $timesheet->refresh();
        $this->assertEquals(6.5, (float) $timesheet->total_hours);
        $this->assertEquals(1, $timesheet->entries()->count());
    }

    /**
     * T238: Timesheet Submission and Lock Against Modifications.
     */
    public function test_t238_timesheet_submission_and_editing_lock(): void
    {
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'status' => TimesheetStatus::DRAFT->value,
            'total_hours' => 8.0,
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'entry_date' => '2026-08-10',
            'hours' => 8.0,
        ]);

        // 1. Submit Timesheet
        $response = $this->actingAs($this->employeeUser)->post(route('employee.timesheets.submit', $timesheet));
        $response->assertRedirect();

        $timesheet->refresh();
        $this->assertEquals(TimesheetStatus::SUBMITTED, $timesheet->status);
        $this->assertNotNull($timesheet->submitted_at);
        $this->assertTrue($timesheet->isLocked());
        $this->assertFalse($timesheet->isEditable());

        // 2. Prevent adding entry while locked
        $response = $this->actingAs($this->employeeUser)->post(route('employee.timesheets.entries.store', $timesheet), [
            'project_id' => $this->project->id,
            'entry_date' => '2026-08-11',
            'hours' => 4.0,
        ]);
        $response->assertSessionHas('error');

        // 3. Prevent deleting entry while locked
        $entry = $timesheet->entries()->first();
        $response = $this->actingAs($this->employeeUser)->delete(route('employee.timesheets.entries.destroy', ['timesheet' => $timesheet, 'entry' => $entry]));
        $response->assertSessionHas('error');
    }

    /**
     * T239 & T240: Timesheet Approval Queue, Approvals, Rejections, and Revision Returns.
     */
    public function test_t239_t240_approval_workflow_rejection_and_return(): void
    {
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'status' => TimesheetStatus::SUBMITTED->value,
            'total_hours' => 8.0,
            'submitted_at' => now(),
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => '2026-08-10',
            'hours' => 8.0,
            'is_billable' => true,
        ]);

        // 1. Manager views Approval Queue
        $response = $this->actingAs($this->manager)->get(route('manager.timesheets.index'));
        $response->assertOk();
        $response->assertSee('John Developer');

        // 2. Manager Returns Timesheet for Revisions
        $response = $this->actingAs($this->manager)->post(route('manager.timesheets.return', $timesheet), [
            'rejection_reason' => 'Please elaborate on the task description and log specific subtasks.',
        ]);
        $response->assertRedirect(route('manager.timesheets.show', $timesheet));

        $timesheet->refresh();
        $this->assertEquals(TimesheetStatus::RETURNED, $timesheet->status);
        $this->assertEquals('Please elaborate on the task description and log specific subtasks.', $timesheet->rejection_reason);
        $this->assertTrue($timesheet->isEditable()); // Unlocked for employee

        // 3. Employee re-submits
        $this->actingAs($this->employeeUser)->post(route('employee.timesheets.submit', $timesheet));
        $timesheet->refresh();
        $this->assertEquals(TimesheetStatus::SUBMITTED, $timesheet->status);

        // 4. Manager Approves Timesheet
        $initialTaskHours = (float) $this->task->actual_hours;

        $response = $this->actingAs($this->manager)->post(route('manager.timesheets.approve', $timesheet));
        $response->assertRedirect(route('manager.timesheets.show', $timesheet));

        $timesheet->refresh();
        $this->assertEquals(TimesheetStatus::APPROVED, $timesheet->status);
        $this->assertEquals($this->manager->id, $timesheet->approved_by);
        $this->assertNotNull($timesheet->approved_at);

        // Assert Task actual_hours was incremented (Task T240)
        $this->task->refresh();
        $this->assertEquals($initialTaskHours + 8.0, (float) $this->task->actual_hours);

        // 5. Team Lead Queue & Approvals
        $squadTimesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-17',
            'end_date' => '2026-08-23',
            'status' => TimesheetStatus::SUBMITTED->value,
            'total_hours' => 5.0,
            'submitted_at' => now(),
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $squadTimesheet->id,
            'project_id' => $this->project->id,
            'entry_date' => '2026-08-17',
            'hours' => 5.0,
        ]);

        $response = $this->actingAs($this->teamLead)->get(route('team-lead.timesheets.index'));
        $response->assertOk();

        $response = $this->actingAs($this->teamLead)->post(route('team-lead.timesheets.approve', $squadTimesheet));
        $response->assertRedirect(route('team-lead.timesheets.show', $squadTimesheet));

        $squadTimesheet->refresh();
        $this->assertEquals(TimesheetStatus::APPROVED, $squadTimesheet->status);
    }

    /**
     * T241: Labor Cost Calculation via Configurable Rules.
     */
    public function test_t241_project_labor_cost_calculation(): void
    {
        $laborCostService = app(ProjectLaborCostService::class);

        // John Developer: Basic Salary = 5280.00. Working days = 22, Daily hours = 8 (Total monthly hours = 176)
        // Hourly rate = 5280 / 176 = 30.00 / hr
        $hourlyRate = $laborCostService->getHourlyRate($this->employee);
        $this->assertEquals(30.00, $hourlyRate);

        // 8 hours effort = 8 * 30.00 = 240.00
        $cost = $laborCostService->calculateEntryCost($this->employee, 8.0);
        $this->assertEquals(240.00, $cost);

        // Jane Engineer: Basic Salary = 3520.00. 3520 / 176 = 20.00 / hr
        $janeHourlyRate = $laborCostService->getHourlyRate($this->otherEmployee);
        $this->assertEquals(20.00, $janeHourlyRate);

        // Create and approve timesheet with labor cost entries
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'status' => TimesheetStatus::SUBMITTED->value,
            'total_hours' => 10.0,
            'submitted_at' => now(),
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'task_id' => $this->task->id,
            'entry_date' => '2026-08-10',
            'hours' => 10.0,
            'calculated_cost' => 300.00,
        ]);

        $this->actingAs($this->manager)->post(route('manager.timesheets.approve', $timesheet));

        // Total project labor cost calculation
        $totalProjectLabor = $laborCostService->getTotalLaborCostForProject($this->project->id);
        $this->assertGreaterThanOrEqual(300.00, $totalProjectLabor);

        // Total task labor cost calculation
        $totalTaskLabor = $laborCostService->getTotalLaborCostForTask($this->task->id);
        $this->assertGreaterThanOrEqual(300.00, $totalTaskLabor);
    }

    /**
     * T242 & RBAC: Audit Logging & Cross-Role Security Isolation.
     */
    public function test_t242_and_rbac_boundaries(): void
    {
        // 1. Timesheet actions log audit records
        $timesheet = Timesheet::create([
            'employee_id' => $this->employee->id,
            'user_id' => $this->employeeUser->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-16',
            'status' => TimesheetStatus::DRAFT->value,
            'total_hours' => 4.0,
        ]);

        TimesheetEntry::create([
            'timesheet_id' => $timesheet->id,
            'project_id' => $this->project->id,
            'entry_date' => '2026-08-10',
            'hours' => 4.0,
        ]);

        $this->actingAs($this->employeeUser)->post(route('employee.timesheets.submit', $timesheet));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->employeeUser->id,
            'target_type' => 'Project',
            'action' => 'timesheet.submitted',
        ]);

        // 2. Client receives 403 when accessing Employee or Manager timesheet routes
        $this->actingAs($this->clientUser)->get(route('employee.timesheets.index'))->assertForbidden();
        $this->actingAs($this->clientUser)->get(route('manager.timesheets.index'))->assertForbidden();

        // 3. Employee receives 403 when accessing Manager approval queue
        $this->actingAs($this->employeeUser)->get(route('manager.timesheets.index'))->assertForbidden();

        // 4. Super Admin can access Manager timesheet queue
        $this->actingAs($this->superAdmin)->get(route('manager.timesheets.index'))->assertOk();
    }
}
