<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PancakeWebhookLog;
use Illuminate\Http\Request;

class PancakeWebhookLogController extends Controller
{
    public function index(Request $request)
    {
        $query = PancakeWebhookLog::query()
            ->with(['order', 'customer'])
            ->latest();

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by event type if provided
        if ($request->filled('event_type')) {
            $query->where('event_type', 'like', '%' . $request->event_type . '%');
        }

        // Filter by date range if provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        // Search in request_data if search term is provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('request_data', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.pancake.webhook-logs.index', compact('logs'));
    }

    public function show(PancakeWebhookLog $log)
    {
        return view('admin.pancake.webhook-logs.show', compact('log'));
    }
}
