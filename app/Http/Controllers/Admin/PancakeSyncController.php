<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PancakeShop;
use App\Models\PancakePage;
use Illuminate\Support\Facades\DB;

class PancakeSyncController extends Controller
{
    /**
     * Display the Pancake sync page.
     */
    public function index()
    {
        $shops = PancakeShop::with('pages')->orderBy('name')->get();
        $lastSyncTime = PancakeShop::max('updated_at'); // A simple way to get a recent sync time
        // If no shops, maybe check PancakePage or a dedicated log table in the future
        if (!$lastSyncTime && PancakePage::count() > 0) {
            $lastSyncTime = PancakePage::max('updated_at');
        }

        return view('admin.pancake.sync', compact('shops', 'lastSyncTime'));
    }

    /**
     * Perform the synchronization with Pancake API for shops and pages.
     */
    public function syncNow(Request $request)
    {
        $apiKey = config('pancake.api_key');
        $baseUri = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1/'), '/');

        if (empty($apiKey)) {
            Log::error('Pancake API key is not configured for sync.');
            return response()->json(['success' => false, 'message' => 'Pancake API key is not configured.'], 500);
        }

        $endpoint = $baseUri . '/shops?api_key=' . $apiKey;
        Log::info('Pancake Sync: Fetching shops from ' . $endpoint);

        try {
            $response = Http::get($endpoint);
            $data = $response->json();

            if (!$response->successful() || !isset($data['success']) || $data['success'] !== true || !isset($data['shops'])) {
                Log::error('Pancake Sync: Failed to fetch shops or invalid response format.', [
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                $errorMessage = $data['message'] ?? ('Failed to fetch shops from Pancake. Status: ' . $response->status());
                return response()->json(['success' => false, 'message' => $errorMessage, 'details' => $data], $response->status() ?: 500);
            }

            $shopsFromApi = $data['shops'];
            $syncedShopsCount = 0;
            $syncedPagesCount = 0;

            DB::beginTransaction();

            foreach ($shopsFromApi as $shopData) {
                if (!isset($shopData['id'])) {
                    Log::warning('Pancake Sync: Shop data missing ID, skipping.', ['shop_data' => $shopData]);
                    continue;
                }

                $pancakeShop = PancakeShop::updateOrCreate(
                    ['pancake_id' => $shopData['id']],
                    [
                        'name' => $shopData['name'] ?? 'N/A',
                        'avatar_url' => $shopData['avatar_url'] ?? null,
                        'raw_data' => $shopData ?? [],
                    ]
                );
                $syncedShopsCount++;

                if (isset($shopData['pages']) && is_array($shopData['pages'])) {
                    foreach ($shopData['pages'] as $pageData) {
                        if (!isset($pageData['id'])) {
                            Log::warning('Pancake Sync: Page data missing ID, skipping.', ['page_data' => $pageData, 'shop_pancake_id' => $pancakeShop->pancake_id]);
                            continue;
                        }
                        PancakePage::updateOrCreate(
                            ['pancake_page_id' => (string)$pageData['id']], // Page ID can be string
                            [
                                'pancake_shop_table_id' => $pancakeShop->id, // Link to our DB's shop PK
                                'name' => $pageData['name'] ?? 'N/A',
                                'platform' => $pageData['platform'] ?? null,
                                'settings' => $pageData['settings'] ?? [],
                                'raw_data' => $pageData ?? [],
                            ]
                        );
                        $syncedPagesCount++;
                    }
                }
            }

            DB::commit();
            Log::info("Pancake Sync: Successfully synced {$syncedShopsCount} shops and {$syncedPagesCount} pages.");
            return response()->json(['success' => true, 'message' => "Đồng bộ thành công! Đã cập nhật {$syncedShopsCount} shop và {$syncedPagesCount} trang."]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            DB::rollBack();
            Log::error('Pancake Sync: ConnectionException - ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể kết nối đến Pancake API: ' . $e->getMessage()], 503);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pancake Sync: General Exception - ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Lỗi đồng bộ chung: ' . $e->getMessage()], 500);
        }
    }
}
