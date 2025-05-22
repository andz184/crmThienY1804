<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PancakeStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pancake_id',
        'user_id_pancake',
        'profile_id',
        'email',
        'name',
        'phone',
        'fb_id',
        'avatar_url',
        'role',
        'shop_id',
        'is_assigned',
        'is_assigned_break_time',
        'enable_api',
        'api_key',
        'note_api_key',
        'app_warehouse',
        'department',
        'department_id',
        'preferred_shop',
        'profile',
        'pending_order_count',
        'permission_in_sale_group',
        'transaction_tags',
        'work_time',
        'creator',
        'pancake_inserted_at',
    ];

    protected $casts = [
        'is_assigned' => 'boolean',
        'is_assigned_break_time' => 'boolean',
        'enable_api' => 'boolean',
        'pending_order_count' => 'integer',
        'transaction_tags' => 'array',
        'pancake_inserted_at' => 'datetime',
    ];

    /**
     * Get the user this Pancake staff is associated with.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders this staff is assigned to.
     */
    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'assigning_seller_id', 'pancake_id');
    }
}
