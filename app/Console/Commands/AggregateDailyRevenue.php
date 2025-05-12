<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\DailyRevenueAggregate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AggregateDailyRevenue extends Command
{
    protected $signature = 'revenue:aggregate-daily {--date= : Optional date for backfilling/testing, format YYYY-MM-DD}';
    protected $description = 'Aggregates daily revenue from orders and stores it.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $dateInput = $this->option('date');
        $dateToProcess = $dateInput ? Carbon::parse($dateInput) : Carbon::yesterday();

        $this->info("Aggregating revenue for date: " . $dateToProcess->toDateString());

        // Assuming Order::STATUS_DA_THU_TIEN is the status for orders contributing to revenue
        // You might need to adjust this status based on your Order model definitions
        $statusForRevenue = defined('App\Models\Order::STATUS_DA_THU_TIEN') ? Order::STATUS_DA_THU_TIEN : 'completed'; // Fallback if constant not found

        $dailyData = Order::query()
            ->where('status', $statusForRevenue)
            ->whereDate('created_at', $dateToProcess)
            ->whereNotNull('user_id')
            ->select(
                // Ensure the date is cast/formatted correctly for your DB if not using Carbon objects directly
                DB::raw('DATE(created_at) as aggregation_date_raw'),
                'user_id',
                DB::raw('SUM(total_value) as total_revenue'),
                DB::raw('COUNT(id) as completed_orders_count')
            )
            ->groupBy('aggregation_date_raw', 'user_id')
            ->get();

        if ($dailyData->isEmpty()) {
            $this->info('No revenue data to aggregate for ' . $dateToProcess->toDateString());
            return Command::SUCCESS;
        }

        $aggregatedCount = 0;
        foreach ($dailyData as $data) {
            // Ensure date is in Y-m-d format for the database
            $aggregationDate = Carbon::parse($data->aggregation_date_raw)->format('Y-m-d');

            DailyRevenueAggregate::updateOrInsert(
                [
                    'aggregation_date' => $aggregationDate,
                    'user_id' => $data->user_id,
                ],
                [
                    'total_revenue' => $data->total_revenue,
                    'completed_orders_count' => $data->completed_orders_count,
                    'updated_at' => now(),
                    'created_at' => now(), // Explicitly set created_at on insert
                ]
            );
            $aggregatedCount++;
        }

        $this->info("Successfully aggregated daily revenue for " . $dateToProcess->toDateString() . ". Processed " . $aggregatedCount . " user records.");
        return Command::SUCCESS;
    }
}
