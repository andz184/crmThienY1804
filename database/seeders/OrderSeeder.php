<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ShippingProvider;
use App\Models\PancakeShop;
use App\Models\PancakePage;
use App\Models\Province;
use App\Models\District;
use App\Models\Ward;
use App\Models\Customer;
use App\Models\CustomerPhone;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Order::truncate(); // Xóa các đơn hàng cũ trước khi seed
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $faker = Faker::create('vi_VN'); // Sử dụng Faker tiếng Việt cho địa chỉ, tên...

        // Fetch prerequisite data
        $staffUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['staff', 'manager']);
        })->pluck('id')->toArray();

        $warehouses = Warehouse::pluck('id')->toArray();
        $shippingProviders = ShippingProvider::pluck('id')->toArray();
        $pancakeShops = PancakeShop::pluck('id')->toArray();
        // $pancakePages = PancakePage::pluck('id')->toArray(); // We'll fetch pages based on selected shop

        $provinces = Province::pluck('code')->toArray();

        if (empty($staffUsers) || empty($warehouses) || empty($provinces)) {
            $this->command->warn('Cannot seed orders: Missing staff users, warehouses, or provinces. Please seed them first.');
            return;
        }

        $orderStatuses = [
            Order::STATUS_MOI,
            Order::STATUS_CAN_XU_LY,
            Order::STATUS_CHO_HANG,
            Order::STATUS_DA_DAT_HANG,
            Order::STATUS_CHO_CHUYEN_HANG,
            Order::STATUS_DA_GUI_HANG,
            Order::STATUS_DA_NHAN,
            Order::STATUS_DA_THU_TIEN,
            Order::STATUS_DA_HOAN,
            Order::STATUS_DA_HUY,
        ];

        $paymentMethods = ['cod', 'banking', 'momo', 'zalopay', 'other'];

        $this->command->info("Bắt đầu tạo khoảng 100 đơn hàng mẫu...");
        $progressBar = $this->command->getOutput()->createProgressBar(100);
        $progressBar->start();

        for ($i = 0; $i < 100; $i++) {
            $selectedUserId = $staffUsers[array_rand($staffUsers)];
            $selectedWarehouseId = $warehouses[array_rand($warehouses)];
            $selectedShippingProviderId = !empty($shippingProviders) ? $shippingProviders[array_rand($shippingProviders)] : null;

            $selectedPancakeShopId = null;
            $selectedPancakePageId = null;
            if (!empty($pancakeShops)) {
                $selectedPancakeShopId = $pancakeShops[array_rand($pancakeShops)];
                $availablePagesForShop = PancakePage::where('pancake_shop_table_id', $selectedPancakeShopId)->pluck('id')->toArray();
                if (!empty($availablePagesForShop)) {
                    $selectedPancakePageId = $availablePagesForShop[array_rand($availablePagesForShop)];
                }
            }

            $selectedProvinceCode = $provinces[array_rand($provinces)];
            $districtsInProvince = District::where('province_code', $selectedProvinceCode)->pluck('code')->toArray();
            $selectedDistrictCode = !empty($districtsInProvince) ? $districtsInProvince[array_rand($districtsInProvince)] : null;

            $wardsInDistrict = null;
            if ($selectedDistrictCode) {
                $wardsInDistrict = Ward::where('district_code', $selectedDistrictCode)->pluck('code')->toArray();
            }
            $selectedWardCode = !empty($wardsInDistrict) ? $wardsInDistrict[array_rand($wardsInDistrict)] : null;

            // Create or find customer
            $customerName = $faker->name;
            $customerPhone = $faker->numerify('09########');
            $customerEmail = $faker->optional()->safeEmail;

            $customer = Customer::create([
                'name' => $customerName,
                'email' => $customerEmail,
                'full_address' => $faker->address,
                'province' => $selectedProvinceCode,
                'district' => $selectedDistrictCode,
                'ward' => $selectedWardCode,
                'street_address' => $faker->streetAddress,
            ]);

            // Create customer phone
            CustomerPhone::create([
                'customer_id' => $customer->id,
                'phone_number' => $customerPhone,
                'is_primary' => true,
            ]);

            $order = Order::create([
                'order_code' => 'ORD-' . strtoupper(Str::random(4)) . '-' . time() . $i,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'shipping_fee' => $faker->numberBetween(0, 100) * 1000,
                'transfer_money' => $faker->optional(0.3)->randomElement([$faker->numberBetween(50, 500) * 1000, (string)($faker->numberBetween(50, 500) * 1000)]),
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'shipping_provider_id' => $selectedShippingProviderId,
                'internal_status' => 'Seeded Order',
                'notes' => $faker->optional()->sentence,
                'additional_notes' => $faker->optional()->paragraph,
                'total_value' => 0, // Will be updated after items
                'status' => $orderStatuses[array_rand($orderStatuses)],
                'user_id' => $selectedUserId,
                'created_by' => $selectedUserId,
                'province_code' => $selectedProvinceCode,
                'district_code' => $selectedDistrictCode,
                'ward_code' => $selectedWardCode,
                'street_address' => $faker->streetAddress,
                'full_address' => $faker->address,
                'warehouse_id' => $selectedWarehouseId,
                'pancake_shop_id' => $selectedPancakeShopId,
                'pancake_page_id' => $selectedPancakePageId,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);

            $totalOrderValue = 0;
            $itemCount = $faker->numberBetween(1, 3);

            for ($j = 0; $j < $itemCount; $j++) {
                $itemPrice = $faker->numberBetween(50, 1000) * 1000;
                $itemQuantity = $faker->numberBetween(1, 5);
                $itemName = 'Seeded Item ' . Str::random(3);

                OrderItem::create([
                    'order_id' => $order->id,
                    'code' => 'SKU-' . strtoupper(Str::random(5)), // Internal SKU
                    'quantity' => $itemQuantity,
                    'name' => $itemName,
                    'price' => $itemPrice,
                ]);
                $totalOrderValue += $itemPrice * $itemQuantity;
            }
            $order->total_value = $totalOrderValue + $order->shipping_fee;
            $order->save();

            $progressBar->advance();
        }
        $progressBar->finish();
        $this->command->info("\nĐã tạo xong 100 đơn hàng mẫu.");
    }
}
