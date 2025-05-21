<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PancakeConfigController extends Controller
{
    /**
     * Display the Pancake configuration page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('settings.manage');

        $settings = [
            'api_key' => WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? '',
            'shop_id' => WebsiteSetting::where('key', 'pancake_default_shop_id')->first()->value ?? '',
            'page_id' => WebsiteSetting::where('key', 'pancake_default_page_id')->first()->value ?? '',
            'webhook_secret' => WebsiteSetting::where('key', 'pancake_webhook_secret')->first()->value ?? '',
            'auto_sync' => WebsiteSetting::where('key', 'pancake_auto_sync')->first()->value ?? '1',
        ];

        return view('pancake.config', compact('settings'));
    }

    /**
     * Update Pancake API configuration
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $this->authorize('settings.manage');

        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'shop_id' => 'nullable|string',
            'page_id' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'auto_sync' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            WebsiteSetting::updateOrCreate(
                ['key' => 'pancake_api_key'],
                ['value' => $request->api_key]
            );

            WebsiteSetting::updateOrCreate(
                ['key' => 'pancake_default_shop_id'],
                ['value' => $request->shop_id]
            );

            WebsiteSetting::updateOrCreate(
                ['key' => 'pancake_default_page_id'],
                ['value' => $request->page_id]
            );

            WebsiteSetting::updateOrCreate(
                ['key' => 'pancake_webhook_secret'],
                ['value' => $request->webhook_secret]
            );

            WebsiteSetting::updateOrCreate(
                ['key' => 'pancake_auto_sync'],
                ['value' => $request->has('auto_sync') ? '1' : '0']
            );

            // Set config values for current request
            config(['pancake.api_key' => $request->api_key]);
            config(['pancake.default_shop_id' => $request->shop_id]);
            config(['pancake.default_page_id' => $request->page_id]);
            config(['pancake.webhook_secret' => $request->webhook_secret]);
            config(['pancake.auto_sync_enabled' => $request->has('auto_sync')]);

            // Test API connection
            if ($request->filled('test_connection')) {
                return $this->testConnection($request);
            }

            return redirect()->route('pancake.config')
                ->with('success', 'Cấu hình Pancake đã được cập nhật');

        } catch (\Exception $e) {
            Log::error('Error updating Pancake config', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test connection to Pancake API
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testConnection(Request $request)
    {
        $apiKey = $request->api_key ?? WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? '';

        if (empty($apiKey)) {
            return redirect()->back()
                ->with('error', 'Vui lòng nhập API Key trước khi kiểm tra kết nối')
                ->withInput();
        }

        try {
            $response = \Illuminate\Support\Facades\Http::get('https://pos.pages.fm/api/v1/shops', [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                $shops = $response->json()['shops'] ?? [];
                $shopInfo = [];

                foreach ($shops as $shop) {
                    $shopInfo[] = [
                        'id' => $shop['id'],
                        'name' => $shop['name'],
                        'pages' => collect($shop['pages'] ?? [])->map(function($page) {
                            return [
                                'id' => $page['id'],
                                'name' => $page['name']
                            ];
                        })->toArray()
                    ];
                }

                return redirect()->back()
                    ->with('success', 'Kết nối thành công với Pancake API')
                    ->with('shop_info', $shopInfo)
                    ->withInput();

            } else {
                $errorMessage = $response->json()['message'] ?? $response->body();

                return redirect()->back()
                    ->with('error', 'Kết nối thất bại: ' . $errorMessage)
                    ->withInput();
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi kết nối: ' . $e->getMessage())
                ->withInput();
        }
    }
}
