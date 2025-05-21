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
use Carbon\Carbon;
use App\Models\Warehouse;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Province;
use App\Models\District;
use App\Models\Ward;

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
 * Create a new order from Pancake data
 *
 * @param array $orderData
 * @return Order
 */
private function createOrderFromPancake(array $orderData)
{
    // Debug log the order data structure to help diagnose issues
    Log::info('Creating order from Pancake data structure', [
        'keys' => array_keys($orderData),
        'order_id' => $orderData['id'] ?? 'Unknown',
        'has_customer' => isset($orderData['customer']),
        'has_items' => isset($orderData['items']) ? count($orderData['items']) : 0,
        'has_line_items' => isset($orderData['line_items']) ? count($orderData['line_items']) : 0,
    ]);

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
        } else if (!$customer && !empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
            // Try with the first phone number in the array
            $customer = Customer::where('phone', $customerData['phone_numbers'][0])->first();
        }

        // If still not found, create new customer
        if (!$customer) {
            $customer = new Customer();
            $customer->name = $customerData['name'] ?? '';
            
            // Handle phone number(s)
            if (!empty($customerData['phone'])) {
                $customer->phone = $customerData['phone'];
            } elseif (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                $customer->phone = $customerData['phone_numbers'][0];
                
                // Store all phone numbers in a JSON field if available
                if (Schema::hasColumn('customers', 'phone_numbers')) {
                    $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                }
            }
            
            // Only set email if not empty to avoid unique constraint violation
            if (!empty($customerData['email'])) {
                $customer->email = $customerData['email'];
            } elseif (!empty($customerData['emails']) && is_array($customerData['emails'])) {
                if (!empty($customerData['emails'][0])) {
                    $customer->email = $customerData['emails'][0];
                }
                
                // Store all emails in a JSON field if available
                if (Schema::hasColumn('customers', 'emails')) {
                    $customer->emails = json_encode($customerData['emails']);
                }
            }
            
            $customer->pancake_id = $customerData['id'] ?? null;
            $customer->gender = $customerData['gender'] ?? null;
            $customer->date_of_birth = $customerData['date_of_birth'] ?? null;
            
            // Store additional customer fields if the columns exist
            if (Schema::hasColumn('customers', 'fb_id')) {
                $customer->fb_id = $customerData['fb_id'] ?? null;
            }
            
            if (Schema::hasColumn('customers', 'order_count')) {
                $customer->order_count = $customerData['order_count'] ?? 0;
            }
            
            if (Schema::hasColumn('customers', 'succeeded_order_count')) {
                $customer->succeeded_order_count = $customerData['succeed_order_count'] ?? 0;
            }
            
            if (Schema::hasColumn('customers', 'returned_order_count')) {
                $customer->returned_order_count = $customerData['returned_order_count'] ?? 0;
            }
            
            if (Schema::hasColumn('customers', 'purchased_amount')) {
                $customer->purchased_amount = $customerData['purchased_amount'] ?? 0;
            }
            
            if (Schema::hasColumn('customers', 'customer_level')) {
                $customer->customer_level = $customerData['level'] ?? null;
            }
            
            if (Schema::hasColumn('customers', 'tags') && !empty($customerData['tags'])) {
                $customer->tags = json_encode($customerData['tags']);
            }
            
            if (Schema::hasColumn('customers', 'conversation_tags') && !empty($customerData['conversation_tags'])) {
                $customer->conversation_tags = json_encode($customerData['conversation_tags']);
            }
            
            if (Schema::hasColumn('customers', 'reward_points')) {
                $customer->reward_points = $customerData['reward_point'] ?? 0;
            }

            // Parse address details
            $addressInfo = $this->parseAddress($customerData['address'] ?? null);
            $customer->full_address = $addressInfo['full_address'];
            $customer->province = $addressInfo['province'];
            $customer->district = $addressInfo['district'];
            $customer->ward = $addressInfo['ward'];
            $customer->street_address = $addressInfo['street_address'];
            
            // Save customer addresses if available
            if (Schema::hasColumn('customers', 'addresses') && !empty($customerData['shop_customer_addresses'])) {
                $customer->addresses = json_encode($customerData['shop_customer_addresses']);
            }

            $customer->save();

            Log::info('Created new customer', [
                'customer_id' => $customer->id,
                'pancake_id' => $customerData['id'] ?? null,
                'phone' => $customer->phone
            ]);
        } else {
            // Update existing customer with any new information
            $customer->pancake_id = $customerData['id'] ?? $customer->pancake_id;
            $customer->name = $customerData['name'] ?? $customer->name;
            
            // Update phone if available
            if (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                // Keep existing phone as primary if available
                if (empty($customer->phone)) {
                    $customer->phone = $customerData['phone_numbers'][0];
                }
                
                // Store all phone numbers in a JSON field if available
                if (Schema::hasColumn('customers', 'phone_numbers')) {
                    $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                }
            }
            
            // Only update email if not empty
            if (!empty($customerData['email'])) {
                $customer->email = $customerData['email'];
            } elseif (!empty($customerData['emails']) && is_array($customerData['emails'])) {
                if (!empty($customerData['emails'][0]) && empty($customer->email)) {
                    $customer->email = $customerData['emails'][0];
                }
                
                // Store all emails in a JSON field if available
                if (Schema::hasColumn('customers', 'emails')) {
                    $customer->emails = json_encode($customerData['emails']);
                }
            }
            
            // Update additional customer fields
            $customer->gender = $customerData['gender'] ?? $customer->gender;
            $customer->date_of_birth = $customerData['date_of_birth'] ?? $customer->date_of_birth;
            
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

            // Only update address if it's provided
            if (!empty($customerData['address'])) {
                $addressInfo = $this->parseAddress($customerData['address']);
                $customer->full_address = $addressInfo['full_address'];
                $customer->province = $addressInfo['province'];
                $customer->district = $addressInfo['district'];
                $customer->ward = $addressInfo['ward'];
                $customer->street_address = $addressInfo['street_address'];
            }
            
            // Update customer addresses if available
            if (Schema::hasColumn('customers', 'addresses') && !empty($customerData['shop_customer_addresses'])) {
                $customer->addresses = json_encode($customerData['shop_customer_addresses']);
            }

            $customer->save();

            Log::info('Updated existing customer', [
                'customer_id' => $customer->id,
                'pancake_id' => $customer->pancake_id
            ]);
        }
    } else if (isset($orderData['customer_name']) || isset($orderData['customer_phone']) || isset($orderData['customer_email'])) {
        // Handle flat customer data structure
        $phone = $orderData['customer_phone'] ?? null;

        // Try to find customer by phone
        if (!empty($phone)) {
            $customer = Customer::where('phone', $phone)->first();
        }

        // If not found, create new customer
        if (!$customer && !empty($phone)) {
            $customer = new Customer();
            $customer->name = $orderData['customer_name'] ?? '';
            $customer->phone = $phone;
            // Only set email if not empty
            if (!empty($orderData['customer_email'])) {
                $customer->email = $orderData['customer_email'];
            }
            $customer->save();

            Log::info('Created new customer from flat data', [
                'customer_id' => $customer->id,
                'phone' => $phone
            ]);
        }
    }

    // Find shop and page if they exist
    $shopId = null;
    $pageId = null;
    $saleId = null;

    if (!empty($orderData['shop_id'])) {
        $shop = PancakeShop::where('pancake_id', $orderData['shop_id'])->first();
        if ($shop) {
            $shopId = $shop->id;
        }
    }

    // Handle page information
    if (!empty($orderData['page'])) {
        $pageData = $orderData['page'];
        $page = PancakePage::where('pancake_id', $pageData['id'])->first();
        
        // Create page if doesn't exist
        if (!$page) {
            $page = new PancakePage();
            $page->pancake_id = $pageData['id'];
            $page->name = $pageData['name'];
            $page->username = $pageData['username'] ?? null;
            
            // Make sure to set the pancake_shop_table_id field
            if ($shopId) {
                $page->pancake_shop_table_id = $shopId;
            } else {
                // If no shop ID, try to find or create a default shop
                $defaultShop = PancakeShop::firstOrCreate(
                    ['name' => 'Default Shop'],
                    ['pancake_id' => $orderData['shop_id'] ?? '0', 'description' => 'Auto-created default shop']
                );
                $page->pancake_shop_table_id = $defaultShop->id;
            }
            
            $page->save();
            
            Log::info('Created new Pancake Page', [
                'page_id' => $page->id,
                'pancake_id' => $page->pancake_id,
                'name' => $page->name,
                'shop_id' => $page->pancake_shop_table_id
            ]);
        } else {
            // Update page info if needed
            $page->name = $pageData['name'] ?? $page->name;
            $page->username = $pageData['username'] ?? $page->username;
            
            // Update shop association if it's missing
            if (empty($page->pancake_shop_table_id) && $shopId) {
                $page->pancake_shop_table_id = $shopId;
            }
            
            $page->save();
        }
        
        $pageId = $page->id;
    }
    else if (!empty($orderData['page_id'])) {
        $page = PancakePage::where('pancake_id', $orderData['page_id'])->first();
        if ($page) {
            $pageId = $page->id;
        }
    }

    // Handle staff assignment
    if (!empty($orderData['user_id'])) {
        // Try to find user by pancake_user_id
        $staff = \App\Models\User::where('pancake_user_id', $orderData['user_id'])->first();

        if (!$staff && !empty($orderData['user_name'])) {
            // Try to find by name as fallback
            $staff = \App\Models\User::where('name', $orderData['user_name'])->first();
        }

        if ($staff) {
            $saleId = $staff->id;
            Log::info('Assigned existing staff member to order', [
                'staff_id' => $staff->id,
                'pancake_user_id' => $orderData['user_id'] ?? null
            ]);
        } elseif (!empty($orderData['user_id']) || !empty($orderData['user_name'])) {
            // Assign to default staff if configured
            $defaultStaffId = WebsiteSetting::where('key', 'default_staff_id')->first()->value ?? null;
            if ($defaultStaffId) {
                $saleId = $defaultStaffId;
                Log::info('Assigned default staff to order', [
                    'default_staff_id' => $defaultStaffId,
                    'pancake_user_id' => $orderData['user_id'] ?? null,
                    'pancake_user_name' => $orderData['user_name'] ?? null
                ]);
            } else {
                Log::warning('Could not find staff match and no default configured', [
                    'pancake_user_id' => $orderData['user_id'] ?? null,
                    'pancake_user_name' => $orderData['user_name'] ?? null
                ]);
            }
        }
    }

    // Handle creator/seller assignment
    $creatorId = null;
    if (!empty($orderData['creator']) && !empty($orderData['creator']['id'])) {
        // Try to find or create the creator/seller
        $creator = \App\Models\User::where('pancake_user_id', $orderData['creator']['id'])->first();
        
        if (!$creator) {
            // If no matching user found, store the creator info in a JSON field
            if (Schema::hasColumn('orders', 'creator_info')) {
                $creatorInfo = $orderData['creator'];
            } else {
                // Try to find by name as fallback
                if (!empty($orderData['creator']['name'])) {
                    $creator = \App\Models\User::where('name', $orderData['creator']['name'])->first();
                }
            }
        }
        
        if ($creator) {
            $creatorId = $creator->id;
        }
    }

    // Create new order
    $order = new Order();

    try {
        // Always store the original Pancake order ID - might be different from code
        if (!empty($orderData['id'])) {
            $order->pancake_order_id = $orderData['id'];
        }

        // Use code field as the order code, fallback to Pancake ID if available
        $orderCode = $orderData['code'] ?? null;
        if (empty($orderCode) && !empty($orderData['id'])) {
            $orderCode = 'PCK-' . $orderData['id'];
        } elseif (empty($orderCode)) {
            $orderCode = 'PCK-' . Str::random(8);
        }

        $order->order_code = $orderCode;

        // Extract customer information
        $customerName = '';
        $customerPhone = '';
        $customerEmail = '';

        if (!empty($orderData['customer'])) {
            $customerName = $orderData['customer']['name'] ?? '';
            
            if (!empty($orderData['customer']['phone'])) {
                $customerPhone = $orderData['customer']['phone'];
            } elseif (!empty($orderData['customer']['phone_numbers']) && is_array($orderData['customer']['phone_numbers'])) {
                $customerPhone = $orderData['customer']['phone_numbers'][0];
            }
            
            if (!empty($orderData['customer']['email'])) {
                $customerEmail = $orderData['customer']['email'];
            } elseif (!empty($orderData['customer']['emails']) && is_array($orderData['customer']['emails'])) {
                $customerEmail = $orderData['customer']['emails'][0] ?? '';
            }
        } else {
            // If no customer object, try direct fields
            $customerName = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? '');
            $customerPhone = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? '');
            $customerEmail = $orderData['customer_email'] ?? '';
        }

        $order->customer_name = $customerName ?: ($customer ? $customer->name : '');
        $order->customer_phone = $customerPhone ?: ($customer ? $customer->phone : '');
        // Handle NULL email values safely
        if (!empty($customerEmail)) {
            $order->customer_email = $customerEmail;
        } elseif ($customer && !empty($customer->email)) {
            $order->customer_email = $customer->email;
        }
        $order->customer_id = $customer ? $customer->id : null;
        
        // Map status
        $pancakeStatus = $orderData['status'] ?? 'moi';
        $order->status = $this->mapPancakeStatus($pancakeStatus);
        
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
        
        $order->internal_status = $orderData['internal_status'] ?? 'Imported from Pancake';
        $order->source = $orderData['source'] ?? ($orderData['order_sources_name'] ?? 'pancake');
        $order->shipping_fee = $orderData['shipping_fee'] ?? 0;
        $order->payment_method = $orderData['payment_method'] ?? 'cod';
        $order->total_value = $orderData['total'] ?? ($orderData['total_price'] ?? 0);
        $order->notes = $orderData['note'] ?? ($orderData['notes'] ?? '');
        $order->additional_notes = $orderData['additional_notes'] ?? '';

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

        // Handle order dates
        if (!empty($orderData['created_at'])) {
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

        // Address information - handle different address format possibilities
        $shippingAddress = $orderData['shipping_address'] ?? null;
        if ($shippingAddress) {
            $order->full_address = !empty($shippingAddress['full_address']) ? $this->sanitizeAddress($shippingAddress['full_address']) : '';
            $order->province_code = $shippingAddress['province_id'] ?? null;
            $order->district_code = $shippingAddress['district_id'] ?? null;
            $order->ward_code = $shippingAddress['commune_id'] ?? null;
            $order->street_address = !empty($shippingAddress['address']) ? $this->sanitizeAddress($shippingAddress['address']) : '';
            
            // Set address names if available
            $order->province_name = $shippingAddress['province_name'] ?? null;
            $order->district_name = $shippingAddress['district_name'] ?? null;
            $order->ward_name = $shippingAddress['ward_name'] ?? ($shippingAddress['commune_name'] ?? null);
            
            // Store the full shipping address info if the column exists
            if (Schema::hasColumn('orders', 'shipping_address_info')) {
                $order->shipping_address_info = json_encode($shippingAddress);
            }
        } else {
            // Fallback to concatenating address fields if no shipping_address object
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

            // Set related names if available
            $order->province_name = $orderData['province_name'] ?? null;
            $order->district_name = $orderData['district_name'] ?? null;
            $order->ward_name = $orderData['ward_name'] ?? null;
        }

        $order->pancake_shop_id = $shopId;
        $order->pancake_page_id = $pageId;
        $order->sale_id = $saleId; // Add staff/sale assignment
        $order->user_id = $saleId; // Set user_id same as sale_id for consistency
        $order->pancake_sale_id = $orderData['user_id'] ?? null; // Store original Pancake user ID
        $order->created_by = Auth::check() ? Auth::id() : ($creatorId ?? null);
        
        // Store creator/seller information
        if (isset($orderData['creator']) && Schema::hasColumn('orders', 'creator_info')) {
            $order->creator_info = json_encode($orderData['creator']);
        }
        
        if (isset($orderData['assigning_seller']) && Schema::hasColumn('orders', 'assigning_seller_info')) {
            $order->assigning_seller_info = json_encode($orderData['assigning_seller']);
        }

        // Store tracking info if available
        $order->tracking_code = $orderData['tracking_code'] ?? null;
        $order->tracking_url = $orderData['tracking_url'] ?? null;
        
        // Set checkboxes - use default values if not provided
        $order->is_free_shipping = $orderData['is_free_shipping'] ?? false;
        $order->is_livestream = $orderData['is_livestream'] ?? false;
        $order->is_live_shopping = $orderData['is_live_shopping'] ?? false;
        $order->customer_pay_fee = $orderData['customer_pay_fee'] ?? false;
        $order->partner_fee = $orderData['partner_fee'] ?? 0;
        $order->transfer_money = $orderData['transfer_money'] ?? 0;
        $order->returned_reason = $orderData['returned_reason'] ?? null;
        
        // Store status history if column exists
        if (Schema::hasColumn('orders', 'status_history') && !empty($orderData['status_history'])) {
            $order->status_history = json_encode($orderData['status_history']);
        }
        
        // Store partner info if column exists
        if (Schema::hasColumn('orders', 'partner_info') && !empty($orderData['partner'])) {
            $order->partner_info = json_encode($orderData['partner']);
        }
        
        // Store warehouse info if column exists
        if (Schema::hasColumn('orders', 'warehouse_info') && !empty($orderData['warehouse_info'])) {
            $order->warehouse_info = json_encode($orderData['warehouse_info']);
        }
        
        // Store history if column exists
        if (Schema::hasColumn('orders', 'order_history') && !empty($orderData['histories'])) {
            $order->order_history = json_encode($orderData['histories']);
        }

        // Log the creation with Pancake ID for tracking
        if (!empty($orderData['id'])) {
            Log::info('Creating new order from Pancake with ID', [
                'pancake_id' => $orderData['id'],
                'order_code' => $order->order_code
            ]);
        }

        $order->save();

        // Save Pancake warehouse and shipping data
        if (!empty($orderData['warehouse_id'])) {
            // First try to find warehouse by exact pancake_id match
            $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

            // If not found by pancake_id, try code as a fallback (some systems may use same value for both)
            if (!$warehouse) {
                $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
            }

            if ($warehouse) {
                $order->warehouse_id = $warehouse->id;
                $order->warehouse_code = $warehouse->code;
                $order->pancake_warehouse_id = $orderData['warehouse_id'];

                Log::info('Updated order warehouse mapping', [
                    'order_id' => $order->id,
                    'pancake_warehouse_id' => $orderData['warehouse_id'],
                    'local_warehouse_id' => $warehouse->id,
                    'local_warehouse_name' => $warehouse->name
                ]);
            } else {
                Log::warning('Update: Could not find matching warehouse for Pancake ID', [
                    'order_id' => $order->id,
                    'pancake_warehouse_id' => $orderData['warehouse_id']
                ]);
                
                // Set pancake_warehouse_id even if we can't find a local warehouse
                $order->pancake_warehouse_id = $orderData['warehouse_id'];
            }
        }

        // Handle shipping provider mapping
        $partner = null;
        if (!empty($orderData['partner']) && isset($orderData['partner']['partner_id'])) {
            $partner = $orderData['partner'];
            $partnerId = $partner['partner_id'];
            $shippingProvider = \App\Models\ShippingProvider::where('pancake_partner_id', $partnerId)
                ->orWhere('pancake_id', $partnerId)
                ->first();
            if ($shippingProvider) {
                $order->shipping_provider_id = $shippingProvider->id;
                $order->pancake_shipping_provider_id = $partnerId;
            }
        }
        
        // Update checkboxes and other settings
        if (isset($orderData['is_free_shipping'])) {
            $order->is_free_shipping = $orderData['is_free_shipping'];
        }
        if (isset($orderData['is_livestream'])) {
            $order->is_livestream = $orderData['is_livestream'];
        }
        if (isset($orderData['is_live_shopping'])) {
            $order->is_live_shopping = $orderData['is_live_shopping'];
        }
        if (isset($orderData['customer_pay_fee'])) {
            $order->customer_pay_fee = $orderData['customer_pay_fee'];
        }
        if (isset($orderData['partner_fee'])) {
            $order->partner_fee = $orderData['partner_fee'];
        }
        if (isset($orderData['transfer_money'])) {
            $order->transfer_money = $orderData['transfer_money'];
        }
        if (isset($orderData['returned_reason'])) {
            $order->returned_reason = $orderData['returned_reason'];
        }

        $order->save();

        // Create order items - first look for items in the expected format
        $hasItems = false;

        // Try 'items' field first - this is the new format with components
        if (!empty($orderData['items'])) {
            Log::info('Processing order items with components format', [
                'order_id' => $order->id, 
                'items_count' => count($orderData['items'])
            ]);
            
            foreach ($orderData['items'] as $item) {
                // Check if item has components structure
                if (!empty($item['components']) && is_array($item['components'])) {
                    // Each component might need to be a separate order item
                    foreach ($item['components'] as $component) {
                        $orderItem = new OrderItem();
                        $orderItem->order_id = $order->id;
                        $this->setOrderItemFields($orderItem, $item);  // Will handle component extraction
                        $orderItem->save();
                        
                        Log::info('Created order item from component structure', [
                            'order_id' => $order->id,
                            'product_name' => $orderItem->product_name,
                            'quantity' => $orderItem->quantity,
                            'price' => $orderItem->price
                        ]);
                        $hasItems = true;
                        
                        // We're only creating one order item per component set for now
                        // Can be modified if multiple items are needed per component
                        break;
                    }
                } else {
                    // Handle traditional item format
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $this->setOrderItemFields($orderItem, $item);
                    $orderItem->save();
                    
                    Log::info('Created order item from standard format', [
                        'order_id' => $order->id,
                        'product_name' => $orderItem->product_name,
                        'quantity' => $orderItem->quantity
                    ]);
                    $hasItems = true;
                }
            }
        }

        // Then try 'line_items' field if no items were found
        if (!$hasItems && !empty($orderData['line_items'])) {
            foreach ($orderData['line_items'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $this->setOrderItemFields($orderItem, $item);
                $orderItem->save();

                Log::info('Created order item from line_items', [
                    'order_id' => $order->id,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $orderItem->quantity
                ]);
                $hasItems = true;
            }
        }

        // If no items found in either standard place, check if there might be products array
        if (!$hasItems && !empty($orderData['products'])) {
            foreach ($orderData['products'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $this->setOrderItemFields($orderItem, $item);
                $orderItem->save();

                Log::info('Created order item from products array', [
                    'order_id' => $order->id,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $orderItem->quantity
                ]);
                $hasItems = true;
            }
        }

        // If still no items, create a placeholder if we have information about total price
        if (!$hasItems && $order->total_value > 0) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_name = 'Order from Pancake';
            $orderItem->name = 'Order from Pancake';
            $orderItem->quantity = 1;
            $orderItem->price = $order->total_value - ($order->shipping_fee ?? 0);
            
            // Create a basic product_info structure for consistency
            $itemData = [
                'name' => 'Order from Pancake',
                'product_name' => 'Order from Pancake',
                'quantity' => 1,
                'price' => $order->total_value - ($order->shipping_fee ?? 0),
                'is_placeholder' => true
            ];
            $orderItem->product_info = $itemData;
            
            $orderItem->save();

            Log::warning('Created placeholder order item due to missing product data', [
                'order_id' => $order->id,
                'total_value' => $order->total_value
            ]);
        }

        Log::info('Successfully created order from Pancake', [
            'order_id' => $order->id,
            'pancake_order_id' => $order->pancake_order_id,
            'order_code' => $order->order_code
        ]);
    } catch (\Exception $e) {
        Log::error('Error creating order from Pancake data', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'order_data' => json_encode(array_keys($orderData))
        ]);
        throw $e;
    }

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
        Log::info('Updating existing order from Pancake data', [
            'order_id' => $order->id,
            'pancake_order_id' => $orderData['id'] ?? 'N/A',
            'keys' => array_keys($orderData)
        ]);

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
        $order->source = $orderData['source'] ?? ($orderData['order_sources_name'] ?? $order->source);

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
            
            // Update address names
            $order->province_name = $shippingAddress['province_name'] ?? $order->province_name;
            $order->district_name = $shippingAddress['district_name'] ?? $order->district_name;
            $order->ward_name = $shippingAddress['ward_name'] ?? ($shippingAddress['commune_name'] ?? $order->ward_name);
            
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
            }
        }
        
        // Update customer information if provided
        if (!empty($orderData['customer'])) {
            $customerData = $orderData['customer'];
            $order->customer_name = $customerData['name'] ?? $order->customer_name;
            
            // Handle phone numbers
            if (!empty($customerData['phone'])) {
                $order->customer_phone = $customerData['phone'];
            } elseif (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                $order->customer_phone = $customerData['phone_numbers'][0];
            }
            
            // Handle email
            if (!empty($customerData['email'])) {
                $order->customer_email = $customerData['email'];
            } elseif (!empty($customerData['emails']) && is_array($customerData['emails'])) {
                if (!empty($customerData['emails'][0])) {
                    $order->customer_email = $customerData['emails'][0];
                }
            }

            // Update the associated customer if we can find them
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    // Update pancake_id if not already set
                    if (empty($customer->pancake_id) && !empty($customerData['id'])) {
                        $customer->pancake_id = $customerData['id'];
                    }
                    
                    $customer->name = $customerData['name'] ?? $customer->name;
                    
                    // Handle phone
                    if (!empty($customerData['phone'])) {
                        $customer->phone = $customerData['phone'];
                    } elseif (!empty($customerData['phone_numbers']) && is_array($customerData['phone_numbers'])) {
                        // Keep existing phone as primary
                        if (empty($customer->phone)) {
                            $customer->phone = $customerData['phone_numbers'][0];
                        }
                        
                        // Store all phone numbers
                        if (Schema::hasColumn('customers', 'phone_numbers')) {
                            $customer->phone_numbers = json_encode($customerData['phone_numbers']);
                        }
                    }
                    
                    // Handle email
                    if (!empty($customerData['email'])) {
                        $customer->email = $customerData['email'];
                    } elseif (!empty($customerData['emails']) && is_array($customerData['emails']) && empty($customer->email)) {
                        if (!empty($customerData['emails'][0])) {
                            $customer->email = $customerData['emails'][0];
                        }
                        
                        // Store all emails
                        if (Schema::hasColumn('customers', 'emails')) {
                            $customer->emails = json_encode($customerData['emails']);
                        }
                    }
                    
                    // Update other customer fields
                    $customer->gender = $customerData['gender'] ?? $customer->gender;
                    $customer->date_of_birth = $customerData['date_of_birth'] ?? $customer->date_of_birth;
                    
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
                    
                    // Update addresses if available
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
        } else if (isset($orderData['bill_full_name']) || isset($orderData['bill_phone_number']) || isset($orderData['customer_name']) || isset($orderData['customer_phone']) || isset($orderData['customer_email'])) {
            // Handle flat customer data structure
            $order->customer_name = $orderData['bill_full_name'] ?? ($orderData['customer_name'] ?? $order->customer_name);
            $order->customer_phone = $orderData['bill_phone_number'] ?? ($orderData['customer_phone'] ?? $order->customer_phone);
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
                $page->name = $pageData['name'];
                $page->username = $pageData['username'] ?? null;
                $page->save();
                
                Log::info('Created new Pancake Page during order update', [
                    'page_id' => $page->id,
                    'pancake_id' => $page->pancake_id,
                    'name' => $page->name
                ]);
            } else {
                // Update page info if needed
                $page->name = $pageData['name'] ?? $page->name;
                $page->username = $pageData['username'] ?? $page->username;
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
        
        // Update warehouse mapping if available
        if (!empty($orderData['warehouse_id'])) {
            // First try to find warehouse by exact pancake_id match
            $warehouse = Warehouse::where('pancake_id', $orderData['warehouse_id'])->first();

            // If not found by pancake_id, try code as a fallback 
            if (!$warehouse) {
                $warehouse = Warehouse::where('code', $orderData['warehouse_id'])->first();
            }

            if ($warehouse) {
                $order->warehouse_id = $warehouse->id;
                $order->warehouse_code = $warehouse->code;
                $order->pancake_warehouse_id = $orderData['warehouse_id'];
            } else {
                // Set pancake_warehouse_id even if we can't find a local warehouse
                $order->pancake_warehouse_id = $orderData['warehouse_id'];
            }
        }

        $order->save();

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
            $startTimestamp = $request->input('startDateTime');
           
            $endTimestamp=   ''.$request->input('endDateTime').'';
            $dateForCacheKey = null;
            $date = null;
            
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
                    
                    // Format date for API query
                    $startDate = $date->copy()->startOfDay();
                    $endDate = $date->copy()->endOfDay();
                    
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
            $endTimestamp_2 = (int)($request->input('endDateTime'));
            $endTimestamp = ''. $endTimestamp_2.'';
            $apiParams = [
                'api_key' => $apiKey,
                'page_number' => 1,
                'page_size' => 100,
                'startDateTime' => $startTimestamp,
                'endDateTime' => $endTimestamp
            ];
            
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
            $totalPages = ceil(($data['total'] ?? 0) / 100); // Match the page_size
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
                'next_page' => 2,
                'total_pages' => $totalPages,
                'total_entries' => $totalEntries,
                'sync_info' => $cacheKey,
                'stats' => [
                    'created' => $created,
                    'updated' => $updated,
                    'errors' => $errorMessages,
                    'errors_count' => count($errors)
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
                $apiParams['startDateTime'] = $syncInfo['startTimestamp'];
                $apiParams['endDateTime'] = $syncInfo['endTimestamp'];
            } elseif ($request->has('startDateTime') && $request->has('endDateTime')) {
                $apiParams['startDateTime'] = $request->input('startDateTime');
                $apiParams['endDateTime'] = $request->input('endDateTime');
            } elseif (isset($syncInfo['date'])) {
                // Create timestamps from date
                $date = Carbon::createFromFormat('Y-m-d', $syncInfo['date']);
                $apiParams['startDateTime'] = $date->copy()->startOfDay()->timestamp;
                $apiParams['endDateTime'] = $date->copy()->endOfDay()->timestamp;
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
                Cache::put($cacheKey, $syncInfo, now()->addHour());

                return response()->json([
                    'success' => true,
                    'message' => "Đồng bộ hoàn tất: Không có đơn hàng nào ở trang {$pageNumber}",
                    'stats' => [
                        'created' => 0,
                        'updated' => 0,
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

            // Get total pages from API response
            $totalPages = $responseData['total_pages'] ?? $syncInfo['total_pages'];

            // Fix issue where total_pages equals page_number
            // Make sure totalPages is always at least as large as the current page number
            if ($totalPages <= $pageNumber && $responseData['has_next'] !== false) {
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
            
            // Check if we've processed all pages
            $isLastPage = $pageNumber >= $totalPages || (isset($responseData['has_next']) && $responseData['has_next'] === false);
            $syncInfo['in_progress'] = !$isLastPage;

            Cache::put($cacheKey, $syncInfo, now()->addHour());

            // Calculate progress percentage
            $progress = min(100, round(($pageNumber / $totalPages) * 100));

            // Log sync progress
            Log::info("Completed sync page {$pageNumber}/{$totalPages}", [
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
                'progress' => $progress,
                'is_last_page' => $isLastPage
            ]);

            // Determine next page (if any)
            $nextPage = $isLastPage ? null : $pageNumber + 1;

            // Calculate total processed records
            $totalProcessed = $syncInfo['stats']['created'] + $syncInfo['stats']['updated'];
            
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
                    'total_created' => $syncInfo['stats']['created'],
                    'total_updated' => $syncInfo['stats']['updated'],
                    'errors' => $errorMessages,
                    'errors_count' => count($mergedErrors),
                    'current_page' => $pageNumber,
                    'total_pages' => $totalPages
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
            foreach ($dateCacheKeys as $cacheKey) {
                $syncInfo = Cache::get($cacheKey);
                if ($syncInfo) {
                    $activeSyncInfo = $syncInfo;
                    $activeCacheKey = $cacheKey;
                    break;
                }
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
                $syncDate = Carbon::createFromFormat('Y-m-d', $activeSyncInfo['date'])->format('d/m/Y');
            } catch (\Exception $e) {
                $syncDate = $activeSyncInfo['date']; // Use as-is if not a valid date
            }
        }
        
        if ($isInProgress) {
            $message = $syncDate ? 
                "Đang đồng bộ dữ liệu ngày {$syncDate}. Trang {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']}..." :
                "Đang đồng bộ. Trang {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']}...";
        } else if ($progress >= 100) {
            $message = 'Đồng bộ đã hoàn tất.';
        } else if ($activeSyncInfo['page'] > 0) {
            $message = "Đã xử lý {$activeSyncInfo['page']}/{$activeSyncInfo['total_pages']} trang. Đồng bộ tạm dừng.";
        }

        // Calculate elapsed time
        $elapsedTime = null;
        $startTime = null;
        
        if (!empty($activeSyncInfo['start_time'])) {
            try {
                $startTime = Carbon::parse($activeSyncInfo['start_time']);
                $elapsedTime = $startTime->diffForHumans(null, true);
            } catch (\Exception $e) {
                // Handle invalid date format
                $elapsedTime = 'không xác định';
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
        
        // Add detailed progress info about current processing
        $detailedProgress = [
            'current_page' => $activeSyncInfo['page'] ?? 0,
            'total_pages' => $activeSyncInfo['total_pages'] ?? 1,
            'page_progress' => $progress,
            'elapsed_time' => $elapsedTime,
            'start_time' => $activeSyncInfo['start_time'] ?? null,
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
            $this->authorize('sync-pancake');

            // First check if this is actually a date-specific sync that should be redirected
            if ($request->has('sync_type') && $request->input('sync_type') === 'date' && $request->has('date')) {
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

            // Only include date parameters if provided
            if ($startDateTime) {
                $apiParams['startDateTime'] = $startDateTime;
            }

            if ($endDateTime) {
                $apiParams['endDateTime'] = $endDateTime;
            }

            // Log API request
            Log::info('Starting Pancake API sync', [
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
            
            // Store sync progress in cache
            Cache::put($cacheKey, [
                'in_progress' => true,
                'start_time' => now(),
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
            Log::info("Completed first page sync 1/{$totalPages}", $statsFirstPage);
            
            // Continue if more pages exist
            $continueProcess = $totalPages > 1;
            $nextPage = $continueProcess ? 2 : null;

            return response()->json([
                'success' => true,
                'message' => "Đã đồng bộ trang 1/{$totalPages}. Tổng cộng {$totalEntries} đơn hàng.",
                'stats' => [
                    'created' => $statsFirstPage['created'],
                    'updated' => $statsFirstPage['updated'],
                    'skipped' => $statsFirstPage['skipped'],
                    'errors_count' => count($statsFirstPage['errors']),
                    'current_page' => 1,
                    'total_pages' => $totalPages
                ],
                'continue' => $continueProcess,
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
                    $orderItem->product_name = $variationInfo['name'] ?? $itemData['name'] ?? 'Unknown Product';
                    
                    // Get product code from variation_id
                    $orderItem->code = $component['variation_id'] ?? $itemData['code'] ?? null;
                    
                    // Get price from variation_info
                    $orderItem->price = $variationInfo['retail_price'] ?? $itemData['price'] ?? 0;
                    
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
        } else {
            // Handle traditional item format (fallback to original code)
            
            // Set product name
            $orderItem->product_name = $itemData['name'] ?? 'Unknown Product';

            // Set product code/sku based on available columns
            if (Schema::hasColumn('order_items', 'product_code')) {
                $orderItem->product_code = $itemData['sku'] ?? null;
            } else if (Schema::hasColumn('order_items', 'sku')) {
                $orderItem->sku = $itemData['sku'] ?? null;
            } else if (Schema::hasColumn('order_items', 'code')) {
                $orderItem->code = $itemData['sku'] ?? null;
            }

            // Set other common fields
            $orderItem->quantity = $itemData['quantity'] ?? 1;
            $orderItem->price = $itemData['price'] ?? 0;
            $orderItem->weight = $itemData['weight'] ?? 0;
            $orderItem->name = $itemData['name'] ?? $itemData['product_name'] ?? null;
            $orderItem->code = $itemData['code'] ?? $itemData['sku'] ?? null;

            // Set variant ID if available
            if (Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $orderItem->pancake_variant_id = $itemData['variant_id'] ?? $itemData['id'] ?? null;
            }
            
            if (Schema::hasColumn('order_items', 'pancake_product_id')) {
                $orderItem->pancake_product_id = $itemData['product_id'] ?? null;
            }
            
            if (Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $orderItem->pancake_variation_id = $itemData['variation_id'] ?? $itemData['sku'] ?? null;
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
}

