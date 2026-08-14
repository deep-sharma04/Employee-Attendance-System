<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tasks Table
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('title', 200)->index();
            $table->string('task_code', 50)->unique()->index();
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 30)->default(TaskPriority::MEDIUM->value)->index();
            $table->string('status', 30)->default(TaskStatus::TODO->value)->index();
            $table->decimal('estimated_hours', 8, 2)->default(0.00);
            $table->decimal('actual_hours', 8, 2)->default(0.00);
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->boolean('is_recurring')->default(false)->index();
            $table->string('recurrence_pattern', 30)->nullable(); // 'daily', 'weekly', 'monthly'
            $table->date('recurrence_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Task Dependencies (Blocking constraints)
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('dependency_type', 30)->default('blocks'); // 'blocks', 'relates_to'
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id']);
        });

        // 3. Task Checklists
        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title', 255);
            $table->boolean('is_completed')->default(false)->index();
            $table->unsignedSmallInteger('order')->default(0);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 4. Task Comments (Internal with client isolation)
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Task Attachments
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });

        // 6. Task Activity History
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->index();
            $table->string('field_name', 50)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('tasks');
    }
};
