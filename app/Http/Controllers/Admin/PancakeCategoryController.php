<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PancakeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PancakeCategoryController extends Controller
{
    /**
     * Synchronize Pancake categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncCategories(Request $request)
    {
        $this->authorize('settings.manage'); // Ensure user has permission

        $apiKey = config('pancake.api_key');
        $shopId = config('pancake.shop_id'); // Assuming categories are shop-specific
        $baseUri = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1/'), '/');

        if (empty($apiKey) || empty($shopId)) {
            Log::error('Pancake API key or Shop ID is not configured for category sync.');
            return redirect()->route('admin.sync.index')->with('error', 'Chưa cấu hình API key hoặc Shop ID của Pancake.');
        }

        // Determine the API endpoint for categories
        // Based on common Pancake API patterns, this is a likely endpoint.
        // Please verify with official documentation if this is incorrect.
        $endpoint = "{$baseUri}/shops/{$shopId}/categories?api_key={$apiKey}";
        
        Log::info("Pancake Category Sync: Fetching categories from {$endpoint}");

        try {
            $response = Http::get($endpoint);
            $data = $response->json();

            if (!$response->successful() || !isset($data['success']) || $data['success'] !== true || !isset($data['data'])) {
                // The actual key for categories might be 'categories', 'list', or 'data'. We're assuming 'data'.
                Log::error('Pancake Category Sync: Failed to fetch categories or invalid response format.', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'endpoint' => $endpoint
                ]);
                $errorMessage = $data['message'] ?? ('Không thể tải danh mục từ Pancake. Status: ' . $response->status());
                 if ($response->status() == 404) {
                    $errorMessage .= ' (Endpoint not found - please verify the API endpoint for categories)';
                }
                return redirect()->route('admin.sync.index')->with('error', $errorMessage);
            }

            $categoriesFromApi = $data['data']; // Assuming the categories are in a 'data' array
            $syncedCount = 0;
            $updatedCount = 0;
            $failedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($categoriesFromApi as $apiCategory) {
                try {
                    if (empty($apiCategory['id'])) {
                        Log::warning('Pancake Category Sync: Category data missing ID, skipping.', ['category_data' => $apiCategory]);
                        $failedCount++;
                        $errors[] = 'Một danh mục bị thiếu ID từ API.';
                        continue;
                    }

                    // Map API fields to your PancakeCategory model attributes
                    // Adjust these mappings based on the actual API response structure
                    $categoryData = [
                        'name' => $apiCategory['name'] ?? 'N/A',
                        'pancake_parent_id' => $apiCategory['parent_id'] ?? ($apiCategory['parent']['id'] ?? null), // Pancake might use parent_id or a nested parent object
                        'level' => $apiCategory['level'] ?? 0,
                        'status' => $apiCategory['status'] ?? 'active', // Assuming a default status
                        'description' => $apiCategory['description'] ?? null,
                        'image_url' => $apiCategory['image_url'] ?? ($apiCategory['icon'] ?? null),
                        'api_response' => $apiCategory, // Store the full API response for this category
                    ];

                    $category = PancakeCategory::updateOrCreate(
                        ['pancake_id' => $apiCategory['id']], // Condition to find existing record
                        $categoryData                     // Data to create or update
                    );

                    if ($category->wasRecentlyCreated) {
                        $syncedCount++;
                    } else if ($category->wasChanged()) {
                        $updatedCount++;
                    } else {
                        // Not created, not updated (already in sync)
                    }

                } catch (\Exception $e) {
                    Log::error('Pancake Category Sync: Error processing category ID ' . ($apiCategory['id'] ?? 'unknown') . ': ' . $e->getMessage(), ['category_data' => $apiCategory]);
                    $failedCount++;
                    $errors[] = 'Lỗi xử lý danh mục ID ' . ($apiCategory['id'] ?? 'unknown') . ': ' . $e->getMessage();
                    // Continue to next category if one fails
                }
            }

            DB::commit();
            $successMessage = "Đồng bộ danh mục Pancake thành công! Tạo mới: {$syncedCount}, Cập nhật: {$updatedCount}.";
            if ($failedCount > 0) {
                $successMessage .= " Thất bại: {$failedCount}.";
            }
            Log::info("Pancake Category Sync: Completed. {$successMessage}", ['errors' => $errors]);
            return redirect()->route('admin.sync.index')->with('success', $successMessage);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            DB::rollBack();
            Log::error('Pancake Category Sync: ConnectionException - ' . $e->getMessage(), ['endpoint' => $endpoint]);
            return redirect()->route('admin.sync.index')->with('error', 'Không thể kết nối đến Pancake API: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pancake Category Sync: General Exception - ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.sync.index')->with('error', 'Lỗi đồng bộ danh mục chung: ' . $e->getMessage());
        }
    }
} 