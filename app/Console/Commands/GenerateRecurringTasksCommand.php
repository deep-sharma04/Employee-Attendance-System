<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\Task\RecurringTaskService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:generate-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the next occurrences for active recurring tasks that are due.';

    /**
     * Execute the console command.
     */
    public function handle(RecurringTaskService $recurringService): int
    {
        // Find recurring task definitions (not generated occurrences)
        $recurringDefinitions = Task::recurringDefinitions()
            ->whereNotIn('status', ['cancelled']) // Cancelled definitions stop recurring
            ->get();

        $generatedCount = 0;

        foreach ($recurringDefinitions as $task) {
            // Check if it's past its end date
            if ($task->recurrence_end_date && $task->recurrence_end_date->lt(today())) {
                continue;
            }

            try {
                $occurrence = $recurringService->generateNextOccurrence($task);
                if ($occurrence) {
                    // Check if it was newly created or skipped due to idempotency
                    // If it was just created now, it will have a recent timestamp
                    if ($occurrence->wasRecentlyCreated) {
                        $generatedCount++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate recurring occurrence for Task #{$task->id}: " . $e->getMessage());
                $this->error("Failed to process task #{$task->id}. Check logs.");
            }
        }

        $this->info("Successfully generated {$generatedCount} new recurring task occurrences.");

        return Command::SUCCESS;
    }
}
