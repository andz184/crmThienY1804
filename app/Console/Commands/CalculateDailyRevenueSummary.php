<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\DailyRevenueSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateDailyRevenueSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-daily-revenue {date? : Ngày cần tính toán (YYYY-MM-DD). Mặc định là ngày hôm qua.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tính toán và lưu trữ tóm tắt doanh thu hàng ngày từ bảng orders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateInput = $this->argument('date');
        $targetDate = $dateInput ? Carbon::parse($dateInput) : Carbon::yesterday();

        $this->info("Bắt đầu tính toán doanh thu cho ngày: " . $targetDate->toDateString());

        // Định nghĩa các trạng thái đơn hàng được coi là thành công và tính vào doanh thu
        $successfulStatuses = ['completed']; // Có thể thêm 'shipped' hoặc các trạng thái khác tùy theo logic của bạn

        try {
            $summary = Order::whereIn('status', $successfulStatuses)
                ->whereDate('updated_at', $targetDate) // Giả sử doanh thu tính theo ngày đơn hàng được cập nhật sang trạng thái thành công
                                             // Hoặc bạn có thể dùng 'created_at' nếu logic của bạn là vậy
                ->selectRaw(
                    '? as summary_date,
                    SUM(total_value) as total_revenue,
                    COUNT(DISTINCT id) as total_orders,
                    SUM(quantity) as total_quantity_sold',
                    [$targetDate->toDateString()]
                )
                ->first();

            if ($summary) {
                DailyRevenueSummary::updateOrCreate(
                    ['summary_date' => $targetDate->toDateString()],
                    [
                        'total_revenue' => $summary->total_revenue ?? 0.00,
                        'total_orders' => $summary->total_orders ?? 0,
                        'total_quantity_sold' => $summary->total_quantity_sold ?? 0,
                    ]
                );
                $this->info("Đã cập nhật/tạo tóm tắt doanh thu cho ngày " . $targetDate->toDateString() . ":");
                $this->line("- Tổng doanh thu: " . number_format($summary->total_revenue ?? 0, 0, '.', ',') . "đ");
                $this->line("- Tổng đơn thành công: " . ($summary->total_orders ?? 0));
                $this->line("- Tổng sản phẩm bán ra: " . ($summary->total_quantity_sold ?? 0));
            } else {
                // Nếu không có đơn hàng nào thành công trong ngày, vẫn tạo record với giá trị 0
                DailyRevenueSummary::updateOrCreate(
                    ['summary_date' => $targetDate->toDateString()],
                    [
                        'total_revenue' => 0.00,
                        'total_orders' => 0,
                        'total_quantity_sold' => 0,
                    ]
                );
                $this->info("Không có đơn hàng thành công nào được ghi nhận cho ngày " . $targetDate->toDateString() . ". Đã tạo record trống.");
            }

        } catch (\Exception $e) {
            Log::error("Lỗi khi tính toán doanh thu hàng ngày cho ngày " . $targetDate->toDateString() . ": " . $e->getMessage(), ['exception' => $e]);
            $this->error("Đã xảy ra lỗi. Vui lòng kiểm tra logs.");
            return Command::FAILURE;
        }

        $this->info('Hoàn tất tính toán doanh thu hàng ngày.');
        return Command::SUCCESS;
    }
}
