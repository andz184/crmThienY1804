<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the Spatie Permission Service Provider
        // $this->app->register(PermissionServiceProvider::class); // Rely on auto-discovery
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();
        Schema::defaultStringLength(191);
        Order::observe(OrderObserver::class);
    }
}
