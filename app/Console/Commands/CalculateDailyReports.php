<?php

namespace App\Console\Commands;

use App\Models\LiveSessionReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateDailyReports extends Command
{
    protected $signature = 'reports:calculate-daily {--date= : The date to calculate reports for (format: Y-m-d)}';
    protected $description = 'Calculate daily reports and store them in the live_session_reports table';

    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();

        $this->info("Calculating reports for {$date->format('Y-m-d')}...");

        try {
            $report = LiveSessionReport::calculateAndStore($date->format('Y-m-d'), 'daily');
            $this->info("Successfully calculated and stored report.");
            $this->info("Total orders: {$report->report_data['total_orders']}");
            $this->info("Total revenue: {$report->report_data['total_revenue']}");
        } catch (\Exception $e) {
            $this->error("Error calculating report: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
