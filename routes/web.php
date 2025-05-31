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
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LiveSessionRevenueController;

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
    Route::post('/orders/{order}/push-to-pancake', [OrderController::class, 'pushToPancake'])
        ->name('orders.pushToPancake')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':orders.push_to_pancake');

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
        Route::post('/{order}/push-to-pancake', [OrderController::class, 'pushToPancake'])
            ->name('pushToPancake')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':orders.push_to_pancake');
        Route::post('/{order}/update-on-pancake', [OrderController::class, 'updateOnPancake'])
            ->name('updateOnPancake')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':orders.push_to_pancake');
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
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Products
        Route::get('products', [App\Http\Controllers\ProductController::class, 'index'])
            ->name('products.index')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.view');

        Route::get('products/create', [App\Http\Controllers\ProductController::class, 'create'])
            ->name('products.create')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create');

        Route::post('products', [App\Http\Controllers\ProductController::class, 'store'])
            ->name('products.store')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create');

        Route::get('products/{product}/edit', [App\Http\Controllers\ProductController::class, 'edit'])
            ->name('products.edit')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit');

        Route::put('products/{product}', [App\Http\Controllers\ProductController::class, 'update'])
            ->name('products.update')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit');

        Route::delete('products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])
            ->name('products.destroy')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.delete');

        Route::post('products/sync', [App\Http\Controllers\ProductController::class, 'syncFromPancake'])
            ->name('products.sync')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.sync');

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
            ->name('sync.index')
            ->middleware(['auth', 'can:settings.manage']);
        Route::post('pancake-sync/now', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'syncNow'])
            ->name('sync.now')
            ->middleware(['auth', 'can:settings.manage']);
        Route::post('pancake-sync/employees', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'syncEmployees'])
            ->name('sync.employees')
            ->middleware(['auth', 'can:settings.manage']);
        Route::post('pancake-sync/product-sources', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'syncProductSources'])
            ->name('sync.product-sources')
            ->middleware(['auth', 'can:product-sources.sync']);
        Route::get('pancake-sync/skipped-employees', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'getSkippedEmployeesReasons'])
            ->name('sync.skipped-employees')
            ->middleware(['auth', 'can:settings.manage']);

        // Route for syncing categories - now points to PancakeCategoryController
        Route::post('pancake-sync/categories', [\App\Http\Controllers\Admin\PancakeCategoryController::class, 'syncCategories'])
            ->name('sync.categories') // This name is used in the blade file
            ->middleware(['auth', 'can:settings.manage']);

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

        // Order Sources
        Route::post('/sync-order-sources', [PancakeSyncController::class, 'syncOrderSources'])->name('pancake.sync.order-sources');

        // New route for syncing product sources
        Route::post('/admin/pancake-sync/sync-product-sources', [PancakeSyncController::class, 'syncProductSources'])
            ->name('admin.pancake-sync.sync-product-sources')
            ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':product-sources.sync');
    });

// Auth routes
require __DIR__.'/auth.php';

Route::post('/api/log-call', [OrderController::class, 'logCall'])->middleware('auth');

Route::get('/call-window', function () {
    return view('calls.call_window');
})->name('calls.window');

