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
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

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

            // Validate required fields
            if (empty($webhookData['id'])) {
                throw new \Exception('Missing required field: id');
            }

            // Format data theo cấu trúc của PancakeSyncController
            $formattedData = $this->formatWebhookData($webhookData);

            Log::info('Formatted webhook data', [
                'order_id' => $formattedData['id'],
                'has_items' => !empty($formattedData['items']),
                'items_count' => !empty($formattedData['items']) ? count($formattedData['items']) : 0,
                'has_notes' => !empty($formattedData['note']),
                'note' => $formattedData['note'] ?? null
            ]);

            // Check if order exists by pancake_order_id
            $existingOrder = Order::where('pancake_order_id', $webhookData['id'])->first();

            if ($existingOrder) {
                Log::info('Updating existing order', ['order_id' => $existingOrder->id, 'pancake_order_id' => $existingOrder->pancake_order_id]);
                $order = $this->updateOrderFromPancake($existingOrder, $formattedData);
                $message = 'Order updated successfully';
            } else {
                Log::info('Creating new order', ['pancake_order_id' => $webhookData['id']]);
                $order = $this->createOrderFromPancake($formattedData);
                $message = 'Order created successfully';
            }

            if (!$order) {
                throw new \Exception('Failed to process order');
            }

            Log::info('Order processed successfully', [
                'order_id' => $order->id,
                'pancake_order_id' => $order->pancake_order_id,
                'has_live_session_info' => !empty($order->live_session_info),
                'live_session_info' => $order->live_session_info
            ]);

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
        // Validate required fields
        if (empty($webhookData['id'])) {
            throw new \Exception('Missing required field: id in webhook data');
        }

        // Format data according to PancakeSyncController structure
        $formattedData = [
            'id' => $webhookData['id'],
            'code' => $webhookData['id'],
            'status' => $webhookData['status'] ?? 0,
            'inserted_at' => $webhookData['inserted_at'] ?? null,
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

        // Preserve original items data exactly as received
        if (!empty($webhookData['items'])) {
            $formattedData['items'] = $webhookData['items'];
        }

        // Add partner info if exists
        if (!empty($webhookData['partner']) && is_array($webhookData['partner'])) {
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
     * @return void
     */
    private function processLiveSessionRevenue(Order $order)
    {
        // Removed as we no longer need to handle order items
        return;
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
            Log::info('No notes to parse live session info from');
            return null;
        }

        Log::info('Parsing live session info from notes', ['notes' => $notes]);

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
                $liveSessionInfo = [
                    'live_number' => $liveNumber,
                    'session_date' => sprintf('%s-%02d-%02d', $year, $month, $day),
                    'original_text' => trim($matches[0])
                ];

                Log::info('Successfully parsed live session info', $liveSessionInfo);
                return $liveSessionInfo;
            } else {
                Log::warning('Invalid date in live session info', [
                    'day' => $day,
                    'month' => $month,
                    'year' => $year
                ]);
            }
        } else {
            Log::warning('Failed to match live session pattern in notes', ['notes' => $notes]);
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

            // Create/update customer directly, following the PancakeSyncController pattern
            $customer = $this->findOrCreateCustomer($orderData['customer'] ?? [], $orderData['shipping_address'] ?? []);

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

            // Map customer info from the saved customer object
            if ($customer) {
                $order->customer_id = $customer->id;
                $order->customer_name = $customer->name;
                $order->customer_phone = $customer->phone;
                $order->customer_email = $customer->email;
            }

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
                $order->ward_code = $shipping['ward_id'] ?? $shipping['commune_id'] ?? null;
                $order->street_address = $shipping['address'] ?? '';

                // Update related names if available
                $order->province_name = $shipping['province_name'] ?? null;
                $order->district_name = $shipping['district_name'] ?? null;
                $order->ward_name = $shipping['ward_name'] ?? null;
            }

            // Map shipping provider
            if (!empty($orderData['partner']['partner_id'])) {
                $providerId = $orderData['partner']['partner_id'];
                $provider = ShippingProvider::where('pancake_id', $providerId)
                    ->orWhere('pancake_partner_id', $providerId)
                    ->first();

                if ($provider) {
                    $order->shipping_provider_id = $provider->id;
                    $order->pancake_shipping_provider_id = $provider->pancake_id;
                } else {
                    // Lưu pancake_shipping_provider_id ngay cả khi không tìm thấy provider
                    $order->pancake_shipping_provider_id = $providerId;
                }
            }

            // Map financial info
            $order->pancake_inserted_at = !empty($orderData['inserted_at']) ? Carbon::parse($orderData['inserted_at'])->addHours(7) : null;
            $order->shipping_fee = (float)($orderData['shipping_fee'] ?? 0);
            $order->transfer_money = (float)($orderData['transfer_money'] ?? 0);
            $order->total_value = $this->calculateOrderTotal($orderData);

            // Map notes
            $order->notes = $orderData['note'] ?? null;
            $order->additional_notes = $orderData['additional_notes'] ?? null;

            // Map status
            $order->status = $this->mapPancakeStatus($orderData['status'] ?? 'new');
            $order->pancake_status = $orderData['status'] ?? 0;

            // Save products data - preserve exactly as received from Pancake
            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            // Lưu thông tin nhân viên seller và care
            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? null;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? null;

            // Save order
            $order->save();

            // Gửi tin nhắn ZNS khi trạng thái đơn hàng phù hợp
            if (in_array((int)$order->pancake_status, [1, 3])) {
                $this->sendZaloNotification($order, (int)$order->pancake_status);
            }

            // Process live session info if exists
            if (!empty($orderData['note'])) {
                $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
                if ($liveSessionInfo) {
                    $order->live_session_info = json_encode($liveSessionInfo);
                    $order->save();
                    if (isset($liveSessionInfo['live_number'], $liveSessionInfo['session_date'])) {
                        LiveSessionRevenue::recalculateStats($liveSessionInfo['session_date'], $liveSessionInfo['live_number']);
                    }
                }
            }

            // Cập nhật thông tin khách hàng
            if ($customer) {
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
            }

            DB::commit();
            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order from Pancake data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $oldStatus = $order->pancake_status;

            // Create/update customer directly, following the PancakeSyncController pattern
            $customer = $this->findOrCreateCustomer($orderData['customer'] ?? [], $orderData['shipping_address'] ?? []);

            if ($customer) {
                $order->customer_id = $customer->id;
                $order->customer_name = $customer->name;
                $order->customer_phone = $customer->phone;
                $order->customer_email = $customer->email;
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
            if (!empty($orderData['inserted_at'])) {
                $order->pancake_inserted_at = Carbon::parse($orderData['inserted_at'])->addHours(7);
            }

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
                $order->ward_code = $shipping['ward_id'] ?? $shipping['commune_id'] ?? $order->ward_code;
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

            // Update products data - preserve exactly as received from Pancake
            if (!empty($orderData['items'])) {
                $order->products_data = json_encode($orderData['items']);
            }

            // Cập nhật thông tin nhân viên seller và care
            $order->assigning_seller_id = $orderData['assigning_seller']['id'] ?? $order->assigning_seller_id;
            $order->assigning_care_id = $orderData['assigning_care']['id'] ?? $order->assigning_care_id;

            $order->save();

            // Gửi tin nhắn ZNS nếu trạng thái thay đổi
            $newStatus = (int) $order->pancake_status;
            // dd($newStatus);
            if ($oldStatus != $newStatus) {
                // dd(1);
                if ($newStatus === 1 || $newStatus === 3) {
                    Log::info('Order status changed, sending Zalo notification.', [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                    $this->sendZaloNotification($order, $newStatus);
                }
            }

            // Process live session info if exists
            if (!empty($orderData['note'])) {
                $liveSessionInfo = $this->parseLiveSessionInfo($orderData['note']);
                if ($liveSessionInfo) {
                    $order->live_session_info = json_encode($liveSessionInfo);
                    $order->save();
                    if (isset($liveSessionInfo['live_number'], $liveSessionInfo['session_date'])) {
                        LiveSessionRevenue::recalculateStats($liveSessionInfo['session_date'], $liveSessionInfo['live_number']);
                    }
                }
            }

            // Update customer stats
            if ($customer) {
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
            }


            DB::commit();
            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order from Pancake', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $order->id,
                'data' => $orderData
            ]);
            throw $e;
        }
    }

    /**
     * Find or create a customer, mirroring the logic from PancakeSyncController.
     * This method finds, creates/updates, and SAVES the customer directly.
     *
     * @param array $customerData From $orderData['customer']
     * @param array $shippingAddress From $orderData['shipping_address']
     * @return Customer|null
     */
    private function findOrCreateCustomer(array $customerData, array $shippingAddress = [])
    {
        $pancakeId = $customerData['id'] ?? null;
        // Pancake data can be inconsistent. Check multiple fields for phone and name.
        $phone = $customerData['phone'] ?? $customerData['phone_numbers'][0] ?? $shippingAddress['phone_number'] ?? null;
        $name = $customerData['name'] ?? $shippingAddress['full_name'] ?? 'Unknown';
        $email = $customerData['email'] ?? $customerData['emails'][0] ?? null;

        if (empty($pancakeId) && empty($phone)) {
            // Cannot reliably find or create a customer without a unique identifier.
            Log::warning('Cannot find or create customer without pancake_id or phone.', ['customer_data' => $customerData]);
            return null;
        }

        $customer = null;

        // Priority 1: Find by Pancake ID
        if ($pancakeId) {
            $customer = Customer::where('pancake_id', $pancakeId)->first();
        }

        // Priority 2: Find by Phone Number
        if (!$customer && $phone) {
            $customer = Customer::where('phone', $phone)->first();
        }

        // If not found, create a new customer
        if (!$customer) {
            $customer = new Customer();
        }

        // Update or populate fields, ensuring we don't null out existing data on updates.
        $customer->name = $name ?? $customer->name;
        $customer->phone = $phone ?? $customer->phone;
        $customer->email = $email ?? $customer->email;
        $customer->pancake_id = $pancakeId ?? $customer->pancake_id;
        $customer->pancake_customer_id = $customerData['code'] ?? $customer->pancake_customer_id;

        // Update address using the separate shippingAddress array for consistency.
        if (!empty($shippingAddress)) {
            $customer->province = $shippingAddress['province_id'] ?? $customer->province;
            $customer->district = $shippingAddress['district_id'] ?? $customer->district;
            $customer->ward = $shippingAddress['commune_id'] ?? $customer->ward;
            $customer->street_address = $shippingAddress['address'] ?? $customer->street_address;

            $addressParts = array_filter([$shippingAddress['address'] ?? null, $shippingAddress['ward_name'] ?? null, $shippingAddress['district_name'] ?? null, $shippingAddress['province_name'] ?? null]);
            $fullAddress = implode(', ', $addressParts);
            $customer->full_address = !empty($fullAddress) ? $fullAddress : ($shippingAddress['full_address'] ?? $customer->full_address);
        }

        $customer->save();

        return $customer;
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

    /**
     * Send Zalo Notification Service (ZNS) message via eSMS.
     *
     * @param Order $order The order object
     * @param int $status The new pancake_status of the order
     * @return void
     */
    private function sendZaloNotification(Order $order, int $status)
    {
        // Make sure to add these variables to your .env file:
        // ESMS_API_KEY=your_esms_api_key
        // ESMS_SECRET_KEY=your_esms_secret_key
        // ESMS_OAID=your_zalo_oaid
        // ESMS_ZNS_CONFIRM_TEMPLATE_ID=your_confirmation_template_id
        // ESMS_ZNS_DELIVERED_TEMPLATE_ID=your_delivered_template_id
        // dd(1);
        $apiKey = env('ESMS_API_KEY');
      
        $secretKey = env('ESMS_SECRET_KEY');
        $oaid = env('ESMS_OAID');

        if (!$apiKey || !$secretKey || !$oaid) {
            Log::error('eSMS ZNS credentials (API Key, Secret Key, or OAID) are not configured in .env file.');
            return;
        }

        $phone = $order->customer_phone;
        if (!$phone) {
            Log::warning('Cannot send ZNS. Order is missing a customer phone number.', ['order_id' => $order->id]);
            return;
        }

        $tempId = null;
        $tempData = [];
        $campaignId = '';

        if ($status === 1) { // Order Confirmed
            $tempId = env('ESMS_ZNS_CONFIRM_TEMPLATE_ID');
            if (!$tempId) {
                Log::error('eSMS ZNS Confirm Template ID is not configured in .env file.');
                return;
            }
            $tempData = [
                'customer_name' => $order->customer_name,
                'order_code' => $order->order_code,
                // Assuming template for "confirmed by..." has a {{shop_name}} variable.
                'shop_name' => $order->page_name ?? 'Cửa hàng của chúng tôi'
            ];
            $campaignId = 'CRM - Xac nhan don hang';

        } elseif ($status === 3) { // Order Delivered
            $tempId = env('ESMS_ZNS_DELIVERED_TEMPLATE_ID');
            if (!$tempId) {
                Log::error('eSMS ZNS Delivered Template ID is not configured in .env file.');
                return;
            }
            $tempData = [
                'customer_name' => $order->customer_name,
                'order_code' => $order->order_code,
            ];
            $campaignId = 'CRM - Giao hang thanh cong';
        } else {
            return; // Do nothing for other statuses
        }

        $payload = [
            'ApiKey' => $apiKey,
            'SecretKey' => $secretKey,
            'OAID' => $oaid,
            'Phone' => $phone,
            'TempID' => (string)$tempId,
            'TempData' => $tempData,
            'campaignid' => $campaignId,
            'RequestId' => (string) Str::uuid()
        ];

        // try {
            Log::info('Sending eSMS ZNS notification request.', [
                'order_id' => $order->id,
                'status' => $status,
                'payload' => $payload
            ]);

            $response = Http::post('https://rest.esms.vn/MainService.svc/json/SendZaloMessage_V6/', $payload);
            dd($response->json());
            Log::info('eSMS ZNS notification response received.', [
                'order_id' => $order->id,
                'status' => $status,
                'response_code' => $response->json('CodeResult'),
                'response_body' => $response->body()
            ]);

            if ($response->json('CodeResult') != '100') {
                Log::error('Failed to send eSMS ZNS notification (API error).', [
                    'order_id' => $order->id,
                    'status' => $status,
                    'response' => $response->json()
                ]);
            }
        // } catch (\Exception $e) {
        //     Log::error('Exception occurred while sending eSMS ZNS notification.', [
        //         'order_id' => $order->id,
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);
        //     throw $e;
        // }
    }
}