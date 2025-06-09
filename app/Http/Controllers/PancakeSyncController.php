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
use App\Models\PancakeCategory;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Province;
use App\Models\District;
use App\Models\Ward;
use App\Models\ProductVariant;
use App\Services\PancakeApiService; // Added import
use App\Models\PancakeOrderSource;
use App\Models\PancakeProductSource;

class PancakeSyncController extends Controller
{
    protected PancakeApiService $pancakeApiService; // Added property

    // Added constructor for DI
    public function __construct(PancakeApiService $pancakeApiService)
    {
        $this->pancakeApiService = $pancakeApiService;
    }

    /**
     * Display synchronization interface
     */
    public function index()
    {
        $orderSources = PancakeOrderSource::orderBy('name')->get();
        $productSources = PancakeProductSource::orderBy('name')->get();

        return view('admin.pancake-sync.index', compact('orderSources', 'productSources'));
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

        try {
            $orderData = $this->sanitizeOrderData($data);

            // Check if order exists
            $order = Order::where('pancake_id', $orderData['pancake_id'])->first();

            if ($order) {
                $this->updateOrderFromPancake($order, $orderData);
            } else {
                $order = $this->createOrderFromPancakeData($orderData);
            }

            // Variant revenue is now handled in setOrderItemFields
            foreach ($order->items as $item) {
                // Each item is already processed in setOrderItemFields
            }

            return response()->json([
                'success' => true,
                'message' => 'Order synced successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('PancakeSyncController@sync: Error syncing order: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error syncing order: ' . $e->getMessage()
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
            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

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

            // Check if this is a specific date sync or ALL orders sync
            if ($request->has('date') || $request->has('sync_date')) {
                // Add a sync_type parameter to make routing clearer
                $request->merge(['sync_type' => 'date']);

                // Ensure we have a consistent 'date' parameter
                if ($request->has('sync_date') && !$request->has('date')) {
                    $request->merge(['date' => $request->input('sync_date')]);
                }

                // Log request information
                Log::info('Redirecting to syncOrdersByDateManual from syncOrders', [
                    'date' => $request->input('date'),
                    'parameters' => $request->all()
                ]);
                dd(1);
                // Start the sync process for a single day
                return $this->syncOrdersByDateManual($request);
            } else {
                // This is a request to sync ALL orders
                return $this->syncAllOrders($request);
            }
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
     * Sanitize address data to remove problematic characters like quotes
     *
     * @param string|null $address
     * @return string
     */
    private function sanitizeAddress(?string $address): string
    {
        if (empty($address)) {
            return '';
        }

        // Remove quotes from phone numbers in addresses
        $cleanAddress = preg_replace('/(\d+)"/', '$1', $address);

        // Replace any remaining double quotes with single quotes
        $cleanAddress = str_replace('"', "'", $cleanAddress);

        return $cleanAddress;
    }

    /**
     * Try to extract province, district, ward from a full address string
     *
     * @param string|null $address
     * @return array
     */
    private function parseAddress(?string $address): array
    {
        if (!$address) {
            return [
                'full_address' => null,
                'province' => null,
                'district' => null,
                'ward' => null,
                'street_address' => null
            ];
        }

        $result = [
            'full_address' => $address,
            'province' => null,
            'district' => null,
            'ward' => null,
            'street_address' => $address // Default to full address
        ];

        // Try to extract province, district, ward from address
        // Basic pattern: street details, ward, district, province
        $parts = array_map('trim', explode(',', $address));
        $partsCount = count($parts);

        if ($partsCount >= 3) {
            $result['province'] = $parts[$partsCount - 1];
            $result['district'] = $parts[$partsCount - 2];
            $result['ward'] = $parts[$partsCount - 3];

            // Everything else is considered street address
            if ($partsCount > 3) {
                $result['street_address'] = implode(', ', array_slice($parts, 0, $partsCount - 3));
            }
        }

        return $result;
    }

    /**
     * Sanitize order data to ensure no NULL values are passed to fields that don't allow NULL
     *
     * @param array $data
     * @return array
     */
    private function sanitizeOrderData(array $data): array
    {
        // Default empty strings for text fields that don't allow NULL
        $textFields = ['customer_name', 'customer_phone', 'customer_email', 'full_address',
                      'street_address', 'notes', 'internal_status', 'source', 'order_code'];

        foreach ($textFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '';
            }
        }

        return $data;
    }

    /**
     * Tạo đơn hàng mới từ dữ liệu Pancake
     *
     * @param array $orderData Dữ liệu đơn hàng từ Pancake API
     * @return \App\Models\Order
     */
    public function createOrderFromPancakeData(array $orderData)
    {
        // dd($orderData);
        // Tìm hoặc tạo khách hàng
            $customer = null;
        if (!empty($orderData['customer'])) {
            $customerData = $orderData['customer'];

            // Tìm khách hàng theo Pancake ID hoặc số điện thoại
            if (!empty($customerData['id'])) {
                $customer = \App\Models\Customer::where('pancake_id', $customerData['id'])->first();
            }
            if (!$customer && !empty($customerData['phone'])) {
                $customer = \App\Models\Customer::where('phone', $customerData['phone'])->first();
            }

            // Nếu không tìm thấy, tạo khách hàng mới
            if (!$customer) {
                $customer = new \App\Models\Customer();
            }

            // Cập nhật thông tin khách hàng
            $customer->name = $customerData['bill_full_name'] ?? '';
            $customer->phone = $customerData['bill_phone_number'] ?? '';
            $customer->email = $customerData['customer_email'] ?? '';
            $customer->pancake_id = $customerData['id'] ?? null;

            if (!empty($orderData['shipping_address'])) {
                $shippingAddress = $orderData['shipping_address'];
                $customer->full_address = $shippingAddress['full_address'] ?? '';
                $customer->province = $shippingAddress['province_id'] ?? $shippingAddress['province_code'] ?? null;
                $customer->district = $shippingAddress['district_id'] ?? $shippingAddress['district_code'] ?? null;
                $customer->ward = $shippingAddress['commune_id'] ?? $shippingAddress['ward_code'] ?? null;
                $customer->street_address = $shippingAddress['address'] ?? '';
            }

            $customer->save();


        }

        // Tìm hoặc tạo shop và page
        $shopId = null;
        $pageId = null;

        if (!empty($orderData['shop_id'])) {
            $shop = \App\Models\PancakeShop::where('pancake_id', $orderData['shop_id'])->first();
            if (!$shop) {
                // Tạo shop mới nếu không tồn tại
                $shop = new \App\Models\PancakeShop();
                $shop->pancake_id = $orderData['shop_id'];
                $shop->name = $orderData['shop_name'] ?? 'Shop ' . $orderData['shop_id'];
                $shop->save();
            }
            if ($shop) {
                $shopId = $shop->id;
            }
        }

        if (!empty($orderData['page_id'])) {
            $page = \App\Models\PancakePage::where('pancake_id', $orderData['page_id'])->first();
            if (!$page) {
                // Tạo page mới nếu không tồn tại
                $page = new \App\Models\PancakePage();
                $page->pancake_id = $orderData['page_id'];
                $page->pancake_page_id = $orderData['page_id']; // Thêm dòng này để đảm bảo cả pancake_id và pancake_page_id đều được set
                $page->name = $orderData['page_name'] ?? 'Page ' . $orderData['page_id'];
                $page->pancake_shop_table_id = $shopId; // Liên kết với shop
                $page->save();
            }
            if ($page) {
                $pageId = $page->id;
            }
        }

        // Tìm hoặc tạo kho hàng
        $warehouseId = null;
        $warehouseCode = null;
        $pancakeWarehouseId = null;

        if (!empty($orderData['warehouse_id'])) {
            $warehouse = \App\Models\Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();
            if (!$warehouse) {
                // Thử tìm theo code
                $warehouse = \App\Models\Warehouse::where('code', $orderData['warehouse_id'])->first();
            }

            // Nếu không tìm thấy và có thông tin kho, tạo kho mới
            if (!$warehouse && !empty($orderData['warehouse_name'])) {
                $warehouse = new \App\Models\Warehouse();
                $warehouse->name = $orderData['warehouse_name'];
                $warehouse->code = 'WH-' . $orderData['warehouse_id'];
                $warehouse->pancake_id = $orderData['warehouse_id'];
                $warehouse->save();

                Log::info('Đã tạo kho hàng mới từ dữ liệu Pancake', [
                    'warehouse_id' => $warehouse->id,
                    'pancake_id' => $orderData['warehouse_id'],
                    'name' => $orderData['warehouse_name']
                ]);
            }

            if ($warehouse) {
                $warehouseId = $warehouse->id;
                $warehouseCode = $warehouse->code;
                $pancakeWarehouseId = $warehouse->pancake_id;
                } else {
                // Lưu pancake_warehouse_id ngay cả khi không tìm thấy warehouse
                $pancakeWarehouseId = $orderData['warehouse_id'];
            }
        }

        // Tìm hoặc tạo đơn vị vận chuyển
        $shippingProviderId = null;
        $pancakeShippingProviderId = null;

        if (!empty($orderData['partner']['partner_id'])) {
            $providerId = $orderData['partner']['partner_id'];
            $provider = \App\Models\ShippingProvider::where('pancake_id', $providerId)
                ->orWhere('pancake_partner_id', $providerId)
                ->first();

            // Nếu không tìm thấy và có tên đơn vị vận chuyển, tạo mới
            if (!$provider && !empty($orderData['shipping_provider_name'])) {
                $provider = new \App\Models\ShippingProvider();
                $provider->name = $orderData['shipping_provider_name'];
                $provider->pancake_id = $providerId;
                $provider->save();

                Log::info('Đã tạo đơn vị vận chuyển mới từ dữ liệu Pancake', [
                    'provider_id' => $provider->id,
                    'pancake_id' => $providerId,
                    'name' => $orderData['shipping_provider_name']
                ]);
            }

            if ($provider) {
                $shippingProviderId = $provider->id;
                $pancakeShippingProviderId = $provider->pancake_id;
        } else {
                // Lưu pancake_shipping_provider_id ngay cả khi không tìm thấy provider
                $pancakeShippingProviderId = $providerId;
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
        $product_data = $orderData['items'] ?? null;
        $order->products_data = json_encode($product_data);
            $order->status = $status;
        $order->post_id = $orderData['post_id'] ?? null;
            $order->pancake_status = $orderData['status'] ?? '';
            $order->internal_status = 'Imported from Pancake';
            $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
            $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->total_value = $orderData['total_price'] ?? 0;
        if (isset($orderData['bill_full_name']) || isset($orderData['bill_phone_number']) || isset($orderData['customer_name']) || isset($orderData['customer_phone']) || isset($orderData['customer_email'])) {
            // Handle flat customer data structure
            $order->bill_full_name = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? $order->customer_name);
            $order->bill_phone_number = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? $order->customer_phone);
            // Handle NULL email safely
            if (!empty($orderData['customer_email'])) {
                $order->customer_email = $orderData['customer_email'];
            }

            // Update the associated customer if we have a phone number
            if (($order->customer_phone || !empty($orderData['bill_phone_number']) || !empty($orderData['customer_phone'])) && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->name = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? $customer->name);
                    $customer->phone = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? $customer->phone);
                    // Only update email if not empty
                    if (!empty($orderData['customer_email'])) {
                        $customer->email = $orderData['customer_email'];
                    }
                    $customer->save();

                    Log::info('Updated associated customer record from flat data', [
                        'customer_id' => $customer->id,
                        'order_id' => $order->id
                    ]);
                }
            }
        }

            // Xử lý địa chỉ nếu có
            if (!empty($orderData['shipping_address'])) {
            $shippingAddress = $orderData['shipping_address'];
            $order->full_address = $shippingAddress['full_address'] ?? '';
            $order->province_code = $shippingAddress['province_id'] ?? $shippingAddress['province_code'] ?? null;
            $order->district_code = $shippingAddress['district_id'] ?? $shippingAddress['district_code'] ?? null;
            $order->ward_code = $shippingAddress['commune_id'] ?? $shippingAddress['ward_code'] ?? null;
            $order->street_address = $shippingAddress['address'] ?? '';

            // Look up and update address names from the database
            $this->updateAddressNames($order);

            // Store the full shipping address info if the column exists
            if (Schema::hasColumn('orders', 'shipping_address_info')) {
                $order->shipping_address_info = json_encode($shippingAddress);
            }
        } else {
            // Fallback for direct address fields
            if (!empty($orderData['address']) || !empty($orderData['province_name']) ||
                !empty($orderData['district_name']) || !empty($orderData['ward_name'])) {

                $addressParts = [];
                if (!empty($orderData['address'])) {
                    // Handle case when address contains phone number
                    $address = $this->sanitizeAddress($orderData['address']);
                    $addressParts[] = $address;
                }
                if (!empty($orderData['ward_name'])) $addressParts[] = $orderData['ward_name'];
                if (!empty($orderData['district_name'])) $addressParts[] = $orderData['district_name'];
                if (!empty($orderData['province_name'])) $addressParts[] = $orderData['province_name'];

                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($orderData['full_address'] ?? '');
                $order->province_code = $orderData['province_id'] ?? null;
                $order->district_code = $orderData['district_id'] ?? null;
                $order->ward_code = $orderData['ward_id'] ?? null;
                $order->street_address = $orderData['address'] ?? '';

                // Update related names if available
                $order->province_name = $orderData['province_name'] ?? null;
                $order->district_name = $orderData['district_name'] ?? null;
                $order->ward_name = $orderData['ward_name'] ?? null;

                // Look up and update address names if codes are provided but names are missing
                if (($order->province_code && empty($order->province_name)) ||
                    ($order->district_code && empty($order->district_name)) ||
                    ($order->ward_code && empty($order->ward_name))) {
                    $this->updateAddressNames($order);
                }
            }
        }

        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;

        $order->warehouse_id = $warehouseId;
        $order->source = $orderData['order_sources'] ?? null;
        $order->warehouse_code = $warehouseCode;
        $order->pancake_warehouse_id = $pancakeWarehouseId;
        $order->shipping_provider_id = $shippingProviderId;
        $order->pancake_shipping_provider_id = $pancakeShippingProviderId;
        $order->notes = $orderData['note'] ?? '';
        $order->created_by = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        // Lưu thông tin người bán từ Pancake
            if (!empty($orderData['assigning_seller_id'])) {
                $order->assigning_seller_id = $orderData['assigning_seller_id'];
                $order->assigning_seller_name = $orderData['assigning_seller_name'] ?? '';
            } else {
            // Nếu không có seller được gán, tự động phân phối đơn hàng
            $this->assignOrderToSalesStaff($order);
            }

        // Lưu thời gian tạo đơn từ Pancake
        if (!empty($orderData['inserted_at'])) {
                try {

                $order->pancake_inserted_at = Carbon::parse($orderData['inserted_at'])->addHours(7)->format('Y-m-d H:i:s');

                } catch (\Exception $e) {
                Log::warning("Could not parse inserted_at date for order {$order->order_code}: " . $e->getMessage());
            }
        }
        $order->save();
        // $customer = \App\Models\Customer::where('phone', $order->bill_phone_number)
        //     ->where('name', $order->bill_full_name)
        //     ->first();

        // if($orderData['customer']){



            // Then update customer stats
            $customer->total_orders_count = \App\Models\Order::where('customer_id', $order->customer_id)
            ->count();
            if($customer->total_orders_count == 0){
               dd("lỗi");
            }
            $customer->total_spent = \App\Models\Order::where('bill_phone_number', $order->bill_phone_number)
                ->where('bill_full_name', $order->bill_full_name)
                ->where('pancake_status', 3)
                ->sum('total_value');

            $customer->save();

        // Parse live session information from notes
        if (!empty($orderData['note'])) {
            $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
            if ($liveSessionInfo) {
                // Store only JSON info in orders table
                $order->live_session_info = json_encode($liveSessionInfo);

                // Save order first to get the ID
                $order->save();
               
                // Create live session order record
                $liveSessionOrder = \App\Models\LiveSessionOrder::create([
                    'order_id' => $order->id,
                    'live_session_id' => "LIVE{$liveSessionInfo['live_number']}",
                    'live_session_date' => \Carbon\Carbon::parse($liveSessionInfo['session_date'])->format('Y-m-d'),
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer_name,
                    'shipping_address' => $order->full_address ?: $order->street_address,
                    'total_amount' => $order->total_value
                ]);

                // Create live session order items
                foreach ($order->items as $item) {
                    $liveSessionOrder->items()->create([
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'total' => $item->total_price
                    ]);

                    // // Update top products statistics
                    // \App\Models\TopProduct::updateOrCreate(
                    //     [
                    //         'product_id' => $item->product_id,
                    //         'name' => $item->product_name,
                    //         'live_session_id' => "LIVE{$liveSessionInfo['live_number']}"
                    //     ],
                    //     [
                    //         'quantity' => \DB::raw('quantity + ' . $item->quantity),
                    //         'revenue' => \DB::raw('revenue + ' . $item->total_price)
                    //     ]
                    // );
                }

                // Update live session revenue statistics
                $liveSessionRevenue = \App\Models\LiveSessionRevenue::updateFromOrder($order);

                // Update top products in live session revenue
                if ($liveSessionRevenue) {
                    $topProducts = $liveSessionRevenue->top_products ?? [];
                    foreach ($order->items as $item) {
                        $productKey = $item->product_id . '_' . $item->product_name;
                        if (!isset($topProducts[$productKey])) {
                            $topProducts[$productKey] = [
                                'product_id' => $item->product_id,
                                'name' => $item->product_name,
                                'quantity' => 0,
                                'revenue' => 0
                            ];
                        }
                        $topProducts[$productKey]['quantity'] += $item->quantity;
                        $topProducts[$productKey]['revenue'] += $item->total_price;
                    }
                    $liveSessionRevenue->top_products = $topProducts;
                    $liveSessionRevenue->save();
                }

                return;
                }
            }

            $order->save();

        // Tạo các item của đơn hàng với hỗ trợ cấu trúc nâng cao
            if (!empty($orderData['items'])) {
            $updated = false;

            // Create new items from the data
                foreach ($orderData['items'] as $item) {
                // Check if item has components structure
                if (!empty($item['components']) && is_array($item['components'])) {
                    foreach ($item['components'] as $component) {
                        $orderItem = new \App\Models\OrderItem();
                        $orderItem->order_id = $order->id;
                        $this->setOrderItemFields($orderItem, $item);  // Will handle component extraction
                        $orderItem->save();
                        $updated = true;

                        Log::info('Created order item from component structure', [
                            'order_id' => $order->id,
                            // 'product_name' => $orderItem->product_name,
                            'quantity' => $orderItem->quantity,
                            'price' => $orderItem->price
                        ]);

                        // We're only creating one order item per component set for now
                        break;
                    }
                } else {
                    // Handle traditional item format
                    $orderItem = new \App\Models\OrderItem();
                    $orderItem->order_id = $order->id;
                    $this->setOrderItemFields($orderItem, $item);
                    $orderItem->save();
                    $updated = true;

                    Log::info('Created order item using standard format', [
                        'order_id' => $order->id,
                        'product_name' => $orderItem->product_name,
                        'quantity' => $orderItem->quantity
                    ]);
                }
            }

            if ($updated) {
                // Update total value based on new items
                $newTotal = $order->items->sum(function($item) {
                    return ($item->price ?? 0) * ($item->quantity ?? 1);
                });

                // Add shipping fee if available
                $newTotal += ($order->shipping_fee ?? 0);

                // Update order total if changed
                if ($newTotal > 0 && $newTotal != $order->total_value) {
                    $order->total_value = $newTotal;
                    $order->save();

                    Log::info('Updated order total value based on new items', [
                        'order_id' => $order->id,
                        'new_total' => $newTotal
                    ]);
                }
            }
        }

        // }else {
        //     $customer->total_orders_count = \App\Models\Customer::where('phone', $order->bill_phone_number)
        //     ->count();
        // $customer->total_spent = \App\Models\Order::where('bill_phone_number', $order->bill_phone_number)

        //     ->where('pancake_status', 3)
        //     ->sum('total_value');

        // $customer->save();
        // }

        return $order;
    }

