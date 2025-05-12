<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRevenueSummary extends Model
{
    use HasFactory;

    protected $table = 'daily_revenue_summaries';

    protected $fillable = [
        'summary_date',
        'total_revenue',
        'total_orders', // Tổng số đơn hàng được tính vào doanh thu
        'total_quantity_sold', // Tổng số lượng sản phẩm bán ra
        // Bạn có thể thêm các trường khác như: average_order_value
    ];

    protected $casts = [
        'summary_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
        'total_quantity_sold' => 'integer',
    ];

    // Không cần timestamps (created_at, updated_at) cho bảng này
    // vì mỗi ngày chỉ có một record và summary_date là đủ.
    // public $timestamps = false;
    // Tuy nhiên, nếu bạn muốn theo dõi khi record summary được tạo/cập nhật thì cứ để timestamps.
}
