<?php

namespace Database\Seeders;

use App\Models\PancakeOrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PancakeOrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all Pancake order statuses with their Vietnamese translations
        $statuses = [
            [
                'status_code' => 0,
                'name' => 'Mới',
                'api_name' => 'new',
                'color' => 'primary',
                'description' => 'Đơn hàng mới được tạo trên Pancake',
                'active' => true
            ],
            [
                'status_code' => 11,
                'name' => 'Chờ hàng',
                'api_name' => 'waiting_for_goods',
                'color' => 'warning',
                'description' => 'Đang chờ hàng về kho',
                'active' => true
            ],
            [
                'status_code' => 20,
                'name' => 'Đã đặt hàng',
                'api_name' => 'ordered',
                'color' => 'info',
                'description' => 'Đơn hàng đã được đặt',
                'active' => true
            ],
            [
                'status_code' => 1,
                'name' => 'Đã xác nhận',
                'api_name' => 'confirmed',
                'color' => 'success',
                'description' => 'Đơn hàng đã được xác nhận',
                'active' => true
            ],
            [
                'status_code' => 12,
                'name' => 'Chờ in',
                'api_name' => 'waiting_for_printing',
                'color' => 'warning',
                'description' => 'Đơn hàng đang chờ in',
                'active' => true
            ],
            [
                'status_code' => 13,
                'name' => 'Đã in',
                'api_name' => 'printed',
                'color' => 'info',
                'description' => 'Đơn hàng đã được in',
                'active' => true
            ],
            [
                'status_code' => 8,
                'name' => 'Đang đóng hàng',
                'api_name' => 'packing',
                'color' => 'info',
                'description' => 'Đơn hàng đang được đóng gói',
                'active' => true
            ],
            [
                'status_code' => 9,
                'name' => 'Chờ chuyển hàng',
                'api_name' => 'waiting_for_delivery',
                'color' => 'warning',
                'description' => 'Đơn hàng đang chờ được chuyển đi',
                'active' => true
            ],
            [
                'status_code' => 2,
                'name' => 'Đã gửi hàng',
                'api_name' => 'shipped',
                'color' => 'info',
                'description' => 'Đơn hàng đã được gửi cho đơn vị vận chuyển',
                'active' => true
            ],
            [
                'status_code' => 3,
                'name' => 'Đã nhận',
                'api_name' => 'delivered',
                'color' => 'success',
                'description' => 'Khách hàng đã nhận được hàng',
                'active' => true
            ],
            [
                'status_code' => 16,
                'name' => 'Đã thu tiền',
                'api_name' => 'paid',
                'color' => 'success',
                'description' => 'Đã thu được tiền từ đơn hàng',
                'active' => true
            ],
            [
                'status_code' => 4,
                'name' => 'Đang trả hàng',
                'api_name' => 'returning',
                'color' => 'danger',
                'description' => 'Đơn hàng đang trong quá trình trả lại',
                'active' => true
            ],
            [
                'status_code' => 15,
                'name' => 'Hoàn 1 phần',
                'api_name' => 'partially_returned',
                'color' => 'warning',
                'description' => 'Đơn hàng đã được hoàn trả một phần',
                'active' => true
            ],
            [
                'status_code' => 5,
                'name' => 'Đã hoàn',
                'api_name' => 'returned',
                'color' => 'danger',
                'description' => 'Đơn hàng đã được hoàn trả hoàn toàn',
                'active' => true
            ],
            [
                'status_code' => 6,
                'name' => 'Đã hủy',
                'api_name' => 'canceled',
                'color' => 'danger',
                'description' => 'Đơn hàng đã bị hủy',
                'active' => true
            ],
        ];

        // Insert or update each status
        foreach ($statuses as $status) {
            PancakeOrderStatus::updateOrCreate(
                ['status_code' => $status['status_code']],
                $status
            );
        }
    }
}
