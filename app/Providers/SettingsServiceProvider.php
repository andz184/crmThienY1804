<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Check if the settings table exists to avoid errors during migrations
        if (Schema::hasTable('settings')) {
            $settings = Setting::pluck('value', 'key')->all();

            // Dynamically set config values from database
            if (!empty($settings)) {
                // Set App Name/Title
                if (isset($settings['app_name'])) {
                    Config::set('adminlte.title', $settings['app_name']);
                    Config::set('adminlte.logo', $settings['app_name']);
                }

                // Set Logo Image
                if (isset($settings['app_logo_url'])) {
                    Config::set('adminlte.logo_img', $settings['app_logo_url']);
                }

                // Set Favicon
                if (isset($settings['favicon_url'])) {
                    // AdminLTE uses an array for favicons, we create it dynamically
                    Config::set('adminlte.favicons', [
                        [
                            'type' => 'image/png', // You might want to store mime type in db
                            'href' => $settings['favicon_url'],
                        ],
                    ]);
                }
            }
        }
    }
}
