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
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Chạy lệnh tính toán doanh thu hàng ngày, ví dụ vào lúc 1:00 AM mỗi ngày
        $schedule->command('app:calculate-daily-revenue')->dailyAt('01:00');

        // Bạn cũng có thể chạy cho ngày hôm qua một cách tường minh
        // $schedule->command('app:calculate-daily-revenue '.now()->subDay()->toDateString())->dailyAt('01:05');

        // Để lệnh này chạy, bạn cần cấu hình cron job trên server của bạn
        // để chạy "php /path/to/your-project/artisan schedule:run" mỗi phút.
        // * * * * * cd /path/to/your-project && php artisan schedule:run >> /dev/null 2>&1

        // Đồng bộ khách hàng từ Pancake mỗi 15 phút
        $schedule->command('pancake:sync-customers')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
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
