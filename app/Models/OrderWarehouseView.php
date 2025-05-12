<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderWarehouseView extends Model
{
    protected $table = 'orders_warehouse_view';

    public $timestamps = false;

    // Thêm các constant status từ Order model
    public const STATUS_MOI = 'moi';
    public const STATUS_CAN_XU_LY = 'can_xu_ly';
    public const STATUS_CHO_HANG = 'cho_hang';
    public const STATUS_DA_DAT_HANG = 'da_dat_hang';
    public const STATUS_CHO_CHUYEN_HANG = 'cho_chuyen_hang';
    public const STATUS_DA_GUI_HANG = 'da_gui_hang';
    public const STATUS_DA_NHAN = 'da_nhan';
    public const STATUS_DA_NHAN_DOI = 'da_nhan_doi';
    public const STATUS_DA_THU_TIEN = 'da_thu_tien';
    public const STATUS_DA_HOAN = 'da_hoan';
    public const STATUS_DA_HUY = 'da_huy';
    public const STATUS_XOA_GAN_DAY = 'xoa_gan_day';

    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_phone',
        'status',
        'user_id',
        'total_value',
        'warehouse_id',
        'payment_method',
        'shipping_provider'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_value' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Hàm để lấy đơn hàng gốc
    public function originalOrder()
    {
        return Order::find($this->id);
    }

    /**
     * Get all possible status codes and their names.
     *
     * @return array
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_MOI => 'Mới',
            self::STATUS_CAN_XU_LY => 'Cần xử lý',
            self::STATUS_CHO_HANG => 'Chờ hàng',
            self::STATUS_DA_DAT_HANG => 'Đã đặt hàng',
            self::STATUS_CHO_CHUYEN_HANG => 'Chờ chuyển hàng',
            self::STATUS_DA_GUI_HANG => 'Đã gửi hàng',
            self::STATUS_DA_NHAN => 'Đã nhận',
            self::STATUS_DA_NHAN_DOI => 'Đã nhận (đổi)',
            self::STATUS_DA_THU_TIEN => 'Đã thu tiền',
            self::STATUS_DA_HOAN => 'Đã hoàn',
            self::STATUS_DA_HUY => 'Đã hủy',
            self::STATUS_XOA_GAN_DAY => 'Xóa gần đây',
        ];
    }

    /**
     * Get the status name for display
     */
    public function getStatusClass(): string
    {
        return match ($this->status) {
            self::STATUS_MOI => 'badge-primary',
            self::STATUS_CAN_XU_LY => 'badge-warning',
            self::STATUS_CHO_HANG => 'badge-info',
            self::STATUS_DA_DAT_HANG => 'badge-secondary',
            self::STATUS_CHO_CHUYEN_HANG => 'badge-info',
            self::STATUS_DA_GUI_HANG => 'badge-success',
            self::STATUS_DA_NHAN => 'badge-success',
            self::STATUS_DA_NHAN_DOI => 'badge-success',
            self::STATUS_DA_THU_TIEN => 'badge-success',
            self::STATUS_DA_HOAN => 'badge-danger',
            self::STATUS_DA_HUY => 'badge-danger',
            self::STATUS_XOA_GAN_DAY => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    /**
     * Get the status text for display
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_MOI => 'Mới',
            self::STATUS_CAN_XU_LY => 'Cần xử lý',
            self::STATUS_CHO_HANG => 'Chờ hàng',
            self::STATUS_DA_DAT_HANG => 'Đã đặt hàng',
            self::STATUS_CHO_CHUYEN_HANG => 'Chờ chuyển hàng',
            self::STATUS_DA_GUI_HANG => 'Đã gửi hàng',
            self::STATUS_DA_NHAN => 'Đã nhận',
            self::STATUS_DA_NHAN_DOI => 'Đã nhận (đổi)',
            self::STATUS_DA_THU_TIEN => 'Đã thu tiền',
            self::STATUS_DA_HOAN => 'Đã hoàn',
            self::STATUS_DA_HUY => 'Đã hủy',
            self::STATUS_XOA_GAN_DAY => 'Xóa gần đây',
            default => ucfirst(str_replace('_', ' ', $this->status ?? 'Không rõ')),
        };
    }
}
