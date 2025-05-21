<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Log;

class UpdateOrderShippingProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:update-shipping-providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật shipping_provider_id cho đơn hàng dựa trên pancake_shipping_provider_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu cập nhật shipping_provider_id cho đơn hàng...');

        // Lấy tất cả đơn hàng có pancake_shipping_provider_id nhưng không có shipping_provider_id
        $orders = Order::whereNotNull('pancake_shipping_provider_id')
            ->whereNull('shipping_provider_id')
            ->get();

        $this->info("Tìm thấy {$orders->count()} đơn hàng cần cập nhật.");

        // Lấy tất cả nhà cung cấp vận chuyển để mapping
        $shippingProviders = ShippingProvider::all();
        
        $updated = 0;
        $errors = 0;

        foreach ($orders as $order) {
            $this->output->write("Đang xử lý đơn hàng ID {$order->id}... ");
            
            // Tìm nhà vận chuyển phù hợp
            $provider = $shippingProviders->first(function($provider) use ($order) {
                return $provider->pancake_partner_id == $order->pancake_shipping_provider_id 
                    || $provider->pancake_id == $order->pancake_shipping_provider_id;
            });
            
            if ($provider) {
                $order->shipping_provider_id = $provider->id;
                $order->save();
                
                $this->info("OK! Đã gán nhà vận chuyển: {$provider->name}");
                $updated++;
            } else {
                $this->error("Không tìm thấy nhà vận chuyển khớp với Pancake ID: {$order->pancake_shipping_provider_id}");
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Đã hoàn tất cập nhật:");
        $this->info("- Cập nhật thành công: $updated đơn hàng");
        $this->info("- Lỗi (không tìm thấy nhà vận chuyển): $errors đơn hàng");
        
        return Command::SUCCESS;
    }
}
