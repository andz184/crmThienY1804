<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LiveSessionRevenue;
use Carbon\Carbon;

class RecalculateTopProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:recalculate-products {--start= : Start date (Y-m-d)} {--end= : End date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate top products for live sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(30);
        $endDate = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();

        $this->info("Recalculating top products from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $sessions = LiveSessionRevenue::whereBetween('date', [$startDate, $endDate])->get();
        $bar = $this->output->createProgressBar(count($sessions));
        $bar->start();

        foreach ($sessions as $session) {
            try {
                $session->calculateTopProducts();
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Error processing session {$session->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Top products recalculation completed!');
    }
}
