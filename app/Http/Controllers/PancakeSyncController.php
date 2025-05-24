<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PancakeSyncController extends Controller
{
    /**
     * Display sync status and logs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSyncStatus()
    {
        try {
            // Auto-detect and cancel stuck syncs
            $stuckSyncCancelled = $this->detectAndCancelStuckSync();

            // Check if there's a sync in progress
            $isRunning = Cache::has('pancake_sync_in_progress');
            $runningSync = $isRunning ? Cache::get('pancake_sync_in_progress') : null;

            // Get last sync time from cache or database
            $lastSync = Cache::get('pancake_last_sync_time');

            // Get recent logs
            $lastLogs = [];
            // Implement actual log fetching based on your logging system
            // This is a placeholder - you might retrieve from a database or log files

            return response()->json([
                'success' => true,
                'is_running' => $isRunning,
                'current_sync' => $runningSync,
                'last_sync' => $lastSync,
                'last_logs' => $lastLogs,
                'stuck_sync_cancelled' => $stuckSyncCancelled
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sync status: ' . $e->getMessage()
            ], 500);
        }
    }
}
