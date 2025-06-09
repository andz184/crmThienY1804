<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\LiveSessionOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LiveSessionRevenue extends Model
{
    protected $fillable = [
        'date',
        'live_number',
        'session_name',
        'total_revenue',
        'total_orders',
        'successful_orders',
        'canceled_orders',
        'delivering_orders',
        'total_customers',
        'new_customers',
        'returning_customers',
        'conversion_rate',
        'cancellation_rate',
        'top_products',
        'orders_by_status',
        'orders_by_province'
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cancellation_rate' => 'decimal:2',
        'top_products' => 'array',
        'orders_by_status' => 'array',
        'orders_by_province' => 'array',
        'daily_stats' => 'array'
    ];

    /**
     * Update or create revenue record from order data
     */
    public static function updateFromOrder($order)
    {

        if (!$order->live_session_info) {
            return;
        }

        $liveSessionOrder = LiveSessionOrder::where('order_id', $order->id)->first();
       
        if (!$liveSessionOrder) {
            return;
        }

        $liveSessionInfo = json_decode($order->live_session_info, true);
        if (!isset($liveSessionInfo['live_number'])) {
            return;
        }

        self::recalculateStats($liveSessionOrder->live_session_date, $liveSessionInfo['live_number']);
    }

    /**
     * Recalculate all stats for a specific session
     */
    public static function recalculateStats($date, $liveNumber)
    {
        Log::info("Starting recalculateStats for date: {$date}, live number: {$liveNumber}");

        // Get all orders for this live session using live_session_orders table
        $liveSessionOrders = LiveSessionOrder::where('live_session_date', $date)
            ->whereHas('order', function ($query) use ($liveNumber) {
                $query->whereRaw("JSON_EXTRACT(live_session_info, '$.live_number') = ?", [$liveNumber]);
            })
            ->with(['order.items']) // Eager load order items
            ->get();

        Log::info("Found live session orders: " . $liveSessionOrders->count());

        // Get all orders
        $orders = $liveSessionOrders->pluck('order');
        Log::info("Total orders: " . $orders->count());
        Log::info("Completed orders: " . $orders->where('pancake_status', '3')->count());

        // Create or update revenue record
        $revenue = self::firstOrNew([
            'date' => $date,
            'live_number' => $liveNumber
        ]);

        // Set session name if not set
        if (!$revenue->session_name) {
            $revenue->session_name = "LIVE {$liveNumber} (" . \Carbon\Carbon::parse($date)->format('d/m/Y') . ")";
        }

        // Calculate statistics
        $revenue->total_orders = $orders->count();
        $revenue->successful_orders = $orders->where('pancake_status', '3')->count();
        $revenue->canceled_orders = $orders->where('pancake_status', '2')->count();
        $revenue->delivering_orders = $orders->where('pancake_status', '1')->count();
        $revenue->total_revenue = $orders->where('pancake_status', '3')->sum('total_value');

        // Calculate customer statistics
        $customerIds = $orders->pluck('customer_id')->unique();
        $revenue->total_customers = $customerIds->count();

        // Calculate new customers (first order is from this live session)
        $newCustomers = 0;
        foreach ($customerIds as $customerId) {
            $firstOrder = Order::where('customer_id', $customerId)
                ->orderBy('created_at')
                ->first();

            if ($firstOrder && $firstOrder->live_session_info) {
                $firstOrderInfo = json_decode($firstOrder->live_session_info, true);
                if (isset($firstOrderInfo['session_date']) &&
                    $firstOrderInfo['session_date'] == $date &&
                    isset($firstOrderInfo['live_number']) &&
                    $firstOrderInfo['live_number'] == $liveNumber) {
                    $newCustomers++;
                }
            }
        }
        $revenue->new_customers = $newCustomers;
        $revenue->returning_customers = $revenue->total_customers - $newCustomers;

        // Calculate rates
        if ($revenue->total_orders > 0) {
            $revenue->conversion_rate = ($revenue->successful_orders / $revenue->total_orders) * 100;
            $revenue->cancellation_rate = ($revenue->canceled_orders / $revenue->total_orders) * 100;
        }

        // Calculate orders by status
        $ordersByStatus = [];
        foreach ($orders as $order) {
            $status = $order->pancake_status;
            if (!isset($ordersByStatus[$status])) {
                $ordersByStatus[$status] = [
                    'count' => 0,
                    'revenue' => 0
                ];
            }
            $ordersByStatus[$status]['count']++;
            $ordersByStatus[$status]['revenue'] += $order->total_value;
        }
        $revenue->orders_by_status = $ordersByStatus;

        // Calculate orders by province
        $ordersByProvince = [];
        foreach ($orders as $order) {
            if ($order->province_code) {
                if (!isset($ordersByProvince[$order->province_code])) {
                    $ordersByProvince[$order->province_code] = [
                        'count' => 0,
                        'revenue' => 0
                    ];
                }
                $ordersByProvince[$order->province_code]['count']++;
                $ordersByProvince[$order->province_code]['revenue'] += $order->total_value;
            }
        }
        $revenue->orders_by_province = $ordersByProvince;

        // Save changes
        $revenue->save();

        // Update products data
        self::updateProductsData($date, $liveNumber);

        return $revenue;
    }

    /**
     * Get revenue data for a date range
     */
    public static function getRevenueData($startDate, $endDate)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('live_number', 'asc')
            ->get();
    }

    /**
     * Get daily aggregated stats
     */
    public static function getDailyStats($startDate, $endDate)
    {
        $sessions = self::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('live_number', 'asc')
            ->get();

        return $sessions->groupBy('date')
            ->map(function($daySessions) {
                return [
                    'total_revenue' => $daySessions->sum('total_revenue'),
                    'total_revenue_potential' => $daySessions->sum('total_revenue'),
                    'successful_revenue' => $daySessions->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_COMPLETED)->sum('total_revenue'),
                    'canceled_revenue' => $daySessions->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_CANCELED)->sum('total_revenue'),
                    'total_orders' => $daySessions->sum('total_orders'),
                    'successful_orders' => $daySessions->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_COMPLETED)->sum('successful_orders'),
                    'canceled_orders' => $daySessions->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_CANCELED)->sum('canceled_orders'),
                    'delivering_orders' => $daySessions->where('pancake_status', \App\Models\Order::PANCAKE_STATUS_SHIPPING)->sum('delivering_orders'),
                    'sessions' => $daySessions->map(function($session) {
                        return [
                            'id' => $session->id,
                            'name' => $session->session_name,
                            'total_revenue' => $session->total_revenue,
                            'total_orders' => $session->total_orders,
                            'successful_orders' => $session->successful_orders,
                            'canceled_orders' => $session->canceled_orders,
                            'delivering_orders' => $session->delivering_orders,
                            'orders_by_status' => $session->orders_by_status,
                            'orders_by_province' => $session->orders_by_province
                        ];
                    })
                ];
            });
    }

    /**
     * Calculate and save top products for this session
     */
    public function calculateTopProducts()
    {
        $products = [];

        // Get all orders for this live session
        $liveSessionOrders = LiveSessionOrder::where('live_session_date', $this->date)
            ->whereHas('order', function ($query) {
                $query->whereRaw("JSON_EXTRACT(live_session_info, '$.live_number') = ?", [$this->live_number])
                    ->where('pancake_status', Order::PANCAKE_STATUS_COMPLETED); // Only count completed orders
            })
            ->with(['order.items']) // Eager load order items
            ->get();

        // Process each order's items
        foreach ($liveSessionOrders as $liveSessionOrder) {
            foreach ($liveSessionOrder->order->items as $item) {
                $productId = $item->pancake_variant_id ?? $item->product_code ?? $item->code;
                if (!$productId) continue;

                if (!isset($products[$productId])) {
                    $products[$productId] = [
                        'id' => $productId,
                        'name' => $item->product_name ?? $item->name ?? 'Unknown Product',
                        'quantity' => 0,
                        'revenue' => 0
                    ];
                }

                $products[$productId]['quantity'] += $item->quantity;
                $products[$productId]['revenue'] += $item->price * $item->quantity;
            }
        }

        // Sort by revenue and get top 5
        uasort($products, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        $this->top_products = array_slice($products, 0, 5);
        $this->save();
    }

    /**
     * Get top products for a date range
     */
    public static function getTopProducts($startDate, $endDate)
    {
        $products = [];

        // Get all sessions in date range
        $sessions = self::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('top_products')
            ->get();

        // Combine top products from all sessions
        foreach ($sessions as $session) {
            if (empty($session->top_products)) continue;

            foreach ($session->top_products as $product) {
                $id = $product['id'];
                if (!isset($products[$id])) {
                    $products[$id] = [
                        'id' => $id,
                        'name' => $product['name'],
                        'quantity' => 0,
                        'revenue' => 0,
                        'orders' => $product['orders'] ?? 1,
                        'unit_price' => $product['unit_price'] ?? 0
                    ];
                }
                $products[$id]['quantity'] += $product['quantity'];
                $products[$id]['revenue'] += $product['revenue'];
                $products[$id]['orders'] += $product['orders'] ?? 1;
            }
        }

        // Sort by revenue and get top 5
        uasort($products, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        // Add average_price field, avoid division by zero
        foreach ($products as &$product) {
            $product['average_price'] = ($product['quantity'] > 0) ? ($product['revenue'] / $product['quantity']) : 0;
        }
        unset($product);

        return array_values($products);
    }

    /**
     * Update products data for a specific live session
     */
    public static function updateProductsData($date, $liveNumber)
    {
        Log::info("Starting updateProductsData for date: {$date}, live number: {$liveNumber}");

        // Get all orders for this live session
        $liveSessionOrders = LiveSessionOrder::where('live_session_date', $date)
            ->whereHas('order', function ($query) use ($liveNumber) {
                $query->whereRaw("JSON_EXTRACT(live_session_info, '$.live_number') = ?", [$liveNumber]);
            })
            ->with(['order']) // Eager load order
            ->get();

        Log::info("Found {$liveSessionOrders->count()} orders for live session", [
            'date' => $date,
            'live_number' => $liveNumber,
            'order_ids' => $liveSessionOrders->pluck('order_id')->toArray()
        ]);

        if ($liveSessionOrders->isEmpty()) {
            Log::warning("No orders found for live session", [
                'date' => $date,
                'live_number' => $liveNumber
            ]);
            return null;
        }

        $productsData = [];
        $hasProducts = false;

        // Process each order's items
        foreach ($liveSessionOrders as $liveSessionOrder) {
            if (!$liveSessionOrder->order) {
                Log::warning("Order not found for live session order ID: {$liveSessionOrder->id}");
                continue;
            }

            // Get products data from order
            $orderProducts = json_decode($liveSessionOrder->order->products_data, true);
            Log::info("Processing order products", [
                'order_id' => $liveSessionOrder->order->id,
                'products_data' => $orderProducts
            ]);

            if (!is_array($orderProducts)) {
                Log::warning("Invalid products_data format for order ID: {$liveSessionOrder->order->id}", [
                    'products_data' => $liveSessionOrder->order->products_data
                ]);
                continue;
            }

            foreach ($orderProducts as $item) {
                if (!is_array($item)) {
                    Log::warning("Invalid product item format in order ID: {$liveSessionOrder->order->id}", [
                        'item' => $item
                    ]);
                    continue;
                }

                $hasProducts = true;

                // Get product ID with fallback options
                $productId = $item['id'] ?? $item['product_code'] ?? $item['code'] ?? 'unknown_' . uniqid();

                // Get product name with fallback options
                $productName = $item['variation_info']['name'] ?? $item['name'] ?? 'Unknown Product';

                // Get price and quantity with fallback to 0
                $price = floatval($item['variation_info']['retail_price'] ?? $item['price'] ?? 0);
                $quantity = intval($item['quantity'] ?? 0);

                Log::info("Processing product", [
                    'product_id' => $productId,
                    'name' => $productName,
                    'price' => $price,
                    'quantity' => $quantity,
                    'order_status' => $liveSessionOrder->order->pancake_status ?? 'unknown'
                ]);

                if (!isset($productsData[$productId])) {
                    $productsData[$productId] = [
                        'id' => $productId,
                        'name' => $productName,
                        'unit_price' => $price, // Store unit price
                        'quantity_ordered' => 0,
                        'expected_revenue' => 0,
                        'quantity_actual' => 0,
                        'actual_revenue' => 0,
                        'orders_count' => 0
                    ];
                }

                $productsData[$productId]['quantity_ordered'] += $quantity;
                $productsData[$productId]['expected_revenue'] += ($price * $quantity);
                $productsData[$productId]['orders_count']++; // Increment order count for this product

                // Check if the order is completed for actual revenue and quantity
                if (isset($liveSessionOrder->order) && $liveSessionOrder->order->pancake_status == Order::PANCAKE_STATUS_COMPLETED) {
                    $productsData[$productId]['quantity_actual'] += $quantity;
                    $productsData[$productId]['actual_revenue'] += ($price * $quantity);
                }
            }
        }

        // If no products found, create a default "Unknown" product structure
        if (!$hasProducts) {
            Log::info("No products found for live session, creating default Unknown product structure");
            $productsData['unknown'] = [
                'id' => 'unknown',
                'name' => 'Unknown Product',
                'unit_price' => 0,
                'quantity_ordered' => 0,
                'expected_revenue' => 0,
                'quantity_actual' => 0,
                'actual_revenue' => 0,
                'orders_count' => 0
            ];
        }

        // Sort products by expected_revenue (or actual_revenue, as per preference) and get top 5 (or all if needed)
        uasort($productsData, function($a, $b) {
            return $b['expected_revenue'] <=> $a['expected_revenue']; // Sorting by expected revenue
        });

        // Get or create live session revenue record
        $revenue = self::firstOrNew([
            'date' => $date,
            'live_number' => $liveNumber
        ]);

        // Set required fields if they don't exist
        // if (!$revenue->session_name) {
        //     $revenue->session_name = "LIVE {$liveNumber} (" . Carbon::parse($date)->format('d/m/Y') . ")";
        // }

        // Calculate total revenue from products
        $totalRevenue = array_sum(array_column($productsData, 'actual_revenue'));
        // $revenue->total_revenue = $totalRevenue;

        // Update top products
        $topProducts = array_values(array_slice($productsData, 0, 5));

        Log::info("Preparing to save revenue record", [
            'date' => $date,
            'live_number' => $liveNumber,
            'revenue_id' => $revenue->id,
            'session_name' => $revenue->session_name,
            'total_revenue' => $totalRevenue,
            'top_products' => $topProducts,
            'products_count' => count($topProducts)
        ]);

        try {
            DB::beginTransaction();

            $revenue->top_products = $topProducts;
            $saved = $revenue->save();

            if (!$saved) {
                Log::error("Failed to save revenue record", [
                    'revenue' => $revenue->toArray(),
                    'errors' => $revenue->getErrors()
                ]);
                DB::rollBack();
                return null;
            }

            DB::commit();

            Log::info("Successfully saved revenue record", [
                'saved' => $saved,
                'revenue_id' => $revenue->id,
                'date' => $date,
                'live_number' => $liveNumber,
                'total_revenue' => $totalRevenue
            ]);

            return $revenue;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Exception while saving revenue record", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'revenue' => $revenue->toArray()
            ]);
            throw $e;
        }
    }
}
