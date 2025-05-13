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

    // Order Management
    Route::get('orders', 'App\Http\Controllers\Api\OrderController@index');
    Route::get('orders/{id}', 'App\Http\Controllers\Api\OrderController@show');
    Route::post('orders', 'App\Http\Controllers\Api\OrderController@store');
    Route::put('orders/{id}', 'App\Http\Controllers\Api\OrderController@update');
    Route::delete('orders/{id}', 'App\Http\Controllers\Api\OrderController@destroy');
});