// Pancake Sync Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/pancake/sync', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'sync'])->name('pancake.sync');
    Route::get('/pancake/sync/status', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'status'])->name('pancake.sync.status');
    Route::post('/pancake/sync/cancel', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'cancelSync'])->name('pancake.sync.cancel');
    Route::post('/customers/sync', [App\Http\Controllers\CustomerController::class, 'syncFromPancake'])->name('customers.sync');

    // New order sync routes
    Route::post('/pancake/orders/sync', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'syncOrders'])->name('pancake.orders.sync');
    Route::post('/pancake/orders/push/bulk', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'bulkPushOrdersToPancake'])->name('pancake.orders.push.bulk');
    Route::get('/pancake/sync', [\App\Http\Controllers\Admin\PancakeSyncController::class, 'index'])->name('pancake.sync.index');

    // Đồng bộ đơn hàng Pancake theo ngày
    Route::post('/pancake/orders/sync-by-date', [\App\Http\Controllers\PancakeSyncController::class, 'syncOrdersByDateManual'])->name('pancake.orders.sync-by-date');
    Route::get('/pancake/orders/sync-result', [\App\Http\Controllers\PancakeSyncController::class, 'checkSyncOrdersResult'])->name('pancake.orders.sync-result');
    Route::get('/pancake/orders/sync-progress', [\App\Http\Controllers\PancakeSyncController::class, 'getSyncProgress'])->name('pancake.orders.sync-progress');

    // Route đồng bộ đơn hàng từ API bên ngoài
    Route::post('/pancake/orders/sync-from-api', [\App\Http\Controllers\PancakeSyncController::class, 'syncOrdersFromApi'])->name('pancake.orders.sync-from-api');

    // Route đồng bộ tất cả đơn hàng
    Route::post('/pancake/orders/sync-all', [\App\Http\Controllers\PancakeSyncController::class, 'syncAllOrders'])->name('pancake.orders.sync-all');
    Route::get('/pancake/orders/sync-all-progress', [\App\Http\Controllers\PancakeSyncController::class, 'getAllOrdersSyncProgress'])->name('pancake.orders.sync-all-progress');
    Route::post('/pancake/orders/sync-process-next', [\App\Http\Controllers\PancakeSyncController::class, 'processNextPage'])->name('pancake.orders.sync-process-next');

    // Product sync routes
    Route::get('/pancake/products', [App\Http\Controllers\ProductController::class, 'getProductsFromPancake'])
        ->name('pancake.products.list')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.sync');

    Route::post('/pancake/products/sync', [App\Http\Controllers\ProductController::class, 'syncFromPancake'])
        ->name('pancake.products.sync')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.sync');

    Route::post('/pancake/products', [App\Http\Controllers\ProductController::class, 'createProductInPancake'])
        ->name('pancake.products.create')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.create');

    Route::put('/pancake/products/{product}', [App\Http\Controllers\ProductController::class, 'updateProductInPancake'])
        ->name('pancake.products.update')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit');

    Route::put('/pancake/products/{product}/inventory', [App\Http\Controllers\ProductController::class, 'updateInventoryInPancake'])
        ->name('pancake.products.inventory.update')
        ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':products.edit');
});

Route::get('/pancake-config', [PancakeConfigController::class, 'index'])->name('pancake.config');
Route::post('/pancake-config', [PancakeConfigController::class, 'update'])->name('pancake.config.update');
Route::post('/pancake-config/test', [PancakeConfigController::class, 'testConnection'])->name('pancake.config.test');

// Pancake Webhook Configuration
Route::get('/admin/pancake/webhooks', [App\Http\Controllers\Admin\PancakeController::class, 'webhooks'])
    ->middleware(['auth', 'can:settings.manage'])
    ->name('admin.pancake.webhooks');

