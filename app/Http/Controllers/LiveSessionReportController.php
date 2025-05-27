<?php

namespace App\Http\Controllers;

use App\Models\LiveSessionStats;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LiveSessionReportController extends Controller
{
    public function index()
    {
        return view('live-sessions.index');
    }

    public function getStats(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'session_id' => 'nullable|string'
        ]);

        $cacheKey = "live_session_stats:{$request->start_date}:{$request->end_date}:" . ($request->session_id ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(15), function() use ($request) {
            $query = LiveSessionStats::whereBetween('live_session_date', [
                $request->start_date,
                $request->end_date
            ]);

            if ($request->session_id) {
                $query->where('live_session_id', $request->session_id);
            }

            $stats = $query->get();

            return [
                'summary' => [
                    'total_revenue' => $stats->sum('total_revenue'),
                    'total_orders' => $stats->sum('total_orders'),
                    'successful_orders' => $stats->sum('successful_orders'),
                    'canceled_orders' => $stats->sum('canceled_orders'),
                    'total_customers' => $stats->sum('total_customers'),
                    'new_customers' => $stats->sum('new_customers'),
                    'avg_conversion_rate' => $stats->avg('conversion_rate'),
                    'avg_cancellation_rate' => $stats->avg('cancellation_rate')
                ],
                'daily_stats' => $stats->groupBy('live_session_date')
                    ->map(function($dayStats) {
                        return [
                            'total_revenue' => $dayStats->sum('total_revenue'),
                            'total_orders' => $dayStats->sum('total_orders'),
                            'successful_orders' => $dayStats->sum('successful_orders'),
                            'canceled_orders' => $dayStats->sum('canceled_orders'),
                            'sessions' => $dayStats->map(function($session) {
                                return [
                                    'session_id' => $session->live_session_id,
                                    'session_name' => $session->session_name,
                                    'total_revenue' => $session->total_revenue,
                                    'total_orders' => $session->total_orders,
                                    'successful_orders' => $session->successful_orders,
                                    'conversion_rate' => $session->conversion_rate,
                                    'top_products' => $session->top_products
                                ];
                            })
                        ];
                    }),
                'top_products_overall' => $this->calculateOverallTopProducts($stats)
            ];
        });
    }

    private function calculateOverallTopProducts($stats)
    {
        $allProducts = collect();

        foreach ($stats as $stat) {
            foreach ($stat->top_products as $product) {
                $existingProduct = $allProducts->firstWhere('product_id', $product['product_id']);

                if ($existingProduct) {
                    $existingProduct['total_quantity'] += $product['total_quantity'];
                    $existingProduct['total_revenue'] += $product['total_revenue'];
                } else {
                    $allProducts->push($product);
                }
            }
        }

        return $allProducts->sortByDesc('total_revenue')
            ->take(10)
            ->values()
            ->toArray();
    }
}
