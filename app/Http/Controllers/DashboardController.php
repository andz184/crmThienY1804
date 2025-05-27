<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\DailyRevenueAggregate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Import DB facade
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Ensure Carbon is imported

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('dashboard.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $statsData = []; // Renamed from $stats to $statsData to avoid conflict if $stats was used later

        $today = Carbon::today();
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        // Define Pancake revenue-eligible statuses
        $revenuePancakeStatuses = [
            Order::PANCAKE_STATUS_DELIVERED,
            Order::PANCAKE_STATUS_DONE,
            Order::PANCAKE_STATUS_COMPLETED
        ];

        // Base Order Query using pancake_status
        $orderQuery = Order::query()->whereIn('pancake_status', $revenuePancakeStatuses);

        if ($user->hasRole('manager')) {
            $teamId = $user->manages_team_id;
            if ($teamId) {
                $teamMemberIds = User::where('team_id', $teamId)->pluck('id');
                $orderQuery->whereIn('user_id', $teamMemberIds);
            } else {
                $orderQuery->whereRaw('1 = 0'); // No results if manager has no team
            }
        } elseif ($user->hasRole('staff')) {
            $orderQuery->where('user_id', $user->id);
        }
        // Admin/Super Admin sees all by default

        // 1. Doanh thu tháng này (Pancake status)
        // Assuming updated_at reflects the date when pancake_status reached a final state
        $statsData['monthly_revenue'] = (clone $orderQuery)
            ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_value');

        // 2. Doanh thu hôm nay (Pancake status)
        $statsData['today_revenue'] = (clone $orderQuery)
            ->whereDate('updated_at', $today)
            ->sum('total_value');

        // 3. Số đơn hoàn thành hôm nay (Pancake status)
        $statsData['today_completed_orders'] = (clone $orderQuery)
            ->whereDate('updated_at', $today)
            ->count();

        // Format numbers
        $statsData['monthly_revenue_formatted'] = number_format($statsData['monthly_revenue'] ?? 0, 0, ',', '.');
        $statsData['today_revenue_formatted'] = number_format($statsData['today_revenue'] ?? 0, 0, ',', '.');

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
        return view('dashboard', ['stats' => $statsData, 'filterableStaff' => $filterableStaff, 'filterableManagers' => $filterableManagers]);
    }

    /**
     * Get data for dashboard charts via AJAX.
     */
    public function getChartData(Request $request)
    {
        $this->authorize('dashboard.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        Log::info('getChartData: Fetching dynamic revenue data based on pancake_status.', [
            'user_id' => $user->id,
            'start_date_input' => $startDateInput,
            'end_date_input' => $endDateInput
        ]);

        $maxDays = 365; // Allow up to a year for date range
        try {
            $start = $startDateInput ? \Carbon\Carbon::parse($startDateInput)->startOfDay() : now()->subDays(29)->startOfDay();
            $end = $endDateInput ? \Carbon\Carbon::parse($endDateInput)->endOfDay() : now()->endOfDay();

            if ($end->greaterThan(now()->endOfDay())) $end = now()->endOfDay(); // Cap end date to today
            if ($start->greaterThan($end)) $start = $end->copy()->subDays(29)->startOfDay(); // Ensure start is before end
            if ($start->diffInDays($end) > $maxDays) $start = $end->copy()->subDays($maxDays)->startOfDay(); // Limit range

        } catch (\Exception $e) {
            Log::error('getChartData: Date parsing error', ['exception' => $e]);
            $start = now()->subDays(29)->startOfDay();
            $end = now()->endOfDay();
        }

        Log::info('getChartData: Date range calculated', ['start' => $start->toDateString(), 'end' => $end->toDateString()]);

        try {
            $revenuePancakeStatuses = [
                Order::PANCAKE_STATUS_DELIVERED,
                Order::PANCAKE_STATUS_DONE,
                Order::PANCAKE_STATUS_COMPLETED
            ];

            $orderQuery = Order::query()
                ->whereIn('pancake_status', $revenuePancakeStatuses)
                ->whereBetween('updated_at', [$start, $end]); // Assuming updated_at is when status changed to final

            // Apply scoping based on user role and filters from request
            $selectedSaleId = $request->input('sale_id');
            $selectedManagerId = $request->input('manager_id');

            if ($selectedSaleId) {
                $orderQuery->where('user_id', $selectedSaleId);
            } elseif ($selectedManagerId) {
                $manager = User::find($selectedManagerId);
                if ($manager && $manager->manages_team_id) {
                    $teamMemberIds = User::where('team_id', $manager->manages_team_id)->pluck('id');
                    $orderQuery->whereIn('user_id', $teamMemberIds);
                } else {
                    $orderQuery->whereRaw('1 = 0'); // No data if manager not found or no team
                }
            } elseif ($user->hasRole('manager')) {
                $teamId = $user->manages_team_id;
                if ($teamId) {
                    $teamMemberIds = User::where('team_id', $teamId)->pluck('id');
                    $orderQuery->whereIn('user_id', $teamMemberIds);
                } else {
                    $orderQuery->whereRaw('1 = 0'); // Manager with no team sees no data
                }
            } elseif ($user->hasRole('staff')) {
                $orderQuery->where('user_id', $user->id);
            }
            // Admin/Super Admin sees all data if no specific sale/manager filter is applied

            // Calculate Total Filtered Revenue
            $totalFilteredRevenue = (clone $orderQuery)->sum('total_value');

            // 1. Revenue by Day (Pancake Status)
            $revenueByDaySource = (clone $orderQuery)
                ->selectRaw('DATE(updated_at) as order_date, SUM(total_value) as revenue')
                ->groupBy('order_date')
                ->orderBy('order_date', 'asc')
                ->pluck('revenue', 'order_date');

            $revenueDailyLabels = [];
            $revenueDailyData = [];
            $currentDate = $start->copy();
            while ($currentDate <= $end) {
                $formattedDate = $currentDate->format('Y-m-d');
                $revenueDailyLabels[] = $currentDate->format('d/m');
                $revenueDailyData[] = (float)($revenueByDaySource[$formattedDate] ?? 0);
                $currentDate->addDay();
            }
            Log::info('getChartData: Revenue by Day calculated from DB', ['count' => count($revenueDailyData)]);

            $dataToReturn = [
                'totalFilteredRevenueFormatted' => number_format($totalFilteredRevenue ?? 0, 0, ',', '.'),
                'revenueDailyLabels' => $revenueDailyLabels,
                'revenueDailyData' => $revenueDailyData,
                // Return empty structures for other charts to prevent JS errors
                'revenueMonthlyLabels' => [], 'revenueMonthlyData' => [],
                'orderStatusChart' => ['labels' => [], 'data' => [], 'colors' => []],
                'staffRevenueChart' => ['enabled' => false, 'labels' => [], 'data' => []],
                'staffSpecificStats' => ['show_staff_specific_stats' => false, 'title_name' => null, 'total_revenue_formatted' => '0', 'total_orders' => 0 ],
            ];
            return response()->json($dataToReturn);

        } catch (\Throwable $e) { // Changed to Throwable to catch more error types
            Log::error("Dashboard Chart Data (Simplified Dynamic): " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Could not load chart data. Error: ' . $e->getMessage()], 500);
        }
    }

    // Helper function to map status class to a color for Chart.js
    private function getStatusColor(string $statusClass): string
    {
        // Map AdminLTE badge classes to hex colors for Chart.js
        return match ($statusClass) {
            'badge-primary' => '#007bff',
            'badge-warning' => '#ffc107',
            'badge-info' => '#17a2b8',
            'badge-purple' => '#6f42c1',
            'badge-indigo' => '#6610f2',
            'badge-success' => '#28a745',
            'badge-secondary' => '#6c757d',
            'badge-danger' => '#dc3545',
            'badge-dark' => '#343a40',
            'badge-light' => '#f8f9fa', // Consider a border for light colors on light backgrounds
            default => '#6c757d', // Default to secondary for unknown classes
        };
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