// Reports Routes
Route::middleware(['auth'])->group(function () {
    // Base reports route
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Report pages
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/total-revenue', [ReportController::class, 'totalRevenuePage'])->name('total-revenue');
        Route::get('/detail', [ReportController::class, 'detailPage'])->name('detail');
        Route::get('/product-groups', [ReportController::class, 'productGroupsPage'])->name('product_groups');
        Route::get('/campaigns', [ReportController::class, 'campaignsPage'])->name('campaigns');
        Route::get('/live-sessions', [ReportController::class, 'liveSessionsPage'])->name('live-sessions');
        Route::get('/conversion-rates', [ReportController::class, 'conversionRatesPage'])->name('conversion-rates');
        Route::get('/new-customers', [ReportController::class, 'newCustomersPage'])->name('new-customers');
        Route::get('/returning-customers', [ReportController::class, 'returningCustomersPage'])->name('returning-customers');
        Route::get('/payments', [ReportController::class, 'paymentsPage'])->name('payments');
        Route::get('/order-report', [ReportController::class, 'orderReportIndex'])->name('order-report');
        Route::get('/overall-revenue', [ReportController::class, 'overallRevenueSummaryPage'])->name('overall-revenue');
        Route::get('/overall-revenue', [ReportController::class, 'overallRevenueSummaryPage'])->name('overall_revenue_summary');
        Route::get('/general', [ReportController::class, 'generalReportPage'])->name('general_report');
        Route::get('/overall-revenue-data', [ReportController::class, 'getOverallRevenueData'])->name('overall-revenue-data');

        // API routes for report data
        Route::get('/total-revenue-overview-data', [ReportController::class, 'getTotalRevenueOverviewData'])->name('total-revenue-overview-data');
        Route::get('/total-revenue-data', [ReportController::class, 'getTotalRevenue'])->name('total-revenue-data');
        Route::get('/daily-revenue', [ReportController::class, 'getDailyRevenue'])->name('daily-revenue');
        Route::get('/campaign-report', [ReportController::class, 'getCampaignReport'])->name('campaign-report');
        Route::get('/campaign-products', [ReportController::class, 'getCampaignProducts'])->name('campaign-products');
        Route::get('/product-group-report', [ReportController::class, 'getProductGroupReport'])->name('product-group-report');
        Route::get('/live-session-report', [ReportController::class, 'getLiveSessionReport'])->name('live-session-report');
        Route::get('/live-session-detail', [ReportController::class, 'getLiveSessionDetail'])->name('live-session-detail');
        Route::get('/customer-order-report', [ReportController::class, 'getCustomerOrderReport'])->name('customer-order-report');
        Route::get('/conversion-report', [ReportController::class, 'getConversionReport'])->name('conversion-report');
        Route::get('/detail-report', [ReportController::class, 'getDetailReport'])->name('detail-report');
        Route::get('/daily-report', [ReportController::class, 'getDailyReport'])->name('daily-report');
        Route::get('/payment-report', [ReportController::class, 'getPaymentReport'])->name('payment-report');
        Route::get('/order-report-data', [ReportController::class, 'getOrderReportData'])->name('order-report-data');
        Route::get('/general-report-data', [ReportController::class, 'getGeneralReportData'])->name('general-report-data');
        Route::get('/overall-revenue-chart-data', [ReportController::class, 'getOverallRevenueChartData'])->name('overall-revenue-chart-data');
        Route::get('/variant-revenue-report', [ReportController::class, 'getVariantRevenueReport'])->name('variant-revenue-report');
        Route::get('/compare-variants', [ReportController::class, 'compareVariants'])->name('compare-variants');
        Route::get('/category-revenue-report', [ReportController::class, 'getCategoryRevenueReport'])->name('category-revenue-report');
    });
});

// Live Session Revenue Routes
Route::prefix('reports')->middleware(['auth', \Spatie\Permission\Middleware\PermissionMiddleware::class . ':reports.live-sessions'])->group(function () {
    Route::get('live-sessions', [LiveSessionRevenueController::class, 'index'])->name('reports.live-sessions');
    Route::get('live-sessions/data', [LiveSessionRevenueController::class, 'getData'])->name('reports.live-sessions.data');
    Route::get('live-sessions/filtered', [LiveSessionRevenueController::class, 'getFilteredData'])->name('reports.live-sessions.filtered');
    Route::get('live-sessions/{id}', [LiveSessionRevenueController::class, 'getSessionDetail'])->name('reports.live-sessions.detail');
});

