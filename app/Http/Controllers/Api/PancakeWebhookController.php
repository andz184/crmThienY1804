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

            // Check for order data
            $orderData = $request->input('data.order');
            if (!empty($orderData)) {
                // Check if order already exists
                $existingOrder = Order::where('pancake_order_id', $orderData['id'] ?? null)->first();

                if ($existingOrder) {
                    // Update existing order
                    $this->updateOrder($existingOrder, $orderData);
                    $results['order'] = 'Order updated successfully';
                } else {
                    // Create new order
                    $newOrder = $this->createOrder($orderData);
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

            // If we didn't process anything, log a warning
            if (empty($results)) {
                Log::warning('Received webhook with no recognizable data', [
                    'data_keys' => array_keys($request->input('data', []))
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
        $customer = $this->findOrCreateCustomer($orderData['customer'] ?? []);

        // Map Pancake order status to internal status
        $status = $this->mapPancakeStatus($orderData['status'] ?? 'moi');

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
            $page = PancakePage::firstOrCreate([
                'pancake_id' => $orderData['page_id']
            ], [
                'name' => $orderData['page_name'] ?? 'Pancake Page',
                'pancake_shop_id' => $shopId,
            ]);
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
        $order->customer_name = $orderData['customer']['name'] ?? $customer->name;
        $order->customer_phone = $orderData['customer']['phone'] ?? $customer->phone;
        $order->customer_email = $orderData['customer']['email'] ?? $customer->email;
        $order->customer_id = $customer->id;
        $order->status = $status;
        $order->internal_status = 'Imported from Pancake webhook';
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->total_value = $orderData['total'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->user_id = $userId;
        $order->created_by = $userId;

        // Address data
        $order->province_code = $orderData['shipping_address']['province_code'] ?? null;
        $order->district_code = $orderData['shipping_address']['district_code'] ?? null;
        $order->ward_code = $orderData['shipping_address']['ward_code'] ?? null;
        $order->street_address = $orderData['shipping_address']['address'] ?? null;
        $order->full_address = $orderData['shipping_address']['full_address'] ?? null;

        // Pancake data
        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->warehouse_id = $warehouse->id ?? null;
        $order->post_id = $orderData['post_id'] ?? null; // For campaign tracking

        // Product info JSON for reporting
        $order->product_info = !empty($orderData['items']) ? $orderData['items'] : null;

        $order->save();

        // Create order items
        if (!empty($orderData['items'])) {
            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'] ?? null;
                $orderItem->name = $item['name'] ?? 'Unknown Product';
                $orderItem->sku = $item['sku'] ?? null;
                $orderItem->quantity = $item['quantity'] ?? 1;
                $orderItem->price = $item['price'] ?? 0;
                $orderItem->total = $item['total'] ?? $orderItem->price * $orderItem->quantity;
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
                $orderItem->product_id = $item['product_id'] ?? null;
                $orderItem->name = $item['name'] ?? 'Unknown Product';
                $orderItem->sku = $item['sku'] ?? null;
                $orderItem->quantity = $item['quantity'] ?? 1;
                $orderItem->price = $item['price'] ?? 0;
                $orderItem->total = $item['total'] ?? $orderItem->price * $orderItem->quantity;
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

        if (!empty($customerData['phone'])) {
            $customer = Customer::where('phone', $customerData['phone'])->first();
        }

        if (!$customer && !empty($customerData['email'])) {
            $customer = Customer::where('email', $customerData['email'])->first();
        }

        if (!$customer) {
            // Create new customer
            $customer = new Customer();
            $customer->name = $customerData['name'] ?? 'Unknown';
            $customer->phone = $customerData['phone'] ?? null;
            $customer->email = $customerData['email'] ?? null;
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
}
