<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $query = Order::query();

        // Áp dụng các bộ lọc
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        if ($request->filled('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->where('manager_id', $request->manager_id);
            });
        }

        // Doanh thu theo ngày
        $dailyRevenue = $query->clone()
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Doanh thu theo tháng
        $monthlyRevenue = $query->clone()
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Thống kê trạng thái đơn hàng
        $orderStatus = $query->clone()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'daily_revenue' => [
                'labels' => $dailyRevenue->pluck('date')->toArray(),
                'data' => $dailyRevenue->pluck('revenue')->toArray(),
            ],
            'monthly_revenue' => [
                'labels' => $monthlyRevenue->pluck('month')->toArray(),
                'data' => $monthlyRevenue->pluck('revenue')->toArray(),
            ],
            'order_status' => [
                'completed' => $orderStatus['completed'] ?? 0,
                'processing' => $orderStatus['processing'] ?? 0,
                'cancelled' => $orderStatus['cancelled'] ?? 0,
            ]
        ]);
    }
}
