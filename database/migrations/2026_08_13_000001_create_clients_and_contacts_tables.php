<?php

use App\Enums\ClientStatus;
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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150)->index();
            $table->string('company_code', 50)->unique()->nullable()->index();
            $table->string('email', 150)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 30)->default(ClientStatus::ACTIVE->value)->index();
            $table->string('currency', 10)->default('USD');
            $table->string('billing_type', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 150)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('position', 100)->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('client_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();

            $table->unique(['client_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_users');
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
