<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'stock_quantity',
        'is_active',
        'pancake_id',
        'attributes'
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'array'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }
}
