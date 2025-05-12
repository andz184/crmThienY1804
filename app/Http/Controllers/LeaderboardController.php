<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\DailyRevenueAggregate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('leaderboard.view'); // Assuming a permission - REMOVED FOR PUBLIC ACCESS
        $period = $request->input('period', 'month'); // 'month' or 'year'
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $query = DailyRevenueAggregate::query()
            ->join('users', 'daily_revenue_aggregates.user_id', '=', 'users.id')
            ->select(
                'daily_revenue_aggregates.user_id',
                'users.name as user_name',
                DB::raw('SUM(daily_revenue_aggregates.total_revenue) as total_revenue')
            )
            ->groupBy('daily_revenue_aggregates.user_id', 'users.name')
            ->orderByDesc('total_revenue');

        if ($period === 'month') {
            $query->whereYear('daily_revenue_aggregates.aggregation_date', $year)
                  ->whereMonth('daily_revenue_aggregates.aggregation_date', $month);
            $currentPeriodLabel = Carbon::create($year, $month)->format('F Y');
        } else { // year
            $query->whereYear('daily_revenue_aggregates.aggregation_date', $year);
            $currentPeriodLabel = "Năm " . $year;
        }

        $leaderboardData = $query->get();

        $top3 = $leaderboardData->take(3);
        $fullList = $leaderboardData;

        // Data for filters - availableYears can come from daily_revenue_aggregates or orders
        // For consistency and future performance, using daily_revenue_aggregates is better if populated historically.
        // If daily_revenue_aggregates might not have full history, orders table is safer for now.
        $availableYears = DailyRevenueAggregate::select(DB::raw('YEAR(aggregation_date) as year'))
                               ->distinct()
                               ->orderBy('year', 'desc')
                               ->pluck('year');
        if ($availableYears->isEmpty()) { // Fallback if no aggregate data yet
            $availableYears = Order::select(DB::raw('YEAR(created_at) as year'))
                                   ->distinct()
                                   ->orderBy('year', 'desc')
                                   ->pluck('year');
        }

        $availableMonths = range(1, 12);

        return view('leaderboard.index', compact(
            'top3',
            'fullList',
            'period',
            'year',
            'month',
            'availableYears',
            'availableMonths',
            'currentPeriodLabel'
        ));
    }
}
