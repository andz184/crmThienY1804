<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LiveSessionStats extends Model
{
    protected $fillable = [
        'live_session_id',
        'live_session_date',
        'live_number',
        'session_name',
        'total_orders',
        'successful_orders',
        'canceled_orders',
        'delivering_orders',
        'total_revenue',
        'conversion_rate',
        'cancellation_rate',
        'delivering_rate',
        'total_customers',
        'new_customers',
        'returning_customers',
        'top_products',
        'orders_by_status',
        'last_calculated_at'
    ];

    protected $casts = [
        'live_session_date' => 'date',
        'total_orders' => 'integer',
        'successful_orders' => 'integer',
        'canceled_orders' => 'integer',
        'delivering_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cancellation_rate' => 'decimal:2',
        'delivering_rate' => 'decimal:2',
        'total_customers' => 'integer',
        'new_customers' => 'integer',
        'returning_customers' => 'integer',
        'top_products' => 'array',
        'orders_by_status' => 'array',
        'last_calculated_at' => 'datetime'
    ];

    /**
     * Scope a query to filter by date range
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by live number
     */
    public function scopeByLiveNumber($query, $liveNumber)
    {
        return $query->where('live_number', $liveNumber);
    }

    /**
     * Get sessions with calculated metrics for a specific period
     */
    public static function getSessionMetrics($startDate, $endDate)
    {
        return self::query()
            ->select([
                'live_number',
                'session_date',
                'session_name',
                'total_orders',
                'successful_orders',
                'canceled_orders',
                'delivering_orders',
                'total_revenue',
                'success_rate',
                'cancel_rate',
                'total_customers',
                'new_customers',
                'returning_customers'
            ])
            ->inPeriod($startDate, $endDate)
            ->orderBy('session_date', 'desc')
            ->orderBy('live_number', 'asc')
            ->get();
    }

    /**
     * Get detailed stats for a specific session
     */
    public static function getSessionDetail($liveNumber, $sessionDate)
    {
        return self::query()
            ->where('live_number', $liveNumber)
            ->where('session_date', $sessionDate)
            ->first();
    }

    /**
     * Update or create session stats
     */
    public static function updateStats($liveNumber, $sessionDate, $data)
    {
        return self::updateOrCreate(
            [
                'live_number' => $liveNumber,
                'session_date' => $sessionDate
            ],
            array_merge($data, [
                'period_start' => Carbon::parse($sessionDate)->startOfDay(),
                'period_end' => Carbon::parse($sessionDate)->endOfDay()
            ])
        );
    }

    /**
     * Format session name
     */
    public static function formatSessionName($liveNumber, $sessionDate)
    {
        return "LIVE {$liveNumber} (" . Carbon::parse($sessionDate)->format('d/m/Y') . ")";
    }

    /**
     * Calculate rates based on order counts
     */
    public function calculateRates()
    {
        if ($this->total_orders > 0) {
            $this->conversion_rate = ($this->successful_orders / $this->total_orders) * 100;
            $this->cancellation_rate = ($this->canceled_orders / $this->total_orders) * 100;
            $this->delivering_rate = ($this->delivering_orders / $this->total_orders) * 100;
        }
    }

    /**
     * Format for revenue report display
     */
    public function toRevenueReport()
    {
        return [
            'id' => $this->live_session_id,
            'name' => $this->session_name,
            'live_number' => $this->live_number,
            'session_date' => $this->live_session_date->format('Y-m-d'),
            'year' => $this->live_session_date->year,
            'month' => $this->live_session_date->month,
            'day' => $this->live_session_date->day,
            'total_orders' => $this->total_orders,
            'successful_orders' => $this->successful_orders,
            'canceled_orders' => $this->canceled_orders,
            'delivering_orders' => $this->delivering_orders,
            'revenue' => $this->total_revenue,
            'total_customers' => $this->total_customers,
            'new_customers' => $this->new_customers,
            'returning_customers' => $this->returning_customers,
            'conversion_rate' => round($this->conversion_rate, 2),
            'cancellation_rate' => round($this->cancellation_rate, 2),
            'delivering_rate' => round($this->delivering_rate, 2),
            'orders_by_status' => $this->orders_by_status,
            'orders' => [], // Will be filled by service if needed
            'customers' => [] // Will be filled by service if needed
        ];
    }
}
