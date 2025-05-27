<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class PancakeOrdersTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test products if not exists
        if (Product::count() === 0) {
            $products = [
                ['name' => 'Áo thun basic', 'price' => 150000],
                ['name' => 'Quần jean', 'price' => 450000],
                ['name' => 'Áo khoác', 'price' => 550000],
                ['name' => 'Váy đầm', 'price' => 350000],
            ];

            foreach ($products as $product) {
                Product::create([
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'description' => 'Sản phẩm test',
                    'status' => 'active'
                ]);
            }
        }

        // Get all products
        $products = Product::all();

        // Create test customers if not exists
        if (Customer::count() === 0) {
            for ($i = 1; $i <= 5; $i++) {
                Customer::create([
                    'name' => "Khách hàng test {$i}",
                    'phone' => "098765432{$i}",
                    'email' => "customer{$i}@test.com",
                    'province' => '01', // Hà Nội
                    'district' => '001',
                    'ward' => '00001',
                    'street_address' => "Địa chỉ test {$i}"
                ]);
            }
        }

        // Get all customers
        $customers = Customer::all();

        // Create live session orders
        $liveNumber = 3;
        $liveDate = Carbon::create(2024, 5, 19); // "Live 3 19/05"

        // Create 5 orders for live session
        for ($i = 1; $i <= 5; $i++) {
            $customer = $customers->random();
            $product = $products->random();
            $quantity = rand(1, 3);

            $order = Order::create([
                'order_code' => 'PNCK-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'shipping_province' => $customer->province,
                'shipping_district' => $customer->district,
                'shipping_ward' => $customer->ward,
                'street_address' => $customer->street_address,
                'total_value' => $product->price * $quantity,
                'status' => ['pending', 'completed', 'delivering'][rand(0, 2)],
                'notes' => "Live {$liveNumber} {$liveDate->format('d/m')}", // Format: "Live 3 19/05"
                'live_session_info' => json_encode([
                    'session_date' => $liveDate->format('Y-m-d'),
                    'live_number' => $liveNumber,
                    'is_live_order' => true
                ])
            ]);

            // Add order items
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_price' => $product->price * $quantity
            ]);
        }

        // Create regular orders (non-live session)
        for ($i = 1; $i <= 5; $i++) {
            $customer = $customers->random();
            $product = $products->random();
            $quantity = rand(1, 3);

            $order = Order::create([
                'order_code' => 'PNCK-REG-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'shipping_province' => $customer->province,
                'shipping_district' => $customer->district,
                'shipping_ward' => $customer->ward,
                'street_address' => $customer->street_address,
                'total_value' => $product->price * $quantity,
                'status' => ['pending', 'completed', 'delivering'][rand(0, 2)],
                'notes' => 'Đơn hàng thường'
            ]);

            // Add order items
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_price' => $product->price * $quantity
            ]);
        }

        $this->command->info('Created 5 live session orders and 5 regular orders');
    }
}
