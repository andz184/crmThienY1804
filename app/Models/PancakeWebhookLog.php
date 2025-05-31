<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PancakeWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'source_ip',
        'request_data',
        'processed_data',
        'status',
        'error_message',
        'order_id',
        'customer_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'processed_data' => 'array',
    ];

    /**
     * Get the order associated with the webhook log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'pancake_order_id');
    }

    /**
     * Get the customer associated with the webhook log.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'pancake_id');
    }
}
