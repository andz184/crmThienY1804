<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\DailyRevenueAggregate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyRevenueAggregateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding daily revenue aggregates...');

        // Optional: Clear the table before seeding if you want a fresh start
        // DailyRevenueAggregate::truncate();
        // Or, more safely, delete existing records if you might re-run and want to avoid unique constraint errors
        // For a one-time seed or if the observer handles updates, this might not be strictly needed.
        // However, if running after schema changes or for initial population, clearing can be useful.
        // Let's opt for a more controlled deletion if we want to ensure no stale data conflicts
        // For now, we will rely on updateOrInsert type logic or simply insert if the table is empty.
        // The console command `revenue:aggregate-daily` uses updateOrInsert. This seeder can be simpler for a bulk load.

        // Define the status that counts towards revenue
        $revenueStatus = defined('App\Models\Order::STATUS_DA_THU_TIEN') ? Order::STATUS_DA_THU_TIEN : 'completed';

        $this->command->getOutput()->progressStart(Order::where('status', $revenueStatus)->whereNotNull('user_id')->count());

        // Fetch orders in chunks to manage memory for very large datasets
        Order::query()
            ->where('status', $revenueStatus)
            ->whereNotNull('user_id') // Ensure user_id is present
            ->select(
                DB::raw('DATE(created_at) as aggregation_date'),
                'user_id',
                DB::raw('SUM(total_value) as total_revenue'),
                DB::raw('COUNT(id) as completed_orders_count')
            )
            ->groupBy('aggregation_date', 'user_id')
            ->orderBy('aggregation_date') // Process chronologically
            ->chunk(200, function ($aggregates) {
                $dataToInsert = [];
                foreach ($aggregates as $aggregate) {
                    if ($aggregate->user_id && $aggregate->aggregation_date) { // Basic validation
                        $dataToInsert[] = [
                            'aggregation_date' => $aggregate->aggregation_date,
                            'user_id' => $aggregate->user_id,
                            'total_revenue' => $aggregate->total_revenue,
                            'completed_orders_count' => $aggregate->completed_orders_count,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    $this->command->getOutput()->progressAdvance();
                }

                if (!empty($dataToInsert)) {
                    // Use insertOrIgnore to avoid issues if some records (date, user_id pairs) somehow exist due to partial runs
                    // or if the observer has already created some entries.
                    // For a clean seed, truncate() before running is an option.
                    // DailyRevenueAggregate::insert($dataToInsert); // Basic insert
                    // A more robust way for seeding might be to use updateOrInsert if you expect potential conflicts
                    // However, for a seeder, usually we aim for a clean slate or assume it runs on empty/controlled table.
                    // Let's use insert and rely on pre-seeder cleanup if needed.
                    DailyRevenueAggregate::upsert(
                        $dataToInsert,
                        ['aggregation_date', 'user_id'], // Unique keys
                        ['total_revenue', 'completed_orders_count', 'updated_at'] // Columns to update on duplicate
                    );
                }
            });

        $this->command->getOutput()->progressFinish();
        $this->command->info('Daily revenue aggregates seeded successfully.');
    }
}
