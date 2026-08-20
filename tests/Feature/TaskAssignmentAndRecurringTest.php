<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TaskAssignmentAndRecurringTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $employee1;
    protected User $employee2;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createSuperAdmin(['username' => 'manager.test']);
        $this->employee1 = $this->createEmployeeUser(['username' => 'emp1.test', 'name' => 'Emp One', 'email' => 'emp1@hrm.local']);
        $this->employee2 = $this->createEmployeeUser(['username' => 'emp2.test', 'name' => 'Emp Two', 'email' => 'emp2@hrm.local']);

        $this->project = Project::create(['name' => 'Test Project', 'code' => 'TEST']);
        $this->project->projectMembers()->create(['user_id' => $this->employee1->id, 'project_role' => 'member']);
        $this->project->projectMembers()->create(['user_id' => $this->employee2->id, 'project_role' => 'member']);
    }

    public function test_assignee_receives_notification_upon_task_creation()
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('manager.tasks.store'), [
            'project_id' => $this->project->id,
            'title' => 'Test Task Assignment',
            'task_code' => 'TEST-001',
            'assigned_to' => $this->employee1->id,
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $task = Task::where('task_code', 'TEST-001')->first();
        $this->assertNotNull($task);
        $this->assertEquals($this->employee1->id, $task->assigned_to);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employee1->id,
            'type' => 'task_assigned',
        ]);
    }

    public function test_employee_can_view_assigned_tasks()
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Employee Task',
            'task_code' => 'TSK-01',
            'assigned_to' => $this->employee1->id,
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->employee1);

        $response = $this->get(route('employee.tasks.index'));
        $response->assertStatus(200);
        $response->assertSee('Employee Task');
    }

    public function test_employee_cannot_view_unassigned_tasks()
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Other Task',
            'task_code' => 'TSK-02',
            'assigned_to' => $this->employee2->id,
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->employee1);

        $response = $this->get(route('employee.tasks.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Other Task');
    }

    public function test_new_assignee_receives_notification_on_reassignment()
    {
        $task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Reassign Task',
            'task_code' => 'TSK-03',
            'assigned_to' => $this->employee1->id,
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager);

        $response = $this->put(route('manager.tasks.update', $task), [
            'project_id' => $this->project->id,
            'title' => $task->title,
            'task_code' => $task->task_code,
            'assigned_to' => $this->employee2->id, // Reassign to emp2
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
        ]);

        $response->assertRedirect();
        
        // Emp 2 should have a notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employee2->id,
            'type' => 'task_assigned',
        ]);
        
        // History log created for reassignment
        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id,
            'action' => 'task.reassigned',
            'new_value' => $this->employee2->id,
        ]);
    }

    public function test_assigner_does_not_receive_notification()
    {
        $this->actingAs($this->manager);

        $this->post(route('manager.tasks.store'), [
            'project_id' => $this->project->id,
            'title' => 'Manager assigns self',
            'task_code' => 'TEST-002',
            'assigned_to' => $this->employee1->id,
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
        ]);

        // Manager should NOT get a notification just for creating a task
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->manager->id,
            'type' => 'task_assigned',
        ]);
    }

    public function test_recurring_task_generation_preserves_assignee_and_sets_parent()
    {
        $recurringTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Recurring Task',
            'task_code' => 'TSK-04',
            'assigned_to' => $this->employee1->id,
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'due_date' => now()->toDateString(),
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'estimated_hours' => 0,
            'created_by' => $this->manager->id,
        ]);

        $recurringService = app(\App\Services\Task\RecurringTaskService::class);
        $occurrence = $recurringService->generateNextOccurrence($recurringTask);

        $this->assertNotNull($occurrence);
        $this->assertEquals($this->employee1->id, $occurrence->assigned_to);
        $this->assertEquals($recurringTask->id, $occurrence->recurring_parent_id);
    }

    public function test_generated_recurring_task_notifies_assignee()
    {
        $recurringTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Recurring Task Notifies',
            'task_code' => 'TSK-05',
            'assigned_to' => $this->employee1->id,
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'due_date' => now()->toDateString(),
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'estimated_hours' => 0,
            'created_by' => $this->manager->id,
        ]);

        $recurringService = app(\App\Services\Task\RecurringTaskService::class);
        $occurrence = $recurringService->generateNextOccurrence($recurringTask);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->employee1->id,
            'type' => 'task_assigned',
        ]);
    }

    public function test_recurring_task_generation_is_idempotent()
    {
        $recurringTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Idempotent Recurring',
            'task_code' => 'TSK-06',
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'due_date' => now()->toDateString(),
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'estimated_hours' => 0,
            'created_by' => $this->manager->id,
        ]);

        $recurringService = app(\App\Services\Task\RecurringTaskService::class);
        
        // Generate first time
        $occurrence1 = $recurringService->generateNextOccurrence($recurringTask);
        $this->assertNotNull($occurrence1);

        // Generate second time (should return the existing one, not create a new one)
        $occurrence2 = $recurringService->generateNextOccurrence($recurringTask);
        
        $this->assertEquals($occurrence1->id, $occurrence2->id);
        
        // Ensure only one occurrence was actually created in DB
        $count = Task::where('recurring_parent_id', $recurringTask->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_employee_can_view_recurring_tasks_list()
    {
        $recurringTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Daily Standup',
            'task_code' => 'TSK-07',
            'assigned_to' => $this->employee1->id,
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'recurring_parent_id' => null, // Definition
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        $occurrence = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Daily Standup - occurrence',
            'task_code' => 'TSK-08',
            'assigned_to' => $this->employee1->id,
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'recurring_parent_id' => $recurringTask->id, // Occurrence
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->employee1);

        $response = $this->get(route('employee.tasks.recurring'));
        $response->assertStatus(200);
        
        // Should see the definition
        $response->assertSee('Daily Standup');
        // The occurrence shouldn't be listed as a separate definition row
        $response->assertDontSee('Daily Standup - occurrence');
    }

    public function test_scheduler_command_generates_occurrences()
    {
        $recurringTask = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Scheduler Task',
            'task_code' => 'TSK-09',
            'is_recurring' => true,
            'recurrence_pattern' => 'daily',
            'due_date' => now()->subDay()->toDateString(), // Past due
            'status' => TaskStatus::TODO->value,
            'priority' => \App\Enums\TaskPriority::MEDIUM->value,
            'created_by' => $this->manager->id,
        ]);

        Artisan::call('tasks:generate-recurring');

        $this->assertDatabaseHas('tasks', [
            'recurring_parent_id' => $recurringTask->id,
            'due_date' => now()->toDateString(),
        ]);
    }
}
