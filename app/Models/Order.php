<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
// Removed: use App\Models\ProductVariation;
use App\Models\CallLog;
// use App\Traits\Loggable; // Temporarily commented out to resolve fatal error
use App\Traits\LogsActivity;
use App\Models\ActivityLog;
use App\Models\PancakeOrderStatus;
use App\Models\PancakeStaff;

class Order extends Model
{
    // use HasFactory, SoftDeletes, Loggable; // Temporarily commented out
    use HasFactory, SoftDeletes, LogsActivity;

    // New Status Constants
    public const STATUS_MOI = 'moi'; // Mới
    public const STATUS_CAN_XU_LY = 'can_xu_ly'; // Cần xử lý
    public const STATUS_CHO_HANG = 'cho_hang'; // Chờ hàng
    public const STATUS_DA_DAT_HANG = 'da_dat_hang'; // Đã đặt hàng
    public const STATUS_CHO_CHUYEN_HANG = 'cho_chuyen_hang'; // Chờ chuyển hàng
    public const STATUS_DA_GUI_HANG = 'da_gui_hang'; // Đã gửi hàng
    public const STATUS_DA_NHAN = 'da_nhan'; // Đã nhận
    public const STATUS_DA_NHAN_DOI = 'da_nhan_doi'; // Đã nhận (đổi)
    public const STATUS_DA_THU_TIEN = 'da_thu_tien'; // Đã thu tiền
    public const STATUS_DA_HOAN = 'da_hoan'; // Đã hoàn
    public const STATUS_DA_HUY = 'da_huy'; // Đã hủy
    public const STATUS_XOA_GAN_DAY = 'xoa_gan_day'; // Xóa gần đây (Soft Deleted)

    // Pancake Status Constants
    public const PANCAKE_STATUS_PENDING = 'pending'; // Chờ xử lý
    public const PANCAKE_STATUS_PROCESSING = 'processing'; // Đang xử lý
    public const PANCAKE_STATUS_WAITING = 'waiting'; // Chờ hàng
    public const PANCAKE_STATUS_SHIPPING = 'shipping'; // Đang giao
    public const PANCAKE_STATUS_DELIVERED = 'delivered'; // Đã giao
    public const PANCAKE_STATUS_DONE = 'done'; // Hoàn thành
    public const PANCAKE_STATUS_COMPLETED = 'completed'; // Hoàn thành
    public const PANCAKE_STATUS_CANCELED = 'canceled'; // Đã hủy

