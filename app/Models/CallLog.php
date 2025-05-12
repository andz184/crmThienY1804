<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'voip_call_id',
        'sip_extension',
        'caller_number',
        'destination_number',
        'call_status',
        'call_type',
        'start_time',
        'duration_seconds',
        'recording_url',
        'notes',
        'raw_voip_data',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'raw_voip_data' => 'array',
    ];

    /**
     * Get the order that this call log belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user (employee) associated with this call log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
