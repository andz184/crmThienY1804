<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PancakeWebhookLog;
use Illuminate\Http\Request;

class PancakeController extends Controller
{
    public function webhooks(Request $request)
    {
        // Get webhook URL
        $webhookUrl = url('/admin/pancake/webhooks');

        // Get API configuration
        $apiKey = config('pancake.api_key');
        $shopId = config('pancake.shop_id');

        // Get webhook logs with pagination and filters
        $query = PancakeWebhookLog::query()->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('event_type', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('request_data', 'like', "%{$search}%");
            });
        }

        $webhookLogs = $query->paginate(10);

        return view('admin.pancake.webhooks', compact('webhookUrl', 'apiKey', 'shopId', 'webhookLogs'));
    }
}
