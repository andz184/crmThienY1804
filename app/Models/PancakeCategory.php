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
        'level',
        'status',
        'description',
        'image_url',
        'api_response',
    ];

    protected $casts = [
        'api_response' => 'array',
        'level' => 'integer',
    ];

    /**
     * Define many-to-many relationship with Product model
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'pancake_category_product')
                    ->withTimestamps();
    }

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(PancakeCategory::class, 'pancake_parent_id', 'pancake_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(PancakeCategory::class, 'pancake_parent_id', 'pancake_id');
    }

    /**
     * Get all ancestors of the category
     */
    public function ancestors()
    {
        $ancestors = collect();
        $current = $this;

        while ($current->parent) {
            $ancestors->push($current->parent);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants of the category
     */
    public function descendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Check if the category is a root category (has no parent)
     */
    public function isRoot()
    {
        return is_null($this->pancake_parent_id);
    }

    /**
     * Check if the category is a leaf category (has no children)
     */
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    /**
     * Get the full path of the category (including all parent names)
     */
    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $current = $this;

        while ($current->parent) {
            $path->prepend($current->parent->name);
            $current = $current->parent;
        }

        return $path->join(' > ');
    }

    // Nếu bạn có một bảng categories (CRM) riêng và muốn liên kết PancakeCategory với nó:
    // public function crmCategory()
    // {
    //     // Giả sử bảng 'categories' (CRM) có cột 'pancake_id' để map với pancake_id của PancakeCategory
    //     return $this->hasOne(Category::class, 'pancake_id', 'pancake_id');
    //     // Hoặc nếu PancakeCategory có cột 'category_id' tham chiếu đến id của bảng 'categories' (CRM)
    //     // return $this->belongsTo(Category::class, 'category_id', 'id');
    // }
}
