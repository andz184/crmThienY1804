<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPhone extends Model
{
    protected $fillable = [
        'customer_id',
        'phone_number',
        'is_primary',
        'type',
        'notes'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    /**
     * Get the customer that owns the phone number.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
