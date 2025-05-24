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
        try {
            // Increase execution time limit and memory limit
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            $this->authorize('settings.manage');

            // Get API configuration
            $apiKey = config('pancake.api_key');
            $shopId = config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake.'
                ], 400);
            }

            $stats = [
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'total_fetched' => 0,
                'error_messages' => []
            ];

            // Updated endpoint to use the correct path for categories
            $url = "{$baseUrl}/shops/{$shopId}/categories?api_key={$apiKey}";

            Log::info('Attempting to sync Pancake categories.', ['url' => $url, 'shop_id' => $shopId]);

            $response = Http::timeout(120)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($url);

            if (!$response->successful()) {
                $errorMessage = $response->json()['message'] ?? $response->body();
                Log::error('Pancake API call for categories failed.', [
                    'status_code' => $response->status(),
                    'error' => $errorMessage,
                    'url' => $url
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi API Pancake: " . $errorMessage
                ], $response->status());
            }

            $categoriesData = $response->json();
            $categories = [];

            // Handle different possible response formats
            if (isset($categoriesData['data']) && is_array($categoriesData['data'])) {
                $categories = $categoriesData['data'];
            } elseif (isset($categoriesData['categories']) && is_array($categoriesData['categories'])) {
                $categories = $categoriesData['categories'];
            } elseif (is_array($categoriesData) && !empty($categoriesData) && isset($categoriesData[0])) {
                $categories = $categoriesData;
            }

            if (empty($categories)) {
                Log::warning('No categories found in Pancake API response', ['response' => $categoriesData]);
                return response()->json([
                    'success' => true,
                    'message' => 'Không tìm thấy danh mục nào từ Pancake.',
                    'stats' => $stats
                ]);
            }

            DB::beginTransaction();
            try {
                foreach ($categories as $categoryData) {
                    if (!isset($categoryData['id'])) {
                        Log::warning('Category data missing ID, skipping.', ['category_data' => $categoryData]);
                        $stats['errors']++;
                        $stats['error_messages'][] = 'Danh mục thiếu ID, bỏ qua.';
                        continue;
                    }

                    $pancakeId = (string)$categoryData['id'];
                    $name = $categoryData['name'] ?? $categoryData['text'] ?? null;

                    if (empty($name)) {
                        Log::warning('Category missing name, skipping.', ['category_id' => $pancakeId]);
                        $stats['errors']++;
                        $stats['error_messages'][] = "Danh mục ID {$pancakeId} thiếu tên, bỏ qua.";
                        continue;
                    }

                    // Create or update the category
                    $category = PancakeCategory::updateOrCreate(
                        ['pancake_id' => $pancakeId],
                        [
                            'name' => $name,
                            'pancake_parent_id' => $categoryData['parent_id'] ?? null,
                            'level' => $categoryData['level'] ?? 0,
                            'status' => $categoryData['status'] ?? 'active',
                            'description' => $categoryData['description'] ?? null,
                            'image_url' => $categoryData['image_url'] ?? $categoryData['icon'] ?? null,
                            'api_response' => $categoryData
                        ]
                    );

                    if ($category->wasRecentlyCreated) {
                        $stats['created']++;
                    } else if ($category->wasChanged()) {
                        $stats['updated']++;
                    }
                    $stats['total_fetched']++;

                    // Process child categories if they exist
                    if (!empty($categoryData['children']) && is_array($categoryData['children'])) {
                        foreach ($categoryData['children'] as $childCategory) {
                            $this->processCategoryChild($childCategory, $pancakeId, $stats);
                        }
                    }
                }

                DB::commit();

                $message = sprintf(
                    'Đồng bộ thành công. Tạo mới: %d, Cập nhật: %d, Tổng: %d',
                    $stats['created'],
                    $stats['updated'],
                    $stats['total_fetched']
                );

                if ($stats['errors'] > 0) {
                    $message .= sprintf(', Lỗi: %d', $stats['errors']);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'stats' => $stats
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error processing categories', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi xử lý danh mục: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in category sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processCategoryChild($categoryData, $parentId, &$stats)
    {
        if (!isset($categoryData['id'])) {
            Log::warning('Child category missing ID, skipping.', ['parent_id' => $parentId]);
            $stats['errors']++;
            $stats['error_messages'][] = "Danh mục con của {$parentId} thiếu ID, bỏ qua.";
            return;
        }

        $pancakeId = (string)$categoryData['id'];
        $name = $categoryData['name'] ?? $categoryData['text'] ?? null;

        if (empty($name)) {
            Log::warning('Child category missing name, skipping.', ['category_id' => $pancakeId]);
            $stats['errors']++;
            $stats['error_messages'][] = "Danh mục con ID {$pancakeId} thiếu tên, bỏ qua.";
            return;
        }

        $category = PancakeCategory::updateOrCreate(
            ['pancake_id' => $pancakeId],
            [
                'name' => $name,
                'pancake_parent_id' => $parentId,
                'level' => $categoryData['level'] ?? 0,
                'status' => $categoryData['status'] ?? 'active',
                'description' => $categoryData['description'] ?? null,
                'image_url' => $categoryData['image_url'] ?? $categoryData['icon'] ?? null,
                'api_response' => $categoryData
            ]
        );

        if ($category->wasRecentlyCreated) {
            $stats['created']++;
        } else if ($category->wasChanged()) {
            $stats['updated']++;
        }
        $stats['total_fetched']++;

        // Recursively process children
        if (!empty($categoryData['children']) && is_array($categoryData['children'])) {
            foreach ($categoryData['children'] as $childCategory) {
                $this->processCategoryChild($childCategory, $pancakeId, $stats);
            }
        }
    }
}