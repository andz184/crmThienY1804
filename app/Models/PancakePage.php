<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PancakePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pancake_shop_table_id',
        'pancake_page_id',
        'name',
        'platform',
        'settings',
        'raw_data',
    ];

    protected $casts = [
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
}
