<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PancakeShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'pancake_id',
        'name',
        'avatar_url',
        'raw_data',
    ];

    protected $casts = [
        'pancake_id' => 'integer',
        'raw_data' => 'array',
    ];

    /**
     * Get the pages associated with the Pancake shop.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(PancakePage::class, 'pancake_shop_table_id');
    }
}
