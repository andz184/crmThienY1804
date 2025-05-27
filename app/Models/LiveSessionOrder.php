<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSessionOrder extends Model
{
    protected $fillable = [
        'order_id',
        'live_session_id',
        'live_session_date',
        'customer_id',
        'customer_name',
        'shipping_address',
        'total_amount'
    ];

    protected $casts = [
        'live_session_date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LiveSessionOrderItem::class);
    }
}
