<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PancakePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pancake_id',
        'pancake_page_id',
        'pancake_shop_table_id',
        'shop_id',
        'name',
        'platform',
        'settings',
        'raw_data',
    ];

    protected $casts = [
        'pancake_id' => 'string',
        'pancake_page_id' => 'string',
        'shop_id' => 'string',
        'settings' => 'array',
        'raw_data' => 'array',
    ];

    /**
     * Get the Pancake shop that owns the page.
     */
    public function pancakeShop(): BelongsTo
    {
        return $this->belongsTo(PancakeShop::class, 'pancake_shop_table_id');
    }

    /**
     * Scope a query to only include pages with a specific pancake_id.
     */
    public function scopeByPancakeId($query, $pancakeId)
    {
        return $query->where('pancake_id', $pancakeId)
                     ->orWhere('pancake_page_id', $pancakeId);
    }
}
