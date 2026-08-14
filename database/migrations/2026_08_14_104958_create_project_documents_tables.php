<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_client_visible')->default(false);
            $table->integer('current_version')->default(1);
            $table->timestamps();
        });

        Schema::create('project_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_document_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('file_path');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_document_versions');
        Schema::dropIfExists('project_documents');
    }
};