<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignReport extends Model
{
    protected $fillable = [
        'campaign_name',
        'post_id',
        'total_revenue',
        'total_orders',
        'conversion_rate'
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
        'conversion_rate' => 'integer'
    ];
}
