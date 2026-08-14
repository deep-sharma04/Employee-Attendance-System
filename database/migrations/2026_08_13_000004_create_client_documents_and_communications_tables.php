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
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150)->index();
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size'); // in bytes (max 2MB = 2097152 bytes)
            $table->string('mime_type', 100);
            $table->boolean('is_shared_with_client')->default(false)->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('client_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30)->index(); // 'email', 'call', 'meeting', 'note'
            $table->string('subject', 200)->index();
            $table->text('details');
            $table->dateTime('communication_date')->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_communications');
        Schema::dropIfExists('client_documents');
    }
};
