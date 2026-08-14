<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 100);
            $table->string('actor_role', 30);
            $table->string('action', 100)->index();
            $table->string('target_type', 100)->index();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
