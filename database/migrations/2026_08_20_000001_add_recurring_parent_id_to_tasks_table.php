<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add recurring_parent_id to tasks table for recurring task lineage tracking.
     * This links generated occurrences back to the original recurring task definition.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('recurring_parent_id')
                ->nullable()
                ->after('recurrence_end_date')
                ->constrained('tasks')
                ->nullOnDelete();

            $table->index(['recurring_parent_id', 'due_date'], 'tasks_recurring_parent_due_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['recurring_parent_id']);
            $table->dropIndex('tasks_recurring_parent_due_idx');
            $table->dropColumn('recurring_parent_id');
        });
    }
};