// Sales Staff Management
Route::prefix('admin/sales-staff')->name('admin.sales-staff.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\SalesStaffController::class, 'index'])->name('index');
    Route::post('/{user}/toggle-active', [App\Http\Controllers\Admin\SalesStaffController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{user}/reassign-orders', [App\Http\Controllers\Admin\SalesStaffController::class, 'reassignOrders'])->name('reassign-orders');
    Route::post('/distribute-new-orders', [App\Http\Controllers\Admin\SalesStaffController::class, 'distributeNewOrders'])->name('distribute-new-orders');
});

// Test route for pancake page creation
Route::get('/test-pancake-page', function () {
    try {
        // Create a test shop first
        $shop = new App\Models\PancakeShop();
        $shop->pancake_id = 999999;
        $shop->name = 'Test Shop';
        $shop->save();

        // Now create a test page
        $page = new App\Models\PancakePage();
        $page->pancake_id = '888888';
        $page->pancake_page_id = '888888'; // This is the key fix we made
        $page->name = 'Test Page';
        $page->pancake_shop_table_id = $shop->id;
        $page->save();

        return "Test successful! Created shop ID: {$shop->id} and page ID: {$page->id}";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

// Debug routes
Route::get('/debug/live-patterns', [App\Http\Controllers\ReportController::class, 'debugLiveSessions'])->middleware(['auth']);

// Temporary route for debugging
// Route::get('/debug/live-sessions', function() {
//     $data = \App\Models\LiveSessionRevenue::all();
//     dd([
//         'total_records' => $data->count(),
//         'records' => $data->toArray()
//     ]);
// });

// Temporary route for debugging orders
Route::get('/debug/orders-with-live-session', function() {
    $orders = \App\Models\Order::whereNotNull('live_session_info')->get();
    dd([
        'total_orders' => $orders->count(),
        'sample_orders' => $orders->take(5)->map(function($order) {
            return [
                'id' => $order->id,
                'live_session_info' => $order->live_session_info,
                'pancake_status' => $order->pancake_status,
                'total_value' => $order->total_value,
                'province_code' => $order->province_code
            ];
        })
    ]);
});

// Temporary route for creating test order
Route::get('/debug/create-test-order', function() {
    $order = new \App\Models\Order();
    $order->customer_name = 'Test Customer';
    $order->customer_phone = '0123456789';
    $order->total_value = 1000000;
    $order->pancake_status = \App\Models\Order::PANCAKE_STATUS_COMPLETED;
    $order->province_code = 'HN';
    $order->live_session_info = json_encode([
        'session_date' => '2025-05-23',
        'live_number' => 3,
        'session_name' => 'LIVE 3 (23/05/2025)'
    ]);
    $order->save();

    return 'Test order created with ID: ' . $order->id;
});

// Category routes
Route::get('admin/categories', [App\Http\Controllers\CategoryController::class, 'index'])
    ->name('admin.categories.index')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.view');

Route::get('admin/categories/create', [App\Http\Controllers\CategoryController::class, 'create'])
    ->name('admin.categories.create')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.create');

Route::post('admin/categories', [App\Http\Controllers\CategoryController::class, 'store'])
    ->name('admin.categories.store')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.create');

Route::get('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'show'])
    ->name('admin.categories.show')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.view');

Route::get('admin/categories/{category}/edit', [App\Http\Controllers\CategoryController::class, 'edit'])
    ->name('admin.categories.edit')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.edit');

Route::put('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update'])
    ->name('admin.categories.update')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.edit');

Route::delete('admin/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy'])
    ->name('admin.categories.destroy')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.delete');

// Pancake Category Routes
Route::get('admin/pancake/categories', [App\Http\Controllers\CategoryController::class, 'getCategoriesFromPancake'])
    ->name('admin.pancake.categories.list')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.sync');

Route::post('admin/pancake/categories', [App\Http\Controllers\CategoryController::class, 'createCategoryInPancake'])
    ->name('admin.pancake.categories.create')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.create');

Route::put('admin/pancake/categories/{category}', [App\Http\Controllers\CategoryController::class, 'updateCategoryInPancake'])
    ->name('admin.pancake.categories.update')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.edit');

Route::post('admin/pancake/categories/sync', [App\Http\Controllers\CategoryController::class, 'syncFromPancake'])
    ->name('admin.pancake.categories.sync')
    ->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':categories.sync');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // ... existing routes ...
});
