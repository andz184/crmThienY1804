<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    /**
     * Handle all incoming webhooks from Pancake (orders, customers, inventory, warehouse)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // Create webhook log entry
        $webhookLog = new PancakeWebhookLog([
            'event_type' => $request->input('type') . '.' . $request->input('event'),
            'source_ip' => $request->ip(),
            'request_data' => $request->all(),
            'status' => 'processing'
        ]);
        $webhookLog->save();

        // Log the incoming webhook
        Log::info('Received Pancake webhook', [
            'data_size' => strlen($request->getContent()),
            'ip' => $request->ip(),
            'data_type' => $request->input('type'),
            'event' => $request->input('event')
        ]);

        try {
            // Begin transaction
            DB::beginTransaction();

            // Process based on the type of data received
            $results = [];
            $processedData = [];

            // Check for order data
            $orderData = $request->input('data.order');
            if (!empty($orderData)) {
                // Check if order already exists
                $existingOrder = Order::where('pancake_order_id', $orderData['id'] ?? null)->first();

                if ($existingOrder) {
                    // Update existing order
                    $order = $this->updateOrder($existingOrder, $orderData);
                    $results['order'] = 'Đơn hàng đã được cập nhật';
                    $processedData['order'] = [
                        'id' => $order->id,
                        'pancake_order_id' => $order->pancake_order_id,
                        'status' => 'updated'
                    ];
                    $webhookLog->order_id = $order->pancake_order_id;
                } else {
                    // Create new order
                    $order = $this->createOrder($orderData);
                    $results['order'] = 'Đơn hàng đã được tạo';
                    $processedData['order'] = [
                        'id' => $order->id,
                        'pancake_order_id' => $order->pancake_order_id,
                        'status' => 'created'
                    ];
                    $webhookLog->order_id = $order->pancake_order_id;
                }

                // Process live session revenue if notes contain live session info
                if (!empty($orderData['notes'])) {
                    $this->processLiveSessionRevenue($order, $orderData['notes']);
                }
            }

            // Check for customer data
            $customerData = $request->input('data.customer');
            if (!empty($customerData)) {
                // Find or create customer
                $customer = $this->findOrCreateCustomer($customerData);
                $results['customer'] = 'Customer processed successfully';
                $processedData['customer'] = [
                    'id' => $customer->id,
                    'pancake_id' => $customer->pancake_id,
                    'status' => 'processed'
                ];
                $webhookLog->customer_id = $customer->pancake_id;
            }

            // Check for inventory/stock updates
            $inventoryData = $request->input('data.inventory');
            if (!empty($inventoryData)) {
                $this->processInventoryUpdate($inventoryData);
                $results['inventory'] = 'Inventory updated successfully';
                $processedData['inventory'] = [
                    'items_count' => count($inventoryData),
                    'status' => 'processed'
                ];
            }

            // Check for warehouse updates
            $warehouseData = $request->input('data.warehouse');
            if (!empty($warehouseData)) {
                $this->processWarehouseUpdate($warehouseData);
                $results['warehouse'] = 'Warehouse updated successfully';
                $processedData['warehouse'] = [
                    'id' => $warehouseData['id'] ?? null,
                    'status' => 'processed'
                ];
            }

            // If we didn't process anything, log a warning
            if (empty($results)) {
                Log::warning('Received webhook with no recognizable data', [
                    'data_keys' => array_keys($request->all())
                ]);

                $webhookLog->status = 'error';
                $webhookLog->error_message = 'No recognizable data found in webhook';
                $webhookLog->processed_data = ['error' => 'No recognizable data'];
                $webhookLog->save();

                return response()->json([
                    'success' => false,
                    'message' => 'No recognizable data found in webhook'
                ], 400);
            }

            // Update webhook log with processed data
            $webhookLog->status = 'success';
            $webhookLog->processed_data = $processedData;
            $webhookLog->save();

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'processed' => $results,
            ]);

        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();

            Log::error('Error processing Pancake webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update webhook log with error
            $webhookLog->status = 'error';
            $webhookLog->error_message = $e->getMessage();
            $webhookLog->processed_data = ['error' => $e->getMessage()];
            $webhookLog->save();

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage(),
            ], 500);
        }
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
     *
     * @param array $orderData
     * @return Order
     */
    private function createOrder(array $orderData)
    {
        try {
            DB::beginTransaction();

            // 1. Map thông tin khách hàng
            $customer = $this->findOrCreateCustomer([
                'name' => $orderData['bill_full_name'] ?? $orderData['customer']['name'] ?? null,
                'phone' => $orderData['bill_phone_number'] ?? $orderData['customer']['phone'] ?? null,
                'email' => $orderData['customer']['email'] ?? null,
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'id' => $orderData['customer']['id'] ?? null,
                'code' => $orderData['customer']['code'] ?? null
            ]);

            // 2. Tạo đơn hàng mới
        $order = new Order();

            // Map thông tin cơ bản
            $order->pancake_order_id = $orderData['id'];
        $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));
            $order->source = $orderData['order_sources'] ?? -1;

            // Map thông tin kho và trang
            $order->warehouse_id = $this->findOrCreateWarehouse($orderData['warehouse_id']);
            $order->pancake_page_id = $orderData['page_id'] ?? null;

            // Map thông tin khách hàng
        $order->customer_id = $customer->id;
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->phone;
            $order->customer_email = $customer->email;

            // Map thông tin vận chuyển
        if (!empty($orderData['shipping_address'])) {
            $shipping = $orderData['shipping_address'];
                $order->shipping_province = $shipping['province_id'] ?? null;
                $order->shipping_district = $shipping['district_id'] ?? null;
                $order->shipping_ward = $shipping['commune_id'] ?? null;
            $order->street_address = $shipping['address'] ?? null;
            $order->full_address = $shipping['full_address'] ?? null;
            }

            // Map thông tin đơn vị vận chuyển
            if (!empty($orderData['partner'])) {
                $order->shipping_provider_id = $orderData['partner']['partner_id'] ?? null;
            }

            // Map thông tin tài chính
            $order->shipping_fee = (float)($orderData['shipping_fee'] ?? 0);
            $order->transfer_money = (float)($orderData['transfer_money'] ?? 0);
            $order->total_value = $this->calculateOrderTotal($orderData);

            // Map ghi chú
            $order->notes = $orderData['note'] ?? null;
            $order->additional_notes = $orderData['additional_notes'] ?? null;

            // Map trạng thái
            $order->status = $this->mapPancakeStatus($orderData['status'] ?? 'new');

            // Prepare products_data in the same format as OrderController
            $pancakeItemsPayload = [];
            if (!empty($orderData['items'])) {
                foreach ($orderData['items'] as $item) {
                    $pancakeItemsPayload[] = [
                        'id' => null,
                        'product_id' => $item['product_id'] ?? null,
                        'variation_id' => $item['variation_id'] ?? ($item['code'] ?? null),
                        'quantity' => (int)($item['quantity'] ?? 1),
                        'added_to_cart_quantity' => (int)($item['quantity'] ?? 1),
                        'components' => null,
                        'composite_item_id' => null,
                        'discount_each_product' => 0,
                        'exchange_count' => 0,
                        'is_bonus_product' => false,
                        'is_composite' => null,
                        'is_discount_percent' => false,
                        'is_wholesale' => false,
                        'measure_group_id' => null,
                        'note' => null,
                        'one_time_product' => false,
                        'return_quantity' => 0,
                        'returned_count' => 0,
                        'returning_quantity' => 0,
                        'total_discount' => 0,
                        'variation_info' => [
                            'barcode' => $item['variation_info']['barcode'] ?? ($item['code'] ?? null),
                            'brand_id' => null,
                            'category_ids' => [],
                            'detail' => null,
                            'display_id' => $item['variation_info']['display_id'] ?? ($item['code'] ?? null),
                            'exact_price' => 0,
                            'fields' => null,
                            'last_imported_price' => 0,
                            'measure_info' => null,
                            'name' => $item['variation_info']['name'] ?? ($item['name'] ?? 'N/A'),
                            'product_display_id' => $item['variation_info']['product_display_id'] ?? ($item['code'] ?? null),
                            'retail_price' => (float)($item['variation_info']['retail_price'] ?? ($item['price'] ?? 0)),
                            'weight' => (int)($item['variation_info']['weight'] ?? ($item['weight'] ?? 0)),
                        ]
                    ];
                }
            }

            // Save products_data as JSON
            $order->products_data = json_encode($pancakeItemsPayload);
        $order->save();

            // 3. Xử lý các sản phẩm trong đơn
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $itemData) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                    $orderItem->code = $itemData['variation_id'] ?? null;
                    $orderItem->quantity = $itemData['quantity'] ?? 1;

                    // Map thông tin sản phẩm
                    if (!empty($itemData['variation_info'])) {
                        $orderItem->price = $itemData['variation_info']['retail_price'] ?? 0;
                        // Tính tổng tiền cho item
                        $orderItem->total = $orderItem->price * $orderItem->quantity;
                    }

                    // Lưu thông tin chi tiết vào additional_data
                    $orderItem->additional_data = json_encode($itemData);

                $orderItem->save();

                    // Log thông tin
                Log::info('Created order item', [
                    'order_id' => $order->id,
                        'code' => $orderItem->code,
                    'quantity' => $orderItem->quantity,
                        'price' => $orderItem->price,
                        'total' => $orderItem->total
                    ]);
                }
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
     * Calculate order total from items and fees
     *
     * @param array $orderData
     * @return float
     */
    private function calculateOrderTotal(array $orderData): float
    {
        $total = 0;

        // Tính tổng giá trị sản phẩm
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $price = $item['variation_info']['retail_price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $total += $price * $quantity;
            }
        }

        // Cộng phí vận chuyển
        $total += (float)($orderData['shipping_fee'] ?? 0);

        return $total;
    }

    /**
     * Find or create warehouse by Pancake ID
     *
     * @param string|null $warehouseId
     * @return int|null
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
     * Update an existing order with Pancake data
     *
     * @param Order $order
     * @param array $orderData
     * @return Order
     */
    private function updateOrder(Order $order, array $orderData)
    {
        // Update customer if needed
        if (!empty($orderData['customer'])) {
            $customer = $this->findOrCreateCustomer($orderData['customer']);
            $order->customer_id = $customer->id;
            $order->customer_name = $orderData['customer']['name'] ?? $customer->name;
            $order->customer_phone = $orderData['customer']['phone'] ?? $customer->phone;
            $order->customer_email = $orderData['customer']['email'] ?? $customer->email;
        }

        // Update basic order information
        $order->source = $orderData['source'] ?? $orderData['order_sources_name'] ?? $order->source;
        $order->campaign_id = $orderData['campaign_id'] ?? $order->campaign_id;
        $order->campaign_name = $orderData['campaign_name'] ?? $order->campaign_name;

        // Update status and tracking
        if (!empty($orderData['status'])) {
            $order->status = $this->mapPancakeStatus($orderData['status']);
            $order->pancake_status = is_numeric($orderData['status']) ?
                $orderData['status'] :
                ($orderData['status_name'] ?? $order->pancake_status);
        }
        $order->tracking_code = $orderData['tracking_code'] ?? $order->tracking_code;
        $order->tracking_url = $orderData['tracking_url'] ?? $order->tracking_url;

        // Update financial information
        $order->shipping_fee = $orderData['shipping_fee'] ?? $order->shipping_fee;
        $order->cod_fee = $orderData['cod_fee'] ?? $order->cod_fee;
        $order->insurance_fee = $orderData['insurance_fee'] ?? $order->insurance_fee;
        $order->total_value = $orderData['total'] ?? ($orderData['total_price'] ?? $order->total_value);
        $order->discount_amount = $orderData['discount_amount'] ?? $order->discount_amount;
        $order->payment_method = $orderData['payment_method'] ?? $order->payment_method;
        $order->payment_status = $orderData['payment_status'] ?? $order->payment_status;

        // Update shipping information
        if (!empty($orderData['shipping_address'])) {
            $shipping = $orderData['shipping_address'];
            $order->province_code = $shipping['province_code'] ?? $shipping['province_id'] ?? $order->province_code;
            $order->district_code = $shipping['district_code'] ?? $shipping['district_id'] ?? $order->district_code;
            $order->ward_code = $shipping['ward_code'] ?? $shipping['commune_id'] ?? $order->ward_code;
            $order->street_address = $shipping['address'] ?? $order->street_address;
            $order->full_address = $shipping['full_address'] ?? $order->full_address;
            $order->shipping_provider = $shipping['provider'] ?? $order->shipping_provider;
            $order->shipping_service = $shipping['service'] ?? $order->shipping_service;
            $order->shipping_note = $shipping['note'] ?? $order->shipping_note;
        }

        // Update notes
        $order->notes = $orderData['notes'] ?? $order->notes;
        $order->internal_notes = $orderData['internal_notes'] ?? $order->internal_notes;
        $order->additional_notes = $orderData['additional_notes'] ?? $order->additional_notes;

        // Update products_data with the same format as OrderController
        if (!empty($orderData['items'])) {
            $pancakeItemsPayload = [];
            foreach ($orderData['items'] as $item) {
                $pancakeItemsPayload[] = [
                    'id' => null,
                    'product_id' => $item['product_id'] ?? null,
                    'variation_id' => $item['variation_id'] ?? ($item['code'] ?? null),
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'added_to_cart_quantity' => (int)($item['quantity'] ?? 1),
                    'components' => null,
                    'composite_item_id' => null,
                    'discount_each_product' => 0,
                    'exchange_count' => 0,
                    'is_bonus_product' => false,
                    'is_composite' => null,
                    'is_discount_percent' => false,
                    'is_wholesale' => false,
                    'measure_group_id' => null,
                    'note' => null,
                    'one_time_product' => false,
                    'return_quantity' => 0,
                    'returned_count' => 0,
                    'returning_quantity' => 0,
                    'total_discount' => 0,
                    'variation_info' => [
                        'barcode' => $item['variation_info']['barcode'] ?? ($item['code'] ?? null),
                        'brand_id' => null,
                        'category_ids' => [],
                        'detail' => null,
                        'display_id' => $item['variation_info']['display_id'] ?? ($item['code'] ?? null),
                        'exact_price' => 0,
                        'fields' => null,
                        'last_imported_price' => 0,
                        'measure_info' => null,
                        'name' => $item['variation_info']['name'] ?? ($item['name'] ?? 'N/A'),
                        'product_display_id' => $item['variation_info']['product_display_id'] ?? ($item['code'] ?? null),
                        'retail_price' => (float)($item['variation_info']['retail_price'] ?? ($item['price'] ?? 0)),
                        'weight' => (int)($item['variation_info']['weight'] ?? ($item['weight'] ?? 0)),
                    ]
                ];
            }
            $order->products_data = json_encode($pancakeItemsPayload);
        }

        // Update status
        $order->internal_status = 'Updated from Pancake webhook';
        $order->updated_at = now();
        $order->updated_by = $order->updated_by;

        $order->save();

        // Update order items
        if (!empty($orderData['items'])) {
            // First, mark all existing items for potential deletion
            $existingItemIds = $order->items->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($orderData['items'] as $itemData) {
                // Find or create order item
                $orderItem = $order->items()
                    ->where('pancake_product_id', $itemData['product_id'] ?? null)
                    ->first();

                if (!$orderItem) {
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                }

                // Update order item fields
                $this->setOrderItemFields($orderItem, $itemData);
                $orderItem->save();

                $updatedItemIds[] = $orderItem->id;

                Log::info('Updated order item', [
                    'order_id' => $order->id,
                    'item_id' => $orderItem->id,
                    'product_name' => $orderItem->product_name
                ]);
            }

            // Remove items that no longer exist in the updated data
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                OrderItem::whereIn('id', $itemsToDelete)->delete();

                Log::info('Removed deleted items', [
                    'order_id' => $order->id,
                    'deleted_item_ids' => $itemsToDelete
                ]);
            }

            // Update total value based on new items
            $newTotal = $order->items->sum(function($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 1);
            });

            // Add shipping fee
            $newTotal += ($order->shipping_fee ?? 0);

            // Update order total if different
            if ($newTotal > 0 && $newTotal != $order->total_value) {
                $order->total_value = $newTotal;
                $order->save();

                Log::info('Updated order total value based on new items', [
                    'order_id' => $order->id,
                    'new_total' => $newTotal
                ]);
            }
        }

        // Process live session revenue if notes contain session info
        if (!empty($orderData['notes'])) {
            $this->processLiveSessionRevenue($order, $orderData['notes']);
        }

        return $order;
    }

    /**
     * Find or create a customer based on Pancake data
     *
     * @param array $customerData
     * @return Customer
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

        // Chuẩn bị dữ liệu social
        $socialData = [
            'social_type' => $customerData['social_type'] ?? null,
            'social_id' => $customerData['social_id'] ?? null,
            'fb_id' => $customerData['fb_id'] ?? null,
            'social_info' => !empty($customerData['social_info']) ? json_encode($customerData['social_info']) : null
        ];

        if (!$customer) {
            // Create new customer
            $customer = new Customer();
            $customer->name = $name ?? 'Unknown';
            $customer->phone = $phone;
            $customer->email = $email;
            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->pancake_customer_id = $customerData['code'] ?? null;

            // Map địa chỉ
            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? null;
                $customer->district = $shipping['district_id'] ?? null;
                $customer->ward = $shipping['commune_id'] ?? null;
                $customer->street_address = $shipping['address'] ?? null;
                $customer->full_address = $shipping['full_address'] ?? null;
            }

            // Map thông tin social
            foreach ($socialData as $key => $value) {
                if (Schema::hasColumn('customers', $key)) {
                    $customer->$key = $value;
                }
            }

            $customer->save();
        } else {
            // Update existing customer with any new data
            $customer->pancake_id = $customerData['id'] ?? $customer->pancake_id;
            $customer->pancake_customer_id = $customerData['code'] ?? $customer->pancake_customer_id;

            // Cập nhật địa chỉ nếu có
            if (!empty($customerData['shipping_address'])) {
                $shipping = $customerData['shipping_address'];
                $customer->province = $shipping['province_id'] ?? $customer->province;
                $customer->district = $shipping['district_id'] ?? $customer->district;
                $customer->ward = $shipping['commune_id'] ?? $customer->ward;
                $customer->street_address = $shipping['address'] ?? $customer->street_address;
                $customer->full_address = $shipping['full_address'] ?? $customer->full_address;
            }

            // Cập nhật thông tin social
            foreach ($socialData as $key => $value) {
                if (Schema::hasColumn('customers', $key) && !empty($value)) {
                    $customer->$key = $value;
                }
            }

            $customer->save();
        }

        return $customer;
    }

    /**
     * Map Pancake status to internal status
     *
     * @param string|int $pancakeStatus
     * @return string
     */
    private function mapPancakeStatus($pancakeStatus): string
    {
        // If numeric status is provided
        if (is_numeric($pancakeStatus)) {
            return match ((int)$pancakeStatus) {
                1 => 'moi',
                2 => 'dang_xu_ly',
                3 => 'dang_giao_hang',
                4 => 'hoan_thanh',
                5 => 'huy',
                6 => 'tra_hang',
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
            default => 'moi',
        };
    }

    /**
     * Set order item fields safely based on available columns
     *
     * @param OrderItem $orderItem
     * @param array $data
     * @return OrderItem
     */
    private function setOrderItemFields(OrderItem $orderItem, array $data)
    {
        // Handle components structure first (new Pancake format)
        if (!empty($data['components']) && is_array($data['components'])) {
            foreach ($data['components'] as $component) {
                if (!empty($component['variation_info'])) {
                    $variationInfo = $component['variation_info'];

                    // Get product name from variation_info
                    $orderItem->product_name = $component['name'] ?? $data['name'] ?? 'Unknown Product';

                    // Get product code from variation_id
                    $orderItem->code = $component['variation_id'] ?? $data['code'] ?? null;

                    // Get price from variation_info
                    $orderItem->price = $component['retail_price'] ?? $data['price'] ?? 0;

                    // Get quantity from component
                    $orderItem->quantity = $component['quantity'] ?? $data['quantity'] ?? 1;

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
                    $orderItem->product_info = array_merge($data, [
                        'processed_component' => $component,
                        'processed_variation_info' => $variationInfo
                    ]);

                    // Only process the first component for now
                    break;
                }
            }
        }
        // Handle direct variation_info format without components (new Pancake format)
        else if (!empty($data['variation_info'])) {
            $variationInfo = $data['variation_info'];

            // Get product name from variation_info
            $orderItem->product_name = $variationInfo['name'] ?? $data['name'] ?? 'Unknown Product';

            // Get product code from various possible sources
            $orderItem->code = $variationInfo['display_id'] ?? $variationInfo['barcode'] ?? $data['variation_id'] ?? null;

            // Set price from variation_info
            $orderItem->price = $variationInfo['retail_price'] ?? $data['price'] ?? 0;

            // Set quantity
            $orderItem->quantity = $data['quantity'] ?? 1;

            // Set weight if available
            $orderItem->weight = $variationInfo['weight'] ?? $data['weight'] ?? 0;

            // Set name field
            $orderItem->name = $variationInfo['name'] ?? $data['name'] ?? null;

            // Store IDs if columns exist
            if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $orderItem->pancake_variant_id = $data['variation_id'] ?? $data['id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                $orderItem->pancake_product_id = $data['product_id'] ?? null;
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
            $orderItem->product_info = $data;
        }
        else {
            // Handle traditional item format or direct format without variation_info or components

            // Set product name
            $orderItem->product_name = $data['name'] ?? 'Unknown Product';

            // Set product code/sku based on available columns
            if (Schema::hasColumn('order_items', 'product_code')) {
                $orderItem->product_code = $data['sku'] ?? $data['variation_id'] ?? null;
            } else if (Schema::hasColumn('order_items', 'sku')) {
                $orderItem->sku = $data['sku'] ?? $data['variation_id'] ?? null;
            } else if (Schema::hasColumn('order_items', 'code')) {
                $orderItem->code = $data['sku'] ?? $data['variation_id'] ?? $data['display_id'] ?? null;
            }

            // Set other common fields
            $orderItem->quantity = $data['quantity'] ?? 1;
            $orderItem->price = $data['price'] ?? 0;
            $orderItem->weight = $data['weight'] ?? 0;
            $orderItem->name = $data['name'] ?? $data['product_name'] ?? null;

            // Store variation and product IDs
            if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $orderItem->pancake_variant_id = $data['variation_id'] ?? $data['variant_id'] ?? $data['id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                $orderItem->pancake_product_id = $data['product_id'] ?? null;
            }

            if (Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $orderItem->pancake_variation_id = $data['variation_id'] ?? $data['sku'] ?? null;
            }

            // Handle barcode if available
            if (Schema::hasColumn('order_items', 'barcode')) {
                $orderItem->barcode = $data['barcode'] ?? null;
            }

            // Store the complete item data in product_info field
            $orderItem->product_info = $data;
        }

        return $orderItem;
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
