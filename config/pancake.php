<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pancake API Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and configuration for the Pancake API.
    |
    */

    'base_uri' => env('PANCAKE_API_URL', 'https://pos.pages.fm/api/v1'),
    'api_key' => env('PANCAKE_API_KEY'),
    'shop_id' => env('PANCAKE_SHOP_ID'),

    // Default values for Pancake order payload - can be overridden per order if needed
    'default_page_id' => env('PANCAKE_DEFAULT_PAGE_ID', '256469571178082'), // Example from your JSON
    'default_account' => env('PANCAKE_DEFAULT_ACCOUNT', 4), // Example from your JSON
    'default_account_name' => env('PANCAKE_DEFAULT_ACCOUNT_NAME', 'facebook321'), // Example from your JSON
    'default_is_free_shipping' => env('PANCAKE_DEFAULT_IS_FREE_SHIPPING', false),
    'default_received_at_shop' => env('PANCAKE_DEFAULT_RECEIVED_AT_SHOP', false),
    'default_returned_reason' => env('PANCAKE_DEFAULT_RETURNED_REASON', 1), // Example from your JSON

    // Cấu hình tự động đồng bộ
    'auto_sync_enabled' => env('PANCAKE_AUTO_SYNC_ENABLED', true),

    // You might also want to configure mapping for specific IDs if they are static
    // e.g. 'pancake_country_code' => env('PANCAKE_COUNTRY_CODE', 'VN'),
];
