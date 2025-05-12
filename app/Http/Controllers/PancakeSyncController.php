<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PancakeSyncController extends Controller
{
    public function sync(Request $request)
    {
        try {
            // Kiểm tra quyền
            $this->authorize('sync-pancake');

            // Lấy các tham số từ request
            $chunk = $request->input('chunk', 100);
            $force = $request->boolean('force', false);

            // Chạy command đồng bộ
            $exitCode = Artisan::call('pancake:sync-customers', [
                '--chunk' => $chunk,
                '--force' => $force
            ]);

            // Lấy output từ command
            $output = Artisan::output();

            if ($exitCode === 0) {
                // Ghi log thành công
                Log::info('Đồng bộ Pancake thành công', [
                    'user_id' => Auth::id() ?? 'system',
                    'chunk' => $chunk,
                    'force' => $force,
                    'output' => $output
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Đồng bộ thành công',
                    'output' => explode("\n", trim($output))
                ]);
            } else {
                throw new \Exception('Đồng bộ thất bại với mã lỗi: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ Pancake', [
                'user_id' => Auth::id() ?? 'system',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status()
    {
        try {
            $this->authorize('view-sync-status');

            // Đọc log file
            $logPath = storage_path('logs/pancake-sync.log');
            $lastLines = [];

            if (file_exists($logPath)) {
                $lastLines = array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -50);
            }

            // Kiểm tra xem có đang chạy không
            $isRunning = false;
            if (function_exists('shell_exec')) {
                $processCount = shell_exec("ps aux | grep 'pancake:sync-customers' | grep -v grep | wc -l");
                $isRunning = intval(trim($processCount)) > 0;
            }

            return response()->json([
                'success' => true,
                'is_running' => $isRunning,
                'last_logs' => $lastLines,
                'last_sync' => filemtime($logPath) ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi kiểm tra trạng thái đồng bộ', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể kiểm tra trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }
}
