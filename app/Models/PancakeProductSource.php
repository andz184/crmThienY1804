<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PancakeProductSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'pancake_id',
        'custom_id',
        'parent_id',
        'project_id',
        'shop_id',
        'link_source_id',
        'name',
        'type',
        'is_active',
        'is_removed',
        'raw_data',
        'inserted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
        'raw_data' => 'array',
        'inserted_at' => 'datetime',
    ];

    /**
     * Get the parent source
     */
    public function parent()
    {
        return $this->belongsTo(PancakeProductSource::class, 'parent_id');
    }

    /**
     * Get the children sources
     */
    public function children()
    {
        return $this->hasMany(PancakeProductSource::class, 'parent_id');
    }
}
