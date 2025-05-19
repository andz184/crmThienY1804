<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class PancakeWebhookConfigController extends Controller
{
    /**
     * Display the webhook configuration page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('settings.manage');

        // Just a single webhook URL for all types of data
        $webhookUrl = URL::to('/api/webhooks/pancake');

        // Get Pancake API key and shop ID for reference
        $apiKey = config('pancake.api_key');
        $shopId = config('pancake.shop_id');

        return view('admin.pancake.webhooks', compact('webhookUrl', 'apiKey', 'shopId'));
    }
}
