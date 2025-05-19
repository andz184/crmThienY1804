<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGroupReport extends Model
{
    protected $fillable = [
        'group_name',
        'total_revenue',
        'total_orders',
        'total_quantity'
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
        'total_quantity' => 'integer'
    ];
}
