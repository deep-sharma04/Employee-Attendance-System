<?php

use App\Enums\AttendanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('attendance_date')->index();
            $table->time('punch_in')->nullable();
            $table->time('punch_out')->nullable();
            $table->string('punch_in_ip', 45)->nullable();
            $table->string('punch_out_ip', 45)->nullable();
            $table->decimal('total_working_hours', 5, 2)->nullable();
            $table->string('status', 30)->default(AttendanceStatus::PRESENT->value)->index();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->boolean('is_manually_corrected')->default(false)->index();
            $table->text('correction_reason')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });

        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('action', 20)->index(); // punch_in, punch_out
            $table->dateTime('event_timestamp')->index();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('invalidation_reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('attendance_records');
    }
};