    /**
     * Update existing order from Pancake data
     *
     * @param Order $order
     * @param array $orderData
     * @return array
     */
private function updateOrderFromPancake(Order $order, array $orderData)
{
    try {

        DB::beginTransaction();

        Log::info('Updating existing order from Pancake data', [
            'order_id' => $order->id,
            'pancake_order_id' => $orderData['id'] ?? 'N/A',
            'keys' => array_keys($orderData)
        ]);

        // if (!empty($orderData['customer'])) {
        //     $customerData = $orderData['customer'];

        //     // Tìm khách hàng theo Pancake ID
        //     if (!empty($customerData['id'])) {
        //         $customer = \App\Models\Customer::where('pancake_id', $customerData['id'])->first();
        //     }

        //     // Nếu không tìm thấy, thử tìm theo số điện thoại
        //     if (!$customer && !empty($customerData['phone'])) {
        //         $customer = \App\Models\Customer::where('phone', $customerData['phone'])->first();
        //     }

        //     // Nếu vẫn không tìm thấy, tạo khách hàng mới
        //         if (!$customer) {
        //         $customer = new \App\Models\Customer();
        //         $customer->name = $customerData['name'] ?? '';
        //         $customer->phone = $customerData['phone'] ?? '';
        //         $customer->email = $customerData['email'] ?? '';
        //         $customer->pancake_id = $customerData['id'] ?? null;
        //         // Xóa dòng này gây lỗi, column 'address' không tồn tại
        //         // $customer->address = $customerData['address'] ?? '';
        //         $shippingAddress = $orderData['shipping_address'];
        //     $customer->full_address = $shippingAddress['full_address'] ?? '';
        //     $customer->province = $shippingAddress['province_id'] ?? $shippingAddress['province_code'] ?? null;
        //     $customer->district = $shippingAddress['district_id'] ?? $shippingAddress['district_code'] ?? null;
        //     $customer->ward = $shippingAddress['commune_id'] ?? $shippingAddress['ward_code'] ?? null;
        //     $customer->street_address = $shippingAddress['address'] ?? '';
        //             $customer->save();
        //     }
        // }



        // dd($orderData);
        // Update basic order info
        $order->order_code = $orderData['code'] ?? $order->order_code;
        $order->status = $this->mapPancakeStatus($orderData['status'] ?? $order->status);

        // Store the numeric status code directly for Pancake status
        if (isset($orderData['status'])) {
            // If status is numeric, save it directly
            if (is_numeric($orderData['status'])) {
                $order->pancake_status = $orderData['status'];
            }
            // If it's a string status_name, we'll continue using string statuses for backward compatibility
            else if (isset($orderData['status_name'])) {
                $order->pancake_status = $orderData['status_name'];
            }
        }

        $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
        $order->payment_method = $orderData['payment_method'] ?? $order->payment_method;
        $order->total_value = $orderData['total'] ?? ($orderData['total_price'] ?? $order->total_value);
        $order->notes = $orderData['note'] ?? ($orderData['notes'] ?? $order->notes);
        $order->additional_notes = $orderData['additional_notes'] ?? $order->additional_notes;


        // Update package dimensions if provided by Pancake API
        // Fallback to existing order's dimensions if not present in $orderData
        $order->shipping_length = $orderData['shipping_length'] ?? ($orderData['package_length'] ?? $order->shipping_length);
        $order->shipping_width = $orderData['shipping_width'] ?? ($orderData['package_width'] ?? $order->shipping_width);
        $order->shipping_height = $orderData['shipping_height'] ?? ($orderData['package_height'] ?? $order->shipping_height);

        $product_data = $orderData['items'] ?? null;
        $order->products_data = json_encode($product_data);

        // Update customer stats
        if ($order->customer_id) {
            $customer = \App\Models\Customer::find($order->customer_id);
            if ($customer) {
                $customer->total_orders_count = \App\Models\Order::where('customer_id', $order->customer_id)
                    ->count();
                $customer->total_spent = \App\Models\Order::where('bill_phone_number', $order->bill_phone_number)
                    ->where('bill_full_name', $order->bill_full_name)
                    ->where('pancake_status', 3)
                    ->sum('total_value');
                $customer->save();
            }
        }

        $order->source = $orderData['order_sources'] ?? ($orderData['order_sources'] ?? $order->source);

        // Update tracking info if available
        if (isset($orderData['tracking_code'])) {
            $order->tracking_code = $orderData['tracking_code'];
        }

        if (isset($orderData['tracking_url'])) {
            $order->tracking_url = $orderData['tracking_url'];
        }

        // Store COD amount
        if (isset($orderData['cod'])) {
            $order->cod_amount = $orderData['cod'];
        }

        // Store money to collect
        if (isset($orderData['money_to_collect']) && Schema::hasColumn('orders', 'money_to_collect')) {
            $order->money_to_collect = $orderData['money_to_collect'];
        }

        // Store conversation ID
        if (isset($orderData['conversation_id']) && Schema::hasColumn('orders', 'conversation_id')) {
            $order->conversation_id = $orderData['conversation_id'];
        }

        // Store post ID
        if (isset($orderData['post_id']) && Schema::hasColumn('orders', 'post_id')) {
            $order->post_id = $orderData['post_id'];
        }

        // Store system ID
        if (isset($orderData['system_id']) && Schema::hasColumn('orders', 'system_id')) {
            $order->system_id = $orderData['system_id'];
        }

        // Store tags
        if (!empty($orderData['tags']) && Schema::hasColumn('orders', 'tags')) {
            $order->tags = json_encode($orderData['tags']);
        }

        // Update shipping address if provided
        if (!empty($orderData['shipping_address'])) {
            $shippingAddress = $orderData['shipping_address'];
            $order->full_address = $shippingAddress['full_address'] ?? $order->full_address;
            $order->province_code = $shippingAddress['province_id'] ?? $shippingAddress['province_code'] ?? null;
            $order->district_code = $shippingAddress['district_id'] ?? $shippingAddress['district_code'] ?? null;
            $order->ward_code = $shippingAddress['commune_id'] ?? $shippingAddress['ward_code'] ?? null;
            $order->street_address = $shippingAddress['address'] ?? null;

            // Look up and update address names from the database
            $this->updateAddressNames($order);

            // Update seller information if available
            if (!empty($orderData['assigning_seller_id'])) {
                $order->assigning_seller_id = $orderData['assigning_seller_id'];
                $order->assigning_seller_name = $orderData['assigning_seller_name'] ?? '';
            } else if (empty($order->assigning_seller_id)) {
                // Nếu không có seller được gán, tự động phân phối đơn hàng
                $this->assignOrderToSalesStaff($order);
            }

            // Update Pancake insertion timestamp if available and not already set
            if (!empty($orderData['inserted_at']) && empty($order->pancake_inserted_at)) {
                try {
                    $order->pancake_inserted_at = Carbon::parse($orderData['inserted_at'])->addHours(7)->format('Y-m-d H:i:s');

                } catch (\Exception $e) {
                    Log::warning("Could not parse inserted_at date for order {$order->order_code}: " . $e->getMessage());
                }
            }


            // Store the full shipping address info if the column exists
            if (Schema::hasColumn('orders', 'shipping_address_info')) {
                $order->shipping_address_info = json_encode($shippingAddress);
            }
        } else {
            // Fallback for direct address fields
            if (!empty($orderData['address']) || !empty($orderData['province_name']) ||
                !empty($orderData['district_name']) || !empty($orderData['ward_name'])) {

                $addressParts = [];
                if (!empty($orderData['address'])) {
                    // Handle case when address contains phone number
                    $address = $this->sanitizeAddress($orderData['address']);
                    $addressParts[] = $address;
                }
                if (!empty($orderData['ward_name'])) $addressParts[] = $orderData['ward_name'];
                if (!empty($orderData['district_name'])) $addressParts[] = $orderData['district_name'];
                if (!empty($orderData['province_name'])) $addressParts[] = $orderData['province_name'];

                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($orderData['full_address'] ?? '');
                $order->province_code = $orderData['province_id'] ?? null;
                $order->district_code = $orderData['district_id'] ?? null;
                $order->ward_code = $orderData['ward_id'] ?? null;
                $order->street_address = $orderData['address'] ?? '';

                // Update related names if available
                $order->province_name = $orderData['province_name'] ?? null;
                $order->district_name = $orderData['district_name'] ?? null;
                $order->ward_name = $orderData['ward_name'] ?? null;

                // Look up and update address names if codes are provided but names are missing
                if (($order->province_code && empty($order->province_name)) ||
                    ($order->district_code && empty($order->district_name)) ||
                    ($order->ward_code && empty($order->ward_name))) {
                    $this->updateAddressNames($order);
                }
            }
        }

        // Update shop và page
        if (!empty($orderData['shop_id'])) {
            $shop = \App\Models\PancakeShop::where('pancake_id', $orderData['shop_id'])->first();

            if (!$shop && !empty($orderData['shop_name'])) {
                // Tạo shop mới nếu không tồn tại
                $shop = new \App\Models\PancakeShop();
                $shop->pancake_id = $orderData['shop_id'];
                $shop->name = $orderData['shop_name'];
                $shop->save();

                Log::info('Đã tạo shop mới từ dữ liệu Pancake khi cập nhật đơn hàng', [
                    'shop_id' => $shop->id,
                    'pancake_id' => $orderData['shop_id'],
                    'name' => $orderData['shop_name']
                ]);
            }

            if ($shop) {
                $order->pancake_shop_id = $shop->id;
            }
        }

        if (!empty($orderData['page_id'])) {
            $page = \App\Models\PancakePage::where('pancake_id', $orderData['page_id'])->first();

            if (!$page && !empty($orderData['page_name'])) {
                // Tạo page mới nếu không tồn tại
                $page = new \App\Models\PancakePage();
                $page->pancake_id = $orderData['page_id'];
                $page->pancake_page_id = $orderData['page_id']; // Thêm dòng này để đảm bảo cả pancake_id và pancake_page_id đều được set
                $page->name = $orderData['page_name'];
                $page->pancake_shop_table_id = $order->pancake_shop_id; // Liên kết với shop
                $page->save();
            }

            if ($page) {
                $order->pancake_page_id = $page->id;
            }
        }

        // Cập nhật kho hàng
        if (!empty($orderData['warehouse_id'])) {
            $warehouse = \App\Models\Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

            if (!$warehouse) {
                // Thử tìm theo code
                $warehouse = \App\Models\Warehouse::where('code', $orderData['warehouse_id'])->first();
            }

            // Nếu không tìm thấy và có thông tin kho, tạo kho mới
            if (!$warehouse && !empty($orderData['warehouse_name'])) {
                $warehouse = new \App\Models\Warehouse();
                $warehouse->name = $orderData['warehouse_name'];
                $warehouse->code = 'WH-' . $orderData['warehouse_id'];
                $warehouse->pancake_id = $orderData['warehouse_id'];
                $warehouse->save();

                Log::info('Đã tạo kho hàng mới từ dữ liệu Pancake', [
                    'warehouse_id' => $warehouse->id,
                    'pancake_id' => $orderData['warehouse_id'],
                    'name' => $orderData['warehouse_name']
                ]);
            }

            if ($warehouse) {
                $order->warehouse_id = $warehouse->id;
                $order->warehouse_code = $warehouse->code;
                $order->pancake_warehouse_id = $warehouse->pancake_id;
            } else {
                // Lưu pancake_warehouse_id ngay cả khi không tìm thấy warehouse
                $order->pancake_warehouse_id = $orderData['warehouse_id'];
            }
        }

        // Cập nhật đơn vị vận chuyển
        if (!empty($orderData['partner']['partner_id'])) {
            $providerId = $orderData['partner']['partner_id'];
            $provider = \App\Models\ShippingProvider::where('pancake_id', $providerId)
                ->orWhere('pancake_partner_id', $providerId)
                ->first();

            // Nếu không tìm thấy và có tên đơn vị vận chuyển, tạo mới
            if (!$provider && !empty($orderData['shipping_provider_name'])) {
                $provider = new \App\Models\ShippingProvider();
                $provider->name = $orderData['shipping_provider_name'];
                $provider->pancake_id = $providerId;
                $provider->save();

                Log::info('Đã tạo đơn vị vận chuyển mới từ dữ liệu Pancake', [
                    'provider_id' => $provider->id,
                    'pancake_id' => $providerId,
                    'name' => $orderData['shipping_provider_name']
                ]);
            }

            if ($provider) {
                $order->shipping_provider_id = $provider->id;
                $order->pancake_shipping_provider_id = $provider->pancake_id;
            } else {
                // Lưu pancake_shipping_provider_id ngay cả khi không tìm thấy provider
                $order->pancake_shipping_provider_id = $providerId;
            }
        }

        // Update order items and variant revenues
        if (!empty($orderData['items']) && is_array($orderData['items'])) {
            // First, mark all existing items for potential deletion
            $existingItemIds = $order->items->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($orderData['items'] as $itemData) {
                // Find or create order item
                $orderItem = $order->items()
                    ->where('pancake_product_id', $itemData['product_id'])
                    ->first();

                if (!$orderItem) {
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                }

                // Update order item fields and handle variant revenue
                $orderItem = $this->setOrderItemFields($orderItem, $itemData);
                $updatedItemIds[] = $orderItem->id;
            }

            // Remove items that no longer exist in the updated data
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                // Delete variant revenues for removed items
                DB::table('variant_revenues')
                    ->whereIn('order_item_id', $itemsToDelete)
                    ->where('order_id', $order->id)
                    ->delete();

                // Delete the items themselves
                OrderItem::whereIn('id', $itemsToDelete)->delete();

                Log::info('Removed deleted items and their variant revenues', [
                    'order_id' => $order->id,
                    'deleted_item_ids' => $itemsToDelete
                ]);
            }
        }

        // Update customer information if provided
        // Phần 1 sẽ bị xóa và gộp các trường vào phần 2

// Update customer information if provided
if (!empty($orderData['customer'])) {

    $customerData = $orderData['customer'];
    $order->customer_name = $customerData['name'] ?? $order->customer_name;

    // Update the associated customer if we can find them
    if ($order->customer_id) {
        $customer = Customer::find($order->customer_id);

        if ($customer) {
            // Update pancake_id if not already set
            if (empty($customer->pancake_id) && !empty($customerData['id'])) {
                $customer->pancake_id = $customerData['id'];
            }

            // Basic information
            $customer->name = $customerData['bill_full_name'] ?? '';
            $customer->phone = $customerData['bill_phone_number'] ?? '';
            $customer->email = $customerData['customer_email'] ?? '';

            // Address information from shipping_address
            if (!empty($orderData['shipping_address'])) {
                $shippingAddress = $orderData['shipping_address'];
                $customer->full_address = $shippingAddress['full_address'] ?? '';
                $customer->province = $shippingAddress['province_id'] ?? $shippingAddress['province_code'] ?? null;
                $customer->district = $shippingAddress['district_id'] ?? $shippingAddress['district_code'] ?? null;
                $customer->ward = $shippingAddress['commune_id'] ?? $shippingAddress['ward_code'] ?? null;
                $customer->street_address = $shippingAddress['address'] ?? '';
            }

            // Handle multiple phone numbers
            if (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                if (empty($customer->phone)) {
                    $customer->phone = $customerData['phone_numbers'][0];
                }
                if (Schema::hasColumn('customers', 'phone_numbers')) {
                    $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                }
            }

            // Handle multiple emails
            if (!empty($customerData['emails']) && is_array($customerData['emails']) && empty($customer->email)) {
                if (!empty($customerData['emails'][0])) {
                    $customer->email = $customerData['emails'][0];
                }
                if (Schema::hasColumn('customers', 'emails')) {
                    $customer->emails = json_encode($customerData['emails']);
                }
            }

            // Update other customer fields
            $customer->gender = $customerData['gender'] ?? $customer->gender;
            $customer->date_of_birth = $customerData['date_of_birth'] ?? $customer->date_of_birth;


if(!$order->customer_id){
                $phoneNumber = $order->bill_phone_number;
                $customerName = $order->bill_full_name;

                $customer = \App\Models\Customer::where('phone', $phoneNumber)
                                              ->where('name', $customerName)
                                              ->first();


                // if ($customer) {
                //     // Reset totals first
                //     $customer->total_orders_count = 0;
                //     $customer->total_spent = 0;
                //     $customer->save();

                //     $aggregates = \App\Models\Order::where('bill_phone_number', $phoneNumber)->where('bill_full_name', $customerName)->get();

                //     $totalOrders = 0;
                //     foreach ($aggregates as $item) {
                //         $totalOrders++;
                //     }

                //     $totalSpent = 0;
                //     foreach ($aggregates as $item) {
                //         $totalSpent += $item->total_value;
                //     }


                //     // if ($aggregates) {
                //         $customer->total_orders_count = $totalOrders;
                //         $customer->total_spent = $totalSpent;
                //         $customer->save();
                //         // if($customer->total_orders_count !== 0){
                //         //    dd($customer);
                //         // }

                //         Log::info('Đã cập nhật thông tin khách hàng trong updateOrderFromPancake', [
                //             'customer_id' => $customer->id,
                //             'phone' => $customer->phone,
                //             'name' => $customer->name,
                //             'order_id' => $order->id,
                //             'total_orders' => $customer->total_orders_count,
                //             'total_spent' => $customer->total_spent
                //         ]);
                //     // }
                // }



            // Additional fields with schema check
            if (Schema::hasColumn('customers', 'fb_id')) {
                $customer->fb_id = $customerData['fb_id'] ?? $customer->fb_id;
            }
            if (Schema::hasColumn('customers', 'order_count')) {
                $customer->order_count = $customerData['order_count'] ?? $customer->order_count;
            }
            if (Schema::hasColumn('customers', 'succeeded_order_count')) {
                $customer->succeeded_order_count = $customerData['succeed_order_count'] ?? $customer->succeeded_order_count;
            }
            if (Schema::hasColumn('customers', 'returned_order_count')) {
                $customer->returned_order_count = $customerData['returned_order_count'] ?? $customer->returned_order_count;
            }
            if (Schema::hasColumn('customers', 'purchased_amount')) {
                $customer->purchased_amount = $customerData['purchased_amount'] ?? $customer->purchased_amount;
            }
            if (Schema::hasColumn('customers', 'customer_level')) {
                $customer->customer_level = $customerData['level'] ?? $customer->customer_level;
            }
            if (Schema::hasColumn('customers', 'tags') && !empty($customerData['tags'])) {
                $customer->tags = json_encode($customerData['tags']);
            }
            if (Schema::hasColumn('customers', 'conversation_tags') && !empty($customerData['conversation_tags'])) {
                $customer->conversation_tags = json_encode($customerData['conversation_tags']);
            }
            if (Schema::hasColumn('customers', 'reward_points')) {
                $customer->reward_points = $customerData['reward_point'] ?? $customer->reward_points;
            }
            if (Schema::hasColumn('customers', 'addresses') && !empty($customerData['shop_customer_addresses'])) {
                $customer->addresses = json_encode($customerData['shop_customer_addresses']);
            }

            $customer->save();

            Log::info('Updated associated customer record', [
                'customer_id' => $customer->id,
                'order_id' => $order->id
            ]);
        }
    }
}
         if (isset($orderData['bill_full_name']) || isset($orderData['bill_phone_number']) || isset($orderData['customer_name']) || isset($orderData['customer_phone']) || isset($orderData['customer_email'])) {
            // Handle flat customer data structure
            $order->bill_full_name = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? $order->customer_name);
            $order->bill_phone_number = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? $order->customer_phone);
            // Handle NULL email safely
            if (!empty($orderData['customer_email'])) {
                $order->customer_email = $orderData['customer_email'];
            }

            // Update the associated customer if we have a phone number
            if (($order->customer_phone || !empty($orderData['bill_phone_number']) || !empty($orderData['customer_phone'])) && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->name = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? $customer->name);
                    $customer->phone = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? $customer->phone);
                    // Only update email if not empty
                    if (!empty($orderData['customer_email'])) {
                        $customer->email = $orderData['customer_email'];
                    }
                    $customer->save();

