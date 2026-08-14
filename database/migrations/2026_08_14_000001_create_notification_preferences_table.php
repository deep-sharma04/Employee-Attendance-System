<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 50); // task_assignment, deadlines, timesheets, project_milestones, daily_summary, security
            $table->string('channel', 30);  // in_app, email, web_push
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category', 'channel'], 'user_cat_chan_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
