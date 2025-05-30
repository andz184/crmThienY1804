<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PancakeOrderSource extends Model
{
    protected $fillable = [
        'pancake_id',
        'name',
        'platform',
        'is_active',
        'raw_data'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'raw_data' => 'array'
    ];
}
