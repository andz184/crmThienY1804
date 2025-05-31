<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Optional: if you want soft deletes

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id', // For sub-categories
        'is_active',
        'pancake_id'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all products associated with this category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the corresponding Pancake category.
     */
    public function pancakeCategory()
    {
        return $this->belongsTo(PancakeCategory::class, 'pancake_id', 'pancake_id');
    }
}
