<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AssignOrderToSales implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Don't reassign if already assigned
        if (!empty($order->assigning_seller_id)) {
            return;
        }

        // Get active sales staff with pancake_uuid
        $activeStaff = User::role('staff')
            ->where('is_active', true)
            ->whereNotNull('pancake_uuid')
            ->get();

        if ($activeStaff->isEmpty()) {
            Log::warning('No active sales staff found for order assignment. Order ID: ' . $order->id);
            return;
        }

        // Get distribution settings
        $distributionType = WebsiteSetting::get('order_distribution_type', 'sequential');
        $distributionPattern = explode(',', WebsiteSetting::get('order_distribution_pattern', '1,1,1'));

        try {
            DB::beginTransaction();

            if ($distributionType === 'sequential') {
                // For sequential distribution, just get the next staff in line
                
                // Get the count of previously assigned orders for each staff to determine who's next
                $staffOrderCounts = [];
                foreach ($activeStaff as $staff) {
                    $orderCount = DB::table('orders')
                        ->where('assigning_seller_id', $staff->pancake_uuid)
                        ->count();
                    $staffOrderCounts[$staff->id] = $orderCount;
                }
                
                // Find the staff with the least orders
                $minOrderCount = min($staffOrderCounts);
                $staffIds = array_keys($staffOrderCounts, $minOrderCount);
                $staffId = $staffIds[0]; // Take the first one if multiple have the same count
                
                $assignedStaff = $activeStaff->firstWhere('id', $staffId);
            } else {
                // For batch distribution, use the pattern to determine the next staff
                
                // Get total orders and calculate the pattern index for each staff
                $totalOrders = DB::table('orders')
                    ->whereNotNull('assigning_seller_id')
                    ->count();
                
                $patternSum = array_sum($distributionPattern);
                $patternIndex = $totalOrders % $patternSum;
                
                // Find which batch this order falls into
                $currentSum = 0;
                $staffIndex = 0;
                
                foreach ($distributionPattern as $index => $batch) {
                    $currentSum += $batch;
                    if ($patternIndex < $currentSum) {
                        $staffIndex = $index % $activeStaff->count();
                        break;
                    }
                }
                
                $assignedStaff = $activeStaff[$staffIndex];
            }

            if ($assignedStaff) {
                $order->assigning_seller_id = $assignedStaff->pancake_uuid;
                $order->assigning_seller_name = $assignedStaff->name;
                $order->save();
                
                Log::info("Order #{$order->id} automatically assigned to {$assignedStaff->name} (ID: {$assignedStaff->pancake_uuid})");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning order to sales: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
