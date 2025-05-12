<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'customer_name',
        'phone_number',
        'call_duration',
        'notes',
        'call_time',
        'recording_url',
    ];

    protected $casts = [
        'call_time' => 'datetime',
    ];

    /**
     * Get the user that made the call.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the call.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
