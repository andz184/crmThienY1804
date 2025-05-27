<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\LiveSessionStats;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\LiveSessionCacheService;
use Illuminate\Support\Facades\Cache;

class UpdateLiveSessionStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $cacheService;
    protected $chunkSize = 100;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ?? Carbon::now()->startOfMonth();
        $this->endDate = $endDate ?? Carbon::now()->endOfDay();
        $this->cacheService = new LiveSessionCacheService();
    }

    public function handle()
    {
        try {
            Log::info("Starting UpdateLiveSessionStats job", [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate
            ]);

            // Get orders for the period with efficient querying
            Order::query()
                ->select([
                    'id',
                    'status',
                    'total_value',
                    'customer_id',
                    'live_session_info',
                    'created_at'
                ])
                ->whereNotNull('live_session_info')
                ->whereRaw('JSON_EXTRACT(live_session_info, "$.live_number") IS NOT NULL')
                ->whereRaw('JSON_EXTRACT(live_session_info, "$.session_date") IS NOT NULL')
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->with(['items:id,order_id,product_name,quantity,price'])
                ->chunk($this->chunkSize, function($orders) {
                    $this->processOrdersChunk($orders);
                });

            // Clear cache after updating stats
            $this->cacheService->clearAllCaches();

            // Update job status to completed
            $jobId = 'live_session_calc_' . md5($this->startDate->format('Y-m-d') . $this->endDate->format('Y-m-d') . time());
            Cache::put($jobId . '_status', 'completed', 3600);

            Log::info("Completed UpdateLiveSessionStats job successfully");
        } catch (\Exception $e) {
            Log::error("Error in UpdateLiveSessionStats job", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update job status to failed
            $jobId = 'live_session_calc_' . md5($this->startDate->format('Y-m-d') . $this->endDate->format('Y-m-d') . time());
            Cache::put($jobId . '_status', 'failed', 3600);

            throw $e;
        }
    }

    protected function processOrdersChunk($orders)
    {
        $stats = [];

        foreach ($orders as $order) {
            $liveSessionInfo = json_decode($order->live_session_info, true);
            if (!$liveSessionInfo) continue;

            $liveNumber = $liveSessionInfo['live_number'];
            $sessionDate = Carbon::parse($liveSessionInfo['session_date'])->format('Y-m-d');
            $sessionName = $liveSessionInfo['session_name'] ?? null;

            $key = $sessionDate . '_' . $liveNumber;
            if (!isset($stats[$key])) {
                $stats[$key] = [
                    'live_number' => $liveNumber,
                    'session_date' => $sessionDate,
                    'session_name' => $sessionName,
                    'total_orders' => 0,
                    'successful_orders' => 0,
                    'canceled_orders' => 0,
                    'delivering_orders' => 0,
                    'total_revenue' => 0,
                    'total_customers' => 0,
                    'new_customers' => 0,
                    'returning_customers' => 0,
                    'products' => [],
                    'customers' => []
                ];
            }

            // Update order counts
            $stats[$key]['total_orders']++;

            // Update status counts and revenue
            switch (strtolower($order->status)) {
                case 'delivered':
                case 'completed':
                case 'thanh_cong':
                    $stats[$key]['successful_orders']++;
                    $stats[$key]['total_revenue'] += $order->total_value;
                    break;
                case 'cancelled':
                case 'canceled':
                case 'huy':
                    $stats[$key]['canceled_orders']++;
                    break;
                case 'delivering':
                case 'shipping':
                    $stats[$key]['delivering_orders']++;
                    break;
            }

            // Process products
            foreach ($order->items as $item) {
                $productKey = $item->product_name;
                if (!isset($stats[$key]['products'][$productKey])) {
                    $stats[$key]['products'][$productKey] = [
                        'name' => $item->product_name,
                        'quantity' => 0,
                        'revenue' => 0
                    ];
                }
                $stats[$key]['products'][$productKey]['quantity'] += $item->quantity;
                $stats[$key]['products'][$productKey]['revenue'] += ($item->price * $item->quantity);
            }

            // Process customer data
            if ($order->customer_id) {
                $customerId = $order->customer_id;
                if (!isset($stats[$key]['customers'][$customerId])) {
                    $stats[$key]['customers'][$customerId] = [
                        'id' => $customerId,
                        'orders' => 0,
                        'total_spent' => 0
                    ];
                    $stats[$key]['total_customers']++;
                }
                $stats[$key]['customers'][$customerId]['orders']++;
                if (in_array(strtolower($order->status), ['delivered', 'completed', 'thanh_cong'])) {
                    $stats[$key]['customers'][$customerId]['total_spent'] += $order->total_value;
                }
            }
        }

        // Save stats to database
        foreach ($stats as $key => $sessionStats) {
            // Calculate rates
            $totalOrders = $sessionStats['total_orders'];
            $sessionStats['success_rate'] = $totalOrders > 0
                ? round(($sessionStats['successful_orders'] / $totalOrders) * 100, 2)
                : 0;
            $sessionStats['cancel_rate'] = $totalOrders > 0
                ? round(($sessionStats['canceled_orders'] / $totalOrders) * 100, 2)
                : 0;

            // Convert products and customers to JSON
            $sessionStats['products_data'] = json_encode(array_values($sessionStats['products']));
            $sessionStats['customers_data'] = json_encode(array_values($sessionStats['customers']));

            // Remove raw arrays before saving
            unset($sessionStats['products']);
            unset($sessionStats['customers']);

            // Update or create stats record
            LiveSessionStats::updateStats(
                $sessionStats['live_number'],
                $sessionStats['session_date'],
                $sessionStats
            );
        }
    }
}
