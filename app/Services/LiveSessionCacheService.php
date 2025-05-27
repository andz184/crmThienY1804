<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LiveSessionCacheService
{
    const CACHE_PREFIX = 'live_session:';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get cached live session data
     */
    public function getCachedData($startDate, $endDate, $forceRefresh = false)
    {
        $cacheKey = $this->generateCacheKey($startDate, $endDate);

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return [
                'data' => Cache::get($cacheKey),
                'from_cache' => true
            ];
        }

        return null;
    }

    /**
     * Store live session data in cache
     */
    public function cacheData($startDate, $endDate, $data)
    {
        $cacheKey = $this->generateCacheKey($startDate, $endDate);
        Cache::put($cacheKey, $data, Carbon::now()->addSeconds(self::CACHE_TTL));

        return [
            'data' => $data,
            'from_cache' => false
        ];
    }

    /**
     * Generate cache key based on date range
     */
    private function generateCacheKey($startDate, $endDate)
    {
        return self::CACHE_PREFIX . md5($startDate . $endDate);
    }

    /**
     * Clear all live session caches
     */
    public function clearAllCaches()
    {
        $keys = Cache::get(self::CACHE_PREFIX . '*');
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
