<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleOrderUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;

        // Add your order update handling logic here
        // For example:
        \Illuminate\Support\Facades\Log::info('Order updated', [
            'order_id' => $order->id,
            'status' => $order->status,
            'total_value' => $order->total_value
        ]);
    }
}
