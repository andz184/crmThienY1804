<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PancakeSyncController;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncPancakeOrdersByDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pancake:sync-orders-today {--date= : Ngày cần đồng bộ theo định dạng Y-m-d, mặc định là hôm nay}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ đơn hàng từ Pancake theo ngày hiện tại hoặc ngày được chỉ định';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateString = $this->option('date');
        $date = $dateString ? Carbon::createFromFormat('Y-m-d', $dateString) : Carbon::today();

        $this->info('Bắt đầu đồng bộ đơn hàng Pancake cho ngày: ' . $date->format('Y-m-d'));

        try {
            // Sử dụng controller để đồng bộ đơn hàng
            $syncController = new PancakeSyncController();
            $result = $syncController->syncOrdersByDate($date);

            if ($result['success']) {
                $this->info('Đồng bộ thành công: ' . $result['message']);
                $this->info('- Tổng số đơn đồng bộ: ' . $result['total_synced']);
                $this->info('- Đơn mới: ' . $result['new_orders']);
                $this->info('- Đơn cập nhật: ' . $result['updated_orders']);
            } else {
                $this->error('Đồng bộ thất bại: ' . $result['message']);
            }
        } catch (\Exception $e) {
            $this->error('Đã xảy ra lỗi khi đồng bộ: ' . $e->getMessage());
            Log::error('Lỗi đồng bộ đơn hàng Pancake theo ngày', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return 0;
    }
}
