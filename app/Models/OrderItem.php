<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_name',
        'product_code',
        'code',
        'quantity',
        'pancake_product_id',
        'pancake_variation_id',
        'pancake_variant_id',
        'name',
        'price',
        'weight',
        'product_info',
        'total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'product_info' => 'array',
        'total' => 'decimal:2',
    ];

    /**
     * Get the total amount for this order item
     */
    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the variants for this order item
     */
    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'order_item_variants', 'order_item_id', 'pancake_variant_id')
            ->withPivot('quantity', 'price', 'variant_data')
            ->withTimestamps();
    }
}
