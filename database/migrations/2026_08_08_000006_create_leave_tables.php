<?php

use App\Enums\LeaveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 30)->unique()->index();
            $table->decimal('annual_quota', 4, 1)->default(12.0);
            $table->boolean('requires_document')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->decimal('allocated_days', 4, 1)->default(0.0);
            $table->decimal('used_days', 4, 1)->default(0.0);
            $table->decimal('remaining_days', 4, 1)->default(0.0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_type', 20)->nullable();
            $table->decimal('total_days', 4, 1);
            $table->text('reason');
            $table->string('status', 30)->default(LeaveStatus::PENDING->value)->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
