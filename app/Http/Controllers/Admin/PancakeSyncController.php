<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PancakeShop;
use App\Models\PancakePage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;

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

    /**
     * Đồng bộ đơn hàng theo ngày từ giao diện sử dụng Pancake API
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncOrdersByDateManual(Request $request)
    {
        try {
            $this->authorize('settings.manage');

            // Check if date parameter is provided
            $syncAllOrders = !$request->has('date');
            
            if (!$syncAllOrders) {
                $request->validate([
                    'date' => 'required|date_format:Y-m-d',
                ]);
                $date = Carbon::createFromFormat('Y-m-d', $request->date);
                $formattedDate = $date->format('Y-m-d');
                $syncIdentifier = 'date_' . $formattedDate;
            } else {
                // For syncing all orders
                $syncIdentifier = 'all_' . date('Ymd_His');
            }

            // Display value for logs and messages
            $displayValue = $syncAllOrders ? 'TẤT CẢ ĐƠN HÀNG' : 'ngày ' . ($date ?? Carbon::now())->format('d/m/Y');

            // Kiểm tra và hủy bỏ các tiến trình đồng bộ bị treo
            $stuckSyncCancelled = $this->detectAndCancelStuckSync();
            if ($stuckSyncCancelled) {
                Log::info("Đã tự động hủy đồng bộ bị treo trước khi bắt đầu đồng bộ mới cho {$displayValue}");
            }

            // Kiểm tra xem có đồng bộ nào đang chạy không
            if (Cache::has('pancake_sync_in_progress')) {
                $runningSync = Cache::get('pancake_sync_in_progress');
                $syncStartTime = Cache::get('pancake_sync_start_time');

                // Nếu đồng bộ chạy hơn 30 phút, coi như đã timeout và cho phép chạy lại
                if ($syncStartTime && now()->diffInMinutes($syncStartTime) > 30) {
                    Log::warning("Phát hiện đồng bộ treo quá 30 phút, tự động hủy: {$runningSync}");
                    Cache::forget('pancake_sync_in_progress');
                    Cache::forget('pancake_sync_start_time');
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Đã có quá trình đồng bộ đang chạy ({$runningSync}). Vui lòng đợi quá trình này hoàn tất."
                    ], 429); // HTTP 429 Too Many Requests
                }
            }

            // Đánh dấu đang có quá trình đồng bộ chạy
            Cache::put('pancake_sync_in_progress', 'Đồng bộ ' . $displayValue, 3600); // Tối đa 1 giờ
            Cache::put('pancake_sync_start_time', now(), 3600); // Lưu thời điểm bắt đầu

            // Khởi tạo trạng thái tiến trình
            Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                'progress' => 0,
                'total_items' => 0,
                'processed_items' => 0,
                'message' => 'Đang bắt đầu đồng bộ...'
            ], 3600);

            // If syncing all orders, store the syncIdentifier for later reference
            if ($syncAllOrders) {
                Cache::put('pancake_latest_all_sync_id', $syncIdentifier, now()->addDays(7));
            }

            // Lấy thông tin cấu hình API
            $apiKey = config('pancake.api_key');
            $shopId = config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                // Xóa trạng thái đồng bộ nếu không có cấu hình
                Cache::forget('pancake_sync_in_progress');
                Cache::forget('pancake_sync_progress_' . $syncIdentifier);
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake'
                ], 400);
            }

            // Đối với yêu cầu từ người dùng, chúng ta sử dụng queue để thực hiện bất đồng bộ
            dispatch(function() use ($syncAllOrders, $syncIdentifier, $displayValue, $apiKey, $shopId, $baseUrl, $request) {
                try {
                    $date = null;
                    $formattedDate = null;
                    $startDateTime = null;
                    $endDateTime = null;
                    
                    if (!$syncAllOrders) {
                        $date = Carbon::createFromFormat('Y-m-d', $request->date);
                        $formattedDate = $date->format('Y-m-d');
                        // Chuyển đổi ngày thành timestamps để lọc chính xác theo ngày
                        $startDateTime = $date->startOfDay()->timestamp;
                        $endDateTime = $date->copy()->endOfDay()->timestamp;
                    }
                    
                    Log::info("Bắt đầu đồng bộ đơn hàng Pancake cho {$displayValue}", [
                        'sync_all' => $syncAllOrders,
                        'date' => $formattedDate,
                        'shop_id' => $shopId
                    ]);

                    // Cập nhật tiến trình - đang khởi tạo
                    Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                        'progress' => 5,
                        'total_items' => 0,
                        'processed_items' => 0,
                        'message' => 'Đang kết nối đến API Pancake...'
                    ], 3600);

                    // Gọi API đầu tiên để xác định tổng số đơn hàng - sử dụng đúng URL format
                    try {
                        $params = [
                            'api_key' => $apiKey,
                            'page' => 1,
                            'per_page' => 1, // Chỉ lấy 1 đơn để xem meta
                        ];
                        
                        // Thêm timestamp params chỉ khi đồng bộ theo ngày
                        if (!$syncAllOrders && $startDateTime && $endDateTime) {
                            $params['startDateTime'] = $startDateTime;
                            $params['endDateTime'] = $endDateTime;
                        }
                        
                        $initialResponse = Http::get("{$baseUrl}/shops/{$shopId}/orders", $params);

                        $initialData = $initialResponse->json();
                        $totalItems = 0;

                        if (isset($initialData['meta']) && isset($initialData['meta']['total'])) {
                            $totalItems = $initialData['meta']['total'];
                        } elseif (isset($initialData['total'])) {
                            $totalItems = $initialData['total'];
                        }

                        // Cập nhật tổng số đơn hàng
                        Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                            'progress' => 10,
                            'total_items' => $totalItems,
                            'processed_items' => 0,
                            'message' => "Tìm thấy {$totalItems} đơn hàng cần đồng bộ"
                        ], 3600);
                    } catch (\Exception $e) {
                        Log::warning("Không thể xác định tổng số đơn hàng: " . $e->getMessage());
                    }

                    // Gọi API Pancake để lấy danh sách đơn hàng
                    $page = 1;
                    $perPage = 50;
                    $hasMorePages = true;
                    $totalImported = 0;
                    $totalUpdated = 0;
                    $totalFailed = 0;
                    $errors = [];
                    $processedOrders = [];
                    $processedItems = 0;

                    while ($hasMorePages) {
                        try {
                            // Cập nhật tiến trình - đang lấy dữ liệu trang
                            Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                                'progress' => max(10, min(90, 10 + ($processedItems / max(1, $totalItems)) * 80)),
                                'total_items' => $totalItems,
                                'processed_items' => $processedItems,
                                'message' => "Đang xử lý trang {$page}..."
                            ], 3600);

                            // Gọi API Pancake để lấy danh sách đơn hàng
                            $params = [
                                'api_key' => $apiKey,
                                'page' => $page,
                                'per_page' => $perPage,
                            ];
                            
                            // Thêm timestamp params chỉ khi đồng bộ theo ngày
                            if (!$syncAllOrders && $startDateTime && $endDateTime) {
                                $params['startDateTime'] = $startDateTime;
                                $params['endDateTime'] = $endDateTime;
                            }
                            
                            $response = Http::get("{$baseUrl}/shops/{$shopId}/orders", $params);

                            if (!$response->successful()) {
                                throw new \Exception("API Error: " . ($response->json()['message'] ?? $response->status()));
                            }

                            $data = $response->json();

                            // Xác định danh sách đơn hàng từ API response
                            $orders = [];
                            if (isset($data['orders'])) {
                                $orders = $data['orders'];
                            } elseif (isset($data['data'])) {
                                $orders = $data['data'];
                            }

                            if (empty($orders)) {
                                Log::info("Không có đơn hàng nào được tìm thấy" . ($formattedDate ? " cho ngày {$formattedDate}" : ""));
                                break;
                            }

                            // Xử lý từng đơn hàng
                            foreach ($orders as $index => $orderData) {
                                try {
                                    DB::beginTransaction();

                                    $orderInfo = [
                                        'id' => $orderData['id'] ?? 'N/A',
                                        'code' => $orderData['code'] ?? 'N/A',
                                        'status' => $orderData['status'] ?? 'N/A',
                                    ];

                                    // Kiểm tra xem đơn hàng đã tồn tại chưa
                                    $existingOrder = Order::where('pancake_order_id', $orderData['id'])->first();

                                    if ($existingOrder) {
                                        // Cập nhật đơn hàng hiện có
                                        $this->updateOrderFromPancakeData($existingOrder, $orderData);
                                        $totalUpdated++;
                                        $orderInfo['action'] = 'updated';
                                    } else {
                                        // Tạo đơn hàng mới
                                        $newOrder = $this->createOrderFromPancakeData($orderData);
                                        $totalImported++;
                                        $orderInfo['action'] = 'created';
                                        $orderInfo['new_order_id'] = $newOrder->id ?? null;
                                    }

                                    DB::commit();
                                    $processedOrders[] = $orderInfo;
                                    $processedItems++;

                                    // Cập nhật tiến trình cho mỗi đơn hàng đã xử lý
                                    if ($index % 5 == 0 || $index == count($orders) - 1) { // Cập nhật mỗi 5 đơn hàng để tránh quá nhiều ghi cache
                                        Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                                            'progress' => max(10, min(90, 10 + ($processedItems / max(1, $totalItems)) * 80)),
                                            'total_items' => $totalItems,
                                            'processed_items' => $processedItems,
                                            'new_orders' => $totalImported,
                                            'updated_orders' => $totalUpdated,
                                            'failed_orders' => $totalFailed,
                                            'message' => "Đang xử lý đơn hàng: {$orderInfo['code']}"
                                        ], 3600);
                                    }

                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    $totalFailed++;
                                    $errorMsg = "Lỗi xử lý đơn hàng " . ($orderData['id'] ?? 'unknown') . ": " . $e->getMessage();
                                    $errors[] = $errorMsg;
                                    $processedOrders[] = [
                                        'id' => $orderData['id'] ?? 'N/A',
                                        'code' => $orderData['code'] ?? 'N/A',
                                        'action' => 'failed',
                                        'error' => $e->getMessage()
                                    ];
                                    $processedItems++;

                                    Log::error($errorMsg, [
                                        'order_id' => $orderData['id'] ?? 'unknown',
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }

                            // Kiểm tra có trang tiếp theo không
                            if (isset($data['meta'])) {
                                $currentPage = $data['meta']['current_page'] ?? $page;
                                $lastPage = $data['meta']['last_page'] ?? 1;
                                $hasMorePages = count($orders) >= $perPage && $currentPage < $lastPage;
                            } else {
                                $hasMorePages = count($orders) >= $perPage;
                            }

                            $page++;

                        } catch (\Exception $e) {
                            Log::error("Lỗi khi lấy dữ liệu từ API Pancake: " . $e->getMessage());
                            $errors[] = "Lỗi API: " . $e->getMessage();
                            $hasMorePages = false;
                        }
                    }

                    // Cập nhật tiến trình - hoàn tất
                    Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                        'progress' => 100,
                        'total_items' => $totalItems,
                        'processed_items' => $processedItems,
                        'new_orders' => $totalImported,
                        'updated_orders' => $totalUpdated,
                        'failed_orders' => $totalFailed,
                        'message' => "Đồng bộ hoàn tất"
                    ], 3600);

                    // Lưu kết quả đồng bộ
                    $result = [
                        'success' => true,
                        'message' => "Đồng bộ hoàn tất: {$totalImported} đơn mới, {$totalUpdated} đơn cập nhật, {$totalFailed} đơn lỗi",
                        'total_synced' => $totalImported + $totalUpdated,
                        'new_orders' => $totalImported,
                        'updated_orders' => $totalUpdated,
                        'failed' => $totalFailed,
                        'errors' => $errors,
                        'date' => $formattedDate,
                        'sync_all' => $syncAllOrders,
                        'processed_orders' => $processedOrders
                    ];

                    Cache::put('pancake_sync_result_' . $syncIdentifier, $result, now()->addDay());

                    Log::info("Kết thúc đồng bộ đơn hàng cho {$displayValue}", [
                        'total_synced' => $totalImported + $totalUpdated,
                        'new_orders' => $totalImported,
                        'updated_orders' => $totalUpdated
                    ]);

                    // Store the last successful sync timestamp
                    if ($totalImported > 0 || $totalUpdated > 0) {
                        Cache::put('pancake_last_sync_time', now()->timestamp, now()->addMonth());
                        if ($formattedDate) {
                            Cache::put('pancake_last_sync_date', $formattedDate, now()->addMonth());
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("Lỗi nghiêm trọng khi đồng bộ: " . $e->getMessage(), [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Cập nhật tiến trình - lỗi
                    Cache::put('pancake_sync_progress_' . $syncIdentifier, [
                        'progress' => 100,
                        'total_items' => 0,
                        'processed_items' => 0,
                        'message' => "Lỗi: " . $e->getMessage(),
                        'error' => true
                    ], 3600);

                    // Lưu thông tin lỗi vào cache
                    Cache::put('pancake_sync_result_' . $syncIdentifier, [
                        'success' => false,
                        'message' => 'Đồng bộ thất bại: ' . $e->getMessage(),
                        'total_synced' => 0,
                        'new_orders' => 0,
                        'updated_orders' => 0,
                        'failed' => 1,
                        'errors' => [$e->getMessage()],
                        'date' => $formattedDate,
                        'sync_all' => $syncAllOrders,
                        'processed_orders' => []
                    ], now()->addDay());
                } finally {
                    // Luôn xóa trạng thái đang đồng bộ khi hoàn tất
                    Cache::forget('pancake_sync_in_progress');
                    Cache::forget('pancake_sync_start_time');

                    // Log kết thúc quá trình
                    Log::info("Kết thúc quá trình đồng bộ Pancake cho {$displayValue}", [
                        'success' => !isset($e),
                        'error' => isset($e) ? $e->getMessage() : null
                    ]);
                }
            })->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Đã bắt đầu đồng bộ đơn hàng cho ' . $displayValue . '. Quá trình đồng bộ sẽ diễn ra trong nền.'
            ]);

        } catch (\Exception $e) {
            // Đảm bảo xóa khóa nếu có lỗi
            Cache::forget('pancake_sync_in_progress');

            Log::error('Lỗi bắt đầu đồng bộ đơn hàng', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kiểm tra kết quả đồng bộ đơn hàng
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkSyncOrdersResult(Request $request)
    {
        try {
            $this->authorize('settings.manage');

            // Check if request is for all orders or specific date
            if ($request->has('sync_all') && $request->sync_all) {
                // For all orders sync
                $latestAllSync = Cache::get('pancake_latest_all_sync_id');
                
                if (!$latestAllSync) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chưa có kết quả đồng bộ TẤT CẢ đơn hàng hoặc quá trình đồng bộ đang được thực hiện.',
                        'status' => 'pending'
                    ]);
                }
                
                $syncIdentifier = $latestAllSync;
            } else {
                // For date-specific sync
                $request->validate([
                    'date' => 'required|date_format:Y-m-d',
                ]);
                
                $date = $request->date;
                $syncIdentifier = 'date_' . $date;
            }
            
            $result = Cache::get('pancake_sync_result_' . $syncIdentifier);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có kết quả đồng bộ hoặc quá trình đồng bộ đang được thực hiện.',
                    'status' => 'pending'
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 'completed',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin tiến trình đồng bộ đơn hàng
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncProgress(Request $request)
    {
        try {
            $this->authorize('settings.manage');

            // Check if request is for all orders or specific date
            if ($request->has('sync_all') && $request->sync_all) {
                // For all orders sync
                $latestAllSync = Cache::get('pancake_latest_all_sync_id');
                
                if (!$latestAllSync) {
                    // Try to check if sync is in progress
                    if (Cache::has('pancake_sync_in_progress')) {
                        $inProgressSync = Cache::get('pancake_sync_in_progress');
                        if (strpos($inProgressSync, 'TẤT CẢ ĐƠN HÀNG') !== false) {
                            return response()->json([
                                'success' => true,
                                'in_progress' => true,
                                'progress' => 5, // Initial progress value
                                'message' => 'Đang đồng bộ tất cả đơn hàng...',
                                'sync_all' => true
                            ]);
                        }
                    }
                    
                    return response()->json([
                        'success' => false,
                        'in_progress' => false,
                        'progress' => 0,
                        'message' => 'Không tìm thấy thông tin đồng bộ cho tất cả đơn hàng',
                        'sync_all' => true
                    ]);
                }
                
                $syncIdentifier = $latestAllSync;
                $date = null;
            } else {
                // For date-specific sync
                $request->validate([
                    'date' => 'required|date_format:Y-m-d',
                ]);
                
                $date = $request->date;
                $syncIdentifier = 'date_' . $date;
            }
            
            $progress = Cache::get('pancake_sync_progress_' . $syncIdentifier);

            if (!$progress) {
                // Kiểm tra xem có đang đồng bộ không
                if (Cache::has('pancake_sync_in_progress')) {
                    return response()->json([
                        'success' => true,
                        'in_progress' => true,
                        'progress' => 0,
                        'message' => 'Đang đồng bộ trong nền...',
                        'date' => $date,
                        'sync_all' => $date === null
                    ]);
                }

                // Kiểm tra xem đã có kết quả đồng bộ chưa
                $result = Cache::get('pancake_sync_result_' . $syncIdentifier);
                if ($result) {
                    return response()->json([
                        'success' => true,
                        'in_progress' => false,
                        'progress' => 100,
                        'message' => $result['message'] ?? 'Đồng bộ đã hoàn tất',
                        'result' => $result,
                        'date' => $date,
                        'sync_all' => $date === null
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'in_progress' => false,
                    'progress' => 0,
                    'message' => 'Không tìm thấy thông tin đồng bộ',
                    'date' => $date,
                    'sync_all' => $date === null
                ]);
            }

            // Trả về thông tin tiến trình
            return response()->json([
                'success' => true,
                'in_progress' => $progress['progress'] < 100,
                'progress' => $progress['progress'],
                'total_items' => $progress['total_items'],
                'processed_items' => $progress['processed_items'],
                'message' => $progress['message'],
                'new_orders' => $progress['new_orders'] ?? 0,
                'updated_orders' => $progress['updated_orders'] ?? 0,
                'failed_orders' => $progress['failed_orders'] ?? 0,
                'error' => $progress['error'] ?? false,
                'date' => $date,
                'sync_all' => $date === null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo đơn hàng mới từ dữ liệu Pancake
     *
     * @param array $orderData Dữ liệu đơn hàng từ Pancake API
     * @return \App\Models\Order
     */
    protected function createOrderFromPancakeData(array $orderData)
    {
        // Tìm hoặc tạo khách hàng
        $customer = null;
        if (!empty($orderData['customer'])) {
            $customerData = $orderData['customer'];

            // Tìm khách hàng theo Pancake ID
            if (!empty($customerData['id'])) {
                $customer = \App\Models\Customer::where('pancake_id', $customerData['id'])->first();
            }

            // Nếu không tìm thấy, thử tìm theo số điện thoại
            if (!$customer && !empty($customerData['phone'])) {
                $customer = \App\Models\Customer::where('phone', $customerData['phone'])->first();
            }

            // Nếu vẫn không tìm thấy, tạo khách hàng mới
            if (!$customer) {
                $customer = new \App\Models\Customer();
                $customer->name = $customerData['name'] ?? '';
                $customer->phone = $customerData['phone'] ?? '';
                $customer->email = $customerData['email'] ?? '';
                $customer->pancake_id = $customerData['id'] ?? null;
                $customer->address = $customerData['address'] ?? '';
                $customer->save();
            }
        }

        // Tìm hoặc tạo shop và page
        $shopId = null;
        $pageId = null;

        if (!empty($orderData['shop_id'])) {
            $shop = \App\Models\PancakeShop::where('pancake_id', $orderData['shop_id'])->first();
            if ($shop) {
                $shopId = $shop->id;
            }
        }

        if (!empty($orderData['page_id'])) {
            $page = \App\Models\PancakePage::where('pancake_id', $orderData['page_id'])->first();
            if ($page) {
                $pageId = $page->id;
            }
        }

        // Map trạng thái Pancake sang trạng thái nội bộ
        $status = $this->mapPancakeStatus($orderData['status'] ?? 'pending');

        // Tạo đơn hàng mới
        $order = new \App\Models\Order();
        $order->pancake_order_id = $orderData['id'] ?? null;
        $order->order_code = $orderData['code'] ?? ('PCK-' . \Illuminate\Support\Str::random(8));
        $order->customer_name = $orderData['customer']['name'] ?? ($customer ? $customer->name : '');
        $order->customer_phone = $orderData['customer']['phone'] ?? ($customer ? $customer->phone : '');
        $order->customer_email = $orderData['customer']['email'] ?? ($customer ? $customer->email : '');
        $order->customer_id = $customer ? $customer->id : null;
        $order->status = $status;
        $order->pancake_status = $orderData['status'] ?? '';
        $order->internal_status = 'Imported from Pancake';
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->total_value = $orderData['total'] ?? 0;

        // Xử lý địa chỉ nếu có
        if (!empty($orderData['shipping_address'])) {
            $order->full_address = $orderData['shipping_address']['full_address'] ?? '';
            $order->province_code = $orderData['shipping_address']['province_id'] ?? null;
            $order->district_code = $orderData['shipping_address']['district_id'] ?? null;
            $order->ward_code = $orderData['shipping_address']['commune_id'] ?? null;
            $order->street_address = $orderData['shipping_address']['address'] ?? '';
        }

        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->notes = $orderData['note'] ?? '';
        $order->created_by = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;
        $order->save();

        // Tạo các item của đơn hàng
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new \App\Models\OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_name = $item['name'] ?? 'Unknown Product';
                $orderItem->product_code = $item['sku'] ?? null;
                $orderItem->quantity = $item['quantity'] ?? 1;
                $orderItem->price = $item['price'] ?? 0;
                $orderItem->pancake_variant_id = $item['variant_id'] ?? null;
                $orderItem->save();
            }
        }

        return $order;
    }

    /**
     * Cập nhật đơn hàng từ dữ liệu Pancake
     *
     * @param \App\Models\Order $order Đơn hàng cần cập nhật
     * @param array $orderData Dữ liệu đơn hàng từ Pancake API
     * @return \App\Models\Order
     */
    protected function updateOrderFromPancakeData(\App\Models\Order $order, array $orderData)
    {
        // Cập nhật thông tin cơ bản
        $order->order_code = $orderData['code'] ?? $order->order_code;
        $order->status = $this->mapPancakeStatus($orderData['status'] ?? $order->status);
        $order->pancake_status = $orderData['status'] ?? $order->pancake_status;
        $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
        $order->payment_method = $orderData['payment_method'] ?? $order->payment_method;
        $order->total_value = $orderData['total'] ?? $order->total_value;
        $order->notes = $orderData['note'] ?? $order->notes;

        // Cập nhật địa chỉ nếu có
        if (!empty($orderData['shipping_address'])) {
            $order->full_address = $orderData['shipping_address']['full_address'] ?? $order->full_address;
            $order->province_code = $orderData['shipping_address']['province_id'] ?? $order->province_code;
            $order->district_code = $orderData['shipping_address']['district_id'] ?? $order->district_code;
            $order->ward_code = $orderData['shipping_address']['commune_id'] ?? $order->ward_code;
            $order->street_address = $orderData['shipping_address']['address'] ?? $order->street_address;
        }

        $order->save();

        // Cập nhật các item của đơn hàng - xóa cũ và tạo mới
        $order->items()->delete();

        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new \App\Models\OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_name = $item['name'] ?? 'Unknown Product';
                $orderItem->product_code = $item['sku'] ?? null;
                $orderItem->quantity = $item['quantity'] ?? 1;
                $orderItem->price = $item['price'] ?? 0;
                $orderItem->pancake_variant_id = $item['variant_id'] ?? null;
                $orderItem->save();
            }
        }

        return $order;
    }

    /**
     * Map trạng thái Pancake sang trạng thái nội bộ
     *
     * @param string $pancakeStatus Trạng thái từ Pancake
     * @return string Trạng thái nội bộ
     */
    protected function mapPancakeStatus(string $pancakeStatus): string
    {
        return match (strtolower($pancakeStatus)) {
            'done', 'completed' => \App\Models\Order::STATUS_DA_THU_TIEN,
            'shipping' => \App\Models\Order::STATUS_DA_GUI_HANG,
            'delivered' => \App\Models\Order::STATUS_DA_NHAN,
            'canceled' => \App\Models\Order::STATUS_DA_HUY,
            'pending' => \App\Models\Order::STATUS_CAN_XU_LY,
            'processing' => \App\Models\Order::STATUS_CHO_CHUYEN_HANG,
            'waiting' => \App\Models\Order::STATUS_CHO_HANG,
            default => \App\Models\Order::STATUS_MOI,
        };
    }

    /**
     * Sync customer data from Pancake
     */
    public function sync(Request $request)
    {
        try {
            // Kiểm tra quyền
            $this->authorize('settings.manage');

            // Lấy các tham số từ request
            $chunk = $request->input('chunk', 100);
            $force = $request->boolean('force', false);

            // Chạy command đồng bộ
            $exitCode = \Illuminate\Support\Facades\Artisan::call('pancake:sync-customers', [
                '--chunk' => $chunk,
                '--force' => $force
            ]);

            // Lấy output từ command
            $output = \Illuminate\Support\Facades\Artisan::output();

            if ($exitCode === 0) {
                // Ghi log thành công
                Log::info('Đồng bộ Pancake thành công', [
                    'user_id' => Auth::id() ?? 'system',
                    'chunk' => $chunk,
                    'force' => $force,
                    'output' => $output
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Đồng bộ thành công',
                    'output' => explode("\n", trim($output))
                ]);
            } else {
                throw new \Exception('Đồng bộ thất bại với mã lỗi: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ Pancake', [
                'user_id' => Auth::id() ?? 'system',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check sync status
     */
    public function status()
    {
        $progressKey = 'pancake_sync_progress';
        $statsKey = 'pancake_sync_stats';

        $progress = Cache::get($progressKey, 0);
        $stats = Cache::get($statsKey, [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'errors' => []
        ]);

        return response()->json([
            'progress' => $progress,
            'stats' => $stats,
            'is_completed' => $progress >= 100,
            'message' => $progress >= 100 ? 'Đồng bộ hoàn tất' : 'Đang đồng bộ...'
        ]);
    }

    /**
     * Cancel the ongoing sync process
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSync(Request $request)
    {
        try {
            // Lấy ngày từ cache đang chạy, nếu có
            $runningSync = Cache::get('pancake_sync_in_progress');
            $dateMatch = null;

            // Trích xuất ngày từ thông báo đồng bộ, ví dụ: "Đồng bộ ngày 19/05/2025"
            if ($runningSync && preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $runningSync, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $dateMatch = "{$year}-{$month}-{$day}";
            }

            // Xóa cache đánh dấu đồng bộ đang chạy
            Cache::forget('pancake_sync_in_progress');
            Cache::forget('pancake_sync_start_time');

            // Xóa các cache liên quan đến tiến trình đồng bộ của ngày cụ thể
            if ($dateMatch) {
                Cache::forget('pancake_sync_progress_' . $dateMatch);
                Cache::forget('pancake_sync_result_' . $dateMatch);
            }

            // Nếu request bao gồm force=true, xóa tất cả các key liên quan đến đồng bộ
            if ($request->input('force', false)) {
                $keys = [
                    'pancake_sync_progress',
                    'pancake_sync_stats'
                ];

                foreach ($keys as $key) {
                    Cache::forget($key);
                }

                // Quét và xóa tất cả các key tiến trình đồng bộ theo ngày nếu có thể
                $currentDate = now();
                for ($i = 0; $i < 60; $i++) {
                    $date = $currentDate->copy()->subDays($i)->format('Y-m-d');
                    Cache::forget('pancake_sync_progress_' . $date);
                    Cache::forget('pancake_sync_result_' . $date);
                }
            }

            // Log the cancellation for audit purposes
            Log::info('Pancake sync cancelled by user', [
                'user_id' => Auth::id() ?? 'system',
                'running_sync' => $runningSync,
                'date_match' => $dateMatch,
                'force' => $request->input('force', false)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy quá trình đồng bộ' . ($dateMatch ? " ngày {$day}/{$month}/{$year}" : "")
            ]);
        } catch (\Exception $e) {
            // Ghi lại lỗi
            Log::error('Lỗi khi hủy đồng bộ: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy đồng bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronize orders from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncOrders(Request $request)
    {
        try {
            $this->authorize('settings.manage');

            // Get API configuration
            $apiKey = config('pancake.api_key');
            $shopId = config('pancake.shop_id');
            $baseUrl = config('pancake.base_uri', 'https://pos.pages.fm/api/v1');

            if (empty($apiKey) || empty($shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake'
                ], 400);
            }

            // Use only one specific day instead of a date range
            $syncDate = $request->input('sync_date', now()->format('Y-m-d'));
            $date = Carbon::createFromFormat('Y-m-d', $syncDate);

            // Start the sync process for a single day
            return $this->syncOrdersByDateManual($request->merge(['date' => $syncDate]));
        } catch (\Exception $e) {
            Log::error('Error starting Pancake order sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk push pending orders to Pancake
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkPushOrdersToPancake()
    {
        try {
            $this->authorize('settings.manage');

            // Find orders that haven't been pushed to Pancake yet
            $pendingOrders = Order::whereNull('pancake_order_id')
                ->where('status', '!=', Order::STATUS_DA_HUY)
                ->whereNull('deleted_at')
                ->limit(100) // Limit the number of orders to process at once
                ->get();

            if ($pendingOrders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng nào cần đồng bộ lên Pancake.'
                ]);
            }

            // Start progress tracking
            $progressKey = 'pancake_sync_progress';
            $statsKey = 'pancake_sync_stats';

            Cache::put($progressKey, 0, 3600);
            Cache::put($statsKey, [
                'total' => $pendingOrders->count(),
                'synced' => 0,
                'failed' => 0,
                'errors' => []
            ], 3600);

            // Start background process
            dispatch(function() use ($pendingOrders, $progressKey, $statsKey) {
                try {
                    $totalOrders = $pendingOrders->count();
                    $totalPushed = 0;
                    $totalFailed = 0;
                    $errors = [];

                    foreach ($pendingOrders as $index => $order) {
                        // Check if sync was cancelled
                        if (!Cache::has($progressKey)) {
                            Log::info('Order sync to Pancake cancelled by user');
                            return;
                        }

                        try {
                            $result = $this->pushOrderToPancake($order);

                            if ($result['success']) {
                                $totalPushed++;
                            } else {
                                $totalFailed++;
                                $errors[] = "Đơn hàng {$order->order_code}: " . $result['message'];
                            }
                        } catch (\Exception $e) {
                            $totalFailed++;
                            $errors[] = "Lỗi đẩy đơn hàng {$order->order_code}: " . $e->getMessage();
                            Log::error('Error pushing order to Pancake', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }

                        // Update progress
                        $progress = min(99, round(($index + 1) / $totalOrders * 100));
                        Cache::put($progressKey, $progress, 3600);

                        // Update stats
                        Cache::put($statsKey, [
                            'total' => $totalOrders,
                            'synced' => $totalPushed,
                            'failed' => $totalFailed,
                            'errors' => $errors
                        ], 3600);

                        // Small delay to avoid overwhelming the API
                        usleep(500000); // 500ms
                    }

                    // Sync complete
                    Cache::put($progressKey, 100, 3600);

                    Log::info('Bulk push to Pancake completed', [
                        'total' => $totalOrders,
                        'pushed' => $totalPushed,
                        'failed' => $totalFailed
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error in bulk push to Pancake job', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Update progress to indicate completion with error
                    Cache::put($progressKey, 100, 3600);
                    $stats = Cache::get($statsKey);
                    $stats['errors'][] = "Bulk push error: " . $e->getMessage();
                    Cache::put($statsKey, $stats, 3600);
                }
            })->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Đã bắt đầu đồng bộ ' . $pendingOrders->count() . ' đơn hàng lên Pancake.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting bulk push to Pancake', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push an order to Pancake
     *
     * @param Order $order
     * @return array Response data
     */
    public function pushOrderToPancake(Order $order)
    {
        try {
            $apiKey = config('pancake.api_key');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey)) {
                throw new \Exception('Chưa cấu hình API key Pancake');
            }

            // Build order data for Pancake
            $orderData = [
                'code' => $order->order_code,
                'status' => $this->mapInternalStatusToPancake($order->status),
                'customer' => [
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'email' => $order->customer_email,
                ],
                'shipping_address' => [
                    'province_id' => $order->province_code,
                    'district_id' => $order->district_code,
                    'commune_id' => $order->ward_code,
                    'address' => $order->street_address,
                    'full_address' => $order->full_address,
                ],
                'shipping_fee' => $order->shipping_fee,
                'payment_method' => $order->payment_method,
                'total' => $order->total_value,
                'items' => []
            ];

            // Add order items
            foreach ($order->items as $item) {
                $orderData['items'][] = [
                    'sku' => $item->product_code,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price
                ];
            }

            // Check if we're updating an existing Pancake order or creating a new one
            if ($order->pancake_order_id) {
                // Update existing order
                $response = Http::put("{$baseUrl}/orders/{$order->pancake_order_id}", [
                    'api_key' => $apiKey,
                    'order' => $orderData
                ]);
            } else {
                // Create new order
                $response = Http::post("{$baseUrl}/orders", [
                    'api_key' => $apiKey,
                    'order' => $orderData
                ]);
            }

            if (!$response->successful()) {
                $errorMsg = $response->json()['message'] ?? $response->body();
                throw new \Exception("Pancake API Error: {$errorMsg}");
            }

            $responseData = $response->json();

            // Update order with Pancake ID if it was newly created
            if (!$order->pancake_order_id && isset($responseData['order']['id'])) {
                $order->pancake_order_id = $responseData['order']['id'];
                $order->internal_status = 'Pushed to Pancake successfully.';
                $order->save();
            } else {
                $order->internal_status = 'Updated in Pancake successfully.';
                $order->save();
            }

            return [
                'success' => true,
                'message' => $order->pancake_order_id ? 'Order updated in Pancake' : 'Order created in Pancake',
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Error pushing order to Pancake', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            $order->internal_status = 'Pancake Push Error: ' . $e->getMessage();
            $order->save();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map internal order status to Pancake status
     *
     * @param string $internalStatus
     * @return string
     */
    private function mapInternalStatusToPancake(string $internalStatus): string
    {
        return match ($internalStatus) {
            Order::STATUS_MOI => 'pending',
            Order::STATUS_CAN_XU_LY => 'pending',
            Order::STATUS_CHO_HANG => 'waiting',
            Order::STATUS_DA_DAT_HANG => 'processing',
            Order::STATUS_CHO_CHUYEN_HANG => 'processing',
            Order::STATUS_DA_GUI_HANG => 'shipping',
            Order::STATUS_DA_NHAN => 'delivered',
            Order::STATUS_DA_NHAN_DOI => 'delivered',
            Order::STATUS_DA_THU_TIEN => 'completed',
            Order::STATUS_DA_HOAN => 'canceled',
            Order::STATUS_DA_HUY => 'canceled',
            default => 'pending',
        };
    }

    /**
     * Checks and automatically cancels stuck synchronization processes
     *
     * @return bool True if a stuck sync was detected and canceled
     */
    protected function detectAndCancelStuckSync()
    {
        if (Cache::has('pancake_sync_in_progress')) {
            $runningSync = Cache::get('pancake_sync_in_progress');
            $syncStartTime = Cache::get('pancake_sync_start_time');

            // If the sync has been running for more than 30 minutes, consider it stuck
            if ($syncStartTime && now()->diffInMinutes($syncStartTime) > 30) {
                Log::warning("Phát hiện đồng bộ treo quá 30 phút, tự động hủy: {$runningSync}", [
                    'start_time' => $syncStartTime,
                    'running_minutes' => now()->diffInMinutes($syncStartTime)
                ]);

                // Clear the sync cache keys
                Cache::forget('pancake_sync_in_progress');
                Cache::forget('pancake_sync_start_time');

                // Extract date from running sync message if possible
                $dateMatch = null;
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $runningSync, $matches)) {
                    $day = $matches[1];
                    $month = $matches[2];
                    $year = $matches[3];
                    $dateMatch = "{$year}-{$month}-{$day}";

                    // Clear specific date progress
                    Cache::forget('pancake_sync_progress_' . $dateMatch);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Display sync status and logs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncStatus()
    {
        try {
            // Auto-detect and cancel stuck syncs
            $stuckSyncCancelled = $this->detectAndCancelStuckSync();

            // Check if there's a sync in progress
            $isRunning = Cache::has('pancake_sync_in_progress');
            $runningSync = $isRunning ? Cache::get('pancake_sync_in_progress') : null;

            // Get last sync time from cache or database
            $lastSync = Cache::get('pancake_last_sync_time');

            // Get recent logs
            $lastLogs = [];
            // Implement actual log fetching based on your logging system
            // This is a placeholder - you might retrieve from a database or log files

            return response()->json([
                'success' => true,
                'is_running' => $isRunning,
                'current_sync' => $runningSync,
                'last_sync' => $lastSync,
                'last_logs' => $lastLogs,
                'stuck_sync_cancelled' => $stuckSyncCancelled
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving sync status: ' . $e->getMessage()
            ], 500);
        }
    }
}
