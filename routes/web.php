<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CallController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\CustomerController;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\PancakeSyncController;
use App\Http\Controllers\PancakeConfigController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Dashboard chart data route (AJAX)
Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.chartData');

// AJAX route to get staff by manager - Added by AI
Route::get('/ajax/staff-by-manager/{manager}', [DashboardController::class, 'getStaffByManager'])
    ->middleware(['auth']) // Ensure user is authenticated
    ->name('ajax.staffByManager');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Main Orders Page (Consolidated)
    Route::get('/orders', [OrderController::class, 'consolidated'])->name('orders.consolidated');

    // Orders
    Route::get('orders/trashed', [OrderController::class, 'trashed'])->name('orders.trashed');
    Route::get('orders/fetch-voip-history/{order}', [OrderController::class, 'fetchVoipHistory'])->name('orders.fetchVoipHistory');
    Route::get('orders/call-history-rows/{order}', [OrderController::class, 'getCallHistoryTableRows'])->name('orders.callHistoryTableRows');
    Route::post('/orders/{order}/initiate-call', [OrderController::class, 'initiateCall'])->name('orders.initiateCall')->middleware('can:calls.manage');
    Route::post('/orders/{order}/log-call', [OrderController::class, 'logCall'])->name('orders.logCall');
    Route::post('/orders/{order}/assign', [OrderController::class, 'updateAssignment'])->name('orders.updateAssignment')->middleware('can:teams.assign');
    Route::get('/orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign')->middleware('can:teams.assign');
    Route::post('/orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus')->middleware('can:calls.manage');
    Route::post('/orders/{order}/push-to-pancake', [OrderController::class, 'pushToPancake'])->name('orders.pushToPancake'); // Add permission check later

    // New Filtered Order List Routes
    Route::get('/orders/status/new', [OrderController::class, 'index'])->name('orders.index.new_orders');
    Route::get('/orders/pancake/pushed', [OrderController::class, 'index'])->name('orders.index.pushed_to_pancake');
    Route::get('/orders/pancake/failed-stock', [OrderController::class, 'index'])->name('orders.index.pancake_push_failed_stock');
    Route::get('/orders/pancake/failed-other', [OrderController::class, 'index'])->name('orders.index.pancake_push_failed_other');
    Route::get('/orders/pancake/not-pushed', [OrderController::class, 'index'])->name('orders.index.pancake_not_pushed');
    // End New Filtered Order List Routes

    Route::resource('orders', OrderController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('orders')->name('orders.')->group(function() {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::post('/store', [OrderController::class, 'store'])->name('store');
        Route::get('/create', [OrderController::class, 'create'])->name('create');

        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/fetch-voip-history', [OrderController::class, 'fetchVoipHistory'])->name('fetchVoipHistory');
        Route::get('/{order}/call-history-rows', [OrderController::class, 'getCallHistoryTableRows'])->name('callHistoryRows');
        Route::post('/{order}/push-to-pancake', [OrderController::class, 'pushToPancake'])->name('pushToPancake');
    });

    // Routes for fetching address data for order forms
    Route::get('/ajax/districts', [OrderController::class, 'getDistricts'])->name('ajax.districts');
    Route::get('/ajax/wards', [OrderController::class, 'getWards'])->name('ajax.wards');
    Route::get('/ajax/pancake-pages-for-shop', [\App\Http\Controllers\OrderController::class, 'getPancakePagesForShop'])->name('ajax.pancakePagesForShop');

    Route::get('leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Customers routes with proper authorization using permissions
    Route::prefix('customers')->name('customers.')->group(function() {
        Route::get('/archive', [CustomerController::class, 'trashedIndex'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.view_trashed')->name('archive');
        Route::get('/', [CustomerController::class, 'index'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.view')->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.create')->name('create');
        Route::post('/', [CustomerController::class, 'store'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.create')->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.view')->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.edit')->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.edit')->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.delete')->name('destroy');
        Route::get('/{customer}/orders', [CustomerController::class, 'orders'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.view')->name('orders');
        Route::get('/latest', [CustomerController::class, 'latest'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.view')->name('latest');
        Route::post('/sync-from-orders', [CustomerController::class, 'syncFromOrders'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.sync')->name('syncFromOrders');
        Route::post('/bulk-delete', [CustomerController::class, 'bulkDestroy'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.delete')->name('bulkDelete');
        Route::post('/sync', [CustomerController::class, 'startSync'])->name('start-sync');
        Route::get('/sync/progress', [CustomerController::class, 'getSyncProgress'])->name('sync-progress');
        Route::post('/sync/cancel', [CustomerController::class, 'cancelSync'])->name('cancel-sync');

        Route::patch('/{customer_id}/restore', [CustomerController::class, 'restore'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.restore')->name('restore');
        Route::delete('/{customer_id}/force-delete', [CustomerController::class, 'forceDelete'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':customers.force_delete')->name('forceDelete');
    });
});

// Admin Routes with permission middleware
Route::middleware(['auth', \Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.view|roles.view|teams.view|teams.assign|products.view|categories.view|customers.view'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Roles
        Route::resource('roles', RoleController::class)->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':roles.view');

        // Users
        Route::get('users', [UserController::class, 'index'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.view')->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.create')->name('users.create');
        Route::post('users', [UserController::class, 'store'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.create')->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.edit')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.edit')->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.delete')->name('users.destroy');
        Route::get('users/trashed', [UserController::class, 'trashedIndex'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.view_trashed')->name('users.trashed');
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.restore')->name('users.restore');
        Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':users.force_delete')->name('users.forceDelete');

        // Products - Commented out
        /*
        Route::get('products', [ProductController::class, 'index'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.view')->name('products.index');
        Route::get('products/create', [ProductController::class, 'create'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create')->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create')->name('products.store');
        Route::get('products/{product}', [ProductController::class, 'show'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.view')->name('products.show');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit')->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit')->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.delete')->name('products.destroy');
        */

        // Categories - Commented out
        /*
        Route::get('categories', [CategoryController::class, 'index'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.view')->name('categories.index');
        Route::get('categories/create', [CategoryController::class, 'create'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.create')->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.create')->name('categories.store');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.view')->name('categories.show');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.edit')->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.edit')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.delete')->name('categories.destroy');
        */

        // Team structure route
        Route::get('team-structure', [TeamController::class, 'structure'])
              ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':teams.view')
              ->name('teams.structure');

        // Settings Routes
        Route::get('settings', [SettingsController::class, 'index'])
             ->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.update')
             ->name('settings.update');
        Route::get('settings/order-distribution', [SettingsController::class, 'orderDistribution'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.manage')
             ->name('settings.order-distribution');
        Route::put('settings/order-distribution', [SettingsController::class, 'updateOrderDistribution'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.manage')
             ->name('settings.update-order-distribution');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.clear_cache')
             ->name('settings.clearCache');

        // Log routes (Moved inside admin group)
        Route::get('logs', [LogController::class, 'index'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':logs.view')
             ->name('logs.index');
        Route::get('logs/{log}', [LogController::class, 'show'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':logs.details')
             ->name('logs.show');
        Route::get('logs/model/{modelType}/{modelId}', [LogController::class, 'modelLogs'])
             ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':logs.view')
             ->name('logs.model');

        // Pancake Sync Routes
        Route::get('pancake-sync', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'index'])
            ->name('pancake.sync.index')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.manage'); // Reuse a suitable permission
        Route::post('pancake-sync/now', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'syncNow'])
            ->name('pancake.sync.now')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':settings.manage'); // Reuse a suitable permission

        // Activity Logs - Remove duplicate route definitions and consolidate here
        Route::middleware(['auth'])
            ->group(function () {
                Route::get('activity-logs', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])
                    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':logs.view_all|logs.view_own')
                    ->name('logs.index');
                Route::get('activity-logs/{log}', [App\Http\Controllers\Admin\ActivityLogController::class, 'show'])
                    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':logs.view_all|logs.view_own')
                    ->name('logs.show');
            });
    });

// Auth routes
require __DIR__.'/auth.php';

Route::post('/api/log-call', [OrderController::class, 'logCall'])->middleware('auth');

Route::get('/call-window', function () {
    return view('calls.call_window');
})->name('calls.window');

// Pancake Sync Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/pancake/sync', [PancakeSyncController::class, 'sync'])->name('pancake.sync');
    Route::get('/pancake/sync/status', [PancakeSyncController::class, 'status'])->name('pancake.sync.status');
    Route::post('/pancake/sync/cancel', [PancakeSyncController::class, 'cancelSync'])->name('pancake.sync.cancel');
    Route::post('/customers/sync', [App\Http\Controllers\CustomerController::class, 'syncFromPancake'])->name('customers.sync');

    // New order sync routes
    Route::post('/pancake/orders/sync', [PancakeSyncController::class, 'syncOrders'])->name('pancake.orders.sync');
    Route::post('/pancake/orders/push/bulk', [PancakeSyncController::class, 'bulkPushOrdersToPancake'])->name('pancake.orders.push.bulk');
    Route::get('/pancake/sync', [PancakeSyncController::class, 'index'])->name('pancake.sync.index');
});

Route::get('/pancake-config', [PancakeConfigController::class, 'index'])->name('pancake.config');
Route::post('/pancake-config', [PancakeConfigController::class, 'update'])->name('pancake.config.update');
Route::post('/pancake-config/test', [PancakeConfigController::class, 'testConnection'])->name('pancake.config.test');

// Pancake Webhook Configuration
Route::get('/admin/pancake/webhooks', [App\Http\Controllers\Admin\PancakeWebhookConfigController::class, 'index'])->name('admin.pancake.webhooks');

// Report routes
Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');

    // API đồng bộ dữ liệu từ Pancake
    Route::post('/sync-from-pancake', [App\Http\Controllers\ReportController::class, 'syncFromPancake'])->name('sync_from_pancake');

    // Order Reports
    Route::get('/orders', [App\Http\Controllers\ReportController::class, 'orderReportIndex'])->name('orders');
    Route::get('/orders/data', [App\Http\Controllers\ReportController::class, 'getOrderReportData'])->name('orders.data');

    // Báo cáo tổng doanh thu
    Route::get('/total-revenue', [App\Http\Controllers\ReportController::class, 'totalRevenuePage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.total_revenue')
        ->name('total_revenue');

    // Báo cáo chi tiết
    Route::get('/detail', [App\Http\Controllers\ReportController::class, 'detailPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.detailed')
        ->name('detail');

    // Báo cáo theo nhóm hàng hóa
    Route::get('/product-groups', [App\Http\Controllers\ReportController::class, 'productGroupsPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.product_groups')
        ->name('product_groups');

    // Báo cáo theo chiến dịch (bài post)
    Route::get('/campaigns', [App\Http\Controllers\ReportController::class, 'campaignsPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.campaigns')
        ->name('campaigns');

    // Báo cáo phiên live
    Route::get('/live-sessions', [App\Http\Controllers\ReportController::class, 'liveSessionsPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.live_sessions')
        ->name('live_sessions');

    // Báo cáo thanh toán
    Route::get('/payments', [App\Http\Controllers\ReportController::class, 'paymentsPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.view')
        ->name('payments');

    // Báo cáo tỷ lệ chốt đơn
    Route::get('/conversion-rates', [App\Http\Controllers\ReportController::class, 'conversionRatesPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.conversion_rates')
        ->name('conversion_rates');

    // Báo cáo khách hàng mới
    Route::get('/new-customers', [App\Http\Controllers\ReportController::class, 'newCustomersPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.customer_new')
        ->name('new_customers');

    // Báo cáo khách hàng cũ
    Route::get('/returning-customers', [App\Http\Controllers\ReportController::class, 'returningCustomersPage'])
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.customer_returning')
        ->name('returning_customers');
});
