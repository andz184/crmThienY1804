<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateLiveSessionStats as UpdateStatsJob;
use Carbon\Carbon;

class UpdateLiveSessionStats extends Command
{
    protected $signature = 'live-sessions:update-stats {--days=30} {--force}';
    protected $description = 'Update live session statistics for the specified period';

    public function handle()
    {
        $days = $this->option('days');
        $force = $this->option('force');

        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        $this->info("Updating live session stats from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Dispatch job to update stats
        UpdateStatsJob::dispatch($startDate, $endDate)
            ->onQueue('stats');

        $this->info('Stats update job has been queued.');
    }
}