                    Log::info('Updated associated customer record from flat data', [
                        'customer_id' => $customer->id,
                        'order_id' => $order->id
                    ]);
                }
            }
        }
    }
        // Update items if provided
        if (!empty($orderData['items'])) {
            // Get existing items to compare and update
            $existingItems = $order->items;
            $updated = false;

            // Delete existing items if needed
            if ($existingItems->count() > 0) {
                $order->items()->delete();
                $updated = true;
                Log::info('Deleted existing order items before update', [
                    'order_id' => $order->id,
                    'deleted_item_count' => $existingItems->count()
                ]);
            }

            // Create new items from the updated data
            foreach ($orderData['items'] as $item) {
                // Check if item has components structure
                if (!empty($item['components']) && is_array($item['components'])) {
                    foreach ($item['components'] as $component) {
                        $orderItem = new OrderItem();
                        $orderItem->order_id = $order->id;
                        $this->setOrderItemFields($orderItem, $item);  // Will handle component extraction
                        $orderItem->save();
                        $updated = true;

                        Log::info('Updated order item from component structure', [
                            'order_id' => $order->id,
                            'product_name' => $orderItem->product_name,
                            'quantity' => $orderItem->quantity,
                            'price' => $orderItem->price
                        ]);

                        // We're only creating one order item per component set for now
                        break;
                    }
                } else {
                    // Handle traditional item format
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $this->setOrderItemFields($orderItem, $item);
                    $orderItem->save();
                    $updated = true;

                    Log::info('Updated order item using standard format', [
                        'order_id' => $order->id,
                        'product_name' => $orderItem->product_name,
                        'quantity' => $orderItem->quantity
                    ]);
                }
            }

            if ($updated) {
                // Update total value based on new items
                $newTotal = $order->items->sum(function($item) {
                    return ($item->price ?? 0) * ($item->quantity ?? 1);
                });

                // Add shipping fee if available
                $newTotal += ($order->shipping_fee ?? 0);

                // Update order total if changed
                if ($newTotal > 0 && $newTotal != $order->total_value) {
                    $order->total_value = $newTotal;
                    Log::info('Updated order total value based on new items', [
                        'order_id' => $order->id,
                        'old_total' => $order->total_value,
                        'new_total' => $newTotal
                    ]);
                }
            }
        }

        // Update page information if provided
        if (!empty($orderData['page'])) {
            $pageData = $orderData['page'];
            $page = PancakePage::where('pancake_id', $pageData['id'])->first();

            // Create page if doesn't exist
            if (!$page) {
                $page = new PancakePage();
                $page->pancake_id = $pageData['id'];
                $page->pancake_page_id = $pageData['id']; // Thêm dòng này để đảm bảo cả hai trường đều được set
                $page->name = $pageData['name'];
                $page->pancake_shop_table_id = $order->pancake_shop_id; // Liên kết với shop
                $page->save();

                Log::info('Created new Pancake Page during order update', [
                    'page_id' => $page->id,
                    'pancake_id' => $page->pancake_id,
                    'name' => $page->name
                ]);
            } else {
                // Update page info if needed
                $page->name = $pageData['name'] ?? $page->name;

                // Only update username if the column exists
                if (Schema::hasColumn('pancake_pages', 'username')) {
                    $page->username = $pageData['username'] ?? $page->username;
                }

                // Update shop association if it's missing
                    if (empty($page->pancake_shop_table_id) && isset($orderData['shop_id'])) {
                        $page->pancake_shop_table_id = $orderData['shop_id'];
                }

                $page->save();
            }

            $order->pancake_page_id = $page->id;
        }

        // Handle order dates
        if (!empty($orderData['created_at']) && !$order->getOriginal('created_at')) {
            try {
                $order->created_at = Carbon::parse($orderData['created_at']);
            } catch (\Exception $e) {
                Log::warning('Invalid created_at date format from Pancake', [
                    'date' => $orderData['created_at'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($orderData['updated_at'])) {
            try {
                $order->updated_at = Carbon::parse($orderData['updated_at']);
            } catch (\Exception $e) {
                Log::warning('Invalid updated_at date format from Pancake', [
                    'date' => $orderData['updated_at'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Parse and update live session information from notes
        if (!empty($orderData['note'])) {
            $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
            if ($liveSessionInfo) {
                $order->live_session_info = json_encode($liveSessionInfo);
            }
        }

        $order->save();

        // Cập nhật tổng số đơn và tổng chi tiêu của khách hàng


        $order->save();
        DB::commit();

        return [
            'success' => true,
            'message' => 'Đồng bộ thành công',
            'total_synced' => 1,
            'new_orders' => 0,
            'updated_orders' => 1
        ];
    } catch (\Exception $e) {
        Log::error('Lỗi trong quá trình cập nhật đơn hàng từ Pancake', [
            'order_id' => $order->id,
            'pancake_order_id' => $order->pancake_order_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'Đã xảy ra lỗi: ' . $e->getMessage(),
            'total_synced' => 0,
            'new_orders' => 0,
            'updated_orders' => 0
        ];
    }
}

    /**
     * Tự động gán đơn hàng cho nhân viên sale theo cài đặt phân phối
     *
     * @param \App\Models\Order $order
     * @return void
     */
    private function assignOrderToSalesStaff(\App\Models\Order $order)
    {
        try {
            // Lấy danh sách nhân viên sale đang hoạt động
            $activeStaff = \App\Models\User::role('staff')
                ->where('is_active', true)
                ->whereNotNull('pancake_uuid')
                ->get();

            if ($activeStaff->isEmpty()) {
                Log::warning('Không tìm thấy nhân viên sale nào đang hoạt động để phân phối đơn hàng. Order ID: ' . $order->id);
                return;
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            // Phân phối tuần tự: mỗi người 1 đơn lần lượt
            // Lấy ID của người cuối cùng được gán đơn hàng
            $lastAssignedId = \Illuminate\Support\Facades\Cache::get('last_assigned_staff_id');

            // Sắp xếp danh sách nhân viên theo ID
            $staffIds = $activeStaff->pluck('id')->sort()->values();

            // Tìm index của người cuối cùng được gán
            $currentIndex = -1;
            if ($lastAssignedId) {
                $currentIndex = $staffIds->search($lastAssignedId);
            }

            // Lấy người tiếp theo trong danh sách
            $nextIndex = ($currentIndex + 1) % $staffIds->count();
            $nextStaffId = $staffIds[$nextIndex];

            // Lưu ID của người vừa được gán đơn
            \Illuminate\Support\Facades\Cache::put('last_assigned_staff_id', $nextStaffId, now()->addMonth());

            // Tìm thông tin của nhân viên được gán
            $assignedStaff = $activeStaff->firstWhere('id', $nextStaffId);

            if ($assignedStaff) {
                $order->assigning_seller_id = $assignedStaff->pancake_uuid;
                $order->assigning_seller_name = $assignedStaff->name;

                Log::info("Đơn hàng #{$order->id} được tự động phân phối cho {$assignedStaff->name} (ID: {$assignedStaff->id}, Pancake UUID: {$assignedStaff->pancake_uuid})");
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Lỗi khi gán đơn hàng cho nhân viên sale: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Look up and update address names (province, district, ward) from their codes
     *
     * @param Order $order
     * @return void
     */
    private function updateAddressNames(Order $order)
    {
        try {
            // Update province name if code is available
            if (!empty($order->province_code)) {
                $province = \App\Models\Province::where('code', $order->province_code)->first();
                if ($province) {
                    $order->province_name = $province->name;
                    Log::info("Updated province name for order", [
                        'order_id' => $order->id,
                        'province_code' => $order->province_code,
                        'province_name' => $province->name
                    ]);
                }
            }

            // Update district name if code is available
            if (!empty($order->district_code)) {
                $district = \App\Models\District::where('code', $order->district_code)->first();
                if ($district) {
                    $order->district_name = $district->name;
                    Log::info("Updated district name for order", [
                        'order_id' => $order->id,
                        'district_code' => $order->district_code,
                        'district_name' => $district->name
                    ]);
                }
            }

            // Update ward name if code is available
            if (!empty($order->ward_code)) {
                $ward = \App\Models\Ward::where('code', $order->ward_code)->first();
                if ($ward) {
                    $order->ward_name = $ward->name;
                    Log::info("Updated ward name for order", [
                        'order_id' => $order->id,
                        'ward_code' => $order->ward_code,
                        'ward_name' => $ward->name
                    ]);
                }
            }

            // Reconstruct full address if the components are available
            if (!empty($order->street_address)) {
                $addressParts = [];
                $addressParts[] = $order->street_address;

                if (!empty($order->ward_name)) {
                    $addressParts[] = $order->ward_name;
                }

                if (!empty($order->district_name)) {
                    $addressParts[] = $order->district_name;
                }

                if (!empty($order->province_name)) {
                    $addressParts[] = $order->province_name;
                }

                if (count($addressParts) > 1) {
                    $order->full_address = implode(', ', $addressParts);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error updating address names', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);
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
        // Map internal status to Pancake status
        $statusMap = [
            'Chờ xác nhận' => 'moi',
            'Chờ lấy hàng' => 'xac_nhan',
            'Đã giao vận chuyển' => 'dang_van_chuyen',
            'Đã nhận hàng' => 'da_giao_hang',
            'Đã huỷ' => 'huy',
            'Hoàn hàng' => 'hoan',
            'Đơn mới' => 'moi'
        ];

        return $statusMap[$internalStatus] ?? 'moi';
    }

    /**
     * Cancel ongoing sync process
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSync(Request $request)
    {
        try {
            // Check if specific sync session is specified
            $syncInfo = $request->input('sync_info');

            if ($syncInfo) {
                // Cancel specific sync session
                $syncData = Cache::get($syncInfo);
                if ($syncData) {
                    $syncData['in_progress'] = false;
                    Cache::put($syncInfo, $syncData, now()->addHour());

                    return response()->json([
                        'success' => true,
                        'message' => 'Đã hủy quá trình đồng bộ'
                    ]);
                }
            } else {
                // Find and cancel all active sync sessions
                $dateCacheKeys = Cache::get('pancake_sync_date_keys', []);
                $canceledCount = 0;

                foreach ($dateCacheKeys as $cacheKey) {
                    $syncData = Cache::get($cacheKey);
                    if ($syncData && ($syncData['in_progress'] ?? false)) {
                        $syncData['in_progress'] = false;
                        Cache::put($cacheKey, $syncData, now()->addHour());
                        $canceledCount++;
                    }
                }

                if ($canceledCount > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => "Đã hủy {$canceledCount} quá trình đồng bộ đang hoạt động"
                    ]);
                }
            }

            // No active sync found
            return response()->json([
                'success' => true,
                'message' => 'Không có quá trình đồng bộ nào đang chạy'
            ]);

        } catch (\Exception $e) {
            Log::error('Error canceling sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi hủy đồng bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ đơn hàng theo ngày từ giao diện
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncOrdersByDateManual(Request $request)
    {

        try {
            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            // Clear debug log to mark entry into this method
            Log::info('*** ENTERING syncOrdersByDateManual ***');

            $this->authorize('sync-pancake');
           // Add debug logging for incoming request
            Log::info('Request data for syncOrdersByDateManual:', [
                'all_params' => $request->all(),
                'date' => $request->input('date'),
                'sync_date' => $request->input('sync_date'),
                'sync_type' => $request->input('sync_type'),
                'startDateTime' => $request->input('startDateTime'),
                'endDateTime' => $request->input('endDateTime'),
                'request_url' => $request->fullUrl()
            ]);

            // Nếu có startDateTime và endDateTime thì dùng luôn (đồng bộ nhóm ngày)

            $dateForCacheKey = null;
            $date = null;
            $startTimestamp = $request->input('startDateTime');
            $endTimestamp = $request->input('endDateTime');

            if ($startTimestamp && $endTimestamp) {
                Log::info('Using timestamp range for sync', [
                    'startTimestamp' => $startTimestamp,
                    'endTimestamp' => $endTimestamp
                ]);
                $dateForCacheKey = $startTimestamp . '_' . $endTimestamp;
            } else {
                // Try to find a date parameter (could be 'date' or 'sync_date')
                $dateValue = $request->input('date', $request->input('sync_date'));

                if (!$dateValue) {
                    Log::error('No date parameter found in request');
                    return response()->json([
                        'success' => false,
                        'message' => 'Thiếu tham số ngày cần đồng bộ (date).'
                    ], 400);
                }

                // Validate date format
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $dateValue);
                    $dateForCacheKey = $date->format('Y-m-d');

                    // Format date for API query - using created_at field in Pancake


                    // Set timestamps for the API request
                    // These timestamps will be used to filter orders by created_at in Pancake
                    $startTimestamp = $request->input('startDateTime');

            $endTimestamp = ''.$request->input('endDateTime').'';

                    Log::info('Parsed date for sync', [
                        'input_date' => $dateValue,
                        'parsed_date' => $date->format('Y-m-d'),
                        'startTimestamp' => $startTimestamp,
                        'endTimestamp' => $endTimestamp
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to parse date', [
                        'input_date' => $dateValue,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Ngày không hợp lệ. Vui lòng sử dụng định dạng Y-m-d.'
                    ], 400);
                }
            }

            // Create a unique cache key for this sync session
            $cacheKey = 'pancake_sync_' . $dateForCacheKey;

            // Track the active sync session
            $dateKeys = Cache::get('pancake_sync_date_keys', []);
            if (!in_array($cacheKey, $dateKeys)) {
                $dateKeys[] = $cacheKey;
                Cache::put('pancake_sync_date_keys', $dateKeys, now()->addDay());
            }

            // Get API configuration
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? config('pancake.api_key');
            $shopId = WebsiteSetting::where('key', 'pancake_shop_id')->first()->value ?? config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake'
                ], 400);
            }

            // Store sync information in cache
            Cache::put($cacheKey, [
                'in_progress' => true,
                'date' => $dateForCacheKey,
                'start_time' => now()->toDateTimeString(),
                'page' => 1,
                'total_pages' => 0,
                'startTimestamp' => $startTimestamp,
                'endTimestamp' => $endTimestamp,
                'stats' => [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => [],
                    'total' => 0
                ]
            ], now()->addHour());

            // Prepare API parameters
            $apiParams = [
                'api_key' => $apiKey,
                'page_number' => 1,
                'page_size' => 100
            ];


            // Add date filtering parameters if provided
            // if ($startTimestamp) {

            //     $apiParams['startDateTime'] = $startTimestamp;
            // }
            // if ($endTimestamp) {
            //     $apiParams['endDateTime'] = $endTimestamp;
            // }



            // $startTimestamp = strtotime('2025-05-01 00:00:00');
            // $endTimestamp = strtotime('2025-05-31 23:59:59');

            if ($startTimestamp) {
                $apiParams['startDateTime'] = (string) $startTimestamp;
            }
            if ($endTimestamp) {
                $apiParams['endDateTime'] = (string) $endTimestamp;
            }




            // Log API call for debugging
            Log::info('Starting date-based Pancake API sync', [
                'date' => $dateForCacheKey,
                'params' => $apiParams,
                'cache_key' => $cacheKey,
                'api_url' => "{$baseUrl}/shops/{$shopId}/orders"
            ]);

            // Call Pancake API with increased timeout (60 seconds)


            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->get("{$baseUrl}/shops/{$shopId}/orders", $apiParams);

            if (!$response->successful()) {
                Log::error('API call failed', [
                    'status_code' => $response->status(),
                    'error' => $response->body()
                ]);
                throw new \Exception("API Error: " . ($response->json()['message'] ?? $response->body()));
            }

            $data = $response->json();
            $orders = $data['data'] ?? [];
            $totalPages = $data['total_pages'] ?? ceil(($data['total'] ?? 0) / 100); // Match the page_size
            $totalEntries = $data['total'] ?? count($orders);

            Log::info('API response received', [
                'orders_count' => count($orders),
                'total_pages' => $totalPages,
                'total_entries' => $totalEntries,
                'response_meta' => isset($data['meta']) ? $data['meta'] : 'No meta data'
            ]);

            // Initialize counters
            $created = 0;
            $updated = 0;
            $errors = [];

            // Process first page of orders
            foreach ($orders as $orderData) {
                try {
                    DB::beginTransaction();

                    // Sanitize address fields
                    if (!empty($orderData['address'])) {
                        $orderData['address'] = $this->sanitizeAddress($orderData['address']);
                    }

                    if (!empty($orderData['shipping_address'])) {
                        if (!empty($orderData['shipping_address']['address'])) {
                            $orderData['shipping_address']['address'] = $this->sanitizeAddress($orderData['shipping_address']['address']);
                        }
                        if (!empty($orderData['shipping_address']['full_address'])) {
                            $orderData['shipping_address']['full_address'] = $this->sanitizeAddress($orderData['shipping_address']['full_address']);
                        }
                    }

                    // Check if order already exists
                    $existingOrder = Order::where('pancake_order_id', $orderData['id'] ?? '')->first();

                    if ($existingOrder) {
                        // Update existing order
                        $this->updateOrderFromPancake($existingOrder, $orderData);
                        $updated++;
                    } else {
                        // Create new order
                        $this->createOrderFromPancake($orderData);
                        $created++;
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $orderId = $orderData['id'] ?? 'unknown';
                    $errors[] = "Lỗi xử lý đơn hàng {$orderId}: " . $e->getMessage();
                    Log::error("Error processing order: " . $e->getMessage(), [
                        'order_id' => $orderId,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Update sync stats in cache
            $syncInfo = [
                'in_progress' => true,
                'date' => $dateForCacheKey,
                'start_time' => now()->toDateTimeString(),
                'page' => 1,
                'total_pages' => $totalPages,
                'startTimestamp' => $startTimestamp,
                'endTimestamp' => $endTimestamp,
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'errors' => $errors,
                    'total' => count($orders),
                    'total_entries' => $totalEntries
                ]
            ];

            Cache::put($cacheKey, $syncInfo, now()->addHour());

            // Format display date for message
            $displayDate = '';
            if (isset($date)) {
                $displayDate = $date->format('d/m/Y');
            } else {
                $displayDate = "khoảng thời gian đã chọn";
            }

            $message = "Bắt đầu đồng bộ cho {$displayDate}. Trang 1/{$totalPages}.";

            // Create a simplified error response to avoid array conversion issues
            $errorMessages = array_slice($errors, 0, 10); // Limit to first 10 errors

            return response()->json([
                'success' => true,
                'message' => $message,
                'continue' => $totalPages > 1,
                'next_page' => $totalPages > 1 ? 2 : null,
                'total_pages' => $totalPages,
                'total_entries' => $totalEntries,
                'sync_info' => $cacheKey,
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'errors' => $errorMessages,
                    'errors_count' => count($errors),
                    'current_page' => 1
                ]
            ]);

        } catch (\Exception $e) {
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
     * Process the next page of orders in the synchronization
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function processNextPage(Request $request)
    {
        try {
            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            // Authorize access
            $this->authorize('sync-pancake');

            // Get the page number from request
            $pageNumber = $request->input('page_number', 2);

            // Determine sync info - first try from sync_info parameter
            $cacheKey = $request->input('sync_info');
            $syncInfo = null;

            if ($cacheKey) {
                // Try to get sync info from provided key
                $syncInfo = Cache::get($cacheKey);
            }

            // If sync_info not provided or invalid, try to find active sync
            if (!$syncInfo) {
                // Get list of all sync keys
                $dateCacheKeys = Cache::get('pancake_sync_date_keys', []);

                // Find active sync session
                foreach ($dateCacheKeys as $key) {
                    $tempInfo = Cache::get($key);
                    if ($tempInfo && ($tempInfo['in_progress'] ?? false)) {
                        $syncInfo = $tempInfo;
                        $cacheKey = $key;
                        break;
                    }
                }

                // If no active session, try 'pancake_sync_in_progress' as fallback
                if (!$syncInfo) {
                    $cacheKey = 'pancake_sync_in_progress';
                    $syncInfo = Cache::get($cacheKey);
                }

                // If still no sync info, check for date-based key using date from request
                if (!$syncInfo && $request->has('date')) {
                    $dateKey = 'pancake_sync_' . $request->input('date');
                    $syncInfo = Cache::get($dateKey);
                    if ($syncInfo) {
                        $cacheKey = $dateKey;
                    }
                }
            }

            // If still no sync info, we can't continue
            if (!$syncInfo) {
                throw new \Exception('Không tìm thấy thông tin đồng bộ đang hoạt động. Vui lòng bắt đầu lại.');
            }

            // Get API configuration
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? config('pancake.api_key');
            $shopId = WebsiteSetting::where('key', 'pancake_shop_id')->first()->value ?? config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                throw new \Exception('Missing API configuration (API key or Shop ID).');
            }

            // Prepare API parameters
            $apiParams = [
                'api_key' => $apiKey,
                'page_number' => $pageNumber,
                'page_size' => 100
            ];

            // Get timestamps from sync info or request
            if (isset($syncInfo['startTimestamp']) && isset($syncInfo['endTimestamp'])) {
                if (!empty($syncInfo['startTimestamp'])) {
                    $apiParams['startDateTime'] = $syncInfo['startTimestamp'];
                }
                if (!empty($syncInfo['endTimestamp'])) {
                    $apiParams['endDateTime'] = $syncInfo['endTimestamp'];
                }
            } elseif ($request->has('startDateTime') && $request->has('endDateTime')) {
                $apiParams['startDateTime'] = $request->input('startDateTime');
                $apiParams['endDateTime'] = $request->input('endDateTime');
            } elseif (isset($syncInfo['date'])) {
                // Create timestamps from date
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $syncInfo['date']);
                    $apiParams['startDateTime'] = $date->copy()->startOfDay()->timestamp;
                    $apiParams['endDateTime'] = $date->copy()->endOfDay()->timestamp;
                } catch (\Exception $e) {
                    Log::warning('Failed to parse date for timestamp creation', [
                        'date' => $syncInfo['date'],
                        'error' => $e->getMessage()
                    ]);
                    // Continue without date filtering if date parsing fails
                }
            }

            // API endpoint
            $url = "{$baseUrl}/shops/{$shopId}/orders";

            // Log API call for debugging
            Log::info('Calling Pancake API for next page', [
                'page' => $pageNumber,
                'params' => $apiParams,
                'cache_key' => $cacheKey
            ]);

            // Send request to API with increased timeout (60 seconds)
            $response = Http::timeout(60)->get($url, $apiParams);

            // Check response
            if (!$response->successful()) {
                $errorMessage = $response->json()['message'] ?? "HTTP Error: " . $response->status();
                throw new \Exception("API Error (Page {$pageNumber}): {$errorMessage}");
            }

            // Get order data from response
            $responseData = $response->json();
            $orders = $responseData['data'] ?? [];

            // Check if there are no orders to process
            if (empty($orders)) {
                // Update sync progress as completed
                $syncInfo['in_progress'] = false;
                $syncInfo['page'] = $pageNumber;
                $syncInfo['end_time'] = now()->toDateTimeString();
                Cache::put($cacheKey, $syncInfo, now()->addHour());

                // Calculate total processed
                $totalCreated = $syncInfo['stats']['created'] ?? 0;
                $totalUpdated = $syncInfo['stats']['updated'] ?? 0;
                $totalProcessed = $totalCreated + $totalUpdated;

                return response()->json([
                    'success' => true,
                    'message' => "Đồng bộ hoàn tất: Không có đơn hàng nào ở trang {$pageNumber}",
                    'stats' => [
                        'created' => 0,
                        'updated' => 0,
                        'total_created' => $totalCreated,
                        'total_updated' => $totalUpdated,
                        'total_processed' => $totalProcessed,
                        'errors' => [],
                        'errors_count' => 0,
                        'current_page' => $pageNumber,
                        'total_pages' => $syncInfo['total_pages']
                    ],
                    'continue' => false,
                    'next_page' => null,
                    'progress' => 100,
                    'sync_info' => $cacheKey
                ]);
            }

            // Counters for current page
            $created = 0;
            $updated = 0;
            $errors = [];

            // Create a timestamp to measure processing time
            $startProcessingTime = microtime(true);

            // Process each order
            foreach ($orders as $orderData) {
                DB::beginTransaction();
                try {
                    // Sanitize address fields
                    if (!empty($orderData['address'])) {
                        $orderData['address'] = $this->sanitizeAddress($orderData['address']);
                    }

                    if (!empty($orderData['shipping_address'])) {
                        if (!empty($orderData['shipping_address']['address'])) {
                            $orderData['shipping_address']['address'] = $this->sanitizeAddress($orderData['shipping_address']['address']);
                        }
                        if (!empty($orderData['shipping_address']['full_address'])) {
                            $orderData['shipping_address']['full_address'] = $this->sanitizeAddress($orderData['shipping_address']['full_address']);
                        }
                    }

                    // Check if order already exists
                    $existingOrder = null;
                    if (!empty($orderData['id'])) {
                        $existingOrder = Order::where('pancake_order_id', $orderData['id'])->first();
                    }

                    if (!$existingOrder && !empty($orderData['code'])) {
                        $existingOrder = Order::where('order_code', $orderData['code'])->first();
                    }

                    if ($existingOrder) {
                        // Update existing order
                        $this->updateOrderFromPancake($existingOrder, $orderData);
                        $updated++;
                    } else {
                        // Create new order
                        $this->createOrderFromPancake($orderData);
                        $created++;
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $orderId = $orderData['id'] ?? 'N/A';
                    $errors[] = "Đơn hàng {$orderId}: " . $e->getMessage();

                    Log::error("Error processing order", [
                        'order_id' => $orderId,
                        'page' => $pageNumber,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Calculate processing time for this batch
            $processingTime = round(microtime(true) - $startProcessingTime, 2);

            // Calculate processing rate (orders/second)
            $processingRate = count($orders) > 0 ? round(count($orders) / $processingTime, 2) : 0;

            // Extract pagination information from API response
            $totalPages = $responseData['total_pages'] ??
                          $syncInfo['total_pages'] ??
                          ceil(($responseData['total'] ?? 0) / 100);

            // Check response to see if it indicates pagination information
            if (isset($responseData['meta'])) {
                $meta = $responseData['meta'];
                if (isset($meta['last_page'])) {
                    $totalPages = $meta['last_page'];
                }
            }

            // Fix issue where total_pages equals page_number
            // Make sure totalPages is always at least as large as the current page number
            if ($totalPages < $pageNumber && !empty($orders)) {
                $totalPages = $pageNumber + 1;
            }

            // Store the original errors array
            $originalErrors = $syncInfo['stats']['errors'] ?? [];

            // Ensure errors array is properly handled (might be causing the Array to string conversion error)
            if (!is_array($originalErrors)) {
                $originalErrors = [];
            }

            // Create a merged array of errors that is safe for JSON serialization
            $mergedErrors = array_merge($originalErrors, $errors);
            // Limit the number of errors to prevent the array from growing too large
            if (count($mergedErrors) > 100) {
                $mergedErrors = array_slice($mergedErrors, -100);
            }

            // Update statistics in cache
            $syncInfo['page'] = $pageNumber;
            $syncInfo['total_pages'] = $totalPages;
            $syncInfo['stats']['created'] = ($syncInfo['stats']['created'] ?? 0) + $created;
            $syncInfo['stats']['updated'] = ($syncInfo['stats']['updated'] ?? 0) + $updated;
            $syncInfo['stats']['errors'] = $mergedErrors;
            $syncInfo['stats']['total'] = ($syncInfo['stats']['total'] ?? 0) + count($orders);
            $syncInfo['stats']['processing_time'] = ($syncInfo['stats']['processing_time'] ?? 0) + $processingTime;
            $syncInfo['stats']['last_page_processing_time'] = $processingTime;
            $syncInfo['stats']['last_page_processing_rate'] = $processingRate;

            // Also update total_entries from API if available
            if (isset($responseData['total'])) {
                $syncInfo['stats']['total_entries'] = $responseData['total'];
            } else if (isset($responseData['meta']['total'])) {
                $syncInfo['stats']['total_entries'] = $responseData['meta']['total'];
            }

            // Check if we've processed all pages
            $isLastPage = $pageNumber >= $totalPages ||
                          (isset($responseData['has_next']) && $responseData['has_next'] === false) ||
                          empty($orders);

            $syncInfo['in_progress'] = !$isLastPage;

            // If this is the last page, store end time
            if ($isLastPage) {
                $syncInfo['end_time'] = now()->toDateTimeString();
            }

            Cache::put($cacheKey, $syncInfo, now()->addHour());

            // Calculate progress percentage
            $progress = min(100, round(($pageNumber / $totalPages) * 100));

            // Calculate total processed records
            $totalCreated = $syncInfo['stats']['created'];
            $totalUpdated = $syncInfo['stats']['updated'];
            $totalProcessed = $totalCreated + $totalUpdated;

            // Calculate estimated time remaining
            $estimatedTimeRemaining = null;
            if (!$isLastPage && $pageNumber > 1 && isset($syncInfo['stats']['processing_time'])) {
                $avgTimePerPage = $syncInfo['stats']['processing_time'] / $pageNumber;
                $remainingPages = $totalPages - $pageNumber;
                $remainingSeconds = $avgTimePerPage * $remainingPages;

                if ($remainingSeconds < 60) {
                    $estimatedTimeRemaining = "dưới 1 phút";
                } else if ($remainingSeconds < 3600) {
                    $estimatedTimeRemaining = round($remainingSeconds / 60) . " phút";
                } else {
                    $estimatedTimeRemaining = round($remainingSeconds / 3600, 1) . " giờ";
                }
            }

            // Log sync progress
            Log::info("Completed sync page {$pageNumber}/{$totalPages}", [
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
                'progress' => $progress,
                'is_last_page' => $isLastPage,
                'processing_time' => $processingTime,
                'processing_rate' => $processingRate
            ]);

            // Determine next page (if any)
            $nextPage = $isLastPage ? null : $pageNumber + 1;

            // Create a clean response with simplified error information to avoid array conversion issues
            $errorMessages = array_slice($errors, 0, 10); // Limit to 10 recent errors

            return response()->json([
                'success' => true,
                'message' => $isLastPage ?
                    "Đồng bộ hoàn tất: Đã xử lý tất cả {$totalPages} trang" :
                    "Đã xử lý trang {$pageNumber}/{$totalPages}",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'total_created' => $totalCreated,
                    'total_updated' => $totalUpdated,
                    'total_processed' => $totalProcessed,
                    'errors' => $errorMessages,
                    'errors_count' => count($mergedErrors),
                    'current_page' => $pageNumber,
                    'total_pages' => $totalPages,
                    'processing_time' => $processingTime,
                    'processing_rate' => $processingRate,
                    'estimated_time_remaining' => $estimatedTimeRemaining
                ],
                'continue' => !$isLastPage,
                'next_page' => $nextPage,
                'progress' => $progress,
                'total_entries' => $responseData['total'] ?? $totalProcessed,
                'sync_info' => $cacheKey
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing sync page', [
                'page' => $request->input('page_number', 2),
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
     * Get progress of all orders sync
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllOrdersSyncProgress()
    {
        // Get list of all date-based sync keys
        $dateCacheKeys = Cache::get('pancake_sync_date_keys', []);

        // Find active sync session
        $activeSyncInfo = null;
        $activeCacheKey = null;

        foreach ($dateCacheKeys as $cacheKey) {
            $syncInfo = Cache::get($cacheKey);
            if ($syncInfo && ($syncInfo['in_progress'] ?? false)) {
                $activeSyncInfo = $syncInfo;
                $activeCacheKey = $cacheKey;
                break;
            }
        }

        // If no active sync found, get the most recent sync info
        if (!$activeSyncInfo && !empty($dateCacheKeys)) {
            // Sort keys by timestamp to find most recent
            $latestTimestamp = 0;
            $latestKey = null;

            foreach ($dateCacheKeys as $cacheKey) {
                $syncInfo = Cache::get($cacheKey);
                // Extract timestamp from the key if possible
                if (preg_match('/(\d+)$/', $cacheKey, $matches)) {
                    $keyTimestamp = intval($matches[1]);
                    if ($keyTimestamp > $latestTimestamp) {
                        $latestTimestamp = $keyTimestamp;
                        $latestKey = $cacheKey;
                    }
                }
            }

            // If found a latest key, use it
            if ($latestKey) {
                $activeSyncInfo = Cache::get($latestKey);
                $activeCacheKey = $latestKey;
            } else {
                // Fallback to first key if no timestamps found
                $activeSyncInfo = Cache::get($dateCacheKeys[0]);
                $activeCacheKey = $dateCacheKeys[0];
            }
        }

        // If no sync info found at all, return default empty state
        if (!$activeSyncInfo) {
            return response()->json([
                'success' => true,
                'progress' => 0,
                'in_progress' => false,
                'message' => 'Không có quá trình đồng bộ nào đang hoạt động.',
                'stats' => [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => []
                ],
                'order_stats' => [
                    'new' => 0,
                    'updated' => 0,
                    'errors' => 0,
                    'total_processed' => 0,
                    'total_expected' => 0,
                ]
            ]);
        }

        // Calculate progress percentage
        $progress = 0;
        if (($activeSyncInfo['total_pages'] ?? 0) > 0) {
            $progress = min(100, round(($activeSyncInfo['page'] / $activeSyncInfo['total_pages']) * 100));
        }

        // Get counters
        $newOrders = $activeSyncInfo['stats']['created'] ?? 0;
        $updatedOrders = $activeSyncInfo['stats']['updated'] ?? 0;
        $errorCount = 0;
        $errorDetails = [];

        // Safely get error count and details
        if (isset($activeSyncInfo['stats']['errors'])) {
            if (is_array($activeSyncInfo['stats']['errors'])) {
                $errorCount = count($activeSyncInfo['stats']['errors']);
                // Get the 5 most recent errors with details
                $errorDetails = array_slice($activeSyncInfo['stats']['errors'], -5);
            } else {
                // If errors is not an array, set to 0 and fix the structure
                $errorCount = 0;
                $activeSyncInfo['stats']['errors'] = [];
                Cache::put($activeCacheKey, $activeSyncInfo, now()->addHour());
            }
        }

        $isInProgress = $activeSyncInfo['in_progress'] ?? false;

        // Create appropriate message based on sync state
        $message = 'Đồng bộ đã hoàn tất.';
        $syncDate = '';

        if (isset($activeSyncInfo['date']) && !empty($activeSyncInfo['date'])) {
            try {
                // Check if date is a timestamp range or a normal date
                if (strpos($activeSyncInfo['date'], '_') !== false) {
                    // This is a timestamp range
                    $parts = explode('_', $activeSyncInfo['date']);
                    if (count($parts) == 2) {
                        $startTime = Carbon::createFromTimestamp($parts[0])->format('d/m/Y H:i');
                        $endTime = Carbon::createFromTimestamp($parts[1])->format('d/m/Y H:i');
                        $syncDate = "từ {$startTime} đến {$endTime}";
                    } else {
                        $syncDate = $activeSyncInfo['date'];
                    }
                } else {
                    // Normal date
                    $syncDate = Carbon::createFromFormat('Y-m-d', $activeSyncInfo['date'])->format('d/m/Y');
                }
            } catch (\Exception $e) {
                $syncDate = $activeSyncInfo['date']; // Use as-is if not a valid date
            }
        }

        if ($isInProgress) {
            $message = $syncDate ?
                "Đang đồng bộ dữ liệu {$syncDate}. Trang {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']}..." :
                "Đang đồng bộ. Trang {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']}...";
        } else if ($progress >= 100) {
            $message = $syncDate ?
                "Đồng bộ dữ liệu {$syncDate} đã hoàn tất." :
                'Đồng bộ đã hoàn tất.';
        } else if ($activeSyncInfo['page'] > 0) {
            $message = $syncDate ?
                "Đã xử lý {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']} trang cho {$syncDate}. Đồng bộ tạm dừng." :
                "Đã xử lý {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']} trang. Đồng bộ tạm dừng.";
        }

        // Calculate elapsed time
        $elapsedTime = null;
        $startTime = null;
        $elapsedSeconds = 0;

        if (!empty($activeSyncInfo['start_time'])) {
            try {
                $startTime = Carbon::parse($activeSyncInfo['start_time']);
                $elapsedTime = $startTime->diffForHumans(null, true);
                $elapsedSeconds = Carbon::now()->diffInSeconds($startTime);
            } catch (\Exception $e) {
                // Handle invalid date format
                $elapsedTime = 'không xác định';
                $elapsedSeconds = 0;
            }
        }

        // Calculate estimated time remaining if in progress
        $estimatedTimeRemaining = null;
        if ($isInProgress && $progress > 0 && $elapsedSeconds > 0) {
            // Calculate seconds per percent
            $secondsPerPercent = $elapsedSeconds / $progress;
            // Calculate remaining seconds
            $remainingSeconds = $secondsPerPercent * (100 - $progress);

            if ($remainingSeconds < 60) {
                $estimatedTimeRemaining = "dưới 1 phút";
            } else if ($remainingSeconds < 3600) {
                $estimatedTimeRemaining = round($remainingSeconds / 60) . " phút";
            } else {
                $estimatedTimeRemaining = round($remainingSeconds / 3600, 1) . " giờ";
            }
        }

        // Order statistics for display - ensure all values are numeric
        $orderStats = [
            'new' => intval($newOrders),
            'updated' => intval($updatedOrders),
            'errors' => intval($errorCount),
            'total_processed' => intval($newOrders) + intval($updatedOrders),
            'total_expected' => intval($activeSyncInfo['stats']['total_entries'] ?? ($newOrders + $updatedOrders)),
        ];

        // Calculate processing rate (orders per minute) if we have elapsed time
        $processingRate = null;
        if ($elapsedSeconds > 0) {
            $totalProcessed = $orderStats['total_processed'];
            $processingRate = round(($totalProcessed / $elapsedSeconds) * 60, 1);
        }

        // Add detailed progress info about current processing
        $detailedProgress = [
            'current_page' => $activeSyncInfo['page'] ?? 0,
            'total_pages' => $activeSyncInfo['total_pages'] ?? 1,
            'page_progress' => $progress,
            'elapsed_time' => $elapsedTime,
            'elapsed_seconds' => $elapsedSeconds,
            'start_time' => $activeSyncInfo['start_time'] ?? null,
            'processing_rate' => $processingRate, // Orders processed per minute
            'estimated_time_remaining' => $estimatedTimeRemaining,
            'timestamp' => now()->toIso8601String()
        ];

        // Get specific details about any failed order processing
        $failedOrderDetails = [];
        if (!empty($errorDetails)) {
            foreach($errorDetails as $error) {
                // Parse the error message to extract order ID and error details
                if (preg_match('/Đơn hàng ([^:]+): (.+)/', $error, $matches)) {
                    $failedOrderDetails[] = [
                        'order_id' => $matches[1],
                        'error' => $matches[2]
                    ];
                } else {
                    $failedOrderDetails[] = [
                        'error' => $error
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
            'in_progress' => $isInProgress,
            'message' => $message,
            'sync_info' => $activeCacheKey,
            'stats' => [
                'created' => $newOrders,
                'updated' => $updatedOrders,
                'errors' => $errorDetails,
                'errors_count' => $errorCount,
                'failed_orders' => $failedOrderDetails
            ],
            'order_stats' => $orderStats,
            'current_page' => $activeSyncInfo['page'] ?? 0,
            'total_pages' => $activeSyncInfo['total_pages'] ?? 1,
            'elapsed_time' => $elapsedTime,
            'sync_date' => $syncDate ?: ($activeSyncInfo['date'] ?? null),
            'detailed_progress' => $detailedProgress
        ]);
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
            $this->authorize('sync-pancake');

            $request->validate([
                'date' => 'required|date_format:Y-m-d',
            ]);

            $date = $request->date;
            $result = Cache::get('pancake_sync_result_' . $date);

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
     * Synchronize orders from external API
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncOrdersFromApi(Request $request)
    {
        try {
            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            $this->authorize('sync-pancake');

            // Lấy cấu hình API
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? config('pancake.api_key');
            $shopId = WebsiteSetting::where('key', 'pancake_shop_id')->first()->value ?? config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake'
                ], 400);
            }

            // Thống kê kết quả đồng bộ
            $stats = [
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            // Biến để theo dõi phân trang
            $page = 1;
            $perPage = 100; // Số đơn hàng mỗi trang
            $hasMorePages = true;
            $maxPages = 50; // Giới hạn số trang để tránh vòng lặp vô hạn

            DB::beginTransaction();

            try {
                // Xử lý phân trang và lấy tất cả đơn hàng
                while ($hasMorePages && $page <= $maxPages) {
                    // API endpoint với phân trang
                    $url = "{$baseUrl}/shops/{$shopId}/orders";

                    // Gửi request tới API với timeout đủ lớn (5 phút)
                    $response = Http::timeout(300)
                        ->get($url, [
                            'api_key' => $apiKey,
                            'page_number' => $page,
                            'page_size' => $perPage
                        ]);

                    // Kiểm tra kết quả response
                    if (!$response->successful()) {
                        $errorMessage = $response->json()['message'] ?? "HTTP Error: " . $response->status();
                        throw new \Exception("Lỗi kết nối API Pancake (Trang {$page}): {$errorMessage}");
                    }

                    // Lấy dữ liệu đơn hàng từ response
                    $responseData = $response->json();

                    // Xác định danh sách đơn từ response (cấu trúc có thể thay đổi tùy API)
                    $orders = [];
                    if (isset($responseData['orders'])) {
                        $orders = $responseData['orders'];
                    } elseif (isset($responseData['data'])) {
                        $orders = $responseData['data'];
                    }

                    // Nếu không có đơn hàng, dừng vòng lặp
                    if (empty($orders)) {
                        break;
                    }

                    // Cập nhật tổng số đơn hàng
                    $stats['total'] += count($orders);

                    // Xử lý từng đơn hàng
                    foreach ($orders as $orderData) {
                        try {
                            // Kiểm tra xem đơn hàng đã tồn tại chưa (dựa vào order_code hoặc ID)
                            $existingOrder = null;

                            if (!empty($orderData['id'])) {
                                $existingOrder = Order::where('pancake_order_id', $orderData['id'])->first();
                            }

                            if (!$existingOrder && !empty($orderData['code'])) {
                                $existingOrder = Order::where('order_code', $orderData['code'])->first();
                            }

                            if ($existingOrder) {
                                // Cập nhật đơn hàng
                                $this->updateOrderFromPancake($existingOrder, $orderData);
                                $stats['updated']++;
                            } else {
                                // Tạo đơn hàng mới
                                $this->createOrderFromPancake($orderData);
                                $stats['created']++;
                            }
                        } catch (\Exception $e) {
                            // Ghi lại lỗi cho đơn hàng cụ thể nhưng tiếp tục với đơn khác
                            $orderId = isset($orderData['id']) ? $orderData['id'] : 'N/A';
                            $stats['errors'][] = "Đơn hàng {$orderId}: " . $e->getMessage();
                            $stats['skipped']++;

                            $errorOrderId = isset($orderData['id']) ? $orderData['id'] : 'unknown';
                            Log::error("Lỗi xử lý đơn hàng từ Pancake API", [
                                'order_id' => $errorOrderId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Kiểm tra xem có trang tiếp theo không
                    $currentPage = $responseData['meta']['current_page'] ?? $page;
                    $lastPage = $responseData['meta']['last_page'] ?? 1;

                    $hasMorePages = !empty($orders) && count($orders) >= $perPage && $currentPage < $lastPage;
                    $page++;
                }

                DB::commit();

                // Log thành công
                Log::info('Đồng bộ đơn hàng từ Pancake API thành công', [
                    'user_id' => Auth::id() ?? 'system',
                    'stats' => $stats
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Đồng bộ thành công',
                    'stats' => $stats
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ đơn hàng từ Pancake API', [
                'user_id' => Auth::id() ?? 'system',
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
     * Synchronize all orders from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function syncAllOrders(Request $request)
    {
        try {

            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            $this->authorize('sync-pancake');

            // First check if this is actually a date-specific sync that should be redirected
            if ($request->has('startDateTime') && $request->has('endDateTime')) {
                Log::info('Redirecting date sync request to syncOrdersByDateManual', [
                    'date' => $request->input('date'),
                    'startDateTime' => $request->input('startDateTime'),
                    'endDateTime' => $request->input('endDateTime')
                ]);

                return $this->syncOrdersByDateManual($request);
            }

            // Get API configuration
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? config('pancake.api_key');
            $shopId = WebsiteSetting::where('key', 'pancake_shop_id')->first()->value ?? config('pancake.shop_id');
            $baseUrl = rtrim(config('pancake.base_uri', 'https://pos.pages.fm/api/v1'), '/');

            if (empty($apiKey) || empty($shopId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa cấu hình API key hoặc Shop ID của Pancake'
                ], 400);
            }

            // Add date parameters if provided
            $startDateTime = $request->input('startDateTime');
            $endDateTime = $request->input('endDateTime');

            // Create a unique cache key for this sync session
            $cacheKey = 'pancake_sync_all_' . now()->timestamp;

            // Add to date keys list for tracking
            $dateKeys = Cache::get('pancake_sync_date_keys', []);
            if (!in_array($cacheKey, $dateKeys)) {
                $dateKeys[] = $cacheKey;
                Cache::put('pancake_sync_date_keys', $dateKeys, now()->addDay());
            }

            // Prepare API parameters
            $apiParams = [
                'api_key'     => $apiKey,
                'page_number' => 1,
                'page_size'   => 100
            ];

            // Hardcode startDateTime to Monday of the current week
            $apiParams['startDateTime'] = (string)Carbon::now()->startOfWeek(Carbon::MONDAY)->timestamp;
            // Hardcode endDateTime to the current moment
            $apiParams['endDateTime'] = (string)Carbon::now()->timestamp;

            // Remove or comment out the previous logic for startDateTime and endDateTime
            // if ($startDateTime) {
            //     $apiParams['startDateTime'] = $startDateTime;
            // }

            // if ($endDateTime) {
            //     $apiParams['endDateTime'] = $endDateTime;
            // }

            // Log API request
            Log::info('Starting Pancake API sync for current week', [
                'params' => $apiParams,
                'cache_key' => $cacheKey
            ]);

            // API endpoint
            $url = "{$baseUrl}/shops/{$shopId}/orders";

            // Send first request with increased timeout (60 seconds)
            $response = Http::timeout(60)->get($url, $apiParams);

            // Check response
            if (!$response->successful()) {
                $errorMessage = $response->json()['message'] ?? "HTTP Error: " . $response->status();
                throw new \Exception("API Error: {$errorMessage}");
            }

            $responseData = $response->json();

            // Get orders from response
            $orders = $responseData['data'] ?? [];

            // Process first page
            $statsFirstPage = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            // Process each order
            Log::info("Found " . count($orders) . " orders from API in page 1");
            foreach ($orders as $orderData) {
                DB::beginTransaction();
                try {
                    // Check if order exists
                    $existingOrder = null;

                    if (!empty($orderData['id'])) {
                        $existingOrder = Order::where('pancake_order_id', $orderData['id'])->first();
                    }

                    if (!$existingOrder && !empty($orderData['code'])) {
                        $existingOrder = Order::where('order_code', $orderData['code'])->first();
                    }

                    if ($existingOrder) {
                        // Update order
                        $this->updateOrderFromPancake($existingOrder, $orderData);
                        $statsFirstPage['updated']++;
                    } else {
                        // Create new order
                        $this->createOrderFromPancake($orderData);
                        $statsFirstPage['created']++;
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $orderId = $orderData['id'] ?? 'N/A';
                    $statsFirstPage['errors'][] = "Đơn hàng {$orderId}: " . $e->getMessage();
                    $statsFirstPage['skipped']++;

                    Log::error("Error processing order from Pancake API", [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Get total pages and entries
            $totalPages = $responseData['total_pages'] ?? 1;
            $totalEntries = $responseData['total'] ?? count($orders);

            // Check response metadata format
            if (isset($responseData['meta'])) {
                $meta = $responseData['meta'];
                if (isset($meta['last_page'])) {
                    $totalPages = $meta['last_page'];
                }
                if (isset($meta['total'])) {
                    $totalEntries = $meta['total'];
                }
            }

            // Determine if there are more pages
            $hasMorePages = true;
            if (empty($orders)) {
                $hasMorePages = false;
            } else if (isset($responseData['has_next'])) {
                $hasMorePages = $responseData['has_next'];
            } else if (isset($responseData['meta']['current_page']) && isset($responseData['meta']['last_page'])) {
                $hasMorePages = $responseData['meta']['current_page'] < $responseData['meta']['last_page'];
            } else if (count($orders) < $apiParams['page_size']) {
                $hasMorePages = false;
            }

            // Store sync progress in cache
            Cache::put($cacheKey, [
                'in_progress' => $hasMorePages, // Set to true only if there are more pages
                'start_time' => now()->toDateTimeString(),
                'page' => 1,
                'total_pages' => $totalPages,
                'startTimestamp' => $startDateTime,
                'endTimestamp' => $endDateTime,
                'stats' => [
                    'created' => $statsFirstPage['created'],
                    'updated' => $statsFirstPage['updated'],
                    'errors' => $statsFirstPage['errors'],
                    'total' => count($orders),
                    'total_entries' => $totalEntries
                ]
            ], now()->addHour());

            // Log first page completion
            Log::info("Completed first page sync 1/{$totalPages}", [
                'created' => $statsFirstPage['created'],
                'updated' => $statsFirstPage['updated'],
                'has_more_pages' => $hasMorePages
            ]);

            // Continue if more pages exist
            $nextPage = $hasMorePages ? 2 : null;

            return response()->json([
                'success' => true,
                'message' => "Đã đồng bộ trang 1/{$totalPages}. Tổng cộng {$totalEntries} đơn hàng.",
                'stats' => [
                    'created' => $statsFirstPage['created'],
                    'updated' => $statsFirstPage['updated'],
                    'skipped' => $statsFirstPage['skipped'],
                    'errors' => array_slice($statsFirstPage['errors'], 0, 10), // Limit errors
                    'errors_count' => count($statsFirstPage['errors']),
                    'current_page' => 1,
                    'total_pages' => $totalPages
                ],
                'continue' => $hasMorePages,
                'next_page' => $nextPage,
                'total_entries' => $totalEntries,
                'sync_info' => $cacheKey
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing Pancake orders', [
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
     * Set product fields safely based on available database columns
     *
     * @param OrderItem $orderItem
     * @param array $itemData
     * @return OrderItem
     */
    private function setOrderItemFields(OrderItem $orderItem, array $itemData): OrderItem
    {

        // Process component structure if it exists (new API format)
        if (!empty($itemData['components']) && is_array($itemData['components'])) {
            foreach ($itemData['components'] as $component) {
                if (!empty($component['variation_info'])) {
                    $variationInfo = $component['variation_info'];

                    // Get product name from variation_info
                    $orderItem->product_name = $component['name'] ?? $itemData['name'] ?? 'Unknown Product';

                    // Get product code from variation_id
                    $orderItem->code = $component['variation_id'] ?? $itemData['code'] ?? null;

                    // Get price from variation_info
                    $orderItem->price = $component['retail_price'] ?? $itemData['price'] ?? 0;

                    // Get quantity from component
                    $orderItem->quantity = $component['quantity'] ?? $itemData['quantity'] ?? 1;

                    // Store component_id and variation_id if columns exist
                    if (Schema::hasColumn('order_items', 'pancake_component_id')) {
                        $orderItem->pancake_component_id = $component['component_id'] ?? null;
                    }

                    if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                        $orderItem->pancake_variant_id = $component['variation_id'] ?? null;
                    }

                    if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                        $orderItem->pancake_product_id = $variationInfo['product_id'] ?? null;
                    }

                    // Store variation details
                    if (Schema::hasColumn('order_items', 'variation_details')) {
                        $orderItem->variation_details = $variationInfo['detail'] ?? null;
                    }

                    // Store the full variation_info in product_info
                    $orderItem->product_info = array_merge($itemData, [
                        'processed_component' => $component,
                        'processed_variation_info' => $variationInfo
                    ]);

                    // Only process the first component for now
                    // If multiple components needed, would need to create multiple OrderItems or handle differently
                    break;
                }
            }
        }
        // Handle direct variation_info format without components (new Pancake format)
        else if (!empty($itemData['variation_info'])) {
            $variationInfo = $itemData['variation_info'];

            // Get product name from variation_info
            $orderItem->product_name = $variationInfo['name'] ?? $itemData['name'] ?? 'Unknown Product';

            // Get product code from various possible sources
            $orderItem->code = $variationInfo['display_id'] ?? $variationInfo['barcode'] ?? $itemData['variation_id'] ?? null;

            // Set price from variation_info
            $orderItem->price = $variationInfo['retail_price'] ?? $itemData['price'] ?? 0;

            // Set quantity
            $orderItem->quantity = $itemData['quantity'] ?? 1;

            // Set weight if available
            $orderItem->weight = $variationInfo['weight'] ?? $itemData['weight'] ?? 0;

            // Set name field
            $orderItem->name = $variationInfo['name'] ?? $itemData['name'] ?? null;

            // Store IDs if columns exist
            if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $orderItem->pancake_variant_id = $itemData['variation_id'] ?? $itemData['id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                $orderItem->pancake_product_id = $itemData['product_id'] ?? null;
            }

            // Set barcode if column exists
            if (Schema::hasColumn('order_items', 'barcode')) {
                $orderItem->barcode = $variationInfo['barcode'] ?? null;
            }

            // Store variation details
            if (Schema::hasColumn('order_items', 'variation_details')) {
                $orderItem->variation_details = $variationInfo['detail'] ?? null;
            }

            // Store the complete item data in product_info field
            $orderItem->product_info = $itemData;
        }
        else {
            // Handle traditional item format or direct format without variation_info or components

            // Set product name
            $orderItem->product_name = $itemData['name'] ?? 'Unknown Product';

            // Set product code/sku based on available columns
            if (Schema::hasColumn('order_items', 'product_code')) {
                $orderItem->product_code = $itemData['sku'] ?? $itemData['variation_id'] ?? null;
            } else if (Schema::hasColumn('order_items', 'sku')) {
                $orderItem->sku = $itemData['sku'] ?? $itemData['variation_id'] ?? null;
            } else if (Schema::hasColumn('order_items', 'code')) {
                $orderItem->code = $itemData['sku'] ?? $itemData['variation_id'] ?? $itemData['display_id'] ?? null;
            }

            // Set other common fields
            $orderItem->quantity = $itemData['quantity'] ?? 1;
            $orderItem->price = $itemData['price'] ?? 0;
            $orderItem->weight = $itemData['weight'] ?? 0;
            $orderItem->name = $itemData['name'] ?? $itemData['product_name'] ?? null;

            // Store variation and product IDs (important for your data structure)
            if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $orderItem->pancake_variant_id = $itemData['variation_id'] ?? $itemData['variant_id'] ?? $itemData['id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                $orderItem->pancake_product_id = $itemData['product_id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $orderItem->pancake_variation_id = $itemData['variation_id'] ?? $itemData['sku'] ?? null;
            }

            // Handle barcode if available directly in the item data
            if (Schema::hasColumn('order_items', 'barcode')) {
                $orderItem->barcode = $itemData['barcode'] ?? null;
            }

            // Store the complete item data in product_info field
            $orderItem->product_info = $itemData;
        }

        return $orderItem;
    }


    /**
     * Synchronize orders from Pancake by date
     * This method is called from the Artisan command
     *
     * @param \Carbon\Carbon $date
     * @return array
     */
    public function syncOrdersByDate($date)
    {
        // Increase execution time limit to 2 hours and memory limit to 1GB
        set_time_limit(7200);
        ini_set('memory_limit', '1024M');

        // Create a request with the date to reuse the existing method
        $request = new \Illuminate\Http\Request();
        $request->merge(['date' => $date->format('Y-m-d')]);

        try {
            // Call the existing implementation

            $result = $this->syncOrdersByDateManual($request);

            // Convert response to array format expected by the command
            if ($result->getStatusCode() === 200) {
                $data = json_decode($result->getContent(), true);
                return $data;
            } else {
                return [
                    'success' => false,
                    'message' => 'Đồng bộ thất bại với mã lỗi: ' . $result->getStatusCode(),
                    'total_synced' => 0,
                    'new_orders' => 0,
                    'updated_orders' => 0
                ];
            }
        } catch (\Exception $e) {
            Log::error('Lỗi trong quá trình đồng bộ đơn hàng theo ngày', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi: ' . $e->getMessage(),
                'total_synced' => 0,
                'new_orders' => 0,
                'updated_orders' => 0
            ];
        }
    }

    /**
     * Map Pancake status to internal status
     *
     * @param string|int $pancakeStatus
     * @return string
     */
    private function mapPancakeStatus($pancakeStatus): string
    {
        // Map numeric status codes or string status values from Pancake
        $statusMap = [
            // Numeric status codes
            '1' => 'Chờ xác nhận',
            '2' => 'Chờ lấy hàng',
            '3' => 'Đã giao vận chuyển',
            '4' => 'Đã nhận hàng',
            '5' => 'Đã huỷ',
            '6' => 'Hoàn hàng',

            // String status values - lowercase for case insensitive comparison
            'moi' => 'Chờ xác nhận',
            'xac_nhan' => 'Chờ lấy hàng',
            'dang_van_chuyen' => 'Đã giao vận chuyển',
            'da_giao_hang' => 'Đã nhận hàng',
            'huy' => 'Đã huỷ',
            'hoan' => 'Hoàn hàng',

            // Additional known text statuses
            'mới' => 'Chờ xác nhận',
            'chờ xác nhận' => 'Chờ xác nhận',
            'đã xác nhận' => 'Chờ lấy hàng',
            'vận chuyển' => 'Đã giao vận chuyển',
            'đang vận chuyển' => 'Đã giao vận chuyển',
            'đã giao hàng' => 'Đã nhận hàng',
            'hoàn thành' => 'Đã nhận hàng',
            'hoàn tất' => 'Đã nhận hàng',
            'đã huỷ' => 'Đã huỷ',
            'huỷ' => 'Đã huỷ',
            'hoàn hàng' => 'Hoàn hàng'
        ];

        // Convert status to string and lowercase for consistency
        $status = is_string($pancakeStatus) ? strtolower($pancakeStatus) : (string)$pancakeStatus;

        // Return mapped status if found, otherwise default to "Chờ xác nhận"
        return $statusMap[$status] ?? 'Chờ xác nhận';
    }

    /**
     * Create a new order from Pancake data - alias method for createOrderFromPancakeData
     *
     * @param array $orderData Order data from Pancake API
     * @return \App\Models\Order|null
     */
    protected function createOrderFromPancake(array $orderData)
    {
        return $this->createOrderFromPancakeData($orderData);
    }


public function syncCategories(Request $request)
    {
        try {
            // Increase execution time limit and memory limit
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');

            $this->authorize('pancake.sync.categories'); // Updated permission

            // Get API configuration
            $apiKey = WebsiteSetting::where('key', 'pancake_api_key')->first()->value ?? config('pancake.api_key');
            $shopId = WebsiteSetting::where('key', 'pancake_shop_id')->first()->value ?? config('pancake.shop_id');
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

            $url = "{$baseUrl}/shops/{$shopId}/categories";

            Log::info('Attempting to sync Pancake categories.', ['url' => $url, 'shop_id' => $shopId]);

            $response = Http::timeout(120)->get($url, ['api_key' => $apiKey]);

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
            $topLevelCategories = [];

            // Determine the actual array of categories from the response
            if (isset($categoriesData['data']) && is_array($categoriesData['data'])) {
                $topLevelCategories = $categoriesData['data'];
            } elseif (is_array($categoriesData)) {
                // Check if the root itself is an array of category objects
                // A simple check could be if the first element has an 'id' and ('text' or 'name')
                if (!empty($categoriesData) && isset($categoriesData[0]['id']) && (isset($categoriesData[0]['text']) || isset($categoriesData[0]['name']))) {
                     $topLevelCategories = $categoriesData;
                } else {
                     Log::warning('Pancake categories API response is an array but not in the expected format of category objects.', ['response_sample' => array_slice($categoriesData, 0, 1)]);
                }
            } else {
                Log::error('Unexpected Pancake categories API response structure. Not an array or recognized object.', ['response_type' => gettype($categoriesData)]);
            }

            if (empty($topLevelCategories)) {
                 Log::info('No top-level categories found or unexpected API response structure.', ['raw_response' => $categoriesData]);
                 return response()->json([
                    'success' => true, // Still a success, just no data
                    'message' => 'Không tìm thấy danh mục nào từ Pancake hoặc định dạng API không đúng.',
                    'stats' => $stats
                ]);
            }

            DB::beginTransaction();
            try {
                foreach ($topLevelCategories as $categoryData) {
                    if(is_array($categoryData)) {
                        $this->processPancakeCategoryRecursive($categoryData, null, $stats);
                    } else {
                        Log::warning('Top level category data is not an array, skipping.', ['category_data' => $categoryData]);
                        $stats['errors']++;
                        $stats['error_messages'][] = 'Dữ liệu danh mục cấp cao không hợp lệ.';
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saving Pancake categories to database.', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi khi lưu danh mục vào cơ sở dữ liệu: ' . $e->getMessage()
                ], 500);
            }

            Log::info('Pancake categories synchronized successfully.', ['stats' => $stats]);

            return response()->json([
                'success' => true,
                'message' => "Đồng bộ danh mục hoàn tất. Tạo mới: {$stats['created']}, Cập nhật: {$stats['updated']}, Lỗi: {$stats['errors']}",
                'stats' => $stats
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized attempt to sync Pancake categories.', ['user_id' => Auth::id()]);
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.'], 403);
        } catch (\Exception $e) {
            Log::error('Error syncing Pancake categories.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi trong quá trình đồng bộ danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursively processes a Pancake category and its children.
     *
     * @param array $categoryData The category data from Pancake API.
     * @param string|null $parentIdPancake The Pancake ID of the parent category.
     * @param array &$stats Statistics array to be updated.
     * @return void
     */
    private function processPancakeCategoryRecursive(array $categoryData, ?string $parentIdPancake, array &$stats)
    {
        if (empty($categoryData['id'])) {
            Log::warning('Skipping category due to missing ID.', ['category_data_name' => $categoryData['text'] ?? 'N/A', 'parent_id' => $parentIdPancake]);
            $stats['errors']++;
            $stats['error_messages'][] = 'Bỏ qua danh mục do thiếu ID: ' . ($categoryData['text'] ?? 'Không có tên');
            return;
        }

        $pancakeId = (string)$categoryData['id'];
        $name = $categoryData['text'] ?? ($categoryData['name'] ?? 'N/A'); // 'text' or 'name' for category name

        $pancakeCategory = PancakeCategory::updateOrCreate(
            ['pancake_id' => $pancakeId],
            [
                'name' => $name,
                'pancake_parent_id' => $parentIdPancake,
                'level' => $categoryData['level'] ?? null,
                'status' => $categoryData['status'] ?? null,
                'description' => $categoryData['description'] ?? null,
                'image_url' => $categoryData['image_url'] ?? null,
                'api_response' => $categoryData,
            ]
        );

        $stats['total_fetched']++;

        if ($pancakeCategory->wasRecentlyCreated) {
            $stats['created']++;
        } else {
            if ($pancakeCategory->wasChanged()) {
                $stats['updated']++;
            }
        }

        if (!empty($categoryData['nodes']) && is_array($categoryData['nodes'])) {
            foreach ($categoryData['nodes'] as $childNode) {
                if (is_array($childNode)) {
                     $this->processPancakeCategoryRecursive($childNode, $pancakeId, $stats);
                } else {
                    Log::warning('Child node is not an array, skipping.', ['parent_id' => $pancakeId, 'child_node_type' => gettype($childNode)]);
                    $stats['errors']++;
                    $stats['error_messages'][] = 'Dữ liệu nút con không hợp lệ cho danh mục: ' . $name . ' (ID: ' . $pancakeId . ')';
                }
            }
        }
    }

    /**
     * Parse live session information from order notes
     *
     * @param string|null $notes
     * @return array|null
     */
    private function parseLiveSessionInfo(?string $notes): ?array
    {
        if (empty($notes)) {
            return null;
        }

        // Pattern to match "LIVE X DD/MM" or "LIVE X DD/MM/YY" or "LIVE X DD/MM/YYYY"
        $pattern = '/LIVE\s*(\d+)\s*(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/i';

        if (preg_match($pattern, $notes, $matches)) {
            $liveNumber = $matches[1];
            $day = $matches[2];
            $month = $matches[3];
            $year = isset($matches[4]) ? $matches[4] : null;

            // If year is not provided or is 2-digit
            if (!$year) {
                $year = date('Y');
            } elseif (strlen($year) == 2) {
                $year = '20' . $year;
            }

            // Validate date
            if (checkdate($month, $day, (int)$year)) {
                return [
                    'live_number' => $liveNumber,
                    'session_date' => sprintf('%s-%02d-%02d', $year, $month, $day),
                    'original_text' => trim($matches[0])
                ];
            }
        }

        return null;
    }


    // =========== ADDED METHODS START HERE ===========

    /**
     * Push a specific order to Pancake.
     * This method will be called by OrderController.
     *
     * @param Order $order The order to push.
     * @return array ['success' => bool, 'message' => string, 'data' => mixed (optional)]
     */
    public function pushOrderToPancake(Order $order): array
    {
        try {

            $data = $this->prepareDataForPancake($order);
            if($order->pancake_order_id){
                return $this->pancakeApiService->updateOrderOnPancake($order->pancake_order_id, $data);
            }else {
            // Call Pancake API to create order
            $response = $this->pancakeApiService->createOrderOnPancake($data);
            }

            if (!empty($response['success'])) {
                // Update order status
                $order->pancake_order_id = $response['data']['id'] ?? null;
                $order->status = 'pushed_to_pancake';
                // Không ghi đè notes nữa
                $order->saveQuietly();

                return $response;
            }

            // Log the error but return the original response
            error_log('Pancake API Error: ' . ($response['message'] ?? 'Unknown error'));

            // Update order status
            $order->status = 'push_failed';
            $order->notes = $response['message'] ?? 'Unknown error from Pancake API';
            $order->saveQuietly();

            // Return the original response from Pancake
            return $response;

        } catch (\Exception $e) {
            error_log('Pancake Push Error: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());

            $order->status = 'push_failed';
            $order->notes = $e->getMessage();
            $order->saveQuietly();

                return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Helper method to prepare order data for Pancake API.
     * You might need to adjust this based on your Order model structure and Pancake API requirements.
     */
    protected function prepareDataForPancake(Order $order): array
    {
        try {
            // Get and validate products data
            if (empty($order->products_data)) {
                throw new \Exception('Không tìm thấy dữ liệu sản phẩm trong đơn hàng');
            }

            $productsData = json_decode($order->products_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Dữ liệu sản phẩm không đúng định dạng JSON: ' . json_last_error_msg());
            }

            error_log('Raw products data: ' . print_r($productsData, true));

            // Format items array according to Pancake structure
            $items = [];
            foreach ($productsData as $product) {

                if (empty($product['variation_id'])) {
                    error_log('Product without variation_id: ' . print_r($product, true));
                    throw new \Exception('Thiếu variation_id của sản phẩm');
                }

                $items[] = [
                    'variation_id' => $product['variation_id'],
                    'quantity' => $product['quantity'],
                    'variation_info' => [
                        'retail_price' => $product['variation_info']['retail_price']
                    ]
                ];
            }

            if (empty($items)) {
                throw new \Exception('Không có sản phẩm nào trong đơn hàng');
            }
          $assigning_care_id = User::where('id', $order->assigning_care_id)->pluck('pancake_uuid')->first();
            error_log('Formatted items: ' . print_r($items, true));
            $warehouse = Warehouse::find($order->warehouse_id)->pluck('pancake_id')->first();
            $page_id = PancakePage::where('id', $order->pancake_page_id)->pluck('pancake_page_id')->first();
            // Build the complete order data structure
            $orderData = [
                'assigning_seller_id' => $order->assigning_seller_id,
                'assigning_care_id' =>  $assigning_care_id,
                'warehouse_id' => $warehouse ?? "",
                'bill_phone_number' => $order->bill_phone_number ?? "",
                'bill_full_name' => $order->bill_full_name ?? "",
                'shipping_fee' => (float)($order->shipping_fee ?? 0),
                'note' => $order->notes ?? "",
                'note_print' => "",
                'transfer_money' => (float)($order->transfer_money ?? 0),
                'partner' => [
                    'partner_id' => $order->pancake_shipping_provider_id ?? "3",
                ],
                'shipping_address' => [
                    'address' => $order->street_address ?? "",
                    'commune_id' => $order->ward_code ?? "",
                    'country_code' => "84",
                    'district_id' => $order->district_code ?? "101",
                    'full_address' => $order->full_address ?? "",
                    'full_name' => $order->bill_full_name ?? "",
                    'phone_number' => $order->bill_phone_number ?? "",
                    'post_code' => null,
                    'province_id' => $order->province_code ?? ""
                ],
                'third_party' => [
                    'custom_information' => new \stdClass()
                ],
                'order_sources' => $order->source,
                'page_id' => $page_id,
                'account' => $page_id,
                'items' => $items
            ];

            error_log('Final order data: ' . json_encode($orderData, JSON_PRETTY_PRINT));

            return $orderData;
        } catch (\Exception $e) {
            error_log('Error preparing data: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Maps CRM order status (string) to Pancake expected numeric status values.
     * This method is updated to return integer status codes.
     */
    protected function mapCrmStatusToPancakeNumeric(string $crmStatus): int
    {
        $crmStatusLower = strtolower(trim($crmStatus));
        // Based on Pancake API Docs: Trạng thái đơn hàng
        // | Mã trạng thái | Mô tả        |
        // | ------------- | --------------- |
        // | 0             | Mới          |
        // | 1             | Đã xác nhận  | (Corresponds to Chờ lấy hàng in CRM)
        // | 2             | Đã gửi hàng  | (Corresponds to Đã giao vận chuyển in CRM)
        // | 3             | Đã nhận      | (Corresponds to Đã nhận hàng in CRM)
        // | 5             | Đã hoàn      | (Corresponds to Hoàn hàng in CRM) - Verify if 'Hoàn hàng' means fully returned or partially.
        // | 6             | Đã hủy       |
        // | 7             | Đã xóa       |
        // | 8             | Đang đóng hàng|
        // | 9             | Chờ chuyển hàng|
        // | 10            | Đơn Webcake  |
        // | 11            | Chờ hàng     |
        // | 12            | Chờ in       |
        // | 13            | Đã in        |
        // | 17            | Chờ xác nhận | (Could also be 0 for 'Mới')

        $mapping = [
            'new' => 0,
            'mới' => 0,
            'chờ xác nhận' => 0, // Default to 0 if "Chờ xác nhận" in CRM is truly a "New" order for Pancake
            // 'chờ xác nhận' => 17, // Alternative if 17 is preferred for CRM's "Chờ xác nhận"

            'processing' => 1, // Example: 'processing' in CRM -> 'Đã xác nhận' (1) in Pancake
            'đã xác nhận' => 1, // CRM "Đã xác nhận" (nghĩa là chờ lấy hàng) -> Pancake "Đã xác nhận" (1)
            'chờ lấy hàng' => 1, // CRM "Chờ lấy hàng" -> Pancake "Đã xác nhận" (1)

            'shipping' => 2,
            'đã gửi hàng' => 2,
            'đang giao hàng' => 2, // Map "Đang giao hàng" in CRM to Pancake's "Đã gửi hàng"
            'đã giao vận chuyển' => 2,


            'completed' => 3,
            'đã nhận' => 3,
            'đã nhận hàng' => 3,
            'giao thành công' => 3,

            'cancelled' => 6,
            'đã hủy' => 6,
            'huy' => 6,

            'returned' => 5,
            'đã hoàn' => 5,
            'hoàn hàng' => 5, // CRM "Hoàn hàng" -> Pancake "Đã hoàn" (5)

            // Add other specific CRM statuses if needed
            'chờ hàng' => 11,
            'chờ in' => 12,
            'đã in' => 13,
            'đang đóng hàng' => 8,
            'chờ chuyển hàng' => 9,
        ];
        // Default to 0 (Mới) if no mapping is found or status is unclear
        return $mapping[$crmStatusLower] ?? 0;
    }
    // =========== ADDED METHODS END HERE =========== // This comment was from previous edit, will be on its own line now

    /**
     * Đồng bộ nguồn đơn từ Pancake
     */
    public function syncOrderSources()
    {
        try {
            $response = $this->pancakeApiService->get('/order-sources');

            if (!isset($response['success']) || !$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể lấy dữ liệu nguồn đơn từ Pancake: ' . ($response['message'] ?? 'Unknown error')
                ], 400);
            }

            $sources = $response['data'] ?? [];
            $syncCount = 0;

            foreach ($sources as $source) {
                PancakeOrderSource::updateOrCreate(
                    ['pancake_id' => $source['id']],
                    [
                        'name' => $source['name'],
                        'platform' => $source['platform'] ?? null,
                        'is_active' => true,
                        'raw_data' => $source
                    ]
                );
                $syncCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Đã đồng bộ thành công {$syncCount} nguồn đơn từ Pancake"
            ]);

        } catch (\Exception $e) {
            \Log::error('Error syncing Pancake order sources: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ nguồn đơn: ' . $e->getMessage()
            ], 500);
        }
    }

                    }
