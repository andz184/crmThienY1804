<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pancake_variant_id',
        'pancake_product_id',
        'name',
        'sku',
        'price',
        'cost',
        'stock',
        'category_ids',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'attributes' => 'array',
        'metadata' => 'array',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the product that owns this variant
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'pancake_product_id', 'pancake_id');
    }

    /**
     * Get the order items for this variant
     */
    public function orderItems()
    {
        return $this->belongsToMany(OrderItem::class, 'order_item_variants', 'pancake_variant_id', 'order_item_id')
            ->withPivot('quantity', 'price', 'variant_data')
            ->withTimestamps();
    }

    /**
     * Get the categories for this variant
     */
    public function categories()
    {
        return $this->belongsToMany(PancakeCategory::class, 'product_variant_categories', 'variant_id', 'category_id');
    }
}
