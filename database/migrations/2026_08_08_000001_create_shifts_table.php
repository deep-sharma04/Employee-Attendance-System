<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->unique()->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->json('working_days');
            $table->unsignedInteger('grace_period_minutes')->default(15);
            $table->unsignedInteger('half_day_threshold_minutes')->default(60);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
