<?php

// Load Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check order_items table structure
echo "Checking order_items table structure:\n";
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('order_items');
echo "Columns in order_items table: " . implode(', ', $columns) . "\n\n";

// Check if specific columns exist
echo "Does product_code column exist? " . 
    (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_code') ? 'Yes' : 'No') . "\n";
echo "Does product_name column exist? " . 
    (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'product_name') ? 'Yes' : 'No') . "\n";
echo "Does pancake_variant_id column exist? " . 
    (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'pancake_variant_id') ? 'Yes' : 'No') . "\n";
echo "Does code column exist? " . 
    (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'code') ? 'Yes' : 'No') . "\n";

echo "\nDone!\n"; 