    // Old statuses (can be kept if still used internally or for migration, or removed if fully replaced)
    // public const STATUS_COMPLETED = 'completed'; (Covered by DA_THU_TIEN or DA_NHAN)
    // public const STATUS_CALLING = 'calling'; (May map to MOI or CAN_XU_LY)
    // public const STATUS_ASSIGNED = 'assigned'; (May map to MOI or CAN_XU_LY)
    // public const STATUS_PENDING = 'pending'; (May map to MOI or CHO_HANG)
    // public const STATUS_FAILED = 'failed'; (Potentially a sub-status or covered by DA_HUY)
    // public const STATUS_CANCELED = 'canceled'; (Covered by DA_HUY)
    // public const STATUS_NO_ANSWER = 'no_answer'; (Could be a note or a specific type of FAILED/CANCELED)


    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_fee',
        'payment_method',
        'shipping_provider_id',
        'pancake_shipping_provider_id',
        'internal_status',
        'notes',
        'additional_notes',
        'total_value',
        'status',
        'pancake_status',
        'user_id',
        'created_by',
        'province_code',
        'district_code',
        'ward_code',
        'street_address',
        'full_address',
        'warehouse_id',
        'pancake_warehouse_id',
        'warehouse_code',
        'pancake_shop_id',
        'pancake_page_id',
        'pancake_order_id',
        'post_id',
        'transfer_money',
        'pancake_push_status',
        // Campos adicionais para compatibilidade com Pancake
        'bill_full_name',
        'bill_phone_number',
        'bill_email',
        'is_free_shipping',
        'is_livestream',
        'is_live_shopping',
        'partner_fee',
        'customer_pay_fee',
        'returned_reason',
        'warehouse_info',
        'products_data',

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'product_info' => 'array', // Cast product_info to array/json
        'products_data' => 'array',
        'shipping_address_info' => 'array',
        'warehouse_info' => 'array'
    ];

    /**
     * Get the user (sale) that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user (creator) who created the order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the calls for the order.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(CallLog::class)->orderBy('start_time', 'desc');
    }

    /**
     * Get the product variation associated with the order.
     * THIS IS LIKELY OBSOLETE with the move to OrderItems & product_code.
     * Keeping it commented out for reference if needed later for migration.
     */
    // public function productVariation()
    // {
        // // If your orders.variation_id stores the product_variations.sku
        // return $this->belongsTo(ProductVariation::class, 'variation_id', 'sku');

        // // If your orders.variation_id was meant to store product_variations.id (primary key)
        // // return $this->belongsTo(ProductVariation::class, 'variation_id');
    // }

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
     * Get the CSS class for the order status badge.
     */
    public function getStatusClass(): string
    {
        // Prioritize soft deleted status display if applicable
        if ($this->trashed() && $this->status !== self::STATUS_XOA_GAN_DAY) {
            // If an order is soft-deleted but its status wasn't explicitly set to XOA_GAN_DAY,
            // we might still want to show it as such. Or, ensure status is updated upon soft deletion.
            // For now, let's assume status is updated to XOA_GAN_DAY by the delete process.
        }

        return match ($this->status) {
            self::STATUS_MOI => 'badge-primary', // Mới - Primary or Light Blue
            self::STATUS_CAN_XU_LY => 'badge-warning', // Cần xử lý - Orange/Yellow
            self::STATUS_CHO_HANG => 'badge-info', // Chờ hàng - Blue/Teal
            self::STATUS_DA_DAT_HANG => 'badge-purple', // Đã đặt hàng - Purple (custom or use AdminLTE colors)
            self::STATUS_CHO_CHUYEN_HANG => 'badge-info', // Chờ chuyển hàng - Similar to Chờ hàng or a step further
            self::STATUS_DA_GUI_HANG => 'badge-indigo', // Đã gửi hàng - Indigo (custom or use AdminLTE colors)
            self::STATUS_DA_NHAN => 'badge-success', // Đã nhận - Green
            self::STATUS_DA_NHAN_DOI => 'badge-success', // Đã nhận (đổi) - Green (could add a visual cue)
            self::STATUS_DA_THU_TIEN => 'badge-success font-weight-bold', // Đã thu tiền - Bold Green
            self::STATUS_DA_HOAN => 'badge-secondary', // Đã hoàn - Grey
            self::STATUS_DA_HUY => 'badge-danger', // Đã hủy - Red
            self::STATUS_XOA_GAN_DAY => 'badge-dark', // Xóa gần đây - Dark Grey/Black
            default => 'badge-light', // Fallback
        };
    }

    /**
     * Get the human-readable text for the order status.
     */
    public function getStatusText(): string
    {
        if ($this->trashed() && $this->status !== self::STATUS_XOA_GAN_DAY) {
             // If soft-deleted, always show "Xóa gần đây" unless status is already that.
             // This ensures consistency if an order was soft-deleted without its status field being updated.
            // However, best practice is to update status to XOA_GAN_DAY upon soft deletion.
            // return 'Xóa gần đây';
        }

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

    // You might want to add methods to check for specific status groups, e.g.:
    // public function isPendingStock(): bool
    // {
    //     return in_array($this->status, [self::STATUS_CHO_HANG, self::STATUS_DA_DAT_HANG]);
    // }

    // public function isShippedOrDelivered(): bool
    // {
    //     return in_array($this->status, [self::STATUS_DA_GUI_HANG, self::STATUS_DA_NHAN, self::STATUS_DA_NHAN_DOI, self::STATUS_DA_THU_TIEN]);
    // }

    // public function affectsInventoryOnHold(): bool // "Có thể bán"
    // {
    //     return $this->status === self::STATUS_MOI;
    // }

    // public function deductsActualInventory(): bool
    // {
    //     return $this->status === self::STATUS_CHO_CHUYEN_HANG;
    // }

    // public function returnsInventory(): bool
    // {
    //     return in_array($this->status, [self::STATUS_DA_HOAN, self::STATUS_DA_HUY, self::STATUS_XOA_GAN_DAY]);
    // }

    /**
     * Override the delete method to potentially update status to XOA_GAN_DAY.
     */
    // public function delete()
    // {
    //     DB::transaction(function () {
    //         if ($this->status !== self::STATUS_XOA_GAN_DAY) {
    //             $this->status = self::STATUS_XOA_GAN_DAY;
    //             $this->saveQuietly(); // Save without dispatching events if needed
    //         }
    //         parent::delete(); // Call SoftDeletes trait delete method
    //     });
    // }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the shipping provider for the order.
     */
    public function shippingProvider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    // Relationships for Province, District, Ward
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'code');
    }

    // Relationships to local PancakeShop and PancakePage tables
    public function pancakeShop(): BelongsTo
    {
        return $this->belongsTo(PancakeShop::class, 'pancake_shop_id');
    }

    public function pancakePage(): BelongsTo
    {
        return $this->belongsTo(PancakePage::class, 'pancake_page_id');
    }

    /**
     * Get the pancake order status relationship
     */
    public function pancakeOrderStatus(): BelongsTo
    {
        return $this->belongsTo(PancakeOrderStatus::class, 'pancake_status', 'status_code');
    }

    /**
     * Get the Pancake staff member assigned to the order.
     * This uses assigning_seller_id (which stores PancakeStaff->pancake_id) to link.
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigning_seller_id');
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class, 'model_id')
            ->where('model_type', Order::class)
            ->latest();
    }

    /**
     * Get all possible Pancake status codes and their names.
     *
     * @return array
     */
    public static function getAllPancakeStatuses(): array
    {
        return [
            self::PANCAKE_STATUS_PENDING => 'Chờ xử lý',
            self::PANCAKE_STATUS_PROCESSING => 'Đang xử lý',
            self::PANCAKE_STATUS_WAITING => 'Chờ hàng',
            self::PANCAKE_STATUS_SHIPPING => 'Đang giao',
            self::PANCAKE_STATUS_DELIVERED => 'Đã giao',
            self::PANCAKE_STATUS_DONE => 'Hoàn thành',
            self::PANCAKE_STATUS_COMPLETED => 'Hoàn thành',
            self::PANCAKE_STATUS_CANCELED => 'Đã hủy',
        ];
    }

    /**
     * Get the CSS class for the Pancake status badge.
     */
    public function getPancakeStatusClass(): string
    {
        if (!$this->pancake_status) {
            return 'badge-light';
        }

        // Try to get the color from the database first
        $status = PancakeOrderStatus::where('status_code', $this->pancake_status)->first();
        if ($status && $status->color) {
            return 'badge-' . $status->color;
        }

        // Fall back to the hardcoded mapping if not found
        return match ($this->pancake_status) {
            self::PANCAKE_STATUS_PENDING => 'badge-warning',
            self::PANCAKE_STATUS_PROCESSING => 'badge-info',
            self::PANCAKE_STATUS_WAITING => 'badge-secondary',
            self::PANCAKE_STATUS_SHIPPING => 'badge-primary',
            self::PANCAKE_STATUS_DELIVERED => 'badge-success',
            self::PANCAKE_STATUS_DONE, self::PANCAKE_STATUS_COMPLETED => 'badge-success font-weight-bold',
            self::PANCAKE_STATUS_CANCELED => 'badge-danger',
            default => 'badge-light',
        };
    }

    /**
     * Get the human-readable text for the Pancake status.
     */
    public function getPancakeStatusText(): string
    {
        if (!$this->pancake_status) {
            return 'Không có';
        }

        // Try to get the name from the database first
        $status = PancakeOrderStatus::where('status_code', $this->pancake_status)->first();
        if ($status) {
            return $status->name;
        }

        // Fall back to the hardcoded mapping if not found
        return match ($this->pancake_status) {
            self::PANCAKE_STATUS_PENDING => 'Chờ xử lý',
            self::PANCAKE_STATUS_PROCESSING => 'Đang xử lý',
            self::PANCAKE_STATUS_WAITING => 'Chờ hàng',
            self::PANCAKE_STATUS_SHIPPING => 'Đang giao',
            self::PANCAKE_STATUS_DELIVERED => 'Đã giao',
            self::PANCAKE_STATUS_DONE, self::PANCAKE_STATUS_COMPLETED => 'Hoàn thành',
            self::PANCAKE_STATUS_CANCELED => 'Đã hủy',
            default => $this->pancake_status ? ucfirst(str_replace('_', ' ', $this->pancake_status)) : 'Không có',
        };
    }
}
