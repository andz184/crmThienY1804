<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\LiveSessionReport;
use App\Models\Order;
use App\Models\PancakeShop;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Models\DailyRevenueAggregate;
use Carbon\CarbonPeriod;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth');
    }

    /**
     * Đồng bộ dữ liệu từ Pancake trước khi tính toán báo cáo
     */
    public function syncFromPancake(Request $request)
    {
        // Kiểm tra quyền
        $this->authorize('reports.view');

        try {
            // Lấy thông tin khoảng thời gian nếu được cung cấp
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            // Gọi service để đồng bộ dữ liệu từ Pancake
            $result = app(\App\Services\PancakeSyncService::class)->syncOrdersForReports($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Đã đồng bộ dữ liệu thành công từ Pancake.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị trang báo cáo chính
     */
    public function index()
    {
        $this->authorize('reports.view');
        return view('reports.index');
    }

    /**
     * Hiển thị trang báo cáo tổng doanh thu
     */
    public function totalRevenuePage()
    {
        $this->authorize('reports.total_revenue');
        return view('reports.total_revenue');
    }

    /**
     * Hiển thị trang báo cáo chi tiết
     */
    public function detailPage()
    {
        $this->authorize('reports.detailed');
        return view('reports.detail');
    }

    /**
     * Hiển thị trang báo cáo theo nhóm hàng hóa
     */
    public function productGroupsPage(Request $request)
    {
        $this->authorize('reports.product_groups');

        // Date filtering
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        if ($request->filled('date_range')) {
            $dateParts = explode(' - ', $request->input('date_range'));
            if (count($dateParts) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[1]))->endOfDay();
                } catch (\Exception $e) {
                    Log::error('ReportController@productGroupsPage: Invalid date_range format. Value: ' . $request->input('date_range') . ' Error: ' . $e->getMessage());
                    // Fallback to default if parsing fails
                    $startDate = Carbon::now()->startOfMonth()->startOfDay();
                    $endDate = Carbon::now()->endOfDay();
                }
            }
        }

        Log::info("ReportController@productGroupsPage: Processing report for date range: {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}");

        // Fetch orders with items within the date range
        // We need to filter orders based on their creation date, and then process their items.
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at') // Exclude deleted orders
            ->where(function ($query) { // Exclude cancelled orders (example status, adjust as needed)
                $query->where('status', '!=', Order::STATUS_DA_HUY)
                      ->orWhereNull('status');
            })
            ->with(['items']) // Eager load items
            ->get();

        Log::info("ReportController@productGroupsPage: Found " . $orders->count() . " orders for the period.");

        $categoryData = [];
        // Fetch Pancake categories, mapping pancake_id to name
        $categoryMap = \App\Models\PancakeCategory::pluck('name', 'pancake_id')->all();

        foreach ($orders as $order) {
            $orderCategoriesProcessed = []; // To count each category once per order for order count metric

            foreach ($order->items as $item) {
                if (!empty($item->product_info)) {
                    $productInfo = null;
                    if (is_string($item->product_info)) {
                        $productInfo = json_decode($item->product_info, true);
                    } elseif (is_array($item->product_info) || is_object($item->product_info)) {
                        $productInfo = (array) $item->product_info; // Cast to array if it's an object
                    }

                    if (!$productInfo) {
                        Log::warning("ReportController@productGroupsPage: Failed to process product_info for item ID {$item->id}. product_info was: ", [$item->product_info]);
                        continue;
                    }

                    $itemCategoryIds = [];

                    // Extract category_ids, checking various possible structures due to past sync logic
                    if (isset($productInfo['variation_info']['category_ids']) && is_array($productInfo['variation_info']['category_ids'])) {
                        $itemCategoryIds = $productInfo['variation_info']['category_ids'];
                    } elseif (isset($productInfo['category_ids']) && is_array($productInfo['category_ids'])) { // Direct category_ids in product_info
                        $itemCategoryIds = $productInfo['category_ids'];
                    } elseif (isset($productInfo['processed_variation_info']['category_ids']) && is_array($productInfo['processed_variation_info']['category_ids'])) { // From components logic
                        $itemCategoryIds = $productInfo['processed_variation_info']['category_ids'];
                    }


                    foreach ($itemCategoryIds as $categoryId) {
                        // $categoryId from product_info is the pancake_id
                        if (!isset($categoryMap[$categoryId])) {
                            Log::warning("ReportController@productGroupsPage: Pancake Category ID {$categoryId} not found in categoryMap for item ID {$item->id}, product_info: " . json_encode($item->product_info));
                            continue; // Skip if category ID doesn't exist in our map
                        }
                        $categoryName = $categoryMap[$categoryId];

                        // Use $categoryId (which is pancake_id) as the key for $categoryData
                        if (!isset($categoryData[$categoryId])) {
                            $categoryData[$categoryId] = [
                                'id' => $categoryId, // Store pancake_id as 'id' for consistency in the view perhaps, or use a different key
                                'name' => $categoryName,
                                'total_revenue' => 0,
                                'total_orders' => 0,
                                'total_quantity_sold' => 0,
                            ];
                        }

                        // Aggregate data
                        // Revenue is item price * quantity for this item
                        $categoryData[$categoryId]['total_revenue'] += ($item->price * $item->quantity);
                        $categoryData[$categoryId]['total_quantity_sold'] += $item->quantity;

                        // Increment order count for this category if this is the first item from this category for this order
                        if (!isset($orderCategoriesProcessed[$categoryId])) {
                            $categoryData[$categoryId]['total_orders']++;
                            $orderCategoriesProcessed[$categoryId] = true;
                        }
                    }
                } else {
                    Log::debug("ReportController@productGroupsPage: Item ID {$item->id} in order ID {$order->id} has empty product_info.");
                }
            }
        }

        // Sort by revenue by default (descending)
        uasort($categoryData, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        Log::info("ReportController@productGroupsPage: Processed " . count($categoryData) . " categories.");

        // Prepare data for charts
        $chartCategoryNames = [];
        $chartRevenueData = [];
        $chartOrderCountData = [];
        $chartQuantityData = [];

        foreach ($categoryData as $cat) {
            $chartCategoryNames[] = $cat['name'];
            $chartRevenueData[] = $cat['total_revenue'];
            $chartOrderCountData[] = $cat['total_orders'];
            $chartQuantityData[] = $cat['total_quantity_sold'];
        }

        // Overall summary stats
        $totalRevenueAllGroups = array_sum(array_column($categoryData, 'total_revenue'));
        $totalOrdersAllGroups = array_sum(array_column($categoryData, 'total_orders')); // This might double count if an order has items from multiple groups.
                                                                                        // A more accurate total order count for "orders with any product group item" would be $orders->count() if every order had an item with a category.
                                                                                        // For now, sum of category orders might be what user expects for "total orders touching groups".
        $totalQuantityAllGroups = array_sum(array_column($categoryData, 'total_quantity_sold'));


        return view('reports.product_groups', compact(
            'startDate',
            'endDate',
            'categoryData', // For the table
            'chartCategoryNames',
            'chartRevenueData',
            'chartOrderCountData',
            'chartQuantityData',
            'totalRevenueAllGroups',
            'totalOrdersAllGroups',
            'totalQuantityAllGroups'
        ));
    }

    /**
     * Hiển thị trang báo cáo theo chiến dịch
     */
    public function campaignsPage(Request $request)
    {
        // Required permission for viewing campaign reports
        $this->authorize('reports.campaigns');

        // Date filtering
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        if ($request->filled('date_range')) {
            $dateParts = explode(' - ', $request->input('date_range'));
            if (count($dateParts) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[1]))->endOfDay();
                } catch (\Exception $e) {
                    Log::error('ReportController@campaignsPage: Invalid date_range format.', ['value' => $request->input('date_range'), 'error' => $e->getMessage()]);
                    // Keep default if parsing fails
                }
            }
        }

        // Base query for orders within the date range and with a post_id
        $ordersQuery = Order::whereNotNull('post_id')
            ->where('status', '!=', Order::STATUS_DA_HUY) // Exclude cancelled orders
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items']); // Eager load items

        // Shop filtering
        if ($request->filled('pancake_shop_id')) {
            $ordersQuery->where('pancake_shop_id', $request->input('pancake_shop_id'));
        }

        // Page filtering
        if ($request->filled('pancake_page_id')) {
            $ordersQuery->where('pancake_page_id', $request->input('pancake_page_id'));
        }

        $ordersForCampaigns = $ordersQuery->get();

        // Group orders by post_id
        $campaignsData = [];
        foreach ($ordersForCampaigns as $order) {
            $postId = $order->post_id;
            if (!isset($campaignsData[$postId])) {
                $campaignsData[$postId] = [
                    'post_id' => $postId,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'products' => [],
                    'product_summary' => [] // For aggregated product stats
                ];
            }
            $campaignsData[$postId]['total_orders']++;
            $campaignsData[$postId]['total_revenue'] += $order->total_value;

            foreach ($order->items as $item) {
                $productName = $item->product_name ?? ($item->name ?? 'Không xác định');
                $productId = $item->pancake_product_id ?? $item->sku ?? $productName; // Unique key for product

                if (!isset($campaignsData[$postId]['product_summary'][$productId])) {
                    $campaignsData[$postId]['product_summary'][$productId] = [
                        'name' => $productName,
                        'sku' => $item->sku,
                        'quantity' => 0,
                        'revenue' => 0
                    ];
                }
                $campaignsData[$postId]['product_summary'][$productId]['quantity'] += $item->quantity;
                $campaignsData[$postId]['product_summary'][$productId]['revenue'] += ($item->price * $item->quantity);
            }
        }

        // Process product summaries and get top N for each campaign
        $topNProducts = 3; // Show top 3 products per campaign
        foreach ($campaignsData as $postId => &$campaign) { // Use reference to modify directly
            // Sort products by revenue (descending)
            uasort($campaign['product_summary'], function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
            // Get top N products
            $campaign['products'] = array_slice($campaign['product_summary'], 0, $topNProducts, true);
            $campaign['average_order_value'] = $campaign['total_orders'] > 0 ? $campaign['total_revenue'] / $campaign['total_orders'] : 0;
            // unset($campaign['product_summary']); // Optionally remove the full summary to save memory if not needed elsewhere
        }
        unset($campaign); // Unset reference

        // Sort campaigns by total revenue (descending)
        uasort($campaignsData, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        $shops = PancakeShop::orderBy('name')->get();
        $pages = []; // Will be loaded by AJAX or pre-filled if a shop is selected

        if ($request->filled('pancake_shop_id')) {
            $selectedShopId = $request->input('pancake_shop_id');
            $selectedShop = PancakeShop::find($selectedShopId);
            if ($selectedShop) {
                $pages = $selectedShop->pages()->orderBy('name')->get();
            }
        }

        // Data for Campaign Performance Overview Chart
        $chartCampaignLabels = [];
        $chartCampaignRevenue = [];
        foreach($campaignsData as $campaign) {
            $chartCampaignLabels[] = $campaign['post_id']; // Or a more descriptive name if available
            $chartCampaignRevenue[] = $campaign['total_revenue'];
        }


        return view('reports.campaigns', compact(
            'campaignsData',
            'startDate',
            'endDate',
            'shops',
            'pages', // Pass pages for the selected shop
            'chartCampaignLabels',
            'chartCampaignRevenue'
        ));
    }

    /**
     * Hiển thị trang báo cáo phiên live
     */
    public function liveSessionsPage(Request $request)
    {
        // Authorization check can be re-enabled if needed
        // $this->authorize('reports.live_sessions');

        // Daily detail drill-down - keep if used, may need update later
        if ($request->has('daily_detail') && $request->has('detail_date')) {
            return $this->getDailyOrderDetails($request);
        }

        // Date filtering:
        $inputStartDate = null;
        $inputEndDate = null;

        if ($request->filled('date_range')) {
            $dateParts = explode(' - ', $request->input('date_range'));
            if (count($dateParts) === 2) {
                try {
                    $inputStartDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[0]))->startOfDay();
                    $inputEndDate = Carbon::createFromFormat('m/d/Y', trim($dateParts[1]))->endOfDay();
                } catch (\Exception $e) {
                    Log::error('ReportController@liveSessionsPage: Invalid date_range format. Value: ' . $request->input('date_range') . ' Error: ' . $e->getMessage());
                    $inputStartDate = Carbon::now()->startOfMonth()->startOfDay();
                    $inputEndDate = Carbon::now()->endOfDay();
                }
            }
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            try {
                $inputStartDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $inputEndDate = Carbon::parse($request->input('end_date'))->endOfDay();
            } catch (\Exception $e) {
                Log::error('ReportController@liveSessionsPage: Invalid start_date/end_date format. Error: ' . $e->getMessage());
                $inputStartDate = Carbon::now()->startOfMonth()->startOfDay();
                $inputEndDate = Carbon::now()->endOfDay();
            }
        }

        $startDate = $inputStartDate ?? Carbon::now()->startOfMonth()->startOfDay();
        $endDate = $inputEndDate ?? Carbon::now()->endOfDay();

        Log::info("ReportController@liveSessionsPage: Received date_range from request: " . ($request->input('date_range') ?? 'Not provided'));
        Log::info("ReportController@liveSessionsPage: Parsed StartDate: {$startDate->toDateTimeString()}, Parsed EndDate: {$endDate->toDateTimeString()}");

        Log::info("ReportController@liveSessionsPage: Processing report for date range: {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}");

        // Function to process data for a given date range
        $processReportData = function (Carbon $filterPeriodStart, Carbon $filterPeriodEnd, $pancakeStatusMap) use ($request) {
            Log::info("ReportController@processReportData: Filtering for SESSION DATES between: {$filterPeriodStart->toDateTimeString()} to {$filterPeriodEnd->toDateTimeString()}");

            // 1. Fetch ALL orders that could potentially be live sessions (based on live_session_info)
            $allPotentialLiveOrders = Order::query()
                ->whereNotNull('live_session_info')
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(live_session_info, "$.live_number")) IS NOT NULL')
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(live_session_info, "$.session_date")) IS NOT NULL')
                ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(live_session_info, "$.original_text")) IS NOT NULL')
                ->where('pancake_inserted_at', '>=', Carbon::now()->subYears(1)->startOfDay())
                ->with(['items'])
                ->when($request->has('session_date'), function ($query) use ($request) {
                    $sessionDate = $request->input('session_date');
                    return $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(live_session_info, "$.session_date")) = ?', [$sessionDate]);
                })
                ->when($request->has('live_number'), function ($query) use ($request) {
                    $liveNumber = $request->input('live_number');
                    return $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(live_session_info, "$.live_number")) = ?', [$liveNumber]);
                })
                ->when($request->has('status'), function ($query) use ($request) {
                    $status = $request->input('status');
                    return $query->where('status', $status);
                })
                ->when($request->has('customer_id'), function ($query) use ($request) {
                    $customerId = $request->input('customer_id');
                    return $query->where('customer_id', $customerId);
                })
                ->orderBy('pancake_inserted_at', 'desc')
                ->get();

            Log::info('ReportController@processReportData: Found ' . $allPotentialLiveOrders->count() . ' total orders with valid live_session_info.');

            // 2. Process all potential live orders to determine their actual session_date and other details
            $allProcessedLiveSessions = [];

            foreach ($allPotentialLiveOrders as $order) {
                // Parse live_session_info JSON
                $liveSessionInfo = json_decode($order->live_session_info, true);
                if (json_last_error() !== JSON_ERROR_NONE || !isset($liveSessionInfo['live_number'], $liveSessionInfo['session_date'])) {
                    Log::debug("ReportController@processReportData: Invalid or incomplete live_session_info for order ID {$order->id}. Skipping.");
                    continue;
                }

                $liveNumber = (int) $liveSessionInfo['live_number'];
                try {
                    $sessionDateCarbon = Carbon::parse($liveSessionInfo['session_date']);
                } catch (\Exception $e) {
                    Log::warning("ReportController@processReportData: Invalid session_date in live_session_info for order ID {$order->id}. Falling back to pancake_inserted_at.");
                    $sessionDateCarbon = $order->pancake_inserted_at ? Carbon::parse($order->pancake_inserted_at) : Carbon::now();
                }

                $sessionDateStr = $sessionDateCarbon->format('Y-m-d');
                $liveSessionAggKey = "LIVE{$liveNumber}_" . $sessionDateCarbon->format('Ymd');

                if (!isset($allProcessedLiveSessions[$liveSessionAggKey])) {
                    $allProcessedLiveSessions[$liveSessionAggKey] = [
                        'id' => $liveSessionAggKey,
                        'name' => "LIVE {$liveNumber} (" . $sessionDateCarbon->format('d/m/Y') . ")",
                        'live_number' => $liveNumber,
                        'session_date_carbon' => $sessionDateCarbon,
                        'session_date' => $sessionDateStr,
                        'total_orders' => 0,
                        'successful_orders' => 0,
                        'canceled_orders' => 0,
                        'delivering_orders' => 0,
                        'revenue' => 0,
                        'orders_in_session' => [],
                        'products' => [],
                        'customers' => []
                    ];
                }

                $allProcessedLiveSessions[$liveSessionAggKey]['total_orders']++;
                $allProcessedLiveSessions[$liveSessionAggKey]['orders_in_session'][] = $order;

                // Process order status and update statistics
                $isSuccessful = false;
                $isCancelled = false;
                $isDelivering = false;
                $crmStatus = strtolower($order->status ?? '');
                $pancakeApiName = isset($order->pancake_status, $pancakeStatusMap[$order->pancake_status]) ? $pancakeStatusMap[$order->pancake_status] : null;

                $successfulStatuses = ['delivered', 'completed', 'thanh_cong', 'hoan_thanh', 'da_giao', 'da_nhan', 'da_thu_tien'];
                $cancelledStatuses = ['cancelled', 'canceled', 'huy', 'da_huy'];
                $deliveringStatuses = ['waiting_for_delivery', 'packing', 'delivering'];

                if (in_array($crmStatus, $cancelledStatuses) || ($pancakeApiName && in_array($pancakeApiName, $cancelledStatuses))) {
                    $isCancelled = true;
                } elseif (in_array($crmStatus, $deliveringStatuses) || ($pancakeApiName && in_array($pancakeApiName, $deliveringStatuses))) {
                    $isDelivering = true;
                } elseif (in_array($crmStatus, $successfulStatuses) || ($pancakeApiName && in_array($pancakeApiName, $successfulStatuses))) {
                    $isSuccessful = true;
                }

                if ($isCancelled) {
                    $allProcessedLiveSessions[$liveSessionAggKey]['canceled_orders']++;
                } elseif ($isDelivering) {
                    $allProcessedLiveSessions[$liveSessionAggKey]['delivering_orders']++;
                } elseif ($isSuccessful) {
                    $allProcessedLiveSessions[$liveSessionAggKey]['successful_orders']++;
                    $allProcessedLiveSessions[$liveSessionAggKey]['revenue'] += $order->total_value;
                }

                // Process products for this order
                foreach ($order->items as $item) {
                    $productName = $item->product_name ?? ($item->name ?? 'Không xác định');
                    $productId = $item->pancake_product_id ?? $item->sku ?? $productName;

                    if (!isset($allProcessedLiveSessions[$liveSessionAggKey]['products'][$productId])) {
                        $allProcessedLiveSessions[$liveSessionAggKey]['products'][$productId] = [
                            'name' => $productName,
                            'sku' => $item->sku ?? '',
                            'quantity' => 0,
                            'revenue' => 0,
                            'orders' => 0,
                        ];
                    }

                    $allProcessedLiveSessions[$liveSessionAggKey]['products'][$productId]['quantity'] += $item->quantity;
                    if ($isSuccessful) {
                        $allProcessedLiveSessions[$liveSessionAggKey]['products'][$productId]['revenue'] += ($item->price * $item->quantity);
                        $allProcessedLiveSessions[$liveSessionAggKey]['products'][$productId]['orders']++;
                    }
                }

                // Process customer data
                if ($order->customer_id) {
                    if (!isset($allProcessedLiveSessions[$liveSessionAggKey]['customers'][$order->customer_id])) {
                        $allProcessedLiveSessions[$liveSessionAggKey]['customers'][$order->customer_id] = [
                            'id' => $order->customer_id,
                            'name' => $order->customer->name ?? 'Không xác định',
                            'phone' => $order->customer->phone ?? '',
                            'orders' => 0,
                            'total_spent' => 0,
                        ];
                    }

                    $allProcessedLiveSessions[$liveSessionAggKey]['customers'][$order->customer_id]['orders']++;
                    if ($isSuccessful) {
                        $allProcessedLiveSessions[$liveSessionAggKey]['customers'][$order->customer_id]['total_spent'] += $order->total_value;
                    }
                }
            }

            // 3. Filter processed sessions by the date range picker based on their session_date_carbon
            $filteredLiveSessionsForPeriod = [];
            foreach ($allProcessedLiveSessions as $sessionId => $sessionData) {
                if ($sessionData['session_date_carbon'] instanceof Carbon &&
                    $sessionData['session_date_carbon']->betweenIncluded($filterPeriodStart, $filterPeriodEnd)) {
                    $filteredLiveSessionsForPeriod[$sessionId] = $sessionData;
                }
            }

            Log::info('ReportController@processReportData: Found ' . count($filteredLiveSessionsForPeriod) . ' live session AGGREGATES matching SESSION DATE filter.');

            $result = array_values($filteredLiveSessionsForPeriod);

            // Recalculate rates for the $result
            foreach ($result as &$session) {
                $session['success_rate'] = $session['total_orders'] > 0 ? round(($session['successful_orders'] / $session['total_orders']) * 100, 2) : 0;
                $session['cancellation_rate'] = $session['total_orders'] > 0 ? round(($session['canceled_orders'] / $session['total_orders']) * 100, 2) : 0;
                $session['delivering_rate'] = $session['total_orders'] > 0 ? round(($session['delivering_orders'] / $session['total_orders']) * 100, 2) : 0;
            }
            unset($session);

            usort($result, function ($a, $b) {
                $dateComparison = strcmp($b['session_date'], $a['session_date']);
                if ($dateComparison === 0) {
                    return $a['live_number'] <=> $b['live_number'];
                }
                return $dateComparison;
            });

            // Overall summary stats
            $totalSessions = count($result);
            $totalRevenueAll = array_sum(array_column($result, 'revenue'));
            $totalOrdersAll = array_sum(array_column($result, 'total_orders'));
            $totalSuccessfulOrdersAll = array_sum(array_column($result, 'successful_orders'));
            $totalCanceledOrdersAll = array_sum(array_column($result, 'canceled_orders'));
            $totalDeliveringOrdersAll = array_sum(array_column($result, 'delivering_orders'));
            $overallSuccessRate = $totalOrdersAll > 0 ? round(($totalSuccessfulOrdersAll / $totalOrdersAll) * 100, 2) : 0;
            $overallCancellationRate = $totalOrdersAll > 0 ? round(($totalCanceledOrdersAll / $totalOrdersAll) * 100, 2) : 0;
            $overallDeliveringRate = $totalOrdersAll > 0 ? round(($totalDeliveringOrdersAll / $totalOrdersAll) * 100, 2) : 0;

            // Daily chart data preparation
            $dailyChartDataOutput = [];
            $currentDateIterator = $filterPeriodStart->copy();
            while ($currentDateIterator <= $filterPeriodEnd) {
                $dateString = $currentDateIterator->format('Y-m-d');
                $dailyChartDataOutput[$dateString] = [
                    'date_label' => $currentDateIterator->format('d/m'),
                    'full_date' => $dateString,
                    'total_orders' => 0,
                    'successful_orders' => 0,
                    'canceled_orders' => 0,
                    'delivering_orders' => 0,
                    'total_revenue_potential' => 0,
                    'successful_revenue' => 0,
                    'canceled_revenue' => 0,
                    'delivering_revenue' => 0,
                ];
                $currentDateIterator->addDay();
            }

            foreach ($result as $sessionInPeriod) {
                $targetPlotDateString = $sessionInPeriod['session_date_carbon']->format('Y-m-d');
                if (isset($dailyChartDataOutput[$targetPlotDateString])) {
                    foreach ($sessionInPeriod['orders_in_session'] as $order) {
                        $dailyChartDataOutput[$targetPlotDateString]['total_orders']++;
                        $dailyChartDataOutput[$targetPlotDateString]['total_revenue_potential'] += $order->total_value;

                        $isSuccessful = false;
                        $isCancelled = false;
                        $isDelivering = false;
                        $crmStatus = strtolower($order->status ?? '');
                        $pancakeApiNameDaily = isset($order->pancake_status, $pancakeStatusMap[$order->pancake_status]) ? $pancakeStatusMap[$order->pancake_status] : null;

                        if (in_array($crmStatus, $cancelledStatuses) || ($pancakeApiNameDaily && in_array($pancakeApiNameDaily, $cancelledStatuses))) {
                            $isCancelled = true;
                        } elseif (in_array($crmStatus, $deliveringStatuses) || ($pancakeApiNameDaily && in_array($pancakeApiNameDaily, $deliveringStatuses))) {
                            $isDelivering = true;
                        } elseif (in_array($crmStatus, $successfulStatuses) || ($pancakeApiNameDaily && in_array($pancakeApiNameDaily, $successfulStatuses))) {
                            $isSuccessful = true;
                        }

                        if ($isCancelled) {
                            $dailyChartDataOutput[$targetPlotDateString]['canceled_orders']++;
                            $dailyChartDataOutput[$targetPlotDateString]['canceled_revenue'] += $order->total_value;
                        } elseif ($isDelivering) {
                            $dailyChartDataOutput[$targetPlotDateString]['delivering_orders']++;
                            $dailyChartDataOutput[$targetPlotDateString]['delivering_revenue'] += $order->total_value;
                        } elseif ($isSuccessful) {
                            $dailyChartDataOutput[$targetPlotDateString]['successful_orders']++;
                            $dailyChartDataOutput[$targetPlotDateString]['successful_revenue'] += $order->total_value;
                        }
                    }
                }
            }
            $dailyChartDataOutput = array_values($dailyChartDataOutput);

            // Monthly chart data
            $monthlyChartDataForSessionPeriod = [];
            foreach ($result as $session) {
                $sessionMonthYear = $session['session_date_carbon']->format('Y-m');
                $monthLabel = $session['session_date_carbon']->format('m/Y');

                if (!isset($monthlyChartDataForSessionPeriod[$sessionMonthYear])) {
                    $monthlyChartDataForSessionPeriod[$sessionMonthYear] = [
                        'month_label' => $monthLabel,
                        'total_revenue_successful_live' => 0,
                        'successful_live_orders' => 0,
                        'canceled_live_orders' => 0,
                        'delivering_live_orders' => 0,
                        'total_live_orders_in_month' => 0,
                    ];
                }
                $monthlyChartDataForSessionPeriod[$sessionMonthYear]['total_revenue_successful_live'] += $session['revenue'];
                $monthlyChartDataForSessionPeriod[$sessionMonthYear]['successful_live_orders'] += $session['successful_orders'];
                $monthlyChartDataForSessionPeriod[$sessionMonthYear]['canceled_live_orders'] += $session['canceled_orders'];
                $monthlyChartDataForSessionPeriod[$sessionMonthYear]['delivering_live_orders'] += $session['delivering_orders'];
                $monthlyChartDataForSessionPeriod[$sessionMonthYear]['total_live_orders_in_month'] += $session['total_orders'];
            }
            foreach ($monthlyChartDataForSessionPeriod as &$monthData) {
                $monthData['live_success_rate'] = $monthData['total_live_orders_in_month'] > 0 ? round(($monthData['successful_live_orders'] / $monthData['total_live_orders_in_month']) * 100, 2) : 0;
                $monthData['live_cancellation_rate'] = $monthData['total_live_orders_in_month'] > 0 ? round(($monthData['canceled_live_orders'] / $monthData['total_live_orders_in_month']) * 100, 2) : 0;
                $monthData['live_delivering_rate'] = $monthData['total_live_orders_in_month'] > 0 ? round(($monthData['delivering_live_orders'] / $monthData['total_live_orders_in_month']) * 100, 2) : 0;
            }
            unset($monthData);
            ksort($monthlyChartDataForSessionPeriod);
            $monthlyChartDataForSessionPeriod = array_values($monthlyChartDataForSessionPeriod);

            // Top products, province, and customer data
            $ordersForCurrentPeriodStats = [];
            foreach ($result as $sessionInPeriod) {
                foreach ($sessionInPeriod['orders_in_session'] as $order) {
                    $ordersForCurrentPeriodStats[] = $order;
                }
            }
            Log::info('ReportController@processReportData: Number of orders for Top Product/Province/Customer stats: ' . count($ordersForCurrentPeriodStats));

            $liveSessionProductStats = [];
            foreach ($ordersForCurrentPeriodStats as $order) {
                foreach ($order->items as $item) {
                    $productName = $item->product_name ?? ($item->name ?? 'Sản phẩm không xác định');
                    if (empty(trim($productName))) $productName = 'Sản phẩm không xác định';
                    if (!isset($liveSessionProductStats[$productName])) {
                        $liveSessionProductStats[$productName] = ['quantity' => 0, 'revenue' => 0, 'name' => $productName];
                    }
                    $liveSessionProductStats[$productName]['quantity'] += $item->quantity;
                    $liveSessionProductStats[$productName]['revenue'] += ($item->price * $item->quantity);
                }
            }
            uasort($liveSessionProductStats, function ($a, $b) { return $b['revenue'] <=> $a['revenue']; });
            $topProductsOutput = array_slice($liveSessionProductStats, 0, 5, true);

            $allCustomerOrderHistory = [];
            $provinceDataForChartOutput = [];
            $provinceRevenueDataForChartOutput = [];

            foreach ($ordersForCurrentPeriodStats as $order) {
                if (!empty($order->province_name)) {
                    $provinceName = trim($order->province_name);
                    if (!isset($provinceDataForChartOutput[$provinceName])) $provinceDataForChartOutput[$provinceName] = 0;
                    $provinceDataForChartOutput[$provinceName]++;
                    if (!isset($provinceRevenueDataForChartOutput[$provinceName])) $provinceRevenueDataForChartOutput[$provinceName] = 0;
                    $provinceRevenueDataForChartOutput[$provinceName] += $order->total_value;
                }
                if ($order->customer_id) {
                    $customerId = $order->customer_id;
                    $orderDateCarbon = $order->pancake_inserted_at;

                    if (!isset($allCustomerOrderHistory[$customerId])) {
                        $allCustomerOrderHistory[$customerId] = [
                            'first_order_date_in_db' => Carbon::parse($order->pancake_inserted_at),
                            'live_session_order_count_in_current_filter_period' => 0
                        ];
                    }
                    $allCustomerOrderHistory[$customerId]['live_session_order_count_in_current_filter_period']++;
                    $orderDate = Carbon::parse($order->pancake_inserted_at);
                    if ($orderDate < $allCustomerOrderHistory[$customerId]['first_order_date_in_db']) {
                        $allCustomerOrderHistory[$customerId]['first_order_date_in_db'] = $orderDate;
                    }
                }
            }
            arsort($provinceDataForChartOutput);
            arsort($provinceRevenueDataForChartOutput);

            $totalUniqueCustomersOutput = count($allCustomerOrderHistory);
            $finalOverallNewCustomers = 0;
            $finalOverallReturningCustomers = 0;
            foreach ($allCustomerOrderHistory as $custId => $history) {
                if ($history['live_session_order_count_in_current_filter_period'] > 0) {
                    if ($history['first_order_date_in_db'] instanceof Carbon &&
                        $history['first_order_date_in_db']->betweenIncluded($filterPeriodStart, $filterPeriodEnd) &&
                        $history['live_session_order_count_in_current_filter_period'] === 1) {
                        $finalOverallNewCustomers++;
                    } else {
                        $finalOverallReturningCustomers++;
                    }
                }
            }

            // Calculate rates and additional statistics for each session
            foreach ($allProcessedLiveSessions as &$session) {
                $session['success_rate'] = $session['total_orders'] > 0 ? round(($session['successful_orders'] / $session['total_orders']) * 100, 2) : 0;
                $session['cancellation_rate'] = $session['total_orders'] > 0 ? round(($session['canceled_orders'] / $session['total_orders']) * 100, 2) : 0;
                $session['delivering_rate'] = $session['total_orders'] > 0 ? round(($session['delivering_orders'] / $session['total_orders']) * 100, 2) : 0;
                $session['average_order_value'] = $session['successful_orders'] > 0 ? round($session['revenue'] / $session['successful_orders'], 2) : 0;

                if (isset($session['products']) && !empty($session['products'])) {
                    uasort($session['products'], function ($a, $b) {
                        return $b['revenue'] <=> $a['revenue'];
                    });
                    $session['top_products'] = array_slice($session['products'], 0, 5, true);
                }

                if (isset($session['customers']) && !empty($session['customers'])) {
                    uasort($session['customers'], function ($a, $b) {
                        return $b['total_spent'] <=> $a['total_spent'];
                    });
                }

                $session['total_customers'] = count($session['customers'] ?? []);
                $session['repeat_customers'] = count(array_filter($session['customers'] ?? [], function ($customer) {
                    return $customer['orders'] > 1;
                }));
            }
            unset($session);

            usort($allProcessedLiveSessions, function ($a, $b) {
                $dateComparison = strcmp($b['session_date'], $a['session_date']);
                if ($dateComparison === 0) {
                    return $a['live_number'] <=> $b['live_number'];
                }
                return $dateComparison;
            });

            $overallStats = [
                'total_sessions' => count($allProcessedLiveSessions),
                'total_orders' => array_sum(array_column($allProcessedLiveSessions, 'total_orders')),
                'total_revenue' => array_sum(array_column($allProcessedLiveSessions, 'revenue')),
                'total_successful_orders' => array_sum(array_column($allProcessedLiveSessions, 'successful_orders')),
                'total_canceled_orders' => array_sum(array_column($allProcessedLiveSessions, 'canceled_orders')),
                'total_delivering_orders' => array_sum(array_column($allProcessedLiveSessions, 'delivering_orders')),
                'average_success_rate' => 0,
                'average_cancellation_rate' => 0,
                'average_delivering_rate' => 0,
            ];

            if ($overallStats['total_orders'] > 0) {
                $overallStats['average_success_rate'] = round(($overallStats['total_successful_orders'] / $overallStats['total_orders']) * 100, 2);
                $overallStats['average_cancellation_rate'] = round(($overallStats['total_canceled_orders'] / $overallStats['total_orders']) * 100, 2);
                $overallStats['average_delivering_rate'] = round(($overallStats['total_delivering_orders'] / $overallStats['total_orders']) * 100, 2);
            }

            return [
                'result' => $result,
                'totalSessions' => $totalSessions,
                'totalRevenueAll' => $totalRevenueAll,
                'totalOrdersAll' => $totalOrdersAll,
                'totalSuccessfulOrdersAll' => $totalSuccessfulOrdersAll,
                'totalCanceledOrdersAll' => $totalCanceledOrdersAll,
                'totalDeliveringOrdersAll' => $totalDeliveringOrdersAll,
                'overallSuccessRate' => $overallSuccessRate,
                'overallCancellationRate' => $overallCancellationRate,
                'overallDeliveringRate' => $overallDeliveringRate,
                'dailyChartData' => $dailyChartDataOutput,
                'monthlyChartData' => $monthlyChartDataForSessionPeriod,
                'provinceDataForChart' => $provinceDataForChartOutput,
                'provinceRevenueDataForChart' => $provinceRevenueDataForChartOutput,
                'topProducts' => $topProductsOutput,
                'totalUniqueCustomers' => $totalUniqueCustomersOutput,
                'totalNewCustomersAll' => $finalOverallNewCustomers,
                'totalReturningCustomersAll' => $finalOverallReturningCustomers,
                'sessions' => array_values($allProcessedLiveSessions),
                'overall_stats' => $overallStats,
            ];
        };

        // Fetch pancake order statuses
        $pancakeStatusMap = DB::table('pancake_order_statuses')
            ->pluck('api_name', 'status_code')
            ->map(function ($apiName) {
                return strtolower($apiName);
            })
            ->all();
        Log::debug("Pancake Status Map Loaded: ", $pancakeStatusMap);

        // Process data for the overall selected period
        $overallViewData = $processReportData($startDate, $endDate, $pancakeStatusMap);
        $overallViewData['startDate'] = $startDate;
        $overallViewData['endDate'] = $endDate;

        // Data for monthly tabs
        $monthlyTabsData = [];
        $period = CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->endOfMonth());

        foreach ($period as $dateInMonth) {
            $monthStartDate = $dateInMonth->copy()->startOfMonth();
            $monthEndDate = $dateInMonth->copy()->endOfMonth();

            if ($monthStartDate < $startDate) $monthStartDate = $startDate->copy();
            if ($monthEndDate > $endDate) $monthEndDate = $endDate->copy();

            if ($monthStartDate > $monthEndDate) continue;

            $monthKey = $dateInMonth->format('Y-m');
            Log::info("ReportController@liveSessionsPage: Preparing data for monthly tab: {$monthKey}");
            $monthlyReportData = $processReportData($monthStartDate, $monthEndDate, $pancakeStatusMap);
            $monthlyReportData['period_start_date'] = $monthStartDate;
            $monthlyReportData['period_end_date'] = $monthEndDate;
            $monthlyTabsData[$monthKey] = $monthlyReportData;
        }

        $finalViewData = $overallViewData;
        $finalViewData['monthlyTabsData'] = $monthlyTabsData;

        Log::info("ReportController@liveSessionsPage: Successfully processed all data including monthly tabs. Total overall sessions: {$overallViewData['totalSessions']}");

        return view('reports.live_sessions', $finalViewData);
    }

    /**
     * Tạo dữ liệu chi tiết đơn hàng theo ngày - This might need review based on new daily chart logic
     * The new dailyChartData in liveSessionsPage might supersede this if it's just for chart.
     * If this is for a drill-down table, it needs to be consistent.
     */
    protected function generateDailyOrderData($startDate, $endDate)
    {
        // Truy vấn số liệu đơn hàng theo ngày từ database
        $dailyStats = DB::table('orders')
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as total_orders')
            ->selectRaw('SUM(CASE WHEN status = "huy" OR status = "da_huy" OR pancake_status = "canceled" THEN 1 ELSE 0 END) as canceled_orders')
            ->selectRaw('SUM(CASE WHEN status != "huy" AND status != "da_huy" AND (pancake_status != "canceled" OR pancake_status IS NULL) THEN total_value ELSE 0 END) as revenue')
            ->whereNotNull('notes')
            ->where('notes', 'LIKE', '%LIVE%')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get();

        $result = [];

        // Convert to array with calculated fields
        foreach ($dailyStats as $stat) {
            $successful = $stat->total_orders - $stat->canceled_orders;
            $result[] = [
                'date' => $stat->order_date,
                'displayDate' => Carbon::parse($stat->order_date)->format('d/m'),
                'orders' => $stat->total_orders,
                'successful' => $successful,
                'canceled' => $stat->canceled_orders,
                'revenue' => $stat->revenue
            ];
        }

        return $result;
    }

    /**
     * Tạo dữ liệu mẫu theo ngày khi không có đủ dữ liệu thật
     */
    protected function createSampleDailyData($startDate, $endDate)
    {
        $dailyData = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            // Tạo dữ liệu ngẫu nhiên cho mỗi ngày với xu hướng tăng giảm để biểu đồ có độ lên xuống
            $trend = sin($currentDate->format('d') / 5) * 10 + 15; // Tạo đường cong sin
            $orders = max(1, round($trend + (mt_rand(-5, 5))));

            $successRate = mt_rand(65, 90) / 100;
            $successful = round($orders * $successRate);
            $canceled = $orders - $successful;

            // Tạo doanh thu giả lập với giá trị trung bình 300,000 - 800,000 VND mỗi đơn thành công
            $avgOrderValue = mt_rand(300000, 800000);
            $revenue = $successful * $avgOrderValue;

            $dailyData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'displayDate' => $currentDate->format('d/m'),
                'orders' => $orders,
                'successful' => $successful,
                'canceled' => $canceled,
                'revenue' => $revenue
            ];

            $currentDate->addDay();
        }

        return $dailyData;
    }

    /**
     * Tạo dữ liệu mẫu cho trường hợp không có dữ liệu thật
     */
    protected function createSampleLiveSessionsData($startDate, $endDate)
    {
        // Tạo dữ liệu mẫu cho 3 tháng gần nhất
        $now = Carbon::now();
        $result = [];

        // Tạo dữ liệu mẫu cho 10 phiên live gần nhất (2 phiên mỗi tháng trong 5 tháng gần nhất)
        for ($i = 0; $i < 5; $i++) {
            $month = $now->copy()->subMonths($i);

            // Phiên 1 của tháng
            $liveSession1 = [
                'id' => "LIVE1_" . $month->format('dmy'),
                'name' => "LIVE 1 (" . $month->format('d/m/Y') . ")",
                'live_number' => 1,
                'session_date' => $month->format('Y-m-d'),
                'year' => $month->year,
                'month' => $month->month,
                'day' => $month->day,
                'total_orders' => rand(50, 150),
                'successful_orders' => 0,
                'canceled_orders' => 0,
                'revenue' => 0,
                'total_customers' => rand(30, 100),
                'orders' => [],
                'customers' => [],
                'success_rate' => 0,
                'cancellation_rate' => 0
            ];

            // Tính số đơn thành công và đơn hủy
            $liveSession1['successful_orders'] = round($liveSession1['total_orders'] * rand(70, 90) / 100);
            $liveSession1['canceled_orders'] = $liveSession1['total_orders'] - $liveSession1['successful_orders'];
            $liveSession1['revenue'] = $liveSession1['successful_orders'] * rand(300000, 800000);
            $liveSession1['success_rate'] = ($liveSession1['successful_orders'] / $liveSession1['total_orders']) * 100;
            $liveSession1['cancellation_rate'] = ($liveSession1['canceled_orders'] / $liveSession1['total_orders']) * 100;

            $result[] = $liveSession1;

            // Phiên 2 của tháng
            $day2 = $month->copy()->addDays(15);
            $liveSession2 = [
                'id' => "LIVE2_" . $day2->format('dmy'),
                'name' => "LIVE 2 (" . $day2->format('d/m/Y') . ")",
                'live_number' => 2,
                'session_date' => $day2->format('Y-m-d'),
                'year' => $day2->year,
                'month' => $day2->month,
                'day' => $day2->day,
                'total_orders' => rand(50, 150),
                'successful_orders' => 0,
                'canceled_orders' => 0,
                'revenue' => 0,
                'total_customers' => rand(30, 100),
                'orders' => [],
                'customers' => [],
                'success_rate' => 0,
                'cancellation_rate' => 0
            ];

            // Tính số đơn thành công và đơn hủy
            $liveSession2['successful_orders'] = round($liveSession2['total_orders'] * rand(70, 90) / 100);
            $liveSession2['canceled_orders'] = $liveSession2['total_orders'] - $liveSession2['successful_orders'];
            $liveSession2['revenue'] = $liveSession2['successful_orders'] * rand(300000, 800000);
            $liveSession2['success_rate'] = ($liveSession2['successful_orders'] / $liveSession2['total_orders']) * 100;
            $liveSession2['cancellation_rate'] = ($liveSession2['canceled_orders'] / $liveSession2['total_orders']) * 100;

            $result[] = $liveSession2;
        }

        // Sắp xếp theo thời gian giảm dần
        usort($result, function($a, $b) {
            return strtotime($b['session_date']) - strtotime($a['session_date']);
        });

        // Tính toán số liệu tổng hợp
        $totalSessions = count($result);
        $totalRevenue = array_sum(array_column($result, 'revenue'));
        $totalOrders = array_sum(array_column($result, 'total_orders'));
        $successfulOrders = array_sum(array_column($result, 'successful_orders'));
        $canceledOrders = array_sum(array_column($result, 'canceled_orders'));

        // Tính tỷ lệ
        $successRate = $totalOrders > 0 ? ($successfulOrders / $totalOrders) * 100 : 0;
        $cancellationRate = $totalOrders > 0 ? ($canceledOrders / $totalOrders) * 100 : 0;

        // Tạo tổng số khách hàng
        $totalCustomers = array_sum(array_column($result, 'total_customers'));

        // Dữ liệu theo tháng cho biểu đồ
        $monthlyData = [];
        foreach ($result as $session) {
            $month = Carbon::parse($session['session_date'])->format('m/Y');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'revenue' => 0,
                    'orders' => 0,
                    'successful_orders' => 0,
                    'canceled_orders' => 0,
                    'sessions' => 0,
                    'success_rate' => 0,
                    'cancellation_rate' => 0
                ];
            }

            $monthlyData[$month]['revenue'] += $session['revenue'];
            $monthlyData[$month]['orders'] += $session['total_orders'];
            $monthlyData[$month]['successful_orders'] += $session['successful_orders'];
            $monthlyData[$month]['canceled_orders'] += $session['canceled_orders'];
            $monthlyData[$month]['sessions']++;
        }

        // Tính tỷ lệ theo tháng
        foreach ($monthlyData as &$monthData) {
            $totalMonthOrders = $monthData['orders'];
            if ($totalMonthOrders > 0) {
                $monthData['success_rate'] = ($monthData['successful_orders'] / $totalMonthOrders) * 100;
                $monthData['cancellation_rate'] = ($monthData['canceled_orders'] / $totalMonthOrders) * 100;
            }
        }

        // Tạo dữ liệu mẫu chi tiết theo ngày
        $dailyData = $this->createSampleDailyData($startDate, $endDate);

        // Trả về dữ liệu mẫu
        $viewData = compact(
            'result',
            'totalSessions',
            'totalRevenue',
            'totalOrders',
            'successfulOrders',
            'canceledOrders',
            'successRate',
            'cancellationRate',
            'totalCustomers',
            'monthlyData',
            'dailyData',
            'startDate',
            'endDate'
        );

        return view('reports.live_sessions', $viewData);
    }

    /**
     * API tính lại doanh thu phiên live (gọi từ nút Tính Doanh Thu)
     */
    public function recalculateLiveRevenue(Request $request)
    {
        try {
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))
                : Carbon::now()->startOfMonth();

            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))
                : Carbon::now();

            // Xóa cache hiện tại để buộc tính toán lại
            DB::table('live_session_stats')
                ->where('period_start', $startDate->format('Y-m-d'))
                ->where('period_end', $endDate->format('Y-m-d'))
                ->delete();

            // Chuyển hướng đến trang báo cáo với tham số calculate=true
            return redirect()->route('reports.live_sessions', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'calculate' => 'true'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tính lại doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị trang báo cáo tỷ lệ chốt đơn
     */
    public function conversionRatesPage()
    {
        $this->authorize('reports.conversion_rates');
        return view('reports.conversion_rates');
    }

    /**
     * Hiển thị trang báo cáo khách hàng mới
     */
    public function newCustomersPage()
    {
        $this->authorize('reports.customer_new');
        return view('reports.new_customers');
    }

    /**
     * Hiển thị trang báo cáo khách hàng cũ
     */
    public function returningCustomersPage()
    {
        $this->authorize('reports.customer_returning');
        return view('reports.returning_customers');
    }

    /**
     * Lấy dữ liệu tổng doanh thu
     */
    public function getTotalRevenue(Request $request)
    {
        $this->authorize('reports.total_revenue');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $totalRevenue = $this->reportService->getTotalRevenue($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue
            ]
        ]);
    }

    /**
     * Lấy dữ liệu doanh thu theo ngày
     */
    public function getDailyRevenue(Request $request)
    {
        $this->authorize('reports.detailed');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $revenueData = $this->reportService->getDailyRevenue($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $revenueData
        ]);
    }

    /**
     * Lấy báo cáo theo chiến dịch (bài post)
     */
    public function getCampaignReport(Request $request)
    {
        $this->authorize('reports.campaigns');

        $postId = $request->input('post_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getCampaignReport($postId, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy danh sách sản phẩm trong chiến dịch
     */
    public function getCampaignProducts(Request $request)
    {
        $this->authorize('reports.campaigns');

        $postId = $request->input('post_id');
        $products = $this->reportService->getCampaignProducts($postId);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Lấy báo cáo theo nhóm hàng hóa
     */
    public function getProductGroupReport(Request $request)
    {
        $this->authorize('reports.product_groups');

        $groupId = $request->input('group_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getProductGroupReport($groupId, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy báo cáo phiên live (trích xuất từ notes của đơn hàng)
     */
    public function getLiveSessionReport(Request $request)
    {
        $this->authorize('reports.live_sessions');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        $forceRefresh = $request->input('force_refresh', false);

        // For live sessions, allow anyone with the 'reports.live_sessions' permission to see all data
        // Don't use user IDs based on permission for filtering
        $userIds = null;

        // Tạo cache key dựa trên thông tin filter
        $cacheKey = 'live_sessions_' . md5(($startDate ? $startDate->format('Y-m-d') : '') . ($endDate ? $endDate->format('Y-m-d') : '') . json_encode($userIds));

        // Kiểm tra cache trước khi truy vấn database (trừ khi yêu cầu refresh)
        if (!$forceRefresh && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $result = \Illuminate\Support\Facades\Cache::get($cacheKey);

            return response()->json([
                'success' => true,
                'data' => $result,
                'from_cache' => true
            ]);
        }

        // Query từ đơn hàng để lấy thông tin phiên live từ ghi chú
        $query = Order::query();

        // Lọc theo khoảng thời gian
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate->endOfDay());
        }

        // Remove user permission filter - allow all users to see all live session data
        // if ($userIds) {
        //     $query->whereIn('user_id', $userIds);
        // }

        // Chỉ lấy các đơn hàng có ghi chú
        $query->whereNotNull('notes');

        // Pattern để trích xuất thông tin phiên live từ notes
        // LIVE1 19/5 hoặc LIVE 3 20/5 và các biến thể
        $query->where(function($q) {
            // Sử dụng LIKE đơn giản trước để lọc nhanh
            $q->where('notes', 'LIKE', '%LIVE%/%');
        });

        // Lấy danh sách đơn hàng có ghi chú
        $orders = $query->get();

        // Log để debug nếu không tìm thấy dữ liệu
        if ($orders->isEmpty()) {
            \Illuminate\Support\Facades\Log::info('Không tìm thấy đơn hàng nào có phiên live', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Thử query đơn giản hơn để xem có kết quả không
            $checkOrders = Order::whereNotNull('notes')
                ->where('notes', 'LIKE', '%LIVE%')
                ->limit(5)
                ->get(['id', 'notes']);

            \Illuminate\Support\Facades\Log::info('Mẫu notes có chứa LIVE:', [
                'sample_orders' => $checkOrders->toArray()
            ]);
        }

        // Nhóm các đơn hàng theo phiên live
        $liveSessions = [];
        // Pattern chính xác hơn để phân tích cụ thể từng phần
        // Hỗ trợ cả hai định dạng: "LIVE3 20/5" và "LIVE 3 20/5"
        // Và các biến thể của chúng
        $patterns = [
            // Pattern 1: LIVE<số> <ngày>/<tháng>
            '/LIVE(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
            // Pattern 2: LIVE <số> <ngày>/<tháng>
            '/LIVE[\\s]+(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
            // Pattern 3: Bắt thêm các biến thể
            '/LIVE[\\s]*(\d+)[\\s]*:?[\\s]*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
        ];

        foreach ($orders as $order) {
            $matched = false;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $order->notes, $matches)) {
                    $matched = true;
                    break;
                }
            }

            // Tiếp tục với đơn hàng khớp pattern
            if ($matched) {
                $liveNumber = (int)$matches[1]; // Số thứ tự của live (1, 2, 3...)
                $day = (int)$matches[2];        // Ngày
                $month = (int)$matches[3];      // Tháng

                // Xử lý năm nếu có, nếu không thì lấy năm hiện tại
                $year = isset($matches[4]) ? (int)$matches[4] : date('Y');

                // Xử lý năm 2 chữ số thành 4 chữ số
                if ($year < 100) {
                    $year = 2000 + $year;
                }

                $liveDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                $liveSessionId = "LIVE{$liveNumber}_{$day}{$month}{$year}";

                if (!isset($liveSessions[$liveSessionId])) {
                    $liveSessions[$liveSessionId] = [
                        'id' => $liveSessionId,
                        'name' => "LIVE {$liveNumber} ({$day}/{$month}/{$year})",
                        'live_number' => $liveNumber,
                        'session_date' => $liveDate,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'total_orders' => 0,
                        'successful_orders' => 0,
                        'canceled_orders' => 0,
                        'delivering_orders' => 0, // New count for delivering orders
                        'revenue' => 0,
                        'total_customers' => 0,
                        'orders' => [],
                        'customers' => []
                    ];
                }

                $liveSessions[$liveSessionId]['total_orders']++;

                // Đếm đơn thành công và đơn hủy theo trạng thái Pancake
                if ($order->pancake_status == 'cancelled' || $order->status == 'huy' || $order->status == 'da_huy') {
                    $liveSessions[$liveSessionId]['canceled_orders']++;
                }
                else if ($order->pancake_status == 'completed' || $order->pancake_status == 'delivered' ||
                         $order->status == 'thanh_cong' || $order->status == 'hoan_thanh' ||
                         $order->status == 'da_giao' || $order->status == 'da_nhan' ||
                         $order->status == 'da_thu_tien') {
                    $liveSessions[$liveSessionId]['successful_orders']++;
                    // Chỉ tính doanh thu cho đơn thành công
                    $liveSessions[$liveSessionId]['revenue'] += $order->total_value;
                }

                $liveSessions[$liveSessionId]['orders'][] = $order->id;

                if ($order->customer_id && !in_array($order->customer_id, $liveSessions[$liveSessionId]['customers'])) {
                    $liveSessions[$liveSessionId]['customers'][] = $order->customer_id;
                    $liveSessions[$liveSessionId]['total_customers']++;
                }
            }

            // Log notes không khớp với pattern nào để debug
            if (!$matched && strpos($order->notes, 'LIVE') !== false) {
                \Illuminate\Support\Facades\Log::info('Notes chứa LIVE nhưng không khớp pattern', [
                    'order_id' => $order->id,
                    'notes' => $order->notes
                ]);
            }
        }

        // Chuyển đổi mảng kết hợp thành mảng tuần tự để trả về
        $result = array_values($liveSessions);

        // Thêm tỷ lệ chốt đơn và hủy đơn
        foreach ($result as &$session) {
            // Tính tỷ lệ chốt đơn
            $session['conversion_rate'] = $session['total_orders'] > 0
                ? ($session['successful_orders'] / $session['total_orders']) * 100
                : 0;

            // Tính tỷ lệ hủy đơn
            $session['cancellation_rate'] = $session['total_orders'] > 0
                ? ($session['canceled_orders'] / $session['total_orders']) * 100
                : 0;
            $session['delivering_rate'] = $session['total_orders'] > 0
                ? ($session['delivering_orders'] / $session['total_orders']) * 100
                : 0;
        }

        // Sắp xếp theo ngày và số phiên live
        usort($result, function($a, $b) {
            // Sort by year (descending)
            if ($a['year'] != $b['year']) {
                return $b['year'] - $a['year'];
            }

            // Sort by month (descending)
            if ($a['month'] != $b['month']) {
                return $b['month'] - $a['month'];
            }

            // Sort by day (descending)
            if ($a['day'] != $b['day']) {
                return $b['day'] - $a['day'];
            }

            // Sort by live session number (ascending)
            return $a['live_number'] - $b['live_number'];
        });

        // Thêm cache để giảm tải truy vấn
        $cacheKey = 'live_sessions_' . md5($startDate . $endDate . json_encode($userIds));
        \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 3600); // Cache trong 1 giờ

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Lấy chi tiết phiên live
     */
    public function getLiveSessionDetail(Request $request)
    {
        $this->authorize('reports.live_sessions');

        $liveSessionId = $request->input('session_id');

        if (!$liveSessionId) {
            return response()->json([
                'success' => false,
                'message' => 'ID phiên live là bắt buộc'
            ], 400);
        }

        // Phân tích ID phiên live để lấy thông tin
        if (preg_match('/LIVE(\d+)_(\d{1,2})(\d{1,2})(\d{4})/', $liveSessionId, $matches)) {
            $liveNumber = (int)$matches[1]; // Số phiên live
            $day = (int)$matches[2];        // Ngày
            $month = (int)$matches[3];      // Tháng
            $year = (int)$matches[4];       // Năm

            // Xử lý năm 2 chữ số nếu cần (nhưng ID đã có định dạng năm 4 chữ số)
            if ($year < 100) {
                $year = 2000 + $year;
            }

            // Tạo ngày cho phiên live
            $liveDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');

                        // Tạo pattern để tìm đơn hàng thuộc phiên live này
            // Chấp nhận cả 2 định dạng: LIVE1 19/5 và LIVE 1 19/5
            // Cũng chấp nhận cả năm 2 chữ số và 4 chữ số: 19/5/23 hoặc 19/5/2023
            $pattern = "/LIVE\\s*{$liveNumber}\\s+{$day}\\/{$month}(?:\\/{$year}|\\/". ($year-2000) .")?/i";

            // Pattern mở rộng dùng LIKE để tăng khả năng bắt được kết quả
            $likePattern = "%LIVE%{$liveNumber}%{$day}/{$month}%";

            // Kiểm tra cache trước
            $cacheKey = 'live_session_detail_' . $liveSessionId;
            $forceRefresh = $request->input('force_refresh', false);

            if (!$forceRefresh && $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey)) {
            return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'from_cache' => true
                ]);
        }

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = null; // Allow all users to see all live session data

            // Query lấy các đơn hàng thuộc phiên live này - đơn giản hóa tìm kiếm
            $query = Order::query()
                ->whereNotNull('notes')
                ->where('notes', 'LIKE', "%LIVE%{$liveNumber}%{$day}/{$month}%");

            // Remove permission filter
            // if ($userIds) {
            //     $query->whereIn('user_id', $userIds);
            // }

            // Debug query
            \Illuminate\Support\Facades\Log::info('Live session detail query', [
                'pattern' => "%LIVE%{$liveNumber}%{$day}/{$month}%",
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Lấy đầy đủ thông tin đơn hàng và các mối quan hệ
            $orders = $query->with(['customer', 'items.product'])->get();

            // Check if we got any orders
            if ($orders->isEmpty()) {
                \Illuminate\Support\Facades\Log::warning('No orders found for live session', [
                    'session_id' => $liveSessionId,
                    'pattern' => "%LIVE%{$liveNumber}%{$day}/{$month}%"
                ]);

                // Try a broader search if no results
                $query = Order::query()
                    ->whereNotNull('notes')
                    ->where(function($q) use ($liveNumber, $day, $month) {
                        $q->where('notes', 'LIKE', "%LIVE%{$liveNumber}%{$day}/{$month}%")
                          ->orWhere('notes', 'LIKE', "%LIVE {$liveNumber}%{$day}/{$month}%")
                          ->orWhere('notes', 'LIKE', "%LIVE{$liveNumber}%{$day}/{$month}%");
                    });

                // Remove permission filter
                // if ($userIds) {
                //     $query->whereIn('user_id', $userIds);
                // }

                $orders = $query->with(['customer', 'items.product'])->get();

                \Illuminate\Support\Facades\Log::info('Broader search found orders', [
                    'count' => $orders->count()
                ]);
            }

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng nào thuộc phiên live này. ID: ' . $liveSessionId . ', Phiên: LIVE ' . $liveNumber . ' ngày ' . $day . '/' . $month . '/' . $year
                ], 404);
            }

            // Đếm số đơn theo trạng thái
            $successfulOrders = 0;
            $canceledOrders = 0;
            $pendingOrders = 0;
            $revenue = 0;
            $uniqueCustomers = [];

            // Tạo array để theo dõi đơn hàng theo trạng thái
            $ordersByStatus = [];

            foreach ($orders as $order) {
                // Đếm đơn theo trạng thái
                if (!isset($ordersByStatus[$order->status])) {
                    $ordersByStatus[$order->status] = 0;
                }
                $ordersByStatus[$order->status]++;

                // Phân loại và tính toán thông tin tổng hợp dựa trên trạng thái Pancake
                if ($order->pancake_status == 'cancelled' || $order->status == 'huy' || $order->status == 'da_huy') {
                    $canceledOrders++;
                }
                else if ($order->pancake_status == 'completed' || $order->pancake_status == 'delivered' ||
                         $order->status == 'thanh_cong' || $order->status == 'hoan_thanh' ||
                         $order->status == 'da_giao' || $order->status == 'da_nhan' ||
                         $order->status == 'da_thu_tien') {
                    $successfulOrders++;
                    // Chỉ tính doanh thu cho đơn thành công
                    $revenue += $order->total_value;
                }
                else {
                    $pendingOrders++; // Đơn đang xử lý, không thuộc 2 trạng thái trên
                }

                // Đếm khách hàng duy nhất
                if ($order->customer_id && !in_array($order->customer_id, $uniqueCustomers)) {
                    $uniqueCustomers[] = $order->customer_id;
                }
            }

            // Tính tỷ lệ chốt đơn và hủy đơn
            $totalOrders = $orders->count();
            $conversionRate = $totalOrders > 0 ? ($successfulOrders / $totalOrders * 100) : 0;
            $cancellationRate = $totalOrders > 0 ? ($canceledOrders / $totalOrders * 100) : 0;

            // Thông tin phiên live
            $liveSession = [
                'id' => $liveSessionId,
                'name' => "LIVE {$liveNumber} ({$day}/{$month}/{$year})",
                'live_number' => $liveNumber,
                'session_date' => $liveDate,
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'pending_orders' => $pendingOrders,
                'canceled_orders' => $canceledOrders,
                'delivering_orders' => $pendingOrders, // New daily count
                'revenue' => $revenue,
                'total_customers' => count($uniqueCustomers),
                'notes' => "Phiên live số {$liveNumber} ngày {$day}/{$month}/{$year}",
                'conversion_rate' => $conversionRate,
                'cancellation_rate' => $cancellationRate,
                'delivering_rate' => $pendingOrders > 0 ? ($pendingOrders / $totalOrders * 100) : 0,
                'orders_by_status' => $ordersByStatus
            ];

            // Thống kê sản phẩm
            $productsData = [];
            $productCustomers = []; // Track customers per product

            foreach ($orders as $order) {
                // Chỉ tính sản phẩm từ đơn hàng thành công
                if ($order->status == 'huy' || $order->status == 'da_huy') {
                    continue;
                }

                foreach ($order->items as $item) {
                    $productId = $item->product_id;

                    if (!isset($productsData[$productId])) {
                        $productsData[$productId] = [
                            'id' => $productId,
                            'name' => $item->product->name ?? 'Sản phẩm không xác định',
                            'sku' => $item->product->sku ?? '',
                            'total_quantity' => 0,
                            'total_revenue' => 0,
                            'customers' => [], // Array to track unique customer IDs
                            'customer_count' => 0 // Count of unique customers who bought this product
                        ];
                    }

                    $productsData[$productId]['total_quantity'] += $item->quantity;
                    $productsData[$productId]['total_revenue'] += $item->price * $item->quantity;

                    // Track unique customers per product
                    if ($order->customer_id && !in_array($order->customer_id, $productsData[$productId]['customers'])) {
                        $productsData[$productId]['customers'][] = $order->customer_id;
                        $productsData[$productId]['customer_count']++;
                    }
                }
            }

            // Chuyển đổi mảng kết hợp thành mảng tuần tự
            $products = array_values($productsData);

            // Cleanup - remove customers array (we only need the count)
            foreach ($products as &$product) {
                unset($product['customers']);
            }

            // Sắp xếp theo doanh thu giảm dần
            usort($products, function($a, $b) {
                return $b['total_revenue'] - $a['total_revenue'];
            });

            $responseData = [
                'session' => $liveSession,
                'orders' => $orders,
                'products' => $products
            ];

            // Lưu kết quả vào cache
            \Illuminate\Support\Facades\Cache::put($cacheKey, $responseData, 3600); // Cache trong 1 giờ

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không thể phân tích ID phiên live'
        ], 400);
    }

    /**
     * Lấy báo cáo đơn hàng của khách hàng (mới/cũ)
     */
    public function getCustomerOrderReport(Request $request)
    {
        $isFirstOrder = $request->input('is_first_order');

        if ($isFirstOrder) {
            $this->authorize('reports.customer_new');
        } else {
            $this->authorize('reports.customer_returning');
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getCustomerOrderReport($isFirstOrder, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy báo cáo tỷ lệ chốt đơn
     */
    public function getConversionReport(Request $request)
    {
        $this->authorize('reports.conversion_rates');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->getConversionReport($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Lấy báo cáo chi tiết (nhiều loại dữ liệu)
     */
    public function getDetailReport(Request $request)
    {
        $this->authorize('reports.detailed');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->getDetailReport($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Lấy báo cáo hàng ngày
     */
    public function getDailyReport()
    {
        $this->authorize('reports.detailed');

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->generateDailyReport($userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Hiển thị trang báo cáo thanh toán
     */
    public function paymentsPage()
    {
        $this->authorize('reports.view');
        return view('reports.payments');
    }

    /**
     * API lấy báo cáo thanh toán
     */
    public function getPaymentReport(Request $request)
    {
        $this->authorize('reports.view');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        $paymentMethod = $request->input('payment_method');

        // Tạo báo cáo mới nếu yêu cầu
        if ($request->input('regenerate') === 'true') {
            if ($startDate && $endDate) {
                $this->reportService->regeneratePaymentReports($startDate, $endDate);
            } else {
                // Mặc định tạo báo cáo cho 30 ngày gần nhất
                $endDate = Carbon::now();
                $startDate = Carbon::now()->subDays(30);
                $this->reportService->regeneratePaymentReports($startDate, $endDate);
            }
        }

        $reports = $this->reportService->getPaymentReport($paymentMethod, $startDate, $endDate);
        $overview = $this->reportService->getPaymentReportOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'reports' => $reports,
                'overview' => $overview
            ]
        ]);
    }

    /**
     * API tạo mới báo cáo thanh toán cho một ngày cụ thể
     */
    public function generatePaymentReport(Request $request)
    {
        $this->authorize('reports.view');

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $reports = $this->reportService->generatePaymentReportForDate($date);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo báo cáo thanh toán cho ngày ' . $date->format('d/m/Y'),
            'data' => $reports
        ]);
    }

    /**
     * Xác định danh sách user IDs mà người dùng hiện tại có quyền xem báo cáo
     * Dựa trên các quyền reports.view_all, reports.view_team, reports.view_own
     */
    protected function getUserIdsBasedOnPermission()
    {
        $user = Auth::user();

        // Nếu user có quyền view_all, không cần lọc theo user_id
        if (Gate::check('reports.view_all', $user)) {
            return null; // Null means no filtering
        }

        // Nếu user có quyền view_team và là manager, lấy ids của staff trong team
        if (Gate::check('reports.view_team', $user) && $user->manages_team_id) {
            $teamUsers = \App\Models\User::where('team_id', $user->manages_team_id)->pluck('id')->toArray();
            return array_merge([$user->id], $teamUsers);
        }

        // Mặc định chỉ xem dữ liệu của chính mình
        return [$user->id];
    }

    /**
     * Display the order report page
     *
     * @return \Illuminate\View\View
     */
    public function orderReportIndex()
    {
        // Get all possible statuses for filtering
        $statuses = \App\Models\Order::getAllStatuses();

        // Get list of users (sales staff) for filtering
        $users = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'staff');
        })->orderBy('name')->get();

        // Get pancake pages for filtering
        $pancakePages = \App\Models\PancakePage::orderBy('name')->get();

        return view('reports.orders', compact('statuses', 'users', 'pancakePages'));
    }

    /**
     * Get order report data based on filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderReportData(Request $request)
    {
        try {
            // Parse filters
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
            $status = $request->input('status');
            $userId = $request->input('user_id');
            $pancakeStatus = $request->input('pancake_status');
            $pageId = $request->input('page_id');

            // Base query
            $query = \App\Models\Order::with(['user', 'items'])
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply filters
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($pancakeStatus) {
                if ($pancakeStatus === 'pushed') {
                    $query->whereNotNull('pancake_order_id');
                } elseif ($pancakeStatus === 'not_pushed') {
                    $query->whereNull('pancake_order_id');
                }
            }

            if ($pageId) {
                $query->where('pancake_page_id', $pageId);
            }

            // Get data
            $orders = $query->get();

            // Calculate totals and stats
            $totalOrders = $orders->count();
            $totalValue = $orders->sum('total_value');
            $totalShippingFee = $orders->sum('shipping_fee');

            // Calculate totals by status
            $totalsByStatus = [];
            foreach (\App\Models\Order::getAllStatuses() as $statusKey => $statusName) {
                $filteredOrders = $orders->where('status', $statusKey);
                $totalsByStatus[$statusKey] = [
                    'count' => $filteredOrders->count(),
                    'value' => $filteredOrders->sum('total_value'),
                    'name' => $statusName
                ];
            }

            // Calculate daily totals for chart
            $dailyData = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $dayOrders = $orders->filter(function ($order) use ($currentDate) {
                    return $order->created_at->format('Y-m-d') === $currentDate->format('Y-m-d');
                });

                $dailyData[] = [
                    'date' => $dateString,
                    'count' => $dayOrders->count(),
                    'value' => $dayOrders->sum('total_value')
                ];

                $currentDate->addDay();
            }

            // Sales by staff
            $salesByUser = [];
            if ($totalOrders > 0) {
                $userTotals = $orders->groupBy('user_id');
                foreach ($userTotals as $userId => $userOrders) {
                    $user = \App\Models\User::find($userId);
                    $salesByUser[] = [
                        'user_id' => $userId,
                        'name' => $user ? $user->name : 'Không xác định',
                        'count' => $userOrders->count(),
                        'value' => $userOrders->sum('total_value'),
                        'percentage' => round($userOrders->count() / $totalOrders * 100, 2)
                    ];
                }
            }

            // Sort by value descending
            usort($salesByUser, function($a, $b) {
                return $b['value'] <=> $a['value'];
            });

            // Return data
            return response()->json([
                'success' => true,
                'data' => [
                    'totalOrders' => $totalOrders,
                    'totalValue' => $totalValue,
                    'totalShippingFee' => $totalShippingFee,
                    'totalsByStatus' => $totalsByStatus,
                    'dailyData' => $dailyData,
                    'salesByUser' => $salesByUser
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating order report data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export live session data to CSV
     */
    public function exportLiveSessionReport(Request $request)
    {
        $this->authorize('reports.live_sessions');

        $sessionId = $request->input('session_id');

        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'ID phiên live là bắt buộc'
            ], 400);
        }

        // Tìm live session từ cache
        $cacheKey = 'live_session_detail_' . $sessionId;
        $sessionData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // Nếu không có trong cache, lấy lại dữ liệu từ DB
        if (!$sessionData) {
            // Mô phỏng request đến getLiveSessionDetail
            $detailRequest = new Request();
            $detailRequest->merge(['session_id' => $sessionId]);
            $response = $this->getLiveSessionDetail($detailRequest);

            if ($response->getStatusCode() != 200) {
                return $response;
            }

            $content = json_decode($response->getContent(), true);
            if (!$content['success']) {
                return $response;
            }

            $sessionData = $content['data'];
        }

        $session = $sessionData['session'];
        $orders = $sessionData['orders'];
        $products = $sessionData['products'];

        // Tạo tên file CSV
        $fileName = 'LiveSession_' . preg_replace('/[^a-zA-Z0-9]/', '_', $session['name']) . '.csv';

        // Tạo file CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($session, $orders, $products) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Tổng quan
            fputcsv($file, ['TỔNG QUAN PHIÊN LIVE']);
            fputcsv($file, ['Thông tin', 'Giá trị']);
            fputcsv($file, ['Tên phiên live', $session['name']]);
            fputcsv($file, ['Ngày diễn ra', $session['session_date']]);
            fputcsv($file, ['Tổng đơn hàng', $session['total_orders']]);
            fputcsv($file, ['Đơn thành công', $session['successful_orders']]);
            fputcsv($file, ['Đơn hủy', $session['canceled_orders']]);
            fputcsv($file, ['Tỷ lệ chốt đơn', number_format($session['conversion_rate'] ?? 0, 2) . '%']);
            fputcsv($file, ['Tỷ lệ hủy', number_format($session['cancellation_rate'] ?? 0, 2) . '%']);
            fputcsv($file, ['Tổng doanh thu', number_format($session['revenue'] ?? 0, 0, ',', '.') . ' VND']);
            fputcsv($file, ['Tổng số khách hàng', $session['total_customers'] ?? 0]);
            fputcsv($file, []);

            // Sản phẩm
            fputcsv($file, ['DANH SÁCH SẢN PHẨM']);
            fputcsv($file, ['Sản phẩm', 'Mã SP', 'Số lượng', 'Doanh thu (VND)', 'Tỷ lệ (%)']);

            $totalRevenue = collect($products)->sum('total_revenue');

            foreach ($products as $product) {
                fputcsv($file, [
                    $product['name'],
                    $product['sku'] ?? 'N/A',
                    $product['total_quantity'],
                    number_format($product['total_revenue'], 0, ',', '.'),
                    number_format(($totalRevenue > 0 ? $product['total_revenue'] / $totalRevenue * 100 : 0), 2) . '%'
                ]);
            }

            fputcsv($file, []);

            // Đơn hàng
            fputcsv($file, ['DANH SÁCH ĐƠN HÀNG']);
            fputcsv($file, ['ID đơn hàng', 'Khách hàng', 'Số điện thoại', 'Tổng tiền (VND)', 'Trạng thái', 'Ngày tạo', 'Sản phẩm']);

            foreach ($orders as $order) {
                $productsList = [];

                if (isset($order['items'])) {
                    foreach ($order['items'] as $item) {
                        $productName = isset($item['product']) ? $item['product']['name'] : 'Sản phẩm không xác định';
                        $productsList[] = "{$item['quantity']} x {$productName}";
                    }
                }

                fputcsv($file, [
                    $order['id'],
                    isset($order['customer']) ? ($order['customer']['name'] ?? 'N/A') : 'N/A',
                    isset($order['customer']) ? ($order['customer']['phone'] ?? 'N/A') : 'N/A',
                    number_format($order['total_amount'], 0, ',', '.'),
                    $this->formatOrderStatus($order['status']),
                    isset($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A',
                    implode(', ', $productsList)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Format order status for display
     */
    protected function formatOrderStatus($status)
    {
        $statusMap = [
            'moi' => 'Mới',
            'dang_xu_ly' => 'Đang xử lý',
            'da_giao' => 'Đã giao',
            'hoan_thanh' => 'Hoàn thành',
            'thanh_cong' => 'Thành công',
            'huy' => 'Hủy',
            'da_huy' => 'Đã hủy'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * API kiểm tra mẫu notes từ đơn hàng
     */
    public function checkNotesPatterns(Request $request)
    {
        $this->authorize('reports.live_sessions');

        // Lấy mẫu notes chứa "LIVE"
        $notes = Order::whereNotNull('notes')
            ->where('notes', 'LIKE', '%LIVE%')
            ->limit(10)
            ->get(['id', 'notes', 'created_at'])
            ->map(function($order) {
                // Kiểm tra các pattern khác nhau
                $patterns = [
                    'pattern1' => '/LIVE\s*(\d+)\s+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
                    'pattern2' => '/LIVE\s*(\d+)\s*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
                    'pattern3' => '/LIVE\s+(\d+)\s+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i'
                ];

                $matches = [];
                foreach ($patterns as $key => $pattern) {
                    $matches[$key] = preg_match($pattern, $order->notes, $m) ? $m : null;
                }

                return [
                    'id' => $order->id,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at,
                    'matches' => $matches
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notes,
            'message' => 'Đây là kết quả mẫu notes từ đơn hàng chứa từ khóa LIVE'
        ]);
    }

    /**
     * Trang debug cho phiên live
     */
    public function debugLiveSessions()
    {
        $this->authorize('reports.live_sessions');

        // Lấy một số đơn hàng mẫu với notes có LIVE
        $sampleOrders = Order::whereNotNull('notes')
            ->where('notes', 'LIKE', '%LIVE%')
            ->take(20)
            ->get(['id', 'notes', 'created_at']);

        // Kiểm tra các patterns
        $patterns = [
            'simple_like' => '%LIVE%/%',
            'pattern1' => '/LIVE(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
            'pattern2' => '/LIVE[\\s]+(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
            'pattern3' => '/LIVE[\\s]*(\d+)[\\s]*:?[\\s]*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
        ];

        $results = [];
        foreach ($sampleOrders as $order) {
            $orderResults = [
                'id' => $order->id,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'matches' => []
            ];

            foreach ($patterns as $name => $pattern) {
                if (strpos($name, 'simple_like') === 0) {
                    $orderResults['matches'][$name] = $this->testLikePattern($order->notes, $pattern);
                } else {
                    if (preg_match($pattern, $order->notes, $matches)) {
                        $orderResults['matches'][$name] = $matches;
                    } else {
                        $orderResults['matches'][$name] = null;
                    }
                }
            }

            $results[] = $orderResults;
        }

        return view('reports.debug.live_sessions', [
            'results' => $results,
            'patterns' => $patterns
        ]);
    }

    /**
     * Helper để test LIKE pattern
     */
    private function testLikePattern($notes, $pattern)
    {
        $pattern = str_replace('%', '', $pattern);
        return strpos($notes, $pattern) !== false;
    }

    /**
     * Get detailed order information for a specific day
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getDailyOrderDetails(Request $request)
    {
        try {
            $date = $request->input('detail_date');
            $dateObj = Carbon::parse($date);

            // Query orders from this specific date that contain "LIVE" in notes
            $orders = Order::whereDate('created_at', $dateObj)
                ->whereNotNull('notes')
                ->where('notes', 'LIKE', '%LIVE%')
                ->with(['customer', 'items.product', 'user'])
                ->get();

            $totalOrders = $orders->count();
            $successfulOrders = $orders->filter(function ($order) {
                return !($order->status == 'huy' || $order->status == 'da_huy' ||
                         $order->pancake_status == 'cancelled' || $order->pancake_status == 'canceled');
            })->count();

            $canceledOrders = $orders->filter(function ($order) {
                return ($order->status == 'huy' || $order->status == 'da_huy' ||
                        $order->pancake_status == 'cancelled' || $order->pancake_status == 'canceled');
            })->count();

            $totalRevenue = $orders->filter(function ($order) {
                return !($order->status == 'huy' || $order->status == 'da_huy' ||
                         $order->pancake_status == 'cancelled' || $order->pancake_status == 'canceled');
            })->sum('total_value');

            // Get all live sessions for this day from notes
            $liveSessions = [];
            $patterns = [
                '/LIVE(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
                '/LIVE[\\s]+(\d+)[\\s]+(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
                '/LIVE[\\s]*(\d+)[\\s]*:?[\\s]*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i',
                '/LIVE[\\s]*(\d+)/i',
            ];

            foreach ($orders as $order) {
                $matched = false;

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $order->notes, $matches)) {
                        $matched = true;

                        if (count($matches) >= 2) {
                            $liveNumber = $matches[1];
                            $sessionId = "LIVE{$liveNumber}";

                            if (!isset($liveSessions[$sessionId])) {
                                $liveSessions[$sessionId] = [
                                    'id' => $sessionId,
                                    'name' => "LIVE {$liveNumber}",
                                    'count' => 0,
                                    'orders' => []
                                ];
                            }

                            $liveSessions[$sessionId]['count']++;
                            $liveSessions[$sessionId]['orders'][] = $order->id;
                        }

                        break;
                    }
                }
            }

            // Format order data for display
            $formattedOrders = $orders->map(function($order) {
                $products = $order->items->map(function($item) {
                    return [
                        'name' => $item->product->name ?? 'Sản phẩm không xác định',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->quantity * $item->price
                    ];
                });

                return [
                    'id' => $order->id,
                    'customer_name' => $order->customer->name ?? 'Khách hàng không xác định',
                    'customer_phone' => $order->customer->phone ?? 'N/A',
                    'total_value' => $order->total_value,
                    'status' => $order->status,
                    'pancake_status' => $order->pancake_status,
                    'created_at' => $order->created_at->format('H:i:s d/m/Y'),
                    'sales_person' => $order->user->name ?? 'N/A',
                    'notes' => $order->notes,
                    'products' => $products
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $dateObj->format('d/m/Y'),
                    'total_orders' => $totalOrders,
                    'successful_orders' => $successfulOrders,
                    'canceled_orders' => $canceledOrders,
                    'total_revenue' => $totalRevenue,
                    'live_sessions' => array_values($liveSessions),
                    'orders' => $formattedOrders
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting daily order details: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy chi tiết đơn hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function overallRevenueSummaryPage(Request $request)
    {
        $this->authorize('dashboard.view'); // Assuming similar permission as dashboard
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $stats = [];

        $year = now()->year;
        $month = now()->month;
        $today = now()->toDateString();

        // Initial Stats (Similar to DashboardController@index)
        $cacheKeyBase = 'report_overall_revenue_base_stats_v1_' . $user->id . '_' . implode('_', $user->getRoleNames()->toArray()) . "_{$today}";
        $stats = Cache::remember($cacheKeyBase, 300, function() use ($user, $year, $month, $today) {
            $currentStats = [];
            $baseDailyAggQuery = DailyRevenueAggregate::query();
            $targetUserIds = $this->getTargetUserIdsForQuery($user); // Helper to get user IDs based on role

            if ($targetUserIds === false) { // No access / manager with no team
                 $baseDailyAggQuery->whereRaw('1 = 0');
            } elseif (is_array($targetUserIds) && !empty($targetUserIds)) {
                $baseDailyAggQuery->whereIn('user_id', $targetUserIds);
            } // if null, admin/super-admin sees all

            $currentStats['monthly_revenue'] = (clone $baseDailyAggQuery)->whereYear('aggregation_date', $year)->whereMonth('aggregation_date', $month)->sum('total_revenue');
            $currentStats['today_revenue'] = (clone $baseDailyAggQuery)->whereDate('aggregation_date', $today)->sum('total_revenue');
            $currentStats['today_completed_orders'] = (clone $baseDailyAggQuery)->whereDate('aggregation_date', $today)->sum('completed_orders_count');

            $currentStats['monthly_revenue_formatted'] = number_format($currentStats['monthly_revenue'] ?? 0, 0, ',', '.');
            $currentStats['today_revenue_formatted'] = number_format($currentStats['today_revenue'] ?? 0, 0, ',', '.');

            return $currentStats;
        });

        // Prepare Data for Filter Dropdowns (Similar to DashboardController@index)
        $filterableStaff = collect();
        $filterableManagers = collect();
        // ... (Keep existing filterable staff/manager logic from DashboardController or adapt as needed) ...
        if ($user->hasRole(['admin', 'super-admin'])) {
            $filterableStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))->orderBy('name')->pluck('name', 'id');
            $filterableManagers = User::whereNotNull('manages_team_id')
                                      ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                                      ->orderBy('name')
                                      ->pluck('name', 'id');
        } elseif ($user->hasRole('manager') && $user->manages_team_id) {
            $filterableStaff = User::where('team_id', $user->manages_team_id)
                                   ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                   ->orderBy('name')
                                   ->pluck('name', 'id');
        }

        // Initial chart data (for page load)
        // We pass a simple request object with default date range to getOverallRevenueChartData method's core logic
        $initialChartRequest = new Request([
            // Default to last 30 days or current month for initial view
            'start_date' => now()->subDays(29)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ]);
        $initialChartDataResponse = $this->getOverallRevenueChartData($initialChartRequest, true); // Pass true to indicate internal call
        $initialChartData = json_decode($initialChartDataResponse->getContent(), true)['data'] ?? [];

        return view('reports.overall_revenue_summary', compact(
            'stats',
            'filterableStaff',
            'filterableManagers',
            'initialChartData' // Pass initial chart data to the view
        ));
    }

    private function getTargetUserIdsForQuery(User $user, $saleId = null, $managerId = null)
    {
        $targetUserIds = null; // null means all (for admin/super-admin with no filter)
        $isSpecificFilterApplied = false;

        if ($user->hasRole(['admin', 'super-admin'])) {
            if ($managerId) {
                $managedTeamId = User::where('id', $managerId)->whereNotNull('manages_team_id')->value('manages_team_id');
                if ($managedTeamId) {
                    $targetUserIds = User::where('team_id', $managedTeamId)
                                        ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                        ->pluck('id')->toArray();
                } else {
                    $targetUserIds = []; // Manager selected but manages no team
                }
                $isSpecificFilterApplied = true;
            }
            if ($saleId) {
                if ($isSpecificFilterApplied && !empty($targetUserIds)) { // Manager was selected, filter sale within manager's team
                    if (!in_array($saleId, $targetUserIds)) {
                        $targetUserIds = []; // Invalid saleId for selected manager's team
                    }
                    else { $targetUserIds = [$saleId]; }
                } else { // No manager selected or manager had no team, just use saleId
                    $targetUserIds = [$saleId];
                }
                $isSpecificFilterApplied = true;
            }
            // If no specific filter, $targetUserIds remains null for admin (all users)
        } elseif ($user->hasRole('manager')) {
            $teamId = $user->manages_team_id;
            if ($teamId) {
                $targetUserIds = User::where('team_id', $teamId)
                                     ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                     ->pluck('id')->toArray();
                if ($saleId && in_array($saleId, $targetUserIds)) {
                    $targetUserIds = [$saleId];
                    $isSpecificFilterApplied = true;
                } elseif ($saleId) { // Manager selected a sale_id not in their team
                    return false; // Indicate no access / empty results
                }
            } else {
                return false; // Manager with no team
            }
        } elseif ($user->hasRole('staff')) {
            $targetUserIds = [$user->id];
            $isSpecificFilterApplied = true;
        }

        // If a specific filter was applied (e.g. saleId, managerId) and it resulted in an empty $targetUserIds array, it means no data should be shown.
        if($isSpecificFilterApplied && empty($targetUserIds)) return false;

        return $targetUserIds; // null, array of IDs, or false
    }

    public function getOverallRevenueChartData(Request $request, $isInternalCall = false)
    {
        if (!$isInternalCall) { // Only authorize if called via HTTP
            $this->authorize('dashboard.view');
        }
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $saleId = $request->input('sale_id');
        $managerId = $request->input('manager_id');

        $maxDays = 90;
        try {
            $start = $startDateInput ? Carbon::parse($startDateInput)->startOfDay() : now()->subDays(29)->startOfDay();
            $end = $endDateInput ? Carbon::parse($endDateInput)->endOfDay() : now()->endOfDay();
            if ($end->greaterThan(now()->endOfDay())) $end = now()->endOfDay();
            if ($start->greaterThan($end)) $start = $end->copy()->subDays(29)->startOfDay();
            if ($start->diffInDays($end) > $maxDays) $start = $end->copy()->subDays($maxDays)->startOfDay();
        } catch (\Exception $e) {
            $start = now()->subDays(29)->startOfDay();
            $end = now()->endOfDay();
            Log::error('Error parsing dates in getOverallRevenueChartData', ['error' => $e->getMessage()]);
        }

        $cacheKey = 'report_overall_revenue_charts_v1_' . $user->id . '_' . md5(json_encode([$start->toDateString(), $end->toDateString(), $saleId, $managerId]));
        $chartData = Cache::remember($cacheKey, 300, function() use ($user, $start, $end, $saleId, $managerId) {

            $targetUserIds = $this->getTargetUserIdsForQuery($user, $saleId, $managerId);

            $aggQueryBase = DailyRevenueAggregate::query()->whereBetween('aggregation_date', [$start, $end]);
            $orderStatusBaseQuery = DB::table('orders')->whereBetween('created_at', [$start, $end]);

            if ($targetUserIds === false) { // No access scenario
                $aggQueryBase->whereRaw('1 = 0');
                $orderStatusBaseQuery->whereRaw('1 = 0');
            } elseif (is_array($targetUserIds) && !empty($targetUserIds)) {
                $aggQueryBase->whereIn('user_id', $targetUserIds);
                $orderStatusBaseQuery->whereIn('user_id', $targetUserIds);
            } // If $targetUserIds is null (admin/super-admin with no filter), no user_id scoping is applied for global view.

            // Revenue by Day
            $revenueByDayAgg = (clone $aggQueryBase)
                ->selectRaw('aggregation_date, SUM(total_revenue) as revenue')
                ->groupBy('aggregation_date')
                ->orderBy('aggregation_date', 'asc')
                ->pluck('revenue', 'aggregation_date');
            $dateRange = CarbonPeriod::create($start, $end);
            $revenueDailyLabels = []; $revenueDailyData = [];
            foreach ($dateRange as $date) {
                $formattedDate = $date->format('Y-m-d');
                $revenueDailyLabels[] = $date->format('d/m');
                $revenueDailyData[] = $revenueByDayAgg[$formattedDate] ?? 0;
            }

            // Revenue by Month
            $revenueByMonthAgg = (clone $aggQueryBase)
                ->selectRaw('DATE_FORMAT(aggregation_date, \'%Y-%m\') as month_year, SUM(total_revenue) as revenue')
                ->groupBy('month_year')
                ->orderBy('month_year', 'asc')
                ->pluck('revenue', 'month_year');
            $revenueMonthlyLabels = []; $revenueMonthlyData = [];
            $monthPeriod = CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->endOfMonth());
            foreach ($monthPeriod as $date) {
                $monthYearKey = $date->format('Y-m');
                $revenueMonthlyLabels[] = $date->format('m/Y');
                $revenueMonthlyData[] = $revenueByMonthAgg[$monthYearKey] ?? 0;
            }

            // Order Status
            $ordersByStatusRaw = (clone $orderStatusBaseQuery)
                ->selectRaw('status, COUNT(id) as count')
                ->groupBy('status')
                ->pluck('count', 'status');
            $allOrderStatuses = Order::getAllStatuses(); // Assuming this gives a map like ['moi' => 'Mới', ...]
            $orderStatusLabels = []; $orderStatusData = [];
            foreach ($allOrderStatuses as $statusKey => $statusName) {
                $orderStatusLabels[] = $statusName;
                $orderStatusData[] = $ordersByStatusRaw[$statusKey] ?? 0;
            }

            return [
                'revenueDailyLabels' => $revenueDailyLabels,
                'revenueDailyData' => $revenueDailyData,
                'revenueMonthlyLabels' => $revenueMonthlyLabels,
                'revenueMonthlyData' => $revenueMonthlyData,
                'orderStatusLabels' => $orderStatusLabels,
                'orderStatusData' => $orderStatusData,
            ];
        });

        return response()->json(['success' => true, 'data' => $chartData]);
    }
}
