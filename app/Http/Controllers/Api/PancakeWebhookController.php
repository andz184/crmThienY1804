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

class PancakeWebhookController extends Controller
{
    /**
     * Handle all incoming webhooks from Pancake (orders, customers)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // Log the incoming webhook
        Log::info('Received Pancake webhook', [
            'data_size' => strlen($request->getContent()),
            'ip' => $request->ip()
        ]);

        try {
            // Begin transaction
            DB::beginTransaction();

            // Process based on the type of data received
            $results = [];

            // Check for order data
            $orderData = $request->input('data.order');
            if (!empty($orderData)) {
                // Check if order already exists
                $existingOrder = Order::where('pancake_order_id', $orderData['id'] ?? null)->first();

                if ($existingOrder) {
                    // Update existing order
                    $order = $this->updateOrder($existingOrder, $orderData);
                    $results['order'] = 'Order updated successfully';
                } else {
                    // Create new order
                    $order = $this->createOrder($orderData);
                    $results['order'] = 'Order created successfully';
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
                $this->findOrCreateCustomer($customerData);
                $results['customer'] = 'Customer processed successfully';
            }

            // If we didn't process anything, log a warning
            if (empty($results)) {
                Log::warning('Received webhook with no recognizable data', [
                    'data_keys' => array_keys($request->all())
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No recognizable data found in webhook'
                ], 400);
            }

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
        // Find or create customer
        $customer = $this->findOrCreateCustomer($orderData['customer'] ?? $orderData);

        // Map Pancake order status to internal status
        $status = $this->mapPancakeStatus($orderData['status'] ?? $orderData['status_name'] ?? 'moi');

        // Find shop and page
        $shopId = null;
        $pageId = null;

        if (!empty($orderData['shop_id'])) {
            $shop = PancakeShop::firstOrCreate([
                'pancake_id' => $orderData['shop_id']
            ], [
                'name' => $orderData['shop_name'] ?? 'Pancake Shop',
            ]);
            $shopId = $shop->id;
        }

        if (!empty($orderData['page_id'])) {
            $page = PancakePage::where('pancake_id', $orderData['page_id'])
                    ->orWhere('pancake_page_id', $orderData['page_id'])
                    ->first();

            if (!$page) {
                $page = new PancakePage();
                $page->pancake_id = $orderData['page_id'];
                $page->pancake_page_id = $orderData['page_id'];
                $page->name = $orderData['page_name'] ?? 'Pancake Page';
                $page->pancake_shop_id = $shopId;
                $page->pancake_shop_table_id = $shopId ? PancakeShop::where('pancake_id', $shopId)->value('id') : null;
                $page->save();
            }

            $pageId = $page->id;
        }

        // Assign to default user if available
        $userId = null;
        $defaultUser = User::whereHas('roles', function($q) {
            $q->where('name', 'staff');
        })->first();

        if ($defaultUser) {
            $userId = $defaultUser->id;
        }

        // Get default warehouse
        $warehouse = Warehouse::first();

        // Create order
        $order = new Order();

        // Basic order information
        $order->pancake_order_id = $orderData['id'] ?? null;
        $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));
        $order->source = $orderData['source'] ?? $orderData['order_sources_name'] ?? null;
        $order->campaign_id = $orderData['campaign_id'] ?? null;
        $order->campaign_name = $orderData['campaign_name'] ?? null;

        // Customer information
        $order->customer_id = $customer->id;
        $order->customer_name = $orderData['customer']['name'] ?? $orderData['bill_full_name'] ?? $customer->name;
        $order->customer_phone = $orderData['customer']['phone'] ?? $orderData['bill_phone_number'] ?? $customer->phone;
        $order->customer_email = $orderData['customer']['email'] ?? $orderData['bill_email'] ?? $customer->email;

        // Status and tracking
        $order->status = $status;
        $order->pancake_status = is_numeric($orderData['status']) ? $orderData['status'] : ($orderData['status_name'] ?? null);
        $order->internal_status = 'Created from Pancake webhook';
        $order->tracking_code = $orderData['tracking_code'] ?? null;
        $order->tracking_url = $orderData['tracking_url'] ?? null;

        // Financial information
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->cod_fee = $orderData['cod_fee'] ?? 0;
        $order->insurance_fee = $orderData['insurance_fee'] ?? 0;
        $order->total_value = $orderData['total'] ?? $orderData['total_discount'] ?? 0;
        $order->discount_amount = $orderData['discount_amount'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->payment_status = $orderData['payment_status'] ?? 'pending';

        // Shipping information
        if (!empty($orderData['shipping_address'])) {
            $shipping = $orderData['shipping_address'];
            $order->province_code = $shipping['province_code'] ?? $shipping['province_id'] ?? null;
            $order->district_code = $shipping['district_code'] ?? $shipping['district_id'] ?? null;
            $order->ward_code = $shipping['ward_code'] ?? $shipping['commune_id'] ?? null;
            $order->street_address = $shipping['address'] ?? null;
            $order->full_address = $shipping['full_address'] ?? null;
            $order->shipping_provider = $shipping['provider'] ?? null;
            $order->shipping_service = $shipping['service'] ?? null;
            $order->shipping_note = $shipping['note'] ?? null;
        }

        // Notes and additional information
        $order->notes = $orderData['notes'] ?? null;
        $order->internal_notes = $orderData['internal_notes'] ?? null;
        $order->additional_notes = $orderData['additional_notes'] ?? null;

        // Pancake specific data
        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->warehouse_id = $warehouse->id ?? null;
        $order->post_id = $orderData['post_id'] ?? null;

        // User information
        $order->user_id = $userId;
        $order->created_by = $userId;
        $order->updated_by = $userId;

        // Product info JSON for reporting
        $order->product_info = !empty($orderData['items']) ? json_encode($orderData['items']) : null;

        // Save timestamps
        if (!empty($orderData['inserted_at'])) {
            try {
                $order->pancake_inserted_at = \Carbon\Carbon::parse($orderData['inserted_at']);
            } catch (\Exception $e) {
                Log::warning("Could not parse inserted_at date for order {$order->order_code}: " . $e->getMessage());
            }
        }

        $order->save();

        // Create order items
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $itemData) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $this->setOrderItemFields($orderItem, $itemData);
                $orderItem->save();

                Log::info('Created order item', [
                    'order_id' => $order->id,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $orderItem->quantity,
                    'price' => $orderItem->price
                ]);
            }

            // Update total value based on items if needed
            $newTotal = $order->items->sum(function($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 1);
            });

            // Add shipping fee
            $newTotal += ($order->shipping_fee ?? 0);

            // Update order total if different
            if ($newTotal > 0 && $newTotal != $order->total_value) {
                $order->total_value = $newTotal;
                $order->save();

                Log::info('Updated order total value based on items', [
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

        // Update product info JSON
        if (!empty($orderData['items'])) {
            $order->product_info = json_encode($orderData['items']);
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

        if (!$customer) {
            // Create new customer
            $customer = new Customer();
            $customer->name = $name ?? 'Unknown';
            $customer->phone = $phone;
            $customer->email = $email;
            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->pancake_code = $customerData['code'] ?? null;
            $customer->address = $customerData['address'] ?? null;
            $customer->social_type = $customerData['social_type'] ?? null;
            $customer->social_id = $customerData['social_id'] ?? null;
            $customer->save();
        } else {
            // Update existing customer with any new data
            $customer->pancake_id = $customerData['id'] ?? $customer->pancake_id;
            $customer->pancake_code = $customerData['code'] ?? $customer->pancake_code;
            $customer->address = $customerData['address'] ?? $customer->address;
            $customer->social_type = $customerData['social_type'] ?? $customer->social_type;
            $customer->social_id = $customerData['social_id'] ?? $customer->social_id;
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
}
