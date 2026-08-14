<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AI Conversations Table
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title', 200)->default('New AI Session');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('project_id');
            $table->index('client_id');
        });

        // 2. AI Messages Table
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('role', ['user', 'assistant', 'tool', 'system'])->default('user');
            $table->longText('content')->nullable();
            $table->json('tool_calls')->nullable();
            $table->string('tool_call_id', 100)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        // 3. AI Action Logs (Immutable Audit Trail & Approval Gates)
        Schema::create('ai_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            
            $table->string('tool_name', 100)->index();
            $table->string('action_type', 50)->default('mutation'); // query, mutation, destructive
            $table->json('parameters')->nullable();
            
            // Approval Workflow States (T272)
            $table->enum('approval_state', ['not_required', 'pending_approval', 'approved', 'rejected'])->default('not_required');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Execution Status & Resilience (T274)
            $table->enum('execution_status', ['pending', 'success', 'failed', 'aborted'])->default('pending');
            $table->json('execution_result')->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['project_id', 'approval_state']);
            $table->index(['tool_name', 'execution_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_logs');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
