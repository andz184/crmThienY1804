<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\DailyRevenueAggregate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Import DB facade
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $stats = [];

        $year = now()->year;
        $month = now()->month;
        $today = now()->toDateString();

        // Cache key cho các chỉ số cơ bản - Tăng cache lên 15 phút (900 giây)
        $cacheKeyBase = 'dashboard_base_stats_v2_' . $user->id . '_' . implode('_', $user->getRoleNames()->toArray()) . "_{$today}";
        $stats = Cache::remember($cacheKeyBase, 900, function() use ($user, $year, $month, $today) {
            $stats = [];
            $teamMemberIds = collect(); // Initialize teamMemberIds

            // Base query for daily aggregates
            $dailyAggQuery = DailyRevenueAggregate::query();

            if ($user->hasRole('manager')) {
                $teamId = $user->manages_team_id;
                if ($teamId) {
                    $teamMemberIds = DB::table('users')->where('team_id', $teamId)->pluck('id');
                    $dailyAggQuery->whereIn('user_id', $teamMemberIds);
                } else {
                    // Manager not managing any team, so revenue/orders will be 0
                    $dailyAggQuery->whereRaw('1 = 0'); // No results
                }
            } elseif ($user->hasRole('staff')) {
                $dailyAggQuery->where('user_id', $user->id);
            } // Admin/Super Admin sees all, so no user_id filter on base query unless specified

            // 1. Doanh thu tháng này (from daily_revenue_aggregates)
            $monthlyQuery = (clone $dailyAggQuery)
                ->whereYear('aggregation_date', $year)
                ->whereMonth('aggregation_date', $month);
            $stats['monthly_revenue'] = $monthlyQuery->sum('total_revenue');

            // 2. Doanh thu hôm nay (from daily_revenue_aggregates)
            $todayAggQuery = (clone $dailyAggQuery)->whereDate('aggregation_date', $today);
            $stats['today_revenue'] = $todayAggQuery->sum('total_revenue');

            // 3. Số đơn hoàn thành hôm nay (from daily_revenue_aggregates)
            // Need to clone again for a fresh query if $todayAggQuery was modified or to be safe
            $stats['today_completed_orders'] = (clone $dailyAggQuery)->whereDate('aggregation_date', $today)->sum('completed_orders_count');

            // Format numbers
            $stats['monthly_revenue_formatted'] = number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.');
            $stats['today_revenue_formatted'] = number_format($stats['today_revenue'] ?? 0, 0, ',', '.');

            return $stats;
        });

        // --- Prepare Data for Filter Dropdowns ---
        $filterableStaff = collect();
        if ($user->hasRole(['admin', 'super-admin'])) {
            $filterableStaff = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))->orderBy('name')->pluck('name', 'id');
        } elseif ($user->hasRole('manager') && $user->manages_team_id) {
            $filterableStaff = User::where('team_id', $user->manages_team_id)
                                   ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                   ->orderBy('name')
                                   ->pluck('name', 'id');
        }
        $filterableManagers = collect();
        if ($user->hasRole(['admin', 'super-admin'])) {
            $filterableManagers = User::whereNotNull('manages_team_id')
                                      ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                                      ->orderBy('name')
                                      ->pluck('name', 'id');
        }
        return view('dashboard', compact('stats', 'filterableStaff', 'filterableManagers'));
    }

    /**
     * Get data for dashboard charts via AJAX.
     */
    public function getChartData(Request $request)
    {
        $this->authorize('dashboard.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $saleId = $request->input('sale_id');
        $managerId = $request->input('manager_id');

        // Validate and sanitize dates (keep the existing logic)
        $maxDays = 90;
        try {
            $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->subDays(29)->startOfDay();
            $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
            $now = now()->endOfDay();
            if ($end->greaterThan($now)) {
                $end = $now;
            }
            if ($start->greaterThan($end)) {
                $start = $end->copy()->subDays(29);
            }
            if ($start->diffInDays($end) > $maxDays) {
                $start = $end->copy()->subDays($maxDays);
            }
        } catch (\Exception $e) {
            $start = now()->subDays(29)->startOfDay();
            $end = now()->endOfDay();
        }

        try {
            // Cache key based on filters - Tăng cache lên 15 phút (900 giây)
            // Add _v2 or similar to cache key if logic changes significantly
            $cacheKey = 'dashboard_charts_v2_' . $user->id . '_' . md5(json_encode([$start->toDateString(), $end->toDateString(), $saleId, $managerId]));
            $data = Cache::remember($cacheKey, 900, function() use ($user, $start, $end, $saleId, $managerId) {

                $targetUserIds = collect(); // Collection to hold user IDs for filtering queries
                $isSpecificFilterApplied = false; // Flag to check if saleId or managerId (by admin) is applied

                if ($user->hasRole(['admin', 'super-admin'])) {
                    if ($managerId) {
                        $managerTeamMemberIds = User::where('team_id', function($query) use ($managerId) {
                                                    $query->select('manages_team_id')->from('users')->where('id', $managerId)->limit(1);
                                                })
                                                ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                                ->pluck('id');
                        if ($managerTeamMemberIds->isNotEmpty()) {
                            $targetUserIds = $managerTeamMemberIds;
                        }
                        $isSpecificFilterApplied = true; // Manager filter by admin is a specific view
                    }
                    if ($saleId) { // If a sale is also specified, it takes precedence or filters within manager's team
                        if ($targetUserIds->isNotEmpty()) { // Manager was selected, check if saleId is in their team
                            if (!$targetUserIds->contains($saleId)) {
                                $targetUserIds = collect(); // Invalid saleId for the selected manager's team, show no data
                            } else {
                                $targetUserIds = collect([$saleId]); // Valid saleId within manager's team
                            }
                        } else { // No manager selected, or manager had no team, just use saleId
                            $targetUserIds = collect([$saleId]);
                        }
                        $isSpecificFilterApplied = true; // Sale filter is a specific view
                    }
                } elseif ($user->hasRole('manager')) {
                    $teamId = $user->manages_team_id;
                    if ($teamId) {
                        $targetUserIds = User::where('team_id', $teamId)
                                             ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                             ->pluck('id');
                        if ($saleId && $targetUserIds->contains($saleId)) { // Manager filters by a specific staff in their team
                            $targetUserIds = collect([$saleId]);
                            $isSpecificFilterApplied = true;
                        } elseif ($saleId) { // Manager selected a sale_id not in their team
                            $targetUserIds = collect(); // Show no data
                            $isSpecificFilterApplied = true;
                        }
                        // If no saleId, $targetUserIds remains all staff in the manager's team
                    }
                } elseif ($user->hasRole('staff')) {
                    $targetUserIds = collect([$user->id]);
                    $isSpecificFilterApplied = true; // Staff view is always specific to themselves
                }

                // --- Base query for DailyRevenueAggregate ---
                $aggQuery = DailyRevenueAggregate::query()
                    ->whereBetween('aggregation_date', [$start, $end]);

                // --- Base query for Order Status (original orders table) ---
                $orderStatusBaseQuery = DB::table('orders')
                                        ->whereBetween('created_at', [$start, $end]);

                if ($isSpecificFilterApplied) {
                    if ($targetUserIds->isNotEmpty()) {
                        $aggQuery->whereIn('user_id', $targetUserIds);
                        $orderStatusBaseQuery->whereIn('user_id', $targetUserIds);
                    } else {
                        // If a specific filter was applied but resulted in no target users (e.g., manager with no team, invalid saleId)
                        $aggQuery->whereRaw('1 = 0'); // Force no results
                        $orderStatusBaseQuery->whereRaw('1 = 0');
                    }
                } elseif (!$user->hasRole(['admin', 'super-admin'])) {
                    // This case handles when a manager or staff is logged in, and no specific $saleId was chosen by the manager.
                    // The $targetUserIds are already set to their team or self.
                    // This ensures manager sees their team, staff sees self, if no specific filter applied by them.
                    if ($targetUserIds->isNotEmpty()) {
                         $aggQuery->whereIn('user_id', $targetUserIds);
                         $orderStatusBaseQuery->whereIn('user_id', $targetUserIds);
                    }
                }
                // If it's an Admin/Super-Admin and no specific filter ($saleId or $managerId) is applied, queries remain un-scoped by user_id (global view)

                // --- 1. Revenue by Day (Line Chart) from DailyRevenueAggregate ---
                $revenueByDayAgg = (clone $aggQuery)
                    ->selectRaw('aggregation_date, SUM(total_revenue) as revenue')
                    ->groupBy('aggregation_date')
                    ->orderBy('aggregation_date', 'asc')
                    ->pluck('revenue', 'aggregation_date');

                $dateRange = \Carbon\CarbonPeriod::create($start, $end);
                $revenueDailyLabels = [];
                $revenueDailyData = [];
                foreach ($dateRange as $date) {
                    $formattedDate = $date->format('Y-m-d');
                    $revenueDailyLabels[] = $date->format('d/m');
                    // Access revenue directly from the plucked collection, ensuring date is Carbon object or string
                    $revenueDailyData[] = isset($revenueByDayAgg[$formattedDate]) ? (float)$revenueByDayAgg[$formattedDate] : 0;
                }

                // --- 2. Revenue by Month (Bar Chart) from DailyRevenueAggregate ---
                $revenueByMonthAgg = (clone $aggQuery)
                    ->selectRaw('DATE_FORMAT(aggregation_date, \'%Y-%m\') as month_year, SUM(total_revenue) as revenue')
                    ->groupBy('month_year')
                    ->orderBy('month_year', 'asc')
                    ->pluck('revenue', 'month_year');

                $revenueMonthlyLabels = [];
                $revenueMonthlyData = [];
                $period = \Carbon\CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->endOfMonth());
                foreach ($period as $date) {
                    $monthYearKey = $date->format('Y-m');
                    $revenueMonthlyLabels[] = $date->format('m/Y');
                    $revenueMonthlyData[] = isset($revenueByMonthAgg[$monthYearKey]) ? (float)$revenueByMonthAgg[$monthYearKey] : 0;
                }

                // --- 3. Order Status (Doughnut Chart) - This remains the same, queries 'orders' table ---
                $ordersByStatusQuery = (clone $orderStatusBaseQuery)
                    ->selectRaw('status, COUNT(id) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $allStatuses = [
                    Order::STATUS_MOI, Order::STATUS_CAN_XU_LY, Order::STATUS_CHO_HANG,
                    Order::STATUS_DA_DAT_HANG, Order::STATUS_CHO_CHUYEN_HANG, Order::STATUS_DA_GUI_HANG,
                    Order::STATUS_DA_NHAN, Order::STATUS_DA_NHAN_DOI, Order::STATUS_DA_THU_TIEN,
                    Order::STATUS_DA_HOAN, Order::STATUS_DA_HUY, Order::STATUS_XOA_GAN_DAY
                ];
                $orderStatusLabels = [];
                $orderStatusData = [];
                $tempOrderInstance = new Order();
                foreach ($allStatuses as $statusKey) {
                    $tempOrderInstance->status = $statusKey;
                    $orderStatusLabels[] = $tempOrderInstance->getStatusText();
                    $orderStatusData[] = $ordersByStatusQuery->get($statusKey, 0);
                }
                $statusColors = [ // This mapping should ideally be in Order model or a config
                    Order::STATUS_MOI => '#007bff', Order::STATUS_CAN_XU_LY => '#ffc107',
                    Order::STATUS_CHO_HANG => '#17a2b8', Order::STATUS_DA_DAT_HANG => '#6f42c1',
                    Order::STATUS_CHO_CHUYEN_HANG => '#17a2b8', Order::STATUS_DA_GUI_HANG => '#6610f2',
                    Order::STATUS_DA_NHAN => '#28a745', Order::STATUS_DA_NHAN_DOI => '#28a745',
                    Order::STATUS_DA_THU_TIEN => '#20c997', Order::STATUS_DA_HOAN => '#6c757d',
                    Order::STATUS_DA_HUY => '#dc3545', Order::STATUS_XOA_GAN_DAY => '#343a40',
                    'completed' => '#00a65a', 'pending'   => '#f39c12', 'assigned'  => '#ff851b',
                    'calling'   => '#00c0ef', 'failed'    => '#dd4b39', 'no_answer' => '#777',
                    'canceled'  => '#d2d6de',
                ];
                $orderStatusBackgroundColors = [];
                foreach ($allStatuses as $statusKey) {
                    $orderStatusBackgroundColors[] = $statusColors[$statusKey] ?? '#adb5bd';
                }
                if (empty($orderStatusData) || !array_filter($orderStatusData)) {
                    $orderStatusLabels = []; $orderStatusData = []; $orderStatusBackgroundColors = [];
                }

                // --- 4. Revenue by Staff (Bar Chart) - Conditional Display ---
                $staffRevenueDetails = [
                    'labels' => [],
                    'data' => [],
                    'should_display' => false,
                ];

                $isFilteredBySale = !empty($saleId);
                $isFilteredByManager = !empty($managerId);
                // User is manager, viewing their own team, and no overriding admin filters for a specific sale/other manager
                $isManagerViewingOwnTeamGeneral = $user->hasRole('manager') && $user->manages_team_id && !$isFilteredBySale && !$isFilteredByManager;

                // Determine if this chart should be displayed based on filters
                if ($isFilteredBySale || $isFilteredByManager || $isManagerViewingOwnTeamGeneral) {
                    $staffRevenueDetails['should_display'] = true;
                }

                if ($staffRevenueDetails['should_display']) {
                    // $aggQuery is already filtered by date, the logged-in user's role (admin, manager, staff),
                    // and specific filters like saleId or managerId.
                    // Now, we group its results by user_id to get per-staff revenue.
                    $revenueByStaffQuery = (clone $aggQuery)
                        ->join('users', 'daily_revenue_aggregates.user_id', '=', 'users.id')
                        ->select(
                            'users.name as staff_name',
                            'daily_revenue_aggregates.user_id',
                            DB::raw('SUM(daily_revenue_aggregates.total_revenue) as revenue')
                        )
                        ->groupBy('daily_revenue_aggregates.user_id', 'users.name')
                        ->orderByDesc('revenue')
                        ->get();

                    if (!$revenueByStaffQuery->isEmpty()) {
                        $staffRevenueDetails['labels'] = $revenueByStaffQuery->pluck('staff_name')->all();
                        $staffRevenueDetails['data'] = $revenueByStaffQuery->pluck('revenue')->map(fn($val) => (float)$val)->all();
                        // Keep should_display as true because we found data under the filter conditions
                    } else {
                        // If no data was found:
                        // - If it was a specific $saleId filter, it means that sale had 0 revenue. Still display (empty or 0 chart).
                        // - Otherwise (manager filter or manager view yielded no staff with revenue), hide the chart.
                        if (!$isFilteredBySale) {
                            $staffRevenueDetails['should_display'] = false;
                        }
                    }
                }

                $responseArray = [
                    'revenueDaily' => [
                        'labels' => $revenueDailyLabels,
                        'data' => $revenueDailyData,
                    ],
                    'revenueMonthly' => [
                        'labels' => $revenueMonthlyLabels,
                        'data' => $revenueMonthlyData,
                    ],
                    'orderStatusPie' => [
                        'labels' => $orderStatusLabels,
                        'data' => $orderStatusData,
                        'colors' => $orderStatusBackgroundColors
                    ],
                    'staffRevenueDetails' => $staffRevenueDetails,
                ];

                // --- Staff Specific Stat Boxes Data (Revised for custom date range) ---
                $responseArray['staff_specific_stats'] = [
                    'show' => false,
                    'entity_name' => '',
                    'period_label' => '',
                    'total_revenue_formatted' => '0',
                    'total_completed_orders' => 0,
                ];

                $potentialTargetUserIds = collect();
                $entityNameForStats = '';
                $canShowSpecificStats = false;

                // This section is primarily for Super Admin or Manager viewing specific staff in custom range
                if ($saleId) {
                    $selectedStaffUser = User::find($saleId);
                    if ($selectedStaffUser && $selectedStaffUser->hasRole('staff')) {
                        // Super Admin can see any staff. Manager can see staff in their team.
                        if ($user->hasRole(['admin', 'super-admin']) ||
                            ($user->hasRole('manager') && $user->manages_team_id && $selectedStaffUser->team_id == $user->manages_team_id)) {
                            $potentialTargetUserIds->push($selectedStaffUser->id);
                            $entityNameForStats = $selectedStaffUser->name;
                            $canShowSpecificStats = true;
                        }
                    }
                } elseif ($managerId && $user->hasRole(['admin', 'super-admin'])) {
                    // Only Super Admin can use manager_id filter for these specific stat boxes for a whole team.
                    $manager = User::find($managerId);
                    if ($manager && $manager->manages_team_id) {
                        $teamMemberIds = User::where('team_id', $manager->manages_team_id)
                                             ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                                             ->pluck('id');
                        if ($teamMemberIds->isNotEmpty()) {
                            $potentialTargetUserIds = $teamMemberIds;
                            $entityNameForStats = "Nhóm của " . $manager->name;
                            $canShowSpecificStats = true;
                        }
                    }
                }

                if ($canShowSpecificStats && $potentialTargetUserIds->isNotEmpty()) {
                    $statsQuery = DailyRevenueAggregate::query()
                                    ->whereBetween('aggregation_date', [$start, $end]) // Use selected date range ($start, $end from outer scope)
                                    ->whereIn('user_id', $potentialTargetUserIds);

                    $totalRevenueInRange = $statsQuery->sum('total_revenue');
                    $totalOrdersInRange = (clone $statsQuery)->sum('completed_orders_count'); // Clone for fresh sum

                    $responseArray['staff_specific_stats']['show'] = true;
                    $responseArray['staff_specific_stats']['entity_name'] = $entityNameForStats;

                    $periodLabel = $start->format('d/m/Y');
                    if (!$start->isSameDay($end)) {
                        $periodLabel .= ' - ' . $end->format('d/m/Y');
                    }
                    $responseArray['staff_specific_stats']['period_label'] = $periodLabel;
                    $responseArray['staff_specific_stats']['total_revenue_formatted'] = number_format($totalRevenueInRange ?? 0, 0, ',', '.');
                    $responseArray['staff_specific_stats']['total_completed_orders'] = $totalOrdersInRange ?? 0;
                }

                return $responseArray; // Ensure the modified array is returned
            });
            return response()->json($data);
        } catch (\Exception $e) {
            logger()->error('Dashboard Chart Error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Lỗi tải dữ liệu biểu đồ. Vui lòng thử lại.',
            ], 500);
        }
    }

    /**
     * Get staff list for a specific manager via AJAX.
     * Only accessible by Admin/Super-Admin.
     */
    public function getStaffByManager(Request $request, User $manager)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Ensure only Admin/Super-Admin can use this for any manager
        // Or a manager can get their own staff (though usually they have it already)
        if (!$currentUser->hasRole(['admin', 'super-admin'])) {
            // If it's the manager themselves, they might be re-filtering their own view
            // However, this endpoint is designed for an admin to select a manager and see their staff.
            // If a manager is logged in, their $filterableStaff is already scoped.
            // For simplicity, let's restrict this AJAX endpoint to admins for now when a managerId is passed.
            if ($currentUser->id != $manager->id) { // Prevent non-admin from fetching other manager's staff
                 return response()->json([
                    'error' => 'Không có quyền truy cập.',
                    'staff' => [],
                ], 403);
            }
        }

        $staffList = collect();
        if ($manager && $manager->manages_team_id) {
            $staffList = User::where('team_id', $manager->manages_team_id)
                               ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                               ->orderBy('name')
                               ->select('id', 'name') // Select only id and name
                               ->get();
        }

        return response()->json([
            'staff' => $staffList
        ]);
    }
}
