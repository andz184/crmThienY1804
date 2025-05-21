<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class, // Example
        Commands\CalculateDailyRevenueSummary::class, // Đảm bảo dòng này được thêm hoặc đã có
        Commands\SyncPancakeCustomers::class,
        Commands\TestPancakeApi::class,
        Commands\EnsureSuperAdminPermissions::class,
        Commands\SyncPancakeOrdersByDate::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Chạy lệnh tính toán doanh thu hàng ngày, ví dụ vào lúc 1:00 AM mỗi ngày
        $schedule->command('app:calculate-daily-revenue')->dailyAt('01:00');

        // Đảm bảo super-admin luôn có đầy đủ quyền
        $schedule->command('admin:ensure-permissions')->daily();

        // Tự động đồng bộ đơn hàng Pancake của ngày hiện tại - giảm tần suất xuống 2 giờ 1 lần
        $schedule->command('pancake:sync-orders-today')
                ->cron('0 */2 7-23 * * *') // Chạy mỗi 2 giờ từ 7h đến 23h hàng ngày
                ->withoutOverlapping(30) // Đảm bảo không chạy chồng chéo và tối đa 30 phút
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/pancake-orders-sync.log'));

        // Đồng bộ đơn hàng Pancake của ngày hôm qua vào đầu ngày mới
        $schedule->command('pancake:sync-orders-today --date=' . now()->subDay()->format('Y-m-d'))
                ->dailyAt('00:15') // Dời xuống 00:15 thay vì 00:05
                ->withoutOverlapping(60) // Tối đa 60 phút
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/pancake-orders-sync.log'));

        // Đồng bộ khách hàng từ Pancake - giảm xuống mỗi giờ 1 lần thay vì 15 phút
        $schedule->command('pancake:sync-customers')
                ->hourly()
                ->withoutOverlapping(30) // Tối đa 30 phút
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/pancake-sync.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
