<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Api\PancakeWebhookController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LocationController;

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

// Reporting Routes
Route::prefix('reports')->group(function () {
    // Báo cáo tổng quan
    Route::get('total-revenue', [ReportController::class, 'getTotalRevenue']);
    Route::get('daily-revenue', [ReportController::class, 'getDailyRevenue'])->name('api.reports.daily-revenue');
    Route::get('daily', [ReportController::class, 'getDailyReport']);

    // Báo cáo chi tiết
    Route::get('detail', [ReportController::class, 'getDetailReport']);

    // Báo cáo theo chiến dịch
    Route::get('campaign', [ReportController::class, 'getCampaignReport']);
    Route::get('campaign-products', [ReportController::class, 'getCampaignProducts']);

    // Báo cáo theo nhóm hàng hóa
    Route::get('product-group', [ReportController::class, 'getProductGroupReport']);

    // Báo cáo phiên live
    Route::get('live-session', [ReportController::class, 'getLiveSessionReport']);
    Route::get('live-session-detail', [ReportController::class, 'getLiveSessionDetail']);
    Route::get('live-session-export', [ReportController::class, 'exportLiveSessionReport']);
    Route::get('check-live-notes', [ReportController::class, 'checkNotesPatterns']);

    // Báo cáo thanh toán
    Route::get('payment', [ReportController::class, 'getPaymentReport']);
    Route::post('payment/generate', [ReportController::class, 'generatePaymentReport']);

    // Báo cáo tỷ lệ chốt đơn
    Route::get('conversion', [ReportController::class, 'getConversionReport']);

    // Báo cáo khách hàng mới/cũ
    Route::get('customer-orders', [ReportController::class, 'getCustomerOrderReport']);
});


    Route::post('/webhooks/pancake', [App\Http\Controllers\Api\PancakeWebhookController::class, 'handleWebhook']);


// For backward compatibility - these will be deprecated
Route::middleware(['api'])->group(function () {
    Route::post('/webhooks/pancake/order', [PancakeWebhookController::class, 'handleOrderWebhook'])->middleware('can:orders.create');
    Route::post('/webhooks/pancake/customer', [PancakeWebhookController::class, 'handleCustomerWebhook'])->middleware('can:customers.create');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'getStats']);
    // ... existing routes ...
});

// Customer search
Route::get('/customers/search', [CustomerController::class, 'search']);

// Location data
Route::get('/districts', [LocationController::class, 'getDistricts']);
Route::get('/wards', [LocationController::class, 'getWards']);
