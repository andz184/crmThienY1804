<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSessionAggregate extends Model
{
    protected $fillable = [
        'live_session_id',
        'session_date',
        'total_orders',
        'successful_orders',
        'canceled_orders',
        'delivering_orders',
        'revenue',
        'total_customers',
        'customer_ids',
        'product_stats',
        'daily_stats',
        'hourly_stats',
        'product_chart_data',
        'status_chart_data',
        'revenue_chart_data',
        'last_order_date',
        'last_aggregated_at'
    ];

    protected $casts = [
        'session_date' => 'date',
        'customer_ids' => 'array',
        'product_stats' => 'array',
        'daily_stats' => 'array',
        'hourly_stats' => 'array',
        'product_chart_data' => 'array',
        'status_chart_data' => 'array',
        'revenue_chart_data' => 'array',
        'last_order_date' => 'datetime',
        'last_aggregated_at' => 'datetime'
    ];
}
