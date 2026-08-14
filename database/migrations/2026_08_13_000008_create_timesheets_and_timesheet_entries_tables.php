<?php

use App\Enums\TimesheetStatus;
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
        // 1. Timesheets Table (Task T236)
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('period_type', 30)->default('weekly'); // 'weekly', 'daily'
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('total_hours', 8, 2)->default(0.00);
            $table->string('status', 30)->default(TimesheetStatus::DRAFT->value)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'end_date']);
        });

        // 2. Timesheet Entries Table (Task T236)
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('timesheets')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('entry_date')->index();
            $table->decimal('hours', 6, 2)->default(0.00);
            $table->boolean('is_billable')->default(true);
            $table->text('description')->nullable();
            $table->decimal('calculated_cost', 10, 2)->default(0.00); // Task T241
            $table->timestamps();

            $table->index(['timesheet_id', 'entry_date']);
            $table->index(['project_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
        Schema::dropIfExists('timesheets');
    }
};
