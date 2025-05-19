<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrderReport extends Model
{
    protected $fillable = [
        'customer_id',
        'is_first_order',
        'order_value',
        'order_date'
    ];

    protected $casts = [
        'is_first_order' => 'boolean',
        'order_value' => 'decimal:2',
        'order_date' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
