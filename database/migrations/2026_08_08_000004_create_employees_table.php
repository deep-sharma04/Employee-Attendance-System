<?php

use App\Enums\EmployeeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('employee_code', 30)->unique()->index();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 150)->unique()->index();
            $table->string('phone', 25)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->index();
            $table->string('department', 100)->index();
            $table->string('designation', 100);
            $table->string('status', 30)->default(EmployeeStatus::ACTIVE->value)->index();
            $table->text('status_change_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0.00);
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('ifsc_code', 30)->nullable();
            $table->string('pan_number', 30)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
