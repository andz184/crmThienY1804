<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PancakeSyncTimeout
{
    public function handle(Request $request, Closure $next)
    {
        // Set PHP execution time limit to 2 hours for very large syncs
        set_time_limit(7200);

        // Increase memory limit to 2GB for large data sets
        ini_set('memory_limit', '2048M');

        // Set script timeout to 2 hours
        ini_set('max_execution_time', '7200');

        // Set MySQL timeout values
        if (app()->bound('db')) {
            $connection = app('db')->connection();
            // Set wait_timeout and interactive_timeout to 2 hours
            $connection->statement("SET SESSION wait_timeout = 7200");
            $connection->statement("SET SESSION interactive_timeout = 7200");
            // Increase max_allowed_packet for large data transfers
            $connection->statement("SET SESSION max_allowed_packet = 67108864"); // 64MB
        }

        // Increase default socket timeout for API calls
        ini_set('default_socket_timeout', '7200');

        return $next($request);
    }
}
