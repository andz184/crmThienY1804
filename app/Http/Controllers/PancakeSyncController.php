<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\PancakeShop;
use App\Models\PancakePage;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class PancakeSyncController extends Controller
{
    /**
     * Display synchronization interface
     */
    public function index()
    {
        return view('pancake.sync');
    }

    /**
     * Sync customer data from Pancake
     */
    public function sync(Request $request)
    {
        try {
            // Kiểm tra quyền
            $this->authorize('sync-pancake');

            // Lấy các tham số từ request
            $chunk = $request->input('chunk', 100);
            $force = $request->boolean('force', false);

            // Chạy command đồng bộ
            $exitCode = Artisan::call('pancake:sync-customers', [
                '--chunk' => $chunk,
                '--force' => $force
            ]);

            // Lấy output từ command
            $output = Artisan::output();

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
     * Synchronize orders from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncOrders(Request $request)
    {
        try {
            $this->authorize('sync-pancake');

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

            // Set date ranges for filtering
            $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            // Start progress tracking
            $progressKey = 'pancake_sync_progress';
            $statsKey = 'pancake_sync_stats';

            Cache::put($progressKey, 0, 3600);
            Cache::put($statsKey, [
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
                'errors' => []
            ], 3600);

            // Start background process
            dispatch(function() use ($apiKey, $shopId, $baseUrl, $startDate, $endDate, $progressKey, $statsKey) {
                try {
                    // Initialize counters
                    $page = 1;
                    $totalImported = 0;
                    $totalUpdated = 0;
                    $totalFailed = 0;
                    $errors = [];
                    $hasMorePages = true;
                    $perPage = 50;

                    while ($hasMorePages) {
                        // Check if sync was cancelled
                        if (!Cache::has($progressKey)) {
                            Log::info('Order sync cancelled by user');
                            return;
                        }

                        // Fetch orders from Pancake
                        $response = Http::get("{$baseUrl}/orders", [
                            'api_key' => $apiKey,
                            'shop_id' => $shopId,
                            'page' => $page,
                            'per_page' => $perPage,
                            'created_from' => $startDate,
                            'created_to' => $endDate,
                        ]);

                        if (!$response->successful()) {
                            $errors[] = "API Error on page {$page}: " . ($response->json()['message'] ?? $response->status());
                            Cache::put($statsKey, [
                                'total' => $totalImported + $totalUpdated,
                                'synced' => $totalImported + $totalUpdated,
                                'failed' => $totalFailed + 1,
                                'errors' => $errors
                            ], 3600);
                            Cache::put($progressKey, 100, 3600);
                            return;
                        }

                        $responseData = $response->json();
                        $orders = $responseData['data'] ?? [];

                        // Update total count for progress calculation
                        $totalInApi = $responseData['meta']['total'] ?? count($orders);
                        $stats = Cache::get($statsKey);
                        $stats['total'] = $totalInApi;
                        Cache::put($statsKey, $stats, 3600);

                        // Process each order
                        foreach ($orders as $orderData) {
                            try {
                                DB::beginTransaction();

                                // Check if order already exists by pancake_order_id
                                $existingOrder = Order::where('pancake_order_id', $orderData['id'])->first();

                                if ($existingOrder) {
                                    // Update existing order
                                    $this->updateOrderFromPancake($existingOrder, $orderData);
                                    $totalUpdated++;
                                } else {
                                    // Create new order
                                    $this->createOrderFromPancake($orderData);
                                    $totalImported++;
                                }

                                DB::commit();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $totalFailed++;
                                $errors[] = "Error processing order {$orderData['id']}: " . $e->getMessage();
                                Log::error('Error syncing order from Pancake', [
                                    'order_id' => $orderData['id'] ?? 'unknown',
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }

                            // Update progress
                            $progress = min(99, round(($totalImported + $totalUpdated + $totalFailed) / max(1, $totalInApi) * 100));
                            Cache::put($progressKey, $progress, 3600);

                            // Update stats
                            Cache::put($statsKey, [
                                'total' => $totalInApi,
                                'synced' => $totalImported + $totalUpdated,
                                'failed' => $totalFailed,
                                'errors' => $errors
                            ], 3600);
                        }

                        // Check if there are more pages
                        $hasMorePages = !empty($orders) && count($orders) >= $perPage && isset($responseData['meta']['current_page']) && $responseData['meta']['current_page'] < $responseData['meta']['last_page'];
                        $page++;
                    }

                    // Sync complete
                    Cache::put($progressKey, 100, 3600);
                    Log::info('Pancake order sync completed', [
                        'imported' => $totalImported,
                        'updated' => $totalUpdated,
                        'failed' => $totalFailed
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error in Pancake order sync job', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Update progress to indicate completion with error
                    Cache::put($progressKey, 100, 3600);
                    $stats = Cache::get($statsKey, [
                        'total' => 0,
                        'synced' => $totalImported + $totalUpdated,
                        'failed' => $totalFailed + 1,
                        'errors' => []
                    ]);
                    $stats['errors'][] = "Sync error: " . $e->getMessage();
                    Cache::put($statsKey, $stats, 3600);
                }
            })->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ đơn hàng từ Pancake đã được bắt đầu.'
            ]);
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
     * Create a new order from Pancake data
     *
     * @param array $orderData
     * @return Order
     */
    private function createOrderFromPancake(array $orderData)
    {
        // Find or create customer
        $customer = null;
        if (!empty($orderData['customer'])) {
            $customerData = $orderData['customer'];

            // Try to find customer by Pancake ID
            if (!empty($customerData['id'])) {
                $customer = Customer::where('pancake_id', $customerData['id'])->first();
            }

            // If not found, try to find by phone number
            if (!$customer && !empty($customerData['phone'])) {
                $customer = Customer::where('phone', $customerData['phone'])->first();
            }

            // If still not found, create new customer
            if (!$customer) {
                $customer = new Customer();
                $customer->name = $customerData['name'] ?? '';
                $customer->phone = $customerData['phone'] ?? '';
                $customer->email = $customerData['email'] ?? '';
                $customer->pancake_id = $customerData['id'] ?? null;
                $customer->address = $customerData['address'] ?? '';
                $customer->save();
            }
        }

        // Find shop and page if they exist
        $shopId = null;
        $pageId = null;

        if (!empty($orderData['shop_id'])) {
            $shop = PancakeShop::where('pancake_id', $orderData['shop_id'])->first();
            if ($shop) {
                $shopId = $shop->id;
            }
        }

        if (!empty($orderData['page_id'])) {
            $page = PancakePage::where('pancake_id', $orderData['page_id'])->first();
            if ($page) {
                $pageId = $page->id;
            }
        }

        // Create new order
        $order = new Order();
        $order->pancake_order_id = $orderData['id'] ?? null;
        $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));
        $order->customer_name = $orderData['customer']['name'] ?? ($customer ? $customer->name : '');
        $order->customer_phone = $orderData['customer']['phone'] ?? ($customer ? $customer->phone : '');
        $order->customer_email = $orderData['customer']['email'] ?? ($customer ? $customer->email : '');
        $order->customer_id = $customer ? $customer->id : null;
        $order->status = $this->mapPancakeStatus($orderData['status'] ?? 'moi');
        $order->internal_status = 'Imported from Pancake';
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->total_value = $orderData['total'] ?? 0;
        $order->full_address = $orderData['shipping_address']['full_address'] ?? '';
        $order->province_code = $orderData['shipping_address']['province_id'] ?? null;
        $order->district_code = $orderData['shipping_address']['district_id'] ?? null;
        $order->ward_code = $orderData['shipping_address']['commune_id'] ?? null;
        $order->street_address = $orderData['shipping_address']['address'] ?? '';
        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->notes = $orderData['note'] ?? '';
        $order->created_by = Auth::check() ? Auth::id() : null;
        $order->save();

        // Create order items
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
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
     * Update existing order from Pancake data
     *
     * @param Order $order
     * @param array $orderData
     * @return Order
     */
    private function updateOrderFromPancake(Order $order, array $orderData)
    {
        // Update basic order info
        $order->order_code = $orderData['code'] ?? $order->order_code;
        $order->status = $this->mapPancakeStatus($orderData['status'] ?? $order->status);
        $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
        $order->payment_method = $orderData['payment_method'] ?? $order->payment_method;
        $order->total_value = $orderData['total'] ?? $order->total_value;
        $order->notes = $orderData['note'] ?? $order->notes;

        // Update shipping address if provided
        if (!empty($orderData['shipping_address'])) {
            $order->full_address = $orderData['shipping_address']['full_address'] ?? $order->full_address;
            $order->province_code = $orderData['shipping_address']['province_id'] ?? $order->province_code;
            $order->district_code = $orderData['shipping_address']['district_id'] ?? $order->district_code;
            $order->ward_code = $orderData['shipping_address']['commune_id'] ?? $order->ward_code;
            $order->street_address = $orderData['shipping_address']['address'] ?? $order->street_address;
        }

        $order->save();

        // Update order items - delete existing and recreate
        $order->items()->delete();

        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
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
     * Map Pancake status to internal status
     *
     * @param string $pancakeStatus
     * @return string
     */
    private function mapPancakeStatus(string $pancakeStatus): string
    {
        return match (strtolower($pancakeStatus)) {
            'done' => Order::STATUS_DA_THU_TIEN,
            'completed' => Order::STATUS_DA_THU_TIEN,
            'shipping' => Order::STATUS_DA_GUI_HANG,
            'delivered' => Order::STATUS_DA_NHAN,
            'canceled' => Order::STATUS_DA_HUY,
            'pending' => Order::STATUS_CAN_XU_LY,
            'processing' => Order::STATUS_CHO_CHUYEN_HANG,
            'waiting' => Order::STATUS_CHO_HANG,
            default => Order::STATUS_MOI,
        };
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
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? null;
            $baseUrl = 'https://pos.pages.fm/api/v1';

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
                    'province_code' => $order->province_code,
                    'district_code' => $order->district_code,
                    'ward_code' => $order->ward_code,
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
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total
                ];
            }

            // Check if we're updating an existing Pancake order or creating a new one
            if ($order->pancake_order_id) {
                // Update existing order
                $response = Http::put("{$baseUrl}/orders/{$order->pancake_order_id}?api_key={$apiKey}", $orderData);
            } else {
                // Create new order
                $response = Http::post("{$baseUrl}/orders?api_key={$apiKey}", $orderData);
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
     * Cancel the ongoing sync process
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSync()
    {
        Cache::forget('pancake_sync_progress');
        Cache::forget('pancake_sync_stats');

        return response()->json([
            'success' => true,
            'message' => 'Quá trình đồng bộ đã bị hủy'
        ]);
    }

    /**
     * Bulk push pending orders to Pancake
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkPushOrdersToPancake()
    {
        try {
            $this->authorize('sync-pancake');

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
}
