<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_method',
        'total_revenue',
        'total_orders',
        'average_order_value',
        'completed_revenue',
        'completed_orders',
        'completion_rate',
        'report_date',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'completed_revenue' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'total_orders' => 'integer',
        'completed_orders' => 'integer',
        'report_date' => 'date',
    ];

    /**
     * Get formatted payment method name (human-readable)
     *
     * @return string
     */
    public function getFormattedPaymentMethodAttribute(): string
    {
        $methods = [
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'banking' => 'Chuyển khoản ngân hàng',
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'other' => 'Khác'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get orders associated with this payment method and date
     */
    public function orders()
    {
        return Order::where('payment_method', $this->payment_method)
            ->whereDate('created_at', $this->report_date);
    }
}
