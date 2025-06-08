<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PancakeSyncController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ShippingProvider;
use App\Models\PancakeShop;
use App\Models\PancakePage;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\LiveSessionRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductVariant;
use App\Models\PancakeWebhookLog;

class PancakeWebhookController extends Controller
{
    protected $pancakeSyncController;

    public function __construct(PancakeSyncController $pancakeSyncController)
    {
        $this->pancakeSyncController = $pancakeSyncController;
    }

    /**
     * Handle all incoming webhooks from Pancake (orders, customers, inventory, warehouse)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // Log the incoming webhook
        Log::info('Received Pancake webhook', [
            'data' => $request->all()
        ]);

        try {
            DB::beginTransaction();

            $webhookData = $request->all();

            // Format data theo cấu trúc của PancakeSyncController
            $formattedData = $this->formatWebhookData($webhookData);

            // Check if order exists by pancake_order_id
            $existingOrder = Order::where('pancake_order_id', $webhookData['id'])->first();

                if ($existingOrder) {
                $order = $this->updateOrderFromPancake($existingOrder, $formattedData);
                $message = 'Order updated successfully';
                } else {
                $order = $this->createOrderFromPancake($formattedData);
                $message = 'Order created successfully';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing Pancake webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format webhook data to match PancakeSyncController structure
     */
    private function formatWebhookData(array $webhookData): array
    {
        // Format data according to PancakeSyncController structure
        $formattedData = [
            'id' => $webhookData['id'], // Sử dụng id trực tiếp làm pancake_order_id
            'code' => $webhookData['id'], // Sử dụng id làm code
            'status' => $webhookData['status'] ?? 0,
            'status_name' => $webhookData['status_name'] ?? '',
            'order_sources' => $webhookData['order_sources'] ?? '-1',
            'order_sources_name' => $webhookData['order_sources_name'] ?? '',
            'warehouse_id' => $webhookData['warehouse_id'] ?? null,
            'page_id' => $webhookData['page_id'] ?? null,
            'total_price' => $webhookData['total_price'] ?? 0,
            'shipping_fee' => $webhookData['shipping_fee'] ?? 0,
            'total_quantity' => $webhookData['total_quantity'] ?? 0,
            'note' => $webhookData['note'] ?? '',
            'transfer_money' => $webhookData['transfer_money'] ?? 0,
            'prepaid' => $webhookData['prepaid'] ?? 0,
            'cod' => $webhookData['cod'] ?? 0,
            'items' => [],
            'customer' => [
                'id' => $webhookData['customer']['id'] ?? null,
                'name' => $webhookData['customer']['name'] ?? $webhookData['bill_full_name'] ?? '',
                'phone' => $webhookData['customer']['phone_numbers'][0] ?? $webhookData['bill_phone_number'] ?? null,
                'email' => $webhookData['customer']['emails'][0] ?? null,
                'gender' => $webhookData['customer']['gender'] ?? null,
                'fb_id' => $webhookData['customer']['fb_id'] ?? null
            ],
            'shipping_address' => [
                'full_name' => $webhookData['shipping_address']['full_name'] ?? $webhookData['bill_full_name'] ?? '',
                'phone_number' => $webhookData['shipping_address']['phone_number'] ?? $webhookData['bill_phone_number'] ?? '',
                'address' => $webhookData['shipping_address']['address'] ?? '',
                'province_id' => $webhookData['shipping_address']['province_id'] ?? null,
                'district_id' => $webhookData['shipping_address']['district_id'] ?? null,
                'commune_id' => $webhookData['shipping_address']['commune_id'] ?? null,
                'full_address' => $webhookData['shipping_address']['full_address'] ?? ''
            ],
            // Thêm thông tin nhân viên seller và care
            'assigning_seller' => [
                'id' => $webhookData['assigning_seller']['id'] ?? null,
                'email' => $webhookData['assigning_seller']['email'] ?? null,
                'fb_id' => $webhookData['assigning_seller']['fb_id'] ?? null,
                'name' => $webhookData['assigning_seller']['name'] ?? null,
                'phone_number' => $webhookData['assigning_seller']['phone_number'] ?? null,
                'avatar_url' => $webhookData['assigning_seller']['avatar_url'] ?? null
            ],
            'assigning_care' => [
                'id' => $webhookData['assigning_care']['id'] ?? null,
                'email' => $webhookData['assigning_care']['email'] ?? null,
                'fb_id' => $webhookData['assigning_care']['fb_id'] ?? null,
                'name' => $webhookData['assigning_care']['name'] ?? null,
                'phone_number' => $webhookData['assigning_care']['phone_number'] ?? null,
                'avatar_url' => $webhookData['assigning_care']['avatar_url'] ?? null
            ]
        ];

        // Format items data
        if (!empty($webhookData['items'])) {
            $formattedData['items'] = array_map(function($item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'variation_info' => [
                        'name' => $item['variation_info']['name'] ?? '',
                        'retail_price' => $item['variation_info']['retail_price'] ?? 0,
                        'barcode' => $item['variation_info']['barcode'] ?? null,
                        'weight' => $item['variation_info']['weight'] ?? 0
                    ]
                ];
            }, $webhookData['items']);
        }

