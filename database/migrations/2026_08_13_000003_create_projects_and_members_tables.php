<?php

use App\Enums\ProjectHealth;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('code', 50)->unique()->index();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('scope')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('budget', 12, 2)->default(0.00);
            $table->decimal('estimated_hours', 8, 2)->default(0.00);
            $table->date('start_date')->nullable()->index();
            $table->date('deadline')->nullable()->index();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default(ProjectStatus::PLANNING->value)->index();
            $table->string('priority', 30)->default(ProjectPriority::MEDIUM->value)->index();
            $table->string('health', 30)->default(ProjectHealth::GOOD->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('project_role', 30)->default(ProjectMemberRole::MEMBER->value)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
