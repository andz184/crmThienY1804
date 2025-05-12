<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRevenueAggregate extends Model
{
    use HasFactory;

    protected $table = 'daily_revenue_aggregates'; // Explicitly define table name

    protected $fillable = [
        'aggregation_date',
        'user_id',
        'total_revenue',
        'completed_orders_count',
    ];

    protected $casts = [
        'aggregation_date' => 'date',
        'total_revenue' => 'decimal:2',
        'completed_orders_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
