<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'notes',
        'full_address',
        'province',
        'district',
        'ward',
        'street_address',
        'first_order_date',
        'last_order_date',
        'total_orders_count',
        'total_spent',
        'pancake_id',
        'date_of_birth',
        'gender',
        'fb_id',
        'referral_code',
        'reward_point',
        'succeed_order_count',
        'last_order_at',
        'tags',
        'addresses'
    ];

    protected $casts = [
        'first_order_date' => 'date',
        'last_order_date' => 'date',
        'last_order_at' => 'datetime',
        'date_of_birth' => 'date',
        'total_orders_count' => 'integer',
        'succeed_order_count' => 'integer',
        'total_spent' => 'decimal:2',
        'reward_point' => 'decimal:2',
        'tags' => 'array',
        'addresses' => 'array'
    ];

    /**
     * Get all orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the latest order for the customer.
     */
    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latest('pancake_inserted_at');
    }

    /**
     * Get all phone numbers for the customer.
     */
    public function phones(): HasMany
    {
        return $this->hasMany(CustomerPhone::class);
    }

    /**
     * Get the primary phone number for the customer.
     */
    public function primaryPhone()
    {
        return $this->hasOne(CustomerPhone::class)->where('is_primary', true);
    }

    /**
     * Get the primary phone number string.
     */
    public function getPrimaryPhoneAttribute()
    {
        $primaryPhone = $this->primaryPhone()->first();
        return $primaryPhone ? $primaryPhone->phone_number : null;
    }

    /**
     * Get the formatted full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_address,
            $this->ward,
            $this->district,
            $this->province
        ]);
        return implode(', ', $parts) ?: 'N/A';
    }

    /**
     * Get the success rate of orders.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_orders_count === 0) {
            return 0;
        }
        $successfulOrders = $this->orders()->whereIn('status', ['completed', 'delivered'])->count();
        return round(($successfulOrders / $this->total_orders_count) * 100, 2);
    }

    /**
     * Get the formatted total spent amount.
     */
    public function getFormattedTotalSpentAttribute(): string
    {
        return number_format($this->total_spent, 0, '.', ',') . 'đ';
    }

    /**
     * Get the formatted first order date.
     */
    public function getFormattedFirstOrderDateAttribute(): string
    {
        return $this->first_order_date ? $this->first_order_date->format('d/m/Y') : 'N/A';
    }

    /**
     * Get the formatted last order date.
     */
    public function getFormattedLastOrderDateAttribute(): string
    {
        return $this->last_order_date ? $this->last_order_date->format('d/m/Y') : 'N/A';
    }

    /**
     * Get the formatted created date.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Get the formatted updated date.
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return $this->updated_at->format('d/m/Y H:i');
    }

    /**
     * Scope a query to only include customers with orders.
     */
    public function scopeHasOrders($query)
    {
        return $query->has('orders');
    }

    /**
     * Scope a query to only include customers without orders.
     */
    public function scopeNoOrders($query)
    {
        return $query->doesntHave('orders');
    }

    /**
     * Scope a query to only include customers with successful orders.
     */
    public function scopeHasSuccessfulOrders($query)
    {
        return $query->whereHas('orders', function ($q) {
            $q->whereIn('status', ['completed', 'delivered']);
        });
    }

    /**
     * Scope a query to only include customers with failed orders.
     */
    public function scopeHasFailedOrders($query)
    {
        return $query->whereHas('orders', function ($q) {
            $q->whereIn('status', ['failed', 'canceled']);
        });
    }

    /**
     * Scope a query to only include customers with pending orders.
     */
    public function scopeHasPendingOrders($query)
    {
        return $query->whereHas('orders', function ($q) {
            $q->whereIn('status', ['pending', 'assigned', 'calling']);
        });
    }

    /**
     * Scope: chỉ lấy khách hàng còn hoạt động (có đơn gần đây)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: khách hàng không hoạt động X ngày
     */
    public function scopeInactive($query, $days = 90)
    {
        return $query->whereDate('last_order_date', '<=', now()->subDays($days));
    }

    /**
     * Giá trị trung bình đơn hàng
     */
    public function getAverageOrderValueAttribute()
    {
        if ($this->total_orders_count == 0) return 0;
        return round($this->total_spent / $this->total_orders_count, 0);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getFacebookProfileUrlAttribute()
    {
        if ($this->facebook_id) {
            return "https://facebook.com/{$this->facebook_id}";
        }
        return $this->facebook_url;
    }

    public function scopeVip($query)
    {
        return $query->where('customer_type', 'VIP');
    }

    public function scopeCanContact($query)
    {
        return $query->where('can_contact', true);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeSpentMoreThan($query, $amount)
    {
        return $query->where('total_spent', '>', $amount);
    }

    public function scopeOrderedMoreThan($query, $count)
    {
        return $query->where('total_orders_count', '>', $count);
    }

    public function scopeLastOrderBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('last_order_date', [$startDate, $endDate]);
    }

    // You might add accessors here, for example, to get a formatted address
    // or to calculate customer lifetime value if needed beyond total_spent.
}
