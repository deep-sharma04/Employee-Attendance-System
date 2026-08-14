<?php

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique()->index();
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size'); // in bytes, max 512,000 (500 KB)
            $table->string('mime_type', 100);
            $table->string('status', 30)->default(DocumentStatus::PENDING->value)->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
    }
};
