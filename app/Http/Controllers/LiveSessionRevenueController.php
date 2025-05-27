<?php

namespace App\Http\Controllers;

use App\Models\LiveSessionRevenue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LiveSessionRevenueController extends Controller
{
    /**
     * Display the live sessions revenue page
     */
    public function index(Request $request)
    {
        // Get dates from query parameters or use defaults
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        // Get previous period dates (7 days before)
        $previousStartDate = (clone $startDate)->subDays(7);
        $previousEndDate = (clone $endDate)->subDays(7);

        // Get current period data
        $currentData = $this->getData(new Request([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]));

        // Get previous period data
        $previousData = $this->getData(new Request([
            'start_date' => $previousStartDate->format('Y-m-d'),
            'end_date' => $previousEndDate->format('Y-m-d')
        ]));

        $currentStats = json_decode($currentData->getContent(), true);
        $previousStats = json_decode($previousData->getContent(), true);

        // Calculate change rates
        $revenueChangeRate = $this->calculateChangeRate(
            $currentStats['summary']['expected_revenue'] ?? 0,
            $previousStats['summary']['expected_revenue'] ?? 0
        );

        $ordersChangeRate = $this->calculateChangeRate(
            $currentStats['summary']['successful_orders'] ?? 0,
            $previousStats['summary']['successful_orders'] ?? 0
        );

        $canceledOrdersChangeRate = $this->calculateChangeRate(
            $currentStats['summary']['canceled_orders'] ?? 0,
            $previousStats['summary']['canceled_orders'] ?? 0
        );

        $successRateChange = ($currentStats['summary']['conversion_rate'] ?? 0) - ($previousStats['summary']['conversion_rate'] ?? 0);

        // Get live sessions data from daily_stats
        $liveSessions = collect($currentStats['daily_stats'] ?? [])->map(function ($session) {
            return [
                'live_number' => $session['live_number'],
                'date' => Carbon::parse($session['date']),
                'expected_revenue' => $session['expected_revenue'],
                'actual_revenue' => $session['actual_revenue'],
                'total_orders' => $session['total_orders'],
                'successful_orders' => $session['successful_orders'],
                'canceled_orders' => $session['canceled_orders'],
                'success_rate' => $session['conversion_rate'],
                'cancellation_rate' => $session['cancellation_rate'],
                'new_customers' => $session['new_customers'],
                'returning_customers' => $session['returning_customers']
            ];
        })->values()->all();

        return view('reports.live_sessions', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $currentStats['summary'],
            'chartType' => $currentStats['chart_type'],
            'chartData' => $currentStats['chart_data'],
            'provinceStats' => $currentStats['province_stats'] ?? [],
            'topProducts' => $currentStats['top_products'],
            'revenueChangeRate' => $revenueChangeRate,
            'ordersChangeRate' => $ordersChangeRate,
            'canceledOrdersChangeRate' => $canceledOrdersChangeRate,
            'successRateChange' => $successRateChange,
            'liveSessions' => $liveSessions
        ]);
    }

    private function calculateChangeRate($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get live session revenue data
     */
    public function getData(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subDays(30);

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        // Determine chart type based on date range
        $diffInDays = $startDate->diffInDays($endDate);
        $chartType = 'daily';
        if ($diffInDays > 31) {
            $chartType = 'monthly';
        } elseif ($diffInDays === 0) {
            $chartType = 'hourly';
        }

        // Get status codes from pancake_order_statuses table
        $statusCodes = DB::table('pancake_order_statuses')->get()->pluck('name', 'id')->toArray();

        // Get daily data with more detailed information
        $dailyStats = LiveSessionRevenue::select([
            'date',
            'live_number',
            'orders_by_status',
            'new_customers',
            'total_customers',
            'orders_by_province',
            'top_products',
            'total_orders',
            'successful_orders',
            'canceled_orders',
            'delivering_orders',
            'total_revenue'
        ])
        ->whereBetween('date', [$startDate, $endDate])
        ->orderBy('date', 'desc')
        ->get();

        // Map daily stats with expected and actual revenue
        $dailyStats = $dailyStats->map(function($revenue) use ($statusCodes) {
            $statusData = is_array($revenue->orders_by_status) ? $revenue->orders_by_status : [];

            // Calculate totals from orders_by_status
            $totalOrders = $revenue->total_orders ?? 0;
            $expectedRevenue = 0;
            $actualRevenue = $revenue->total_revenue ?? 0;
            $successfulOrders = $revenue->successful_orders ?? 0;
            $canceledOrders = $revenue->canceled_orders ?? 0;
            $deliveringOrders = $revenue->delivering_orders ?? 0;
            $pendingOrders = $totalOrders - ($successfulOrders + $canceledOrders + $deliveringOrders);

            foreach ($statusData as $status => $data) {
                $expectedRevenue += $data['revenue'] ?? 0;
            }

            // Calculate finalized orders (excluding delivering)
            $finalizedOrders = $totalOrders - $deliveringOrders;

            // Calculate rates
            $conversionRate = $finalizedOrders > 0 ? ($successfulOrders / $finalizedOrders) * 100 : 0;
            $cancellationRate = $totalOrders > 0 ? ($canceledOrders / $totalOrders) * 100 : 0;

            return [
                'date' => $revenue->date,
                'live_number' => $revenue->live_number,
                'total_sessions' => 1,
                'expected_revenue' => $expectedRevenue,
                'actual_revenue' => $actualRevenue,
                'potential_revenue' => $expectedRevenue,
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'canceled_orders' => $canceledOrders,
                'delivering_orders' => $deliveringOrders,
                'pending_orders' => $pendingOrders,
                'conversion_rate' => round($conversionRate, 1),
                'cancellation_rate' => round($cancellationRate, 1),
                'new_customers' => intval($revenue->new_customers),
                'returning_customers' => max(0, intval($revenue->total_customers) - intval($revenue->new_customers)),
                'total_customers' => intval($revenue->total_customers),
                'orders_by_province' => $revenue->orders_by_province,
                'top_products' => $revenue->top_products,
                'orders_by_status' => $statusData
            ];
        });

        // Calculate summary with updated metrics
        $summary = [
            'total_sessions' => $dailyStats->sum('total_sessions'),
            'expected_revenue' => $dailyStats->sum('expected_revenue'),
            'actual_revenue' => $dailyStats->sum('actual_revenue'),
            'potential_revenue' => $dailyStats->sum('potential_revenue'),
            'total_orders' => $dailyStats->sum('total_orders'),
            'successful_orders' => $dailyStats->sum('successful_orders'),
            'canceled_orders' => $dailyStats->sum('canceled_orders'),
            'delivering_orders' => $dailyStats->sum('delivering_orders'),
            'pending_orders' => $dailyStats->sum('pending_orders'),
            'new_customers' => $dailyStats->sum('new_customers'),
            'returning_customers' => $dailyStats->sum('returning_customers'),
            'total_customers' => $dailyStats->sum('total_customers')
        ];

        // Calculate overall rates
        $totalFinalizedOrders = $summary['total_orders'] - $summary['delivering_orders'];
        $summary['conversion_rate'] = $totalFinalizedOrders > 0
            ? round(($summary['successful_orders'] / $totalFinalizedOrders) * 100, 1)
            : 0;
        $summary['cancellation_rate'] = $summary['total_orders'] > 0
            ? round(($summary['canceled_orders'] / $summary['total_orders']) * 100, 1)
            : 0;

        // Get top 5 products with more details
        $topProducts = $this->getDetailedTopProducts($dailyStats);

        // Get province statistics with more details
        $provinceStats = [];
        $dailyStats->each(function($stat) use (&$provinceStats) {
            if (!empty($stat['orders_by_province']) && is_array($stat['orders_by_province'])) {
                foreach ($stat['orders_by_province'] as $code => $data) {
                    $provinceName = DB::table('provinces')->where('code', $code)->value('name') ?? $data['name'] ?? 'Không xác định';

                    if (!isset($provinceStats[$code])) {
                        $provinceStats[$code] = [
                            'name' => $provinceName,
                            'revenue' => 0,
                            'expected_revenue' => 0,
                            'orders' => 0,
                            'successful_orders' => 0,
                            'canceled_orders' => 0,
                            'delivering_orders' => 0,
                            'new_customers' => 0,
                            'returning_customers' => 0,
                            'total_customers' => 0
                        ];
                    }

                    // Add revenue and orders
                    $provinceStats[$code]['revenue'] += $data['revenue'] ?? 0;
                    $provinceStats[$code]['expected_revenue'] += $data['expected_revenue'] ?? $data['revenue'] ?? 0;
                    $provinceStats[$code]['orders'] += $data['count'] ?? 0;
                    $provinceStats[$code]['successful_orders'] += $data['successful_orders'] ?? 0;
                    $provinceStats[$code]['canceled_orders'] += $data['canceled_orders'] ?? 0;
                    $provinceStats[$code]['delivering_orders'] += $data['delivering_orders'] ?? 0;
                    $provinceStats[$code]['new_customers'] += $data['new_customers'] ?? 0;
                    $provinceStats[$code]['total_customers'] += $data['total_customers'] ?? 0;
                    $provinceStats[$code]['returning_customers'] =
                        $provinceStats[$code]['total_customers'] - $provinceStats[$code]['new_customers'];
                }
            }
        });

        // Sort provinces by revenue and convert to array
        $provinceStats = collect($provinceStats)
            ->sortByDesc('revenue')
            ->values()
            ->toArray();

        // Prepare chart data based on chart type
        $chartData = [];
        switch ($chartType) {
            case 'hourly':
                $chartData = $this->prepareHourlyChartData($dailyStats);
                break;
            case 'monthly':
                $chartData = $this->prepareMonthlyChartData($dailyStats);
                break;
            default:
                $chartData = $this->prepareDailyChartData($dailyStats);
        }

        return response()->json([
            'summary' => $summary,
            'chart_type' => $chartType,
            'chart_data' => $chartData,
            'daily_stats' => $dailyStats,
            'province_stats' => $provinceStats,
            'top_products' => $topProducts
        ]);
    }

    private function prepareHourlyChartData($dailyStats)
    {
        // Group data by hour for the specific day
        return $dailyStats
            ->groupBy(function($stat) {
                return Carbon::parse($stat['date'])->format('H:00');
            })
            ->map(function($stats) {
                return [
                    'expected_revenue' => $stats->sum('expected_revenue'),
                    'actual_revenue' => $stats->sum('actual_revenue'),
                    'potential_revenue' => $stats->sum('potential_revenue'),
                    'total_orders' => $stats->sum('total_orders'),
                    'successful_orders' => $stats->sum('successful_orders'),
                    'canceled_orders' => $stats->sum('canceled_orders'),
                    'delivering_orders' => $stats->sum('delivering_orders'),
                    'pending_orders' => $stats->sum('pending_orders'),
                    'conversion_rate' => $stats->avg('conversion_rate'),
                    'cancellation_rate' => $stats->avg('cancellation_rate')
                ];
            });
    }

    private function prepareDailyChartData($dailyStats)
    {
        // Group data by day
        return $dailyStats
            ->groupBy(function($stat) {
                return Carbon::parse($stat['date'])->format('Y-m-d');
            })
            ->map(function($stats) {
                return [
                    'expected_revenue' => $stats->sum('expected_revenue'),
                    'actual_revenue' => $stats->sum('actual_revenue'),
                    'potential_revenue' => $stats->sum('potential_revenue'),
                    'total_orders' => $stats->sum('total_orders'),
                    'successful_orders' => $stats->sum('successful_orders'),
                    'canceled_orders' => $stats->sum('canceled_orders'),
                    'delivering_orders' => $stats->sum('delivering_orders'),
                    'pending_orders' => $stats->sum('pending_orders'),
                    'conversion_rate' => $stats->avg('conversion_rate'),
                    'cancellation_rate' => $stats->avg('cancellation_rate')
                ];
            });
    }

    private function prepareMonthlyChartData($dailyStats)
    {
        // Group data by month
        return $dailyStats
            ->groupBy(function($stat) {
                return Carbon::parse($stat['date'])->format('Y-m');
            })
            ->map(function($stats) {
                return [
                    'expected_revenue' => $stats->sum('expected_revenue'),
                    'actual_revenue' => $stats->sum('actual_revenue'),
                    'potential_revenue' => $stats->sum('potential_revenue'),
                    'total_orders' => $stats->sum('total_orders'),
                    'successful_orders' => $stats->sum('successful_orders'),
                    'canceled_orders' => $stats->sum('canceled_orders'),
                    'delivering_orders' => $stats->sum('delivering_orders'),
                    'pending_orders' => $stats->sum('pending_orders'),
                    'conversion_rate' => $stats->avg('conversion_rate'),
                    'cancellation_rate' => $stats->avg('cancellation_rate')
                ];
            });
    }

    private function getDetailedTopProducts($dailyStats)
    {
        $products = collect();

        // Collect all products from daily stats (which are individual LiveSessionRevenue records)
        $dailyStats->each(function($session) use (&$products) {
            if (!empty($session['top_products']) && is_array($session['top_products'])) {
                foreach ($session['top_products'] as $productData) {
                    $existingProduct = $products->where('id', $productData['id'])->first();
                    if ($existingProduct) {
                        $existingProduct['quantity_ordered'] += $productData['quantity_ordered'] ?? 0;
                        $existingProduct['expected_revenue'] += $productData['expected_revenue'] ?? 0;
                        $existingProduct['quantity_actual'] += $productData['quantity_actual'] ?? 0;
                        $existingProduct['actual_revenue'] += $productData['actual_revenue'] ?? 0;
                        $existingProduct['orders_count'] += $productData['orders_count'] ?? 0;
                    } else {
                        $products->push([
                            'id' => $productData['id'],
                            'name' => $productData['name'],
                            'unit_price' => $productData['unit_price'] ?? 0, // Retain unit_price from first encounter
                            'quantity_ordered' => $productData['quantity_ordered'] ?? 0,
                            'expected_revenue' => $productData['expected_revenue'] ?? 0,
                            'quantity_actual' => $productData['quantity_actual'] ?? 0,
                            'actual_revenue' => $productData['actual_revenue'] ?? 0,
                            'orders_count' => $productData['orders_count'] ?? 0,
                        ]);
                    }
                }
            }
        });

        // Calculate average price for each product after aggregation
        $products = $products->map(function ($product) {
            // Calculate average price based on expected revenue and ordered quantity
            $product['average_price'] = ($product['quantity_ordered'] > 0) ? ($product['expected_revenue'] / $product['quantity_ordered']) : 0;
            return $product;
        });

        // Sort by expected_revenue and return all
        return $products->sortByDesc('expected_revenue')->values();
    }

    /**
     * Get detailed data for a specific live session
     */
    public function getSessionDetail(Request $request)
    {
        $date = $request->input('date');
        $liveNumber = $request->input('live_number');

        if (!$date || !$liveNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Date and live number are required'
            ], 400);
        }

        // Get session data from live_session_revenues table
        $session = LiveSessionRevenue::where('date', $date)
            ->where('live_number', $liveNumber)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'date' => $session->date->format('Y-m-d'),
                    'live_number' => $session->live_number,
                    'session_name' => $session->session_name,
                    'total_revenue' => $session->total_revenue,
                    'total_orders' => $session->total_orders,
                    'successful_orders' => $session->successful_orders,
                    'canceled_orders' => $session->canceled_orders,
                    'delivering_orders' => $session->delivering_orders,
                    'total_customers' => $session->total_customers,
                    'new_customers' => $session->new_customers,
                    'returning_customers' => $session->total_customers - $session->new_customers,
                    'conversion_rate' => $session->conversion_rate,
                    'cancellation_rate' => $session->cancellation_rate
                ],
                'products' => $session->top_products ?? [],
                'orders_by_status' => $session->orders_by_status ?? []
            ]
        ]);
    }

    /**
     * Recalculate statistics for a date range
     */
    public function recalculateStats(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()));

        // Clear cache for this date range
        $cacheKey = "live_session_revenue:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        Cache::forget($cacheKey);

        // Recalculate all live session revenues in the date range
        $revenues = LiveSessionRevenue::whereBetween('date', [$startDate, $endDate])->get();
        foreach ($revenues as $revenue) {
            // Recalculate basic stats
            $revenue->recalculateStats($revenue->date, $revenue->live_number);

            // Calculate and save top products
            $revenue->calculateTopProducts();
        }

        return response()->json([
            'success' => true,
            'message' => 'Statistics recalculated successfully'
        ]);
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, $filters)
    {
        if (!empty($filters['status'])) {
            $query->whereJsonContains('orders_by_status', $filters['status']);
        }

        if (!empty($filters['customerType'])) {
            if ($filters['customerType'] === 'new') {
                $query->where('new_customers', '>', 0);
            } elseif ($filters['customerType'] === 'returning') {
                $query->whereRaw('CAST(total_customers AS SIGNED) - CAST(new_customers AS SIGNED) > 0');
            }
        }

        if (!empty($filters['minRevenue'])) {
            $query->where(function($q) use ($filters) {
                $q->whereRaw("JSON_EXTRACT(orders_by_status, '$[*].revenue') >= ?", [$filters['minRevenue']]);
            });
        }

        if (!empty($filters['maxRevenue'])) {
            $query->where(function($q) use ($filters) {
                $q->whereRaw("JSON_EXTRACT(orders_by_status, '$[*].revenue') <= ?", [$filters['maxRevenue']]);
            });
        }

        if (!empty($filters['minSales'])) {
            $query->where(function($q) use ($filters) {
                $q->whereRaw("JSON_EXTRACT(orders_by_status, '$[*].count') >= ?", [$filters['minSales']]);
            });
        }

        if (!empty($filters['maxSales'])) {
            $query->where(function($q) use ($filters) {
                $q->whereRaw("JSON_EXTRACT(orders_by_status, '$[*].count') <= ?", [$filters['maxSales']]);
            });
        }

        if (!empty($filters['provinces'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['provinces'] as $province) {
                    $q->orWhereJsonContains('orders_by_province', $province);
                }
            });
        }

        return $query;
    }

    /**
     * Get comparison data for the specified period
     */
    private function getComparisonData($startDate, $endDate, $comparisonType, $customRange = null)
    {
        $query = LiveSessionRevenue::query();

        switch ($comparisonType) {
            case 'previous_period':
                $daysDiff = $endDate->diffInDays($startDate);
                $comparisonStartDate = (clone $startDate)->subDays($daysDiff);
                $comparisonEndDate = (clone $startDate)->subDay();
                break;

            case 'previous_year':
                $comparisonStartDate = (clone $startDate)->subYear();
                $comparisonEndDate = (clone $endDate)->subYear();
                break;

            case 'custom':
                if ($customRange) {
                    [$comparisonStartDate, $comparisonEndDate] = explode(' - ', $customRange);
                    $comparisonStartDate = Carbon::createFromFormat('d/m/Y', $comparisonStartDate);
                    $comparisonEndDate = Carbon::createFromFormat('d/m/Y', $comparisonEndDate);
                } else {
                    return null;
                }
                break;

            default:
                return null;
        }

        return $this->getData(new Request([
            'start_date' => $comparisonStartDate->format('Y-m-d'),
            'end_date' => $comparisonEndDate->format('Y-m-d')
        ]));
    }

    /**
     * Get filtered live session revenue data
     */
    public function getFilteredData(Request $request)
    {
        $filters = $request->input('filters', []);
        $startDate = Carbon::createFromFormat('d/m/Y', explode(' - ', $filters['dateRange'])[0]);
        $endDate = Carbon::createFromFormat('d/m/Y', explode(' - ', $filters['dateRange'])[1]);

        // Get current period data with filters
        $query = LiveSessionRevenue::whereBetween('date', [$startDate, $endDate]);
        $query = $this->applyFilters($query, $filters);

        $currentData = $this->getData(new Request([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'query' => $query
        ]));

        $response = ['current' => json_decode($currentData->getContent(), true)];

        // Get comparison data if requested
        if (!empty($filters['comparison'])) {
            $comparisonData = $this->getComparisonData(
                $startDate,
                $endDate,
                $filters['comparison']['type'],
                $filters['comparison']['customRange'] ?? null
            );

            if ($comparisonData) {
                $response['comparison'] = json_decode($comparisonData->getContent(), true);
            }
        }

        return response()->json($response);
    }
}
