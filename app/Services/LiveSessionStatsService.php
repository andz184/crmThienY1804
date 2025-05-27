<?php

namespace App\Services;

use App\Models\LiveSessionOrder;
use App\Models\LiveSessionOrderItem;
use App\Models\LiveSessionStats;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveSessionStatsService
{
    /**
     * Calculate and store stats for a specific live session
     */
    public function calculateStatsForSession(string $liveSessionId, string $liveSessionDate)
    {
        // Extract live number from session ID
        $liveNumber = substr($liveSessionId, 0, 1);

        // Get base stats
        $baseStats = LiveSessionOrder::where('live_session_id', $liveSessionId)
            ->where('live_session_date', $liveSessionDate)
            ->select([
                DB::raw('COUNT(DISTINCT id) as total_orders'),
                DB::raw('COUNT(DISTINCT customer_id) as total_customers'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT CASE
                    WHEN status IN ("thanh_cong", "hoan_thanh", "da_giao", "da_nhan", "da_thu_tien")
                    OR pancake_status IN ("completed", "delivered")
                    THEN id END) as successful_orders'),
                DB::raw('COUNT(DISTINCT CASE
                    WHEN status IN ("huy", "da_huy")
                    OR pancake_status = "cancelled"
                    THEN id END) as canceled_orders'),
                DB::raw('COUNT(DISTINCT CASE
                    WHEN status NOT IN ("thanh_cong", "hoan_thanh", "da_giao", "da_nhan", "da_thu_tien", "huy", "da_huy")
                    AND pancake_status NOT IN ("completed", "delivered", "cancelled")
                    THEN id END) as delivering_orders')
            ])
            ->first();

        // Get orders by status
        $ordersByStatus = LiveSessionOrder::where('live_session_id', $liveSessionId)
            ->where('live_session_date', $liveSessionDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Get customer stats
        $customerStats = $this->calculateCustomerStats($liveSessionId, $liveSessionDate);

        // Get top products
        $topProducts = LiveSessionOrderItem::query()
            ->join('live_session_orders', 'live_session_orders.id', '=', 'live_session_order_items.live_session_order_id')
            ->where('live_session_orders.live_session_id', $liveSessionId)
            ->where('live_session_orders.live_session_date', $liveSessionDate)
            ->whereNotIn('live_session_orders.status', ['huy', 'da_huy'])
            ->where('live_session_orders.pancake_status', '!=', 'cancelled')
            ->select(
                'product_name',
                'product_sku',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('COUNT(DISTINCT live_session_orders.customer_id) as customer_count')
            )
            ->groupBy('product_name', 'product_sku')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function($product) {
                return [
                    'name' => $product->product_name,
                    'sku' => $product->product_sku,
                    'quantity' => $product->total_quantity,
                    'revenue' => $product->total_revenue,
                    'customer_count' => $product->customer_count
                ];
            })
            ->toArray();

        // Create stats record
        $stats = LiveSessionStats::updateOrCreate(
            [
                'live_session_id' => $liveSessionId,
                'live_session_date' => $liveSessionDate
            ],
            [
                'live_number' => $liveNumber,
                'session_name' => LiveSessionStats::formatSessionName($liveNumber, $liveSessionDate),
                'total_orders' => $baseStats->total_orders ?? 0,
                'successful_orders' => $baseStats->successful_orders ?? 0,
                'canceled_orders' => $baseStats->canceled_orders ?? 0,
                'delivering_orders' => $baseStats->delivering_orders ?? 0,
                'total_revenue' => $baseStats->total_revenue ?? 0,
                'total_customers' => $baseStats->total_customers ?? 0,
                'new_customers' => $customerStats['new'] ?? 0,
                'returning_customers' => $customerStats['returning'] ?? 0,
                'top_products' => $topProducts,
                'orders_by_status' => $ordersByStatus,
                'last_calculated_at' => now()
            ]
        );

        // Calculate rates
        $stats->calculateRates();
        $stats->save();

        return $stats;
    }

    /**
     * Calculate customer statistics
     */
    private function calculateCustomerStats($liveSessionId, $liveSessionDate)
    {
        $sessionDate = Carbon::parse($liveSessionDate);

        // Get all customers in this session
        $sessionCustomers = LiveSessionOrder::where('live_session_id', $liveSessionId)
            ->where('live_session_date', $liveSessionDate)
            ->select('customer_id')
            ->distinct()
            ->get()
            ->pluck('customer_id');

        // Count customers who had previous orders
        $returningCount = LiveSessionOrder::whereIn('customer_id', $sessionCustomers)
            ->where('live_session_date', '<', $sessionDate)
            ->select('customer_id')
            ->distinct()
            ->count();

        return [
            'new' => $sessionCustomers->count() - $returningCount,
            'returning' => $returningCount
        ];
    }

    /**
     * Update stats when a new order is added
     */
    public function updateStatsForNewOrder(LiveSessionOrder $order)
    {
        // For accuracy, recalculate entire session when new order is added
        $this->calculateStatsForSession($order->live_session_id, $order->live_session_date);
    }

    /**
     * Recalculate all stats for a date range
     */
    public function recalculateStatsForDateRange($startDate, $endDate)
    {
        $sessions = LiveSessionOrder::whereBetween('live_session_date', [$startDate, $endDate])
            ->select('live_session_id', 'live_session_date')
            ->distinct()
            ->get();

        foreach ($sessions as $session) {
            $this->calculateStatsForSession($session->live_session_id, $session->live_session_date);
        }
    }

    public function processOrder(Order $order, array $liveSessionInfo)
    {
        try {
            DB::beginTransaction();

            // Create live session order record
            $liveSessionOrder = LiveSessionOrder::create([
                'order_id' => $order->id,
                'live_session_id' => $liveSessionInfo['session_id'],
                'live_session_date' => $liveSessionInfo['date'],
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'shipping_address' => $order->shipping_address,
                'total_amount' => $order->total_amount
            ]);

            // Create order items
            foreach ($order->items as $item) {
                $liveSessionOrder->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total
                ]);
            }

            // Update statistics
            $this->updateSessionStats($liveSessionInfo['session_id'], $liveSessionInfo['date']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process live session order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateSessionStats(string $sessionId, string $date)
    {
        $stats = $this->calculateSessionStats($sessionId, $date);

        LiveSessionStats::updateOrCreate(
            [
                'live_session_id' => $sessionId,
                'live_session_date' => $date
            ],
            array_merge($stats, ['last_calculated_at' => now()])
        );
    }

    private function calculateSessionStats(string $sessionId, string $date)
    {
        $orders = LiveSessionOrder::where('live_session_id', $sessionId)
            ->where('live_session_date', $date)
            ->with(['order', 'items'])
            ->get();

        $totalOrders = $orders->count();
        $successfulOrders = $orders->filter(fn($o) => $o->order->status === 'completed')->count();
        $canceledOrders = $orders->filter(fn($o) => $o->order->status === 'canceled')->count();
        $deliveringOrders = $orders->filter(fn($o) => $o->order->status === 'delivering')->count();

        $totalRevenue = $orders->sum('total_amount');

        // Calculate customer stats
        $customerIds = $orders->pluck('customer_id')->unique();
        $totalCustomers = $customerIds->count();

        $previousCustomers = LiveSessionOrder::where('live_session_date', '<', $date)
            ->whereIn('customer_id', $customerIds)
            ->pluck('customer_id')
            ->unique();

        $newCustomers = $customerIds->diff($previousCustomers)->count();
        $returningCustomers = $totalCustomers - $newCustomers;

        // Calculate top products
        $topProducts = $this->calculateTopProducts($orders);

        // Calculate orders by status
        $ordersByStatus = $orders->groupBy('order.status')
            ->map(fn($group) => $group->count())
            ->toArray();

        return [
            'total_orders' => $totalOrders,
            'successful_orders' => $successfulOrders,
            'canceled_orders' => $canceledOrders,
            'delivering_orders' => $deliveringOrders,
            'total_revenue' => $totalRevenue,
            'conversion_rate' => $totalOrders > 0 ? ($successfulOrders / $totalOrders) * 100 : 0,
            'cancellation_rate' => $totalOrders > 0 ? ($canceledOrders / $totalOrders) * 100 : 0,
            'delivering_rate' => $totalOrders > 0 ? ($deliveringOrders / $totalOrders) * 100 : 0,
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'top_products' => $topProducts,
            'orders_by_status' => $ordersByStatus
        ];
    }

    private function calculateTopProducts($orders)
    {
        return $orders->flatMap(fn($order) => $order->items)
            ->groupBy('product_id')
            ->map(function($items) {
                $first = $items->first();
                return [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product_name,
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum('total')
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values()
            ->toArray();
    }
}
