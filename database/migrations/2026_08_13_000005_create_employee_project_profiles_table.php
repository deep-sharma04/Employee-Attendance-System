<?php

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
        Schema::create('employee_project_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->unique();
            $table->json('skills')->nullable();
            $table->string('availability_status', 30)->default('available')->index(); // 'available', 'partially_available', 'allocated', 'on_leave'
            $table->unsignedSmallInteger('weekly_capacity_hours')->default(40);
            $table->decimal('experience_years', 4, 1)->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_project_profiles');
    }
};
