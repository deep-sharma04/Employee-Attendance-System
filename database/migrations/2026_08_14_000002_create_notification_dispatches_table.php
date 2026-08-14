<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email', 150)->nullable();
            $table->string('notification_type', 80);
            $table->string('category', 50)->default('general');
            $table->string('channel', 30); // in_app, email, web_push
            $table->string('status', 30)->default('sent'); // sent, failed, skipped
            $table->text('error_message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['notification_type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');
    }
};
