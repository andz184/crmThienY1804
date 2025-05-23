<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PancakeCategory extends Model
{
    use HasFactory;

    protected $table = 'pancake_categories';

    protected $fillable = [
        'pancake_id',
        'name',
        'pancake_parent_id',
        // 'parent_id', 
        'level',
        'status',
        'description',
        'image_url',
        'api_response',
    ];

    protected $casts = [
        'api_response' => 'array',
    ];

    // If you uncomment parent_id in the migration and here, define the relationship:
    // public function parent()
    // {
    //     return $this->belongsTo(PancakeCategory::class, 'parent_id');
    // }

    // public function children()
    // {
    //     return $this->hasMany(PancakeCategory::class, 'parent_id');
    // }
} 