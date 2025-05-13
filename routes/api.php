<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\OrderController;

// Authentication Routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    // Customer routes
    Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index']);

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);
});
