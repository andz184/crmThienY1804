<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Determine the redirect path based on user permissions.
                if (Auth::user()->can('reports.overall_revenue_summary')) {
                    return redirect()->route('reports.overall_revenue_summary');
                }

                // Default redirect for users without the specific permission.
                return redirect()->route('orders.index');
            }
        }

        return $next($request);
    }
}
