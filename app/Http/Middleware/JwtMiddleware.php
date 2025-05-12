<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware extends BaseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Check if token is about to expire and refresh if needed
            $payload = JWTAuth::payload();
            $expiration = $payload->get('exp');
            $refreshThreshold = now()->addMinutes(30)->timestamp;

            if ($expiration < $refreshThreshold) {
                $newToken = JWTAuth::refresh();
                return $next($request)
                    ->header('Authorization', 'Bearer ' . $newToken)
                    ->header('Access-Control-Expose-Headers', 'Authorization');
            }
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json(['status' => 'Token is Invalid'], 401);
            } else if ($e instanceof TokenExpiredException) {
                try {
                    $newToken = JWTAuth::refresh();
                    return $next($request)
                        ->header('Authorization', 'Bearer ' . $newToken)
                        ->header('Access-Control-Expose-Headers', 'Authorization');
                } catch (Exception $e) {
                    return response()->json(['status' => 'Token cannot be refreshed, please login again'], 401);
                }
            } else {
                return response()->json(['status' => 'Authorization Token not found'], 401);
            }
        }

        return $next($request);
    }
}
