<?php

use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedTinyInteger('payroll_month')->index(); // 1 - 12
            $table->unsignedSmallInteger('payroll_year')->index(); // e.g. 2026
            $table->decimal('monthly_salary', 12, 2);
            $table->decimal('daily_salary', 12, 2);
            $table->unsignedTinyInteger('salary_divisor')->default(30);
            $table->unsignedTinyInteger('total_days_in_month')->default(30);
            $table->decimal('present_days', 4, 1)->default(0.0);
            $table->unsignedTinyInteger('late_days')->default(0);
            $table->unsignedTinyInteger('half_days')->default(0);
            $table->decimal('absent_days', 4, 1)->default(0.0);
            $table->decimal('leave_days', 4, 1)->default(0.0);
            $table->unsignedTinyInteger('holiday_days')->default(0);
            $table->unsignedTinyInteger('weekend_days')->default(0);
            $table->unsignedTinyInteger('bridged_holiday_days')->default(0);
            $table->unsignedTinyInteger('converted_late_absent_days')->default(0);
            $table->unsignedTinyInteger('converted_half_day_absent_days')->default(0);
            $table->decimal('total_lop_days', 4, 1)->default(0.0);
            $table->decimal('lop_deduction_amount', 12, 2)->default(0.00);
            $table->decimal('total_earnings', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2)->default(0.00);
            $table->string('status', 30)->default(PayrollStatus::DRAFT->value)->index();
            $table->string('payment_status', 30)->default(PaymentStatus::PENDING->value)->index();
            $table->unsignedTinyInteger('revision_number')->default(1);
            $table->text('revision_reason')->nullable();
            $table->foreignId('generated_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_year', 'payroll_month', 'revision_number'], 'emp_payroll_period_rev_unique');
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->string('type', 20)->index(); // earning, deduction
            $table->string('category', 50)->index(); // basic, hra, bonus, lop_deduction, etc.
            $table->string('label', 100);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->unique()->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payslip_number', 50)->unique()->index();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('net_pay', 12, 2);
            $table->string('file_path', 255)->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
