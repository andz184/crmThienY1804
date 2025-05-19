<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSessionReport extends Model
{
    protected $fillable = [
        'live_session_id',
        'session_date',
        'total_orders',
        'total_revenue',
        'total_customers',
        'top_products',
        'notes'
    ];

    protected $casts = [
        'session_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
        'total_customers' => 'integer',
        'top_products' => 'json'
    ];

    /**
     * Lấy danh sách sản phẩm bán chạy trong phiên live
     *
     * @return array
     */
    public function getTopProductsAsArray()
    {
        return json_decode($this->top_products, true) ?: [];
    }

    /**
     * Lấy các đơn hàng liên quan đến phiên live này
     */
    public function orders()
    {
        return Order::where('live_session_id', $this->live_session_id)
            ->where('live_session_date', $this->session_date);
    }

    /**
     * Lấy thông tin chi tiết phiên live bao gồm danh sách đơn hàng và sản phẩm
     *
     * @return array
     */
    public function getDetailedInfo()
    {
        return [
            'session_info' => $this->toArray(),
            'orders' => $this->orders()->with('customer')->get(),
            'products' => $this->getTopProductsAsArray()
        ];
    }
}
