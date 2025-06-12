<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

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

        // Share favicon URL with the app layout
        View::composer('layouts.app', function ($view) {
            $faviconUrl = null;
            if (Schema::hasTable('settings')) {
                $faviconUrl = Setting::where('key', 'favicon_url')->value('value');
            }
            $view->with('favicon_url', $faviconUrl);
        });
    }
}
