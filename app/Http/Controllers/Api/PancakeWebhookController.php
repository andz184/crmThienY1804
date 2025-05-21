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
     * Handle all incoming webhooks from Pancake (orders, customers, inventory)
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

            // Check if this is an order - Pancake sends order data at root level
            $orderData = $request->all();

            // Validate minimal required fields for order
            if (!empty($orderData['bill_full_name']) && !empty($orderData['bill_phone_number'])) {
                // This is an order from Pancake

                // Map data to our expected format
                $mappedOrderData = [
                    'id' => $orderData['id'] ?? null,
                    'code' => 'PCK-' . ($orderData['id'] ?? Str::random(8)),
                    'status' => $orderData['status'] ?? 0,
                    'total' => $orderData['total'] ?? 0,
                    'shipping_fee' => $orderData['shipping_fee'] ?? 0,
                    'payment_method' => 'cod',
                    'customer' => [
                        'name' => $orderData['bill_full_name'] ?? '',
                        'phone' => $orderData['bill_phone_number'] ?? '',
                        'email' => $orderData['bill_email'] ?? null,
                    ],
                    'items' => $orderData['items'] ?? [],
                    'shipping_address' => $orderData['shipping_address'] ?? [
                        'address' => $orderData['shipping_address']['address'] ?? null,
                        'full_address' => $orderData['shipping_address']['full_address'] ?? null,
                        'province_code' => $orderData['shipping_address']['province_id'] ?? null,
                        'district_code' => $orderData['shipping_address']['district_id'] ?? null,
                        'ward_code' => $orderData['shipping_address']['commune_id'] ?? null,
                    ],
                    'shop_id' => $orderData['shop_id'] ?? null,
                    'page_id' => $orderData['page_id'] ?? null,
                    'post_id' => $orderData['post_id'] ?? null,
                ];

                // Check if order already exists
                $existingOrder = Order::where('pancake_order_id', $mappedOrderData['id'])->first();

                if ($existingOrder) {
                    // Update existing order
                    $this->updateOrder($existingOrder, $mappedOrderData);
                    $results['order'] = 'Order updated successfully';
                } else {
                    // Create new order
                    $newOrder = $this->createOrder($mappedOrderData);
                    $results['order'] = 'Order created successfully';
                }
            }
            // Fallback to the original data.X format checking for backward compatibility
            else {
                // Check for order data in data.order format
                $dataOrderData = $request->input('data.order');
                if (!empty($dataOrderData)) {
                    // Check if order already exists
                    $existingOrder = Order::where('pancake_order_id', $dataOrderData['id'] ?? null)->first();

                    if ($existingOrder) {
                        // Update existing order
                        $this->updateOrder($existingOrder, $dataOrderData);
                        $results['order'] = 'Order updated successfully';
                    } else {
                        // Create new order
                        $newOrder = $this->createOrder($dataOrderData);
                        $results['order'] = 'Order created successfully';
                    }
                }

                // Check for customer data
                $customerData = $request->input('data.customer');
                if (!empty($customerData)) {
                    // Find or create customer
                    $customer = $this->findOrCreateCustomer($customerData);
                    $results['customer'] = 'Customer processed successfully';
                }

                // Check for inventory data
                $inventoryData = $request->input('data.inventory') ?? $request->input('data.stock');
                if (!empty($inventoryData)) {
                    // Just log inventory updates for now
                    Log::info('Received inventory update', [
                        'data' => $inventoryData
                    ]);
                    $results['inventory'] = 'Inventory update logged';
                }
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
     * Handle incoming order webhooks from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleOrderWebhook(Request $request)
    {
        // Validate the webhook signature if Pancake provides one
        if (!$this->validateWebhookSignature($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['error' => 'Invalid webhook signature'], 403);
        }

        try {
            // Log the incoming webhook
            Log::info('Received Pancake webhook', [
                'type' => 'order',
                'data' => $request->all(),
            ]);

            // Process the order data
            $orderData = $request->input('data.order', []);
            if (empty($orderData)) {
                return response()->json(['error' => 'No order data provided'], 400);
            }

            // Begin transaction
            DB::beginTransaction();

            // Check if order already exists
            $existingOrder = Order::where('pancake_order_id', $orderData['id'] ?? null)->first();

            if ($existingOrder) {
                // Update existing order
                $this->updateOrder($existingOrder, $orderData);
                $message = 'Order updated successfully';
            } else {
                // Create new order
                $newOrder = $this->createOrder($orderData);
                $message = 'Order created successfully';
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();

            Log::error('Error processing Pancake order webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate the webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private function validateWebhookSignature(Request $request)
    {
        // Per client requirements, no signature validation is needed
        // Just log the incoming request for debugging purposes
        Log::info('Received webhook request from IP: ' . $request->ip(), [
            'headers' => $request->headers->all(),
            'payload_size' => strlen($request->getContent()),
        ]);

        return true; // Always accept the webhook
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
            // Check if the page already exists with either pancake_id or pancake_page_id
            $page = PancakePage::where('pancake_id', $orderData['page_id'])
                    ->orWhere('pancake_page_id', $orderData['page_id'])
                    ->first();
            
            if (!$page) {
                // If not found, create a new page
                $page = new PancakePage();
                $page->pancake_id = $orderData['page_id'];
                $page->pancake_page_id = $orderData['page_id'];
                $page->name = $orderData['page_name'] ?? 'Pancake Page';
                $page->pancake_shop_id = $shopId;
                $page->pancake_shop_table_id = $shopId ? PancakeShop::where('pancake_id', $shopId)->value('id') : null;
                $page->save();
                
                Log::info('Created new Pancake Page in webhook', [
                    'page_id' => $page->id,
                    'pancake_id' => $orderData['page_id'] 
                ]);
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
        $order->pancake_order_id = $orderData['id'] ?? null;
        $order->order_code = $orderData['code'] ?? ('PCK-' . Str::random(8));
        $order->customer_name = $orderData['customer']['name'] ?? $orderData['bill_full_name'] ?? $customer->name;
        $order->customer_phone = $orderData['customer']['phone'] ?? $orderData['bill_phone_number'] ?? $customer->phone;
        $order->customer_email = $orderData['customer']['email'] ?? $orderData['bill_email'] ?? $customer->email;
        $order->customer_id = $customer->id;
        $order->status = $status;
        $order->internal_status = 'Imported from Pancake webhook';
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->total_value = $orderData['total'] ?? $orderData['total_discount'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->user_id = $userId;
        $order->created_by = $userId;

        // Address data - handle both formats
        if (!empty($orderData['shipping_address'])) {
            $shipping = $orderData['shipping_address'];
            $order->province_code = $shipping['province_code'] ?? $shipping['province_id'] ?? null;
            $order->district_code = $shipping['district_code'] ?? $shipping['district_id'] ?? null;
            $order->ward_code = $shipping['ward_code'] ?? $shipping['commune_id'] ?? null;
            $order->street_address = $shipping['address'] ?? null;
            $order->full_address = $shipping['full_address'] ?? null;
        }

        // Pancake data
        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->warehouse_id = $warehouse->id ?? null;
        $order->post_id = $orderData['post_id'] ?? null; // For campaign tracking

        // Product info JSON for reporting
        $order->product_info = !empty($orderData['items']) ? json_encode($orderData['items']) : null;

        $order->save();

        // Create order items - handle both formats
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;

                // Prepare the item data structure
                $itemData = [];
                
                // Handle old format
                if (isset($item['product_id'])) {
                    if (Schema::hasColumn('order_items', 'product_id')) {
                        $orderItem->product_id = $item['product_id'];
                    }
                    $itemData = [
                        'name' => $item['name'] ?? 'Unknown Product',
                        'sku' => $item['sku'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? 0
                    ];
                }
                // Handle new format
                else {
                    if (Schema::hasColumn('order_items', 'product_id')) {
                        $orderItem->product_id = $item['product_id'] ?? null;
                    }
                    $itemData = [
                        'name' => $item['variation_info']['name'] ?? 'Unknown Product',
                        'sku' => null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['variation_info']['retail_price'] ?? 0
                    ];
                }
                
                // Use helper method to set fields safely
                $this->setOrderItemFields($orderItem, $itemData);

                // Only set total if the column exists
                if (Schema::hasColumn('order_items', 'total')) {
                    $orderItem->total = $item['total'] ?? $orderItem->price * $orderItem->quantity;
                }
                $orderItem->save();
            }
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

        // Update status
        if (!empty($orderData['status'])) {
            $order->status = $this->mapPancakeStatus($orderData['status']);
        }

        // Update shipping and payment
        if (isset($orderData['shipping_fee'])) {
            $order->shipping_fee = $orderData['shipping_fee'];
        }

        if (isset($orderData['total'])) {
            $order->total_value = $orderData['total'];
        }

        if (!empty($orderData['payment_method'])) {
            $order->payment_method = $orderData['payment_method'];
        }

        // Update address data
        if (!empty($orderData['shipping_address'])) {
            $order->province_code = $orderData['shipping_address']['province_code'] ?? $order->province_code;
            $order->district_code = $orderData['shipping_address']['district_code'] ?? $order->district_code;
            $order->ward_code = $orderData['shipping_address']['ward_code'] ?? $order->ward_code;
            $order->street_address = $orderData['shipping_address']['address'] ?? $order->street_address;
            $order->full_address = $orderData['shipping_address']['full_address'] ?? $order->full_address;
        }

        // Update product info for reporting
        if (!empty($orderData['items'])) {
            $order->product_info = $orderData['items'];

            // Delete existing items and create new ones
            $order->items()->delete();

            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                // Set product_id if the column exists (outside the helper method)
                if (Schema::hasColumn('order_items', 'product_id')) {
                    $orderItem->product_id = $item['product_id'] ?? null;
                }
                
                // Use helper method for standard fields
                $this->setOrderItemFields($orderItem, $item);
                // Only set total if the column exists
                if (Schema::hasColumn('order_items', 'total')) {
                    $orderItem->total = $item['total'] ?? $orderItem->price * $orderItem->quantity;
                }
                $orderItem->save();
            }
        }

        $order->internal_status = 'Updated from Pancake webhook';
        $order->save();

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

        // Check if this is direct bill info format
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
            $customer->phone = $phone ?? null;

            // Only set email if it's provided
            if (!empty($email)) {
                $customer->email = $email;
            }
            // Email field will be NULL by default and that's ok

            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->pancake_code = $customerData['code'] ?? null;
            $customer->address = $customerData['address'] ?? null;
            $customer->social_type = $customerData['social_type'] ?? null;
            $customer->social_id = $customerData['social_id'] ?? null;
            $customer->save();
        } else {
            // Update existing customer
            if (!empty($customerData['id']) && empty($customer->pancake_id)) {
                $customer->pancake_id = $customerData['id'];
            }

            if (!empty($customerData['code']) && empty($customer->pancake_code)) {
                $customer->pancake_code = $customerData['code'];
            }

            if (!empty($customerData['address']) && empty($customer->address)) {
                $customer->address = $customerData['address'];
            }

            if (!empty($customerData['social_type']) && empty($customer->social_type)) {
                $customer->social_type = $customerData['social_type'];
            }

            if (!empty($customerData['social_id']) && empty($customer->social_id)) {
                $customer->social_id = $customerData['social_id'];
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
        // Nếu status là số
        if (is_numeric($pancakeStatus)) {
            return match ((int)$pancakeStatus) {
                1 => Order::STATUS_CHO_CHUYEN_HANG,  // processing
                2 => Order::STATUS_DA_GUI_HANG,      // shipping
                3 => Order::STATUS_DA_NHAN,          // delivered
                4 => Order::STATUS_DA_THU_TIEN,      // done/completed
                5 => Order::STATUS_DA_HUY,           // canceled
                default => Order::STATUS_MOI,        // new/default
            };
        }

        // Nếu status là string
        $pancakeStatus = strtolower((string)$pancakeStatus);
        return match ($pancakeStatus) {
            'done' => Order::STATUS_DA_THU_TIEN,
            'completed' => Order::STATUS_DA_THU_TIEN,
            'shipping' => Order::STATUS_DA_GUI_HANG,
            'delivered' => Order::STATUS_DA_NHAN,
            'canceled' => Order::STATUS_DA_HUY,
            'pending' => Order::STATUS_CAN_XU_LY,
            'processing' => Order::STATUS_CHO_CHUYEN_HANG,
            'waiting' => Order::STATUS_CHO_HANG,
            'new' => Order::STATUS_MOI,
            default => Order::STATUS_MOI,
        };
    }

    /**
     * Handle stock update webhooks from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleStockWebhook(Request $request)
    {
        // Validate the webhook signature
        if (!$this->validateWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 403);
        }

        Log::info('Received Pancake stock webhook', [
            'data' => $request->all(),
        ]);

        // Process stock updates
        // This would typically update product inventory in your system

        return response()->json([
            'success' => true,
            'message' => 'Stock webhook received'
        ]);
    }

    /**
     * Handle incoming customer webhooks from Pancake
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleCustomerWebhook(Request $request)
    {
        // Log the incoming webhook
        Log::info('Received Pancake customer webhook', [
            'type' => 'customer',
            'data' => $request->all(),
        ]);

        try {
            // Process the customer data
            $customerData = $request->input('data.customer', []);
            if (empty($customerData)) {
                return response()->json(['error' => 'No customer data provided'], 400);
            }

            // Begin transaction
            DB::beginTransaction();

            // Find or create customer
            $customer = $this->findOrCreateCustomer($customerData);
            $message = 'Customer processed successfully';

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            // Rollback on error
            DB::rollBack();

            Log::error('Error processing Pancake customer webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set product fields safely based on available columns
     *
     * @param OrderItem $orderItem
     * @param array $data
     * @return OrderItem
     */
    private function setOrderItemFields(OrderItem $orderItem, array $data) 
    {
        // Set product name
        $orderItem->product_name = $data['name'] ?? 'Unknown Product';
        
        // Set product identifier based on what column exists
        if (Schema::hasColumn('order_items', 'product_code')) {
            $orderItem->product_code = $data['sku'] ?? null;
        } else if (Schema::hasColumn('order_items', 'sku')) {
            $orderItem->sku = $data['sku'] ?? null;
        } else if (Schema::hasColumn('order_items', 'code')) {
            $orderItem->code = $data['sku'] ?? null;
        }
        
        // Set quantity and price
        $orderItem->quantity = $data['quantity'] ?? 1;
        $orderItem->price = $data['price'] ?? 0;
        
        // Set variant ID if column exists
        if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
            $orderItem->pancake_variant_id = $data['variant_id'] ?? $data['id'] ?? null;
        }
        
        return $orderItem;
    }
}