        // Add partner info if exists
        if (!empty($webhookData['partner'])) {
            $formattedData['partner'] = [
                'partner_id' => $webhookData['partner']['partner_id'] ?? null,
                'partner_name' => $webhookData['partner']['partner_name'] ?? null
            ];
        }

        // Add warehouse info if exists
        if (!empty($webhookData['warehouse_info'])) {
            $formattedData['warehouse_info'] = $webhookData['warehouse_info'];
        }

        return $formattedData;
    }

    /**
     * Process live session revenue from order notes
     *
     * @param Order $order
     * @param string $notes
     * @return void
     */
    private function processLiveSessionRevenue(Order $order, string $notes)
    {
        // Parse live session info from notes
        $sessionInfo = $this->parseLiveSessionInfo($notes);

        if ($sessionInfo) {
            try {
                // Create or update live session revenue
                LiveSessionRevenue::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'live_session_id' => $sessionInfo['session_id']
                    ],
                    [
                        'revenue' => $order->total_value,
                        'session_date' => $sessionInfo['date'],
                        'session_name' => $sessionInfo['name'],
                        'customer_id' => $order->customer_id,
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'order_code' => $order->order_code,
                        'order_status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'shipping_fee' => $order->shipping_fee,
                        'total_amount' => $order->total_value,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                Log::info('Live session revenue processed', [
                    'order_id' => $order->id,
                    'session_id' => $sessionInfo['session_id']
                ]);
            } catch (\Exception $e) {
                Log::error('Error processing live session revenue', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
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

        // Pattern for live session info in notes
        $pattern = '/Live\s+(\d{1,2}\/\d{1,2}\/\d{4})\s*-?\s*(.+?)(?:\s*#(\d+)|$)/i';

        if (preg_match($pattern, $notes, $matches)) {
            return [
                'date' => date('Y-m-d', strtotime(str_replace('/', '-', $matches[1]))),
                'name' => trim($matches[2]),
                'session_id' => $matches[3] ?? null
            ];
        }

        return null;
    }

    /**
     * Create a new order from Pancake data
     */
    protected function createOrderFromPancake(array $orderData)
    {
        try {
            DB::beginTransaction();

            // Create customer first
            $customer = $this->findOrCreateCustomer([
                'name' => $orderData['bill_full_name'] ?? $orderData['customer']['name'] ?? null,
                'phone' => $orderData['bill_phone_number'] ?? $orderData['customer']['phone'] ?? null,
                'email' => $orderData['customer']['email'] ?? null,
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'id' => $orderData['customer']['id'] ?? null,
                'code' => $orderData['customer']['code'] ?? null
            ]);

            // Create new order
            $order = new Order();

            // Map basic info
            $order->pancake_order_id = $orderData['id'];
            $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));

            // Set source from order_sources
            $order->source = $orderData['order_sources'] ?? -1;

            // Get page info
            if (!empty($orderData['page_id'])) {
                $page = PancakePage::where('pancake_id', $orderData['page_id'])->first();
                if ($page) {
                    $order->page_name = $page->name;
                }
            }

            // Map warehouse info
            if (!empty($orderData['warehouse_id'])) {
                $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

                if (!$warehouse) {
                    // Thử tìm theo code
                    $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
                }

                // Nếu không tìm thấy và có thông tin kho, tạo kho mới
                if (!$warehouse && !empty($orderData['warehouse_name'])) {
                    $warehouse = new Warehouse();
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
            $order->pancake_page_id = $orderData['page_id'] ?? null;

            // Map customer info
            $order->customer_id = $customer->id;
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->phone;
            $order->customer_email = $customer->email;

            // Map shipping info
            if (!empty($orderData['shipping_address'])) {
                $shipping = $orderData['shipping_address'];

                $addressParts = [];
                if (!empty($shipping['address'])) {
                    $addressParts[] = $shipping['address'];
                }
                if (!empty($shipping['ward_name'])) $addressParts[] = $shipping['ward_name'];
                if (!empty($shipping['district_name'])) $addressParts[] = $shipping['district_name'];
                if (!empty($shipping['province_name'])) $addressParts[] = $shipping['province_name'];

                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($shipping['full_address'] ?? '');
                $order->province_code = $shipping['province_id'] ?? null;
                $order->district_code = $shipping['district_id'] ?? null;
                $order->ward_code = $shipping['ward_id'] ?? null;
                $order->street_address = $shipping['address'] ?? '';

                // Update related names if available
                $order->province_name = $shipping['province_name'] ?? null;
                $order->district_name = $shipping['district_name'] ?? null;
                $order->ward_name = $shipping['ward_name'] ?? null;

                // Cập nhật địa chỉ cho khách hàng
                if ($customer) {
                    $customer->full_address = $order->full_address;
                    $customer->province = $order->province_code;
                    $customer->district = $order->district_code;
                    $customer->ward = $order->ward_code;
                    $customer->street_address = $order->street_address;
                    $customer->save();
                }
            }

            // Map shipping provider
            if (!empty($orderData['partner']['partner_id'])) {
                $providerId = $orderData['partner']['partner_id'];
                $provider = ShippingProvider::where('pancake_id', $providerId)
                    ->orWhere('pancake_partner_id', $providerId)
                    ->first();

                // Nếu không tìm thấy và có tên đơn vị vận chuyển, tạo mới
                if (!$provider && !empty($orderData['shipping_provider_name'])) {
                    $provider = new ShippingProvider();
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

            // Map financial info
            $order->shipping_fee = (float)($orderData['shipping_fee'] ?? 0);
            $order->transfer_money = (float)($orderData['transfer_money'] ?? 0);
            $order->total_value = $this->calculateOrderTotal($orderData);

            // Map notes
            $order->notes = $orderData['note'] ?? null;
            $order->additional_notes = $orderData['additional_notes'] ?? null;

            // Process live session if notes contain live session info
            if ($order->notes) {
                $this->processLiveSessionRevenue($order, $order->notes);
            }

            // Map status
            $order->status = $this->mapPancakeStatus($orderData['status'] ?? 'new');

            // Save products data
            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            // Lưu thông tin nhân viên seller và care
            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? null;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? null;

            // Save order
            $order->save();

            // Cập nhật thông tin khách hàng
            if ($customer) {
                // Cập nhật số đơn hàng
                $customer->total_orders_count = Order::where('customer_id', $customer->id)->count();

                // Cập nhật tổng chi tiêu - chỉ tính các đơn đã nhận (pancake_status = 3)
                $customer->total_spent = Order::where('customer_id', $customer->id)
                    ->where('pancake_status', 3)
                    ->sum('total_value');

                // Cập nhật số đơn thành công (đã nhận)
                $customer->succeeded_order_count = Order::where('customer_id', $customer->id)
                    ->where('pancake_status', 3)
                    ->count();

                // Cập nhật số đơn trả hàng (status_code = 4 hoặc 5 hoặc 15)
                $customer->returned_order_count = Order::where('customer_id', $customer->id)
                    ->whereIn('pancake_status', [4, 5, 15])
                    ->count();

                // Cập nhật thông tin từ Pancake nếu có
                if (!empty($orderData['customer'])) {
                    $customerData = $orderData['customer'];

                    // Cập nhật thông tin cơ bản
                    $customer->gender = $customerData['gender'] ?? $customer->gender;
                    $customer->date_of_birth = $customerData['date_of_birth'] ?? $customer->date_of_birth;
                    $customer->fb_id = $customerData['fb_id'] ?? $customer->fb_id;

                    // Xử lý nhiều số điện thoại
                    if (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                        if (Schema::hasColumn('customers', 'phone_numbers')) {
                            $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                        }
                    }

                    // Xử lý nhiều email
                    if (!empty($customerData['emails']) && is_array($customerData['emails'])) {
                        if (Schema::hasColumn('customers', 'emails')) {
                            $customer->emails = json_encode($customerData['emails']);
                        }
                    }

                    // Cập nhật tags
                    if (Schema::hasColumn('customers', 'tags') && !empty($customerData['tags'])) {
                        $customer->tags = json_encode($customerData['tags']);
                    }

                    // Cập nhật conversation tags
                    if (Schema::hasColumn('customers', 'conversation_tags') && !empty($customerData['conversation_tags'])) {
                        $customer->conversation_tags = json_encode($customerData['conversation_tags']);
                    }

                    // Cập nhật điểm thưởng
                    if (Schema::hasColumn('customers', 'reward_points')) {
                        $customer->reward_points = $customerData['reward_point'] ?? $customer->reward_points;
                    }

                    // Cập nhật danh sách địa chỉ
                    if (Schema::hasColumn('customers', 'addresses') && !empty($customerData['shop_customer_addresses'])) {
                        $customer->addresses = json_encode($customerData['shop_customer_addresses']);
                    }
                }

                $customer->save();

                Log::info('Đã cập nhật thông tin khách hàng', [
                    'customer_id' => $customer->id,
                    'total_orders' => $customer->total_orders_count,
                    'total_spent' => $customer->total_spent,
                    'succeeded_orders' => $customer->succeeded_order_count,
                    'returned_orders' => $customer->returned_order_count
                ]);
            }

            DB::commit();
            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order from Pancake data', [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            throw $e;
        }
    }

    /**
     * Update existing order with Pancake data
     */
    protected function updateOrderFromPancake(Order $order, array $orderData)
    {
        try {
            DB::beginTransaction();

            // Update customer if needed
            if (!empty($orderData['customer'])) {
                $customer = $this->findOrCreateCustomer($orderData['customer']);
                $order->customer_id = $customer->id;
                $order->customer_name = $orderData['customer']['name'] ?? $customer->name;
                $order->customer_phone = $orderData['customer']['phone'] ?? $customer->phone;
                $order->customer_email = $orderData['customer']['email'] ?? $customer->email;

                // Cập nhật thông tin khách hàng
                if ($customer) {
                    $customerData = $orderData['customer'];

                    // Cập nhật thông tin cơ bản
                    $customer->name = $customerData['name'] ?? $customer->name;
                    $customer->phone = $customerData['phone'] ?? $customer->phone;
                    $customer->email = $customerData['email'] ?? $customer->email;
                    $customer->gender = $customerData['gender'] ?? $customer->gender;
                    $customer->date_of_birth = $customerData['date_of_birth'] ?? $customer->date_of_birth;
                    $customer->fb_id = $customerData['fb_id'] ?? $customer->fb_id;

                    // Xử lý nhiều số điện thoại
                    if (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                        if (Schema::hasColumn('customers', 'phone_numbers')) {
                            $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                        }
                    }

                    // Xử lý nhiều email
                    if (!empty($customerData['emails']) && is_array($customerData['emails'])) {
                        if (Schema::hasColumn('customers', 'emails')) {
                            $customer->emails = json_encode($customerData['emails']);
                        }
                    }

                    // Cập nhật tags
                    if (Schema::hasColumn('customers', 'tags') && !empty($customerData['tags'])) {
                        $customer->tags = json_encode($customerData['tags']);
                    }

                    // Cập nhật conversation tags
                    if (Schema::hasColumn('customers', 'conversation_tags') && !empty($customerData['conversation_tags'])) {
                        $customer->conversation_tags = json_encode($customerData['conversation_tags']);
                    }

                    // Cập nhật điểm thưởng
                    if (Schema::hasColumn('customers', 'reward_points')) {
                        $customer->reward_points = $customerData['reward_point'] ?? $customer->reward_points;
                    }

                    // Cập nhật danh sách địa chỉ
                    if (Schema::hasColumn('customers', 'addresses') && !empty($customerData['shop_customer_addresses'])) {
                        $customer->addresses = json_encode($customerData['shop_customer_addresses']);
                    }

                    // Cập nhật số đơn hàng và doanh thu
                    $customer->total_orders_count = Order::where('customer_id', $customer->id)->count();
                    $customer->total_spent = Order::where('customer_id', $customer->id)
                        ->where('pancake_status', 3)
                        ->sum('total_value');
                    $customer->succeeded_order_count = Order::where('customer_id', $customer->id)
                        ->where('pancake_status', 3)
                        ->count();
                    $customer->returned_order_count = Order::where('customer_id', $customer->id)
                        ->whereIn('pancake_status', [4, 5, 15])
                        ->count();

                    $customer->save();

                    Log::info('Đã cập nhật thông tin khách hàng trong updateOrderFromPancake', [
                        'customer_id' => $customer->id,
                        'total_orders' => $customer->total_orders_count,
                        'total_spent' => $customer->total_spent,
                        'succeeded_orders' => $customer->succeeded_order_count,
                        'returned_orders' => $customer->returned_order_count
                    ]);
                }
            }

            // Update basic order information
            // Set source from order_sources
            if (!empty($orderData['order_sources'])) {
                $order->source = $orderData['order_sources'];
            }

            // Update page info
            if (!empty($orderData['page_id'])) {
                $page = PancakePage::where('pancake_id', $orderData['page_id'])->first();
                if ($page) {
                    $order->page_name = $page->name;
                }
            }

            // Update warehouse info
            if (!empty($orderData['warehouse_id'])) {
                $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

                if (!$warehouse) {
                    // Thử tìm theo code
                    $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
                }

                // Nếu không tìm thấy và có thông tin kho, tạo kho mới
                if (!$warehouse && !empty($orderData['warehouse_name'])) {
                    $warehouse = new Warehouse();
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

            $order->campaign_id = $orderData['campaign_id'] ?? $order->campaign_id;
            $order->campaign_name = $orderData['campaign_name'] ?? $order->campaign_name;

            // Update status and tracking
            if (!empty($orderData['status'])) {
                $order->status = $this->mapPancakeStatus($orderData['status']);
                $order->pancake_status = is_numeric($orderData['status']) ?
                    $orderData['status'] :
                    ($orderData['status_name'] ?? $order->pancake_status);
            }

            // Update shipping info
            if (!empty($orderData['shipping_address'])) {
                $shipping = $orderData['shipping_address'];

                $addressParts = [];
                if (!empty($shipping['address'])) {
                    $addressParts[] = $shipping['address'];
                }
                if (!empty($shipping['ward_name'])) $addressParts[] = $shipping['ward_name'];
                if (!empty($shipping['district_name'])) $addressParts[] = $shipping['district_name'];
                if (!empty($shipping['province_name'])) $addressParts[] = $shipping['province_name'];

                $fullAddress = implode(', ', $addressParts);
                $order->full_address = !empty($fullAddress) ? $fullAddress : ($shipping['full_address'] ?? $order->full_address);
                $order->province_code = $shipping['province_id'] ?? $order->province_code;
                $order->district_code = $shipping['district_id'] ?? $order->district_code;
                $order->ward_code = $shipping['ward_id'] ?? $order->ward_code;
                $order->street_address = $shipping['address'] ?? $order->street_address;

                // Update related names if available
                $order->province_name = $shipping['province_name'] ?? $order->province_name;
                $order->district_name = $shipping['district_name'] ?? $order->district_name;
                $order->ward_name = $shipping['ward_name'] ?? $order->ward_name;
            }

            // Update shipping provider
            if (!empty($orderData['partner']['partner_id'])) {
                $providerId = $orderData['partner']['partner_id'];
                $provider = ShippingProvider::where('pancake_id', $providerId)
                    ->orWhere('pancake_partner_id', $providerId)
                    ->first();

                // Nếu không tìm thấy và có tên đơn vị vận chuyển, tạo mới
                if (!$provider && !empty($orderData['shipping_provider_name'])) {
                    $provider = new ShippingProvider();
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

            // Update financial information
            $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
            $order->transfer_money = $orderData['transfer_money'] ?? $order->transfer_money;
            $order->total_value = $orderData['total_price'] ?? $order->total_value;
            $order->notes = $orderData['note'] ?? $order->notes;

            // Process live session if notes contain live session info
            if ($order->notes) {
                $this->processLiveSessionRevenue($order, $order->notes);
            }

            // Update products data
            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            // Cập nhật thông tin nhân viên seller và care
            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? $order->assigning_seller_id;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? $order->assigning_care_id;

            $order->save();

            DB::commit();
            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order from Pancake', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'data' => $orderData
            ]);
            throw $e;
        }
    }

    /**
     * Find or create warehouse by Pancake ID
     */
    private function findOrCreateWarehouse(?string $warehouseId): ?int
    {
        if (!$warehouseId) {
            return null;
        }

        $warehouse = Warehouse::where('pancake_id', $warehouseId)->first();

        if (!$warehouse) {
            $warehouse = Warehouse::create([
                'pancake_id' => $warehouseId,
                'code' => 'WH-' . $warehouseId,
                'name' => 'Warehouse ' . $warehouseId,
                'status' => true
            ]);
        }

        return $warehouse->id;
    }

    /**
     * Calculate order total from items and fees
     */
    private function calculateOrderTotal(array $orderData): float
    {
        $total = 0;

        // Calculate total from items
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $price = $item['variation_info']['retail_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $total += $price * $quantity;
            }
        }

        // Add shipping fee
        $total += (float)($orderData['shipping_fee'] ?? 0);

        return $total;
    }

    /**
     * Find or create customer from Pancake data
     */
    private function findOrCreateCustomer(array $customerData)
    {
        if (empty($customerData)) {
            return new Customer();
        }

        // Try to find by phone and/or email
        $customer = null;

        $phone = $customerData['phone'] ?? $customerData['bill_phone_number'] ?? null;
        $name = $customerData['name'] ?? $customerData['bill_full_name'] ?? null;
        $email = $customerData['email'] ?? $customerData['bill_email'] ?? null;

        if (!empty($phone)) {
            $customer = Customer::where('phone', $phone)->first();
        }

        if (!$customer && !empty($email)) {
            $customer = Customer::where('email', $email)->first();
        }

        if (!$customer) {
            // Create new customer
            $customer = new Customer();
            $customer->name = $name ?? 'Unknown';
            $customer->phone = $phone;
            $customer->email = $email;
            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->pancake_customer_id = $customerData['code'] ?? null;

            // Map address
            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? null;
                $customer->district = $shipping['district_id'] ?? null;
                $customer->ward = $shipping['commune_id'] ?? null;
                $customer->street_address = $shipping['address'] ?? null;
                $customer->full_address = $shipping['full_address'] ?? null;
            }

            $customer->save();
        } else {
            // Update existing customer
            $customer->pancake_id = $customerData['id'] ?? $customer->pancake_id;
            $customer->pancake_customer_id = $customerData['code'] ?? $customer->pancake_customer_id;

            // Update address if provided
            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? $customer->province;
                $customer->district = $shipping['district_id'] ?? $customer->district;
                $customer->ward = $shipping['commune_id'] ?? $customer->ward;
                $customer->street_address = $shipping['address'] ?? $customer->street_address;
                $customer->full_address = $shipping['full_address'] ?? $customer->full_address;
            }

            $customer->save();
        }

        return $customer;
    }

    /**
     * Map Pancake status to internal status
     */
    private function mapPancakeStatus($pancakeStatus): string
    {
        // If numeric status is provided
        if (is_numeric($pancakeStatus)) {
            return match ((int)$pancakeStatus) {
                0 => 'moi', // Mới
                1 => 'dang_xu_ly', // Đang xử lý
                2 => 'dang_giao_hang', // Đang giao hàng
                3 => 'hoan_thanh', // Hoàn thành
                4 => 'huy', // Hủy
                5 => 'tra_hang', // Trả hàng
                6 => 'cho_lay_hang', // Chờ lấy hàng
                7 => 'da_lay_hang', // Đã lấy hàng
                8 => 'dang_giao', // Đang giao
                9 => 'da_giao', // Đã giao
                10 => 'khong_lay_duoc_hang', // Không lấy được hàng
                11 => 'cho_xac_nhan', // Chờ xác nhận
                12 => 'chuyen_hoan', // Chuyển hoàn
                13 => 'da_chuyen_hoan', // Đã chuyển hoàn
                default => 'moi',
            };
        }

        // If string status is provided
        return match (strtolower((string)$pancakeStatus)) {
            'new' => 'moi',
            'processing' => 'dang_xu_ly',
            'shipping' => 'dang_giao_hang',
            'completed' => 'hoan_thanh',
            'cancelled' => 'huy',
            'returned' => 'tra_hang',
            'waiting_pickup' => 'cho_lay_hang',
            'picked_up' => 'da_lay_hang',
            'delivering' => 'dang_giao',
            'delivered' => 'da_giao',
            'pickup_failed' => 'khong_lay_duoc_hang',
            'waiting' => 'cho_xac_nhan',
            'returning' => 'chuyen_hoan',
            'returned_to_seller' => 'da_chuyen_hoan',
            default => 'moi',
        };
    }

    /**
     * Process inventory/stock updates from Pancake
     *
     * @param array $inventoryData
     * @return void
     */
    private function processInventoryUpdate(array $inventoryData)
    {
        foreach ($inventoryData as $item) {
            try {
                // Find the product variant
                $variant = ProductVariant::where('pancake_variant_id', $item['variation_id'])
                    ->orWhere('pancake_product_id', $item['product_id'])
                    ->first();

                if (!$variant) {
                    // Create new variant if it doesn't exist
                    $variant = new ProductVariant();
                    $variant->pancake_variant_id = $item['variation_id'];
                    $variant->pancake_product_id = $item['product_id'];
                    $variant->name = $item['name'] ?? 'Unknown Product';
                }

                // Update stock and other relevant fields
                $variant->stock = $item['stock'] ?? 0;
                $variant->price = $item['price'] ?? $item['retail_price'] ?? 0;
                $variant->sku = $item['sku'] ?? $item['barcode'] ?? null;

                // Store additional data
                $variant->metadata = array_merge($variant->metadata ?? [], [
                    'last_sync' => now(),
                    'pancake_data' => $item
                ]);

                $variant->save();

                Log::info('Updated product variant inventory', [
                    'variant_id' => $variant->id,
                    'pancake_variant_id' => $variant->pancake_variant_id,
                    'new_stock' => $variant->stock
                ]);

            } catch (\Exception $e) {
                Log::error('Error processing inventory update for item', [
                    'item' => $item,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Process warehouse updates from Pancake
     *
     * @param array $warehouseData
     * @return void
     */
    private function processWarehouseUpdate(array $warehouseData)
    {
        try {
            // Find or create warehouse
            $warehouse = Warehouse::where('pancake_id', $warehouseData['id'])
                ->orWhere('code', $warehouseData['code'])
                ->first();

            if (!$warehouse) {
                $warehouse = new Warehouse();
                $warehouse->pancake_id = $warehouseData['id'];
            }

            // Update warehouse information
            $warehouse->name = $warehouseData['name'];
            $warehouse->code = $warehouseData['code'] ?? $warehouseData['id'];
            $warehouse->description = $warehouseData['description'] ?? null;
            $warehouse->status = $warehouseData['status'] ?? true;

            $warehouse->save();

            Log::info('Updated warehouse from Pancake', [
                'warehouse_id' => $warehouse->id,
                'pancake_id' => $warehouse->pancake_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing warehouse update', [
                'data' => $warehouseData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
