<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, copy data from product_info to new columns
        $orders = DB::table('orders')->get();
        foreach ($orders as $order) {
            if ($order->product_info) {
                $productInfo = json_decode($order->product_info, true);
                if ($productInfo) {
                    DB::table('orders')->where('id', $order->id)->update([
                        'shipping_fee' => $productInfo['shipping_fee'] ?? 0,
                        'payment_method' => $productInfo['payment_method'] ?? null,
                        'shipping_provider' => $productInfo['shipping_provider'] ?? null,
                        'internal_status' => $productInfo['internal_status'] ?? null,
                        'notes' => $productInfo['notes'] ?? null,
                        'additional_notes' => $productInfo['additional_notes'] ?? null,
                    ]);

                    // Create order items
                    if (isset($productInfo['items']) && is_array($productInfo['items'])) {
                        foreach ($productInfo['items'] as $item) {
                            DB::table('order_items')->insert([
                                'order_id' => $order->id,
                                'code' => $item['code'],
                                'quantity' => $item['quantity'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        // Then add new columns
        // Schema::table('orders', function (Blueprint $table) {
        //     $table->decimal('shipping_fee', 10, 2)->default(0)->after('full_address');
        //     $table->string('payment_method')->nullable()->after('shipping_fee');
        //     $table->string('shipping_provider')->nullable()->after('payment_method');
        //     $table->string('internal_status')->nullable()->after('shipping_provider');
        //     $table->text('notes')->nullable()->after('internal_status');
        //     $table->text('additional_notes')->nullable()->after('notes');
        // });

        // Finally, drop product_info column
        // Schema::table('orders', function (Blueprint $table) { // Intentionally commented out
        //     $table->dropColumn('product_info');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // First add back product_info column
            $table->json('product_info')->nullable();

            // Copy data back to product_info
            $orders = DB::table('orders')->get();
            foreach ($orders as $order) {
                $items = DB::table('order_items')->where('order_id', $order->id)->get();
                $productInfo = [
                    'shipping_fee' => $order->shipping_fee,
                    'payment_method' => $order->payment_method,
                    'shipping_provider' => $order->shipping_provider,
                    'internal_status' => $order->internal_status,
                    'notes' => $order->notes,
                    'additional_notes' => $order->additional_notes,
                    'items' => $items->map(function ($item) {
                        return [
                            'code' => $item->code,
                            'quantity' => $item->quantity
                        ];
                    })->toArray()
                ];

                DB::table('orders')->where('id', $order->id)->update([
                    'product_info' => json_encode($productInfo)
                ]);
            }

            // Then drop the new columns
            // $table->dropColumn([
            //     'shipping_fee',
            //     'payment_method',
            //     'shipping_provider',
            //     'internal_status',
            //     'notes',
            //     'additional_notes'
            // ]);
        });
    }
};
