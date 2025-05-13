<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetRequestTimeout
{
    public function handle(Request $request, Closure $next)
    {
        // Set PHP execution time limit to 1 hour
        set_time_limit(3600);

        // Increase memory limit to 1GB
        ini_set('memory_limit', '1024M');

        // Set script timeout to 1 hour
        ini_set('max_execution_time', '3600');

        // Set MySQL timeout values
        if (app()->bound('db')) {
            $connection = app('db')->connection();
            // Set wait_timeout and interactive_timeout to 1 hour
            $connection->statement("SET SESSION wait_timeout = 3600");
            $connection->statement("SET SESSION interactive_timeout = 3600");
        }

        return $next($request);
    }
}
