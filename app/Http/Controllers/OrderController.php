<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Call; // Import Call model
use App\Models\Province; // Added
use App\Models\District; // Added
use App\Models\Ward;     // Added
use App\Models\Warehouse;
use App\Models\ShippingProvider;
use App\Models\PancakeShop;         // Added for type hinting and usage
use App\Models\PancakePage;         // Added for getPancakePagesForShop method
use App\Models\PancakeOrderStatus; // Added for Pancake status handling
use App\Services\PancakeApiService;
use Illuminate\Http\Request;         // Ensure this is present for request handling
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Để log thông tin gọi API (ví dụ)
use Illuminate\Support\Facades\DB;
use App\Helpers\LogHelper;
use App\Models\CallLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule; // Added for Rule::in
use App\Models\OrderWarehouseView;
use Illuminate\Support\Str;
use App\Models\ActivityLog;
use App\Models\PancakeStaff; // Add this use statement at the top
use App\Http\Controllers\PancakeSyncController; // Ensure PancakeSyncController is imported

class OrderController extends Controller
{
    // Updated list of valid order statuses using constants from Order model
    protected $validStatuses = [
        Order::STATUS_MOI,
        Order::STATUS_CAN_XU_LY,
        Order::STATUS_CHO_HANG,
        Order::STATUS_DA_DAT_HANG,
        Order::STATUS_CHO_CHUYEN_HANG,
        Order::STATUS_DA_GUI_HANG,
        Order::STATUS_DA_NHAN,
        Order::STATUS_DA_NHAN_DOI,
        Order::STATUS_DA_THU_TIEN,
        Order::STATUS_DA_HOAN,
        Order::STATUS_DA_HUY,
        // Not including STATUS_XOA_GAN_DAY as a filterable status for active orders
    ];

    /**
     * Display a listing of the resource based on user role and filters.
     */
    public function index(Request $request)
    {
        $this->authorize('orders.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Order::query();

        $sales = collect();
        $selectedSale = $request->input('sale_id');
        $selectedStatus = $request->input('status');
        $searchTerm = $request->input('search_term');
        $selectedWarehouse = $request->input('warehouse_id');
        $paymentMethod = $request->input('payment_method');
        $selectedShippingProviderId = $request->input('shipping_provider_id');
        $minShippingFee = $request->input('min_shipping_fee');
        $maxShippingFee = $request->input('max_shipping_fee');
        $filterType = $request->input('filter_type');
        $pancakeStatusFilter = $request->input('status'); // Value from the Pancake Status dropdown
        $pancakeOrigin = $request->input('pancake_origin'); // New filter for Pancake-originated orders

        if ($user->hasRole('manager')) {
            $teamId = $user->manages_team_id;
            if ($teamId) {
                $teamMemberIds = User::where('team_id', $teamId)->pluck('id');
                $query->whereIn('user_id', $teamMemberIds);
                $sales = User::whereIn('id', $teamMemberIds)->pluck('name', 'id');
                if ($selectedSale && !$teamMemberIds->contains($selectedSale)) {
                     $selectedSale = null;
                }
            } else {
                 $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('staff')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            $sales = User::whereHas('roles', fn($q) => $q->whereIn('name', ['staff', 'manager']))->pluck('name', 'id');
        } else {
             $query->whereRaw('1 = 0');
        }

        if ($selectedSale && ($user->hasRole('admin') || $user->hasRole('super-admin') || $user->hasRole('manager'))) {
             $query->where('user_id', $selectedSale);
        }

        // Filter by Status
        if ($selectedStatus && in_array($selectedStatus, $this->validStatuses)) {
            $query->where('status', $selectedStatus);
        }

        // Filter by Warehouse
        if ($selectedWarehouse) {
            $query->where('warehouse_id', $selectedWarehouse);
        }

        // Filter by payment method
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        // Filter by shipping provider
        if ($selectedShippingProviderId) {
            $query->where('shipping_provider_id', $selectedShippingProviderId);
        }

        // Filter by shipping fee range
        if ($minShippingFee) {
            $query->where('shipping_fee', '>=', $minShippingFee);
        }
        if ($maxShippingFee) {
            $query->where('shipping_fee', '<=', $maxShippingFee);
        }

        // Filter by Pancake Push Status using the value from the 'status' dropdown
        if ($pancakeStatusFilter) {
            switch ($pancakeStatusFilter) {
                case 'success': // Corresponds to 'Đã đẩy OK'
                    $query->where('internal_status', 'Pushed to Pancake successfully.');
                    break;
                case 'not_successfully_pushed': // Corresponds to 'Chưa đẩy hoặc Lỗi'
                    $query->where(function($q_internal_status) {
                        $q_internal_status->whereNull('internal_status')
                                          ->orWhere('internal_status', '!=', 'Pushed to Pancake successfully.');
                    });
                    break;
                case 'failed_stock': // Corresponds to 'Lỗi Stock'
                    $query->where(function($q_internal_status) {
                        // Check for known Pancake error prefixes
                        $q_internal_status->where(function ($q_err_type) {
                            $q_err_type->where('internal_status', 'like', 'Pancake Push Error:%')
                                       ->orWhere('internal_status', 'like', 'Pancake Config Error:%');
                        })
                        // And check for stock-related keywords (case-insensitive)
                        ->where(function ($q_keywords) {
                            $q_keywords->whereRaw('LOWER(internal_status) LIKE ?', ['%stock%'])
                                       ->orWhereRaw('LOWER(internal_status) LIKE ?', ['%tồn kho%'])
                                       ->orWhereRaw('LOWER(internal_status) LIKE ?', ['%hết hàng%'])
                                       ->orWhereRaw('LOWER(internal_status) LIKE ?', ['%số lượng%']); // Catches "số lượng không đủ" etc.
                        });
                    });
                    break;
            }
        }

        // Filter by Pancake Origin
        if ($pancakeOrigin) {
            if ($pancakeOrigin === 'from_pancake') {
                $query->whereNotNull('pancake_order_id');
            } elseif ($pancakeOrigin === 'to_pancake') {
                $query->whereNull('pancake_order_id')
                      ->where(function($q) {
                          $q->where('pancake_push_status', 'pushed')
                            ->orWhere('internal_status', 'Pushed to Pancake successfully.');
                      });
            } elseif ($pancakeOrigin === 'not_synced') {
                $query->whereNull('pancake_order_id')
                      ->where(function($q) {
                          $q->whereNull('pancake_push_status')
                            ->orWhere('pancake_push_status', '!=', 'pushed');
                      })
                      ->where(function($q) {
                          $q->whereNull('internal_status')
                            ->orWhere('internal_status', '!=', 'Pushed to Pancake successfully.');
                      });
            }
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('customer_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('customer_phone', 'like', '%' . $searchTerm . '%');
            });
        }

        // Eager load relationships if needed
        $query->with(['user', 'items', 'warehouse', 'shippingProvider', 'activities.user']);

        $orders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('orders._order_table_body', [
                'orders' => $orders,
            ])->render();
        }

        // Get warehouses for filter dropdown
        $warehouses = Warehouse::orderBy('name')->pluck('name', 'id');
        $shippingProviders = ShippingProvider::orderBy('name')->pluck('name', 'id');

        return view('orders.index', [
            'orders' => $orders,
            'sales' => $sales,
            'statuses' => $this->validStatuses,
            'selectedSale' => $selectedSale,
            'selectedStatus' => $selectedStatus,
            'searchTerm' => $searchTerm,
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'paymentMethod' => $paymentMethod,
            'selectedShippingProviderId' => $selectedShippingProviderId,
            'minShippingFee' => $minShippingFee,
            'maxShippingFee' => $maxShippingFee,
            'shippingProviders' => $shippingProviders,
            'filterType' => $filterType,
            'pancakeOrigin' => $pancakeOrigin,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order, Request $request)
    {
        $this->authorize('orders.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('manager') && $order->user?->team_id != $user->manages_team_id) {
            if ($request->ajax()) {
                return response()->json(['error' => 'You do not have permission to view this order (different team).'], 403);
            }
            abort(403, 'You do not have permission to view this order (different team).');
        }
        if ($user->hasRole('staff') && $order->user_id != $user->id) {
            if ($request->ajax()) {
                return response()->json(['error' => 'You do not have permission to view this order (not assigned to you).'], 403);
            }
             abort(403, 'You do not have permission to view this order (not assigned to you).');
        }

        $order->load([
            'user',
            'items',
            'warehouse',
            'shippingProvider',
            'activities.user' // Removed shippingAddress and billingAddress if they are not defined relationships
        ]);

        if ($request->ajax()) {
            return view('orders._modal_details', compact('order'))->render();
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Simulate initiating a call.
     */
    public function initiateCall(Request $request, Order $order)
    {
        $this->authorize('calls.manage');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->hasRole('staff') && $order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to manage calls for this order.'], 403);
        }
        // Simulation logic
        Log::info("User {$user->id} ({$user->email}) initiating call for order {$order->id} to {$order->customer_phone}");
        $order->update(['status' => 'calling']);
        $simulatedDuration = rand(30, 300);
        $simulatedRecordingUrl = 'https://example.com/recordings/' . uniqid() . '.mp3';
        $call = Call::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'phone_number' => $order->customer_phone,
            'call_duration' => $simulatedDuration,
            'recording_url' => $simulatedRecordingUrl,
            'call_time' => now(),
        ]);
        LogHelper::log('initiate_call', $call, null, $call->toArray());
        return response()->json([
            'success' => true,
            'message' => 'Call initiated (simulation).',
            'order_id' => $order->id,
            'call_id' => $call->id,
            'recording_url' => $simulatedRecordingUrl,
        ]);
    }

    /**
     * Update order status after a call.
     */
    public function updateStatus(Request $request, Order $order)
    {
         $this->authorize('calls.manage');
         /** @var \App\Models\User $user */
         $user = Auth::user();

         // Additional check: Staff can only update status for their own assigned orders
         if ($user->hasRole('staff') && $order->user_id !== $user->id) {
             return redirect()->back()->with('error', 'Unauthorized to update status for this order.');
         }

        $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in($this->validStatuses)],
            'notes' => 'nullable|string',
            'call_id' => 'nullable|integer|exists:calls,id',
        ]);

        $old = $order->toArray();
        $newStatus = $request->input('status');
        $notes = $request->input('notes');
        $callId = $request->input('call_id');

        $order->update(['status' => $newStatus]);
        LogHelper::log('update_status', $order, $old, $order->toArray());

        if ($callId && $notes) {
            $call = Call::find($callId);
            // Ensure user owns the call record they are trying to update notes for
            if ($call && $call->user_id == $user->id) {
                $call->update(['notes' => $notes]);
            }
        }

        // Decide response type based on request (AJAX or form)
        if ($request->ajax()) {
             return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
        }

        return redirect()->route('orders.index')->with('success', 'Order status updated successfully.');
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        $this->authorize('orders.create');

        $order = new \stdClass();
        $order->id = '';
        $order->customer = null;
        $order->customer_name = '';
        $order->customer_phone = '';
        $order->customer_email = '';
        $order->bill_full_name = '';
        $order->bill_phone_number = '';
        $order->bill_email = '';
        $order->province_code = '';
        $order->district_code = '';
        $order->ward_code = '';
        $order->district_name = '';
        $order->ward_name = '';
        $order->street_address = '';
        $order->pancake_shop_id = '';
        $order->pancake_page_id = '';
        $order->is_livestream = 0;
        $order->is_live_shopping = 0;
        $order->order_code = '';
        $order->created_at = now();
        $order->assigning_seller_id = '';
        $order->assigning_care_id = '';
        $order->status = '';
        $order->orderItems = collect([]);
        $order->total_value = 0;
        $order->shipping_provider_id = '';
        $order->shipping_fee = 0;
        $order->is_free_shipping = 0;
        $order->payment_method = '';
        $order->discount_amount = 0;
        $order->partner_fee = 0;
        $order->customer_pay_fee = 0;
        $order->returned_reason = '';
        $order->warehouse_id = '';
        $order->transfer_money = '';
        $order->internal_status = '';
        $order->notes = '';
        $order->additional_notes = '';
        $order->pushed_to_pancake_at = null;
        $order->source = '';

        // Get all pancake pages with their shop_id for dynamic dropdown
        $allPancakePages = PancakePage::select('id', 'name', 'pancake_shop_table_id as shop_id')->get()->groupBy('shop_id');

        // Get active product sources
        $productSources = \App\Models\PancakeProductSource::where('is_active', true)
            ->orderBy('name')
            ->get();

        $data = [
            'order' => $order,
            'provinces' => Province::pluck('name', 'code'),
            'statuses' => Order::getAllStatuses(),
            'pancakeShops' => PancakeShop::pluck('name', 'id'),
            'allPancakePages' => $allPancakePages,
            'warehouses' => Warehouse::pluck('name', 'id'),
            'shippingProviders' => ShippingProvider::pluck('name', 'id'),
            'users' => User::whereNotNull('pancake_uuid')->orWhereNotNull('pancake_care_uuid')->get(),
            'productSources' => $productSources
        ];

        return view('orders.create', $data);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('orders.create');

        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'shipping_fee' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'shipping_provider_id' => 'nullable|exists:shipping_providers,id',
            'internal_status' => 'nullable|string',
            'notes' => 'nullable|string', // Internal note
            'additional_notes' => 'nullable|string', // Partner note
            'assigning_seller_id' => 'required|string', // Maps to user_id via pancake_uuid
            'assigning_care_id' => 'nullable|exists:users,id', // Care staff
            'marketer_id' => 'nullable|exists:users,id', // Marketer
            'warehouse_id' => 'required|exists:warehouses,id',
            'province_code' => 'nullable|string',
            'district_code' => 'nullable|string',
            'ward_code' => 'nullable|string',
            'street_address' => 'nullable|string',
            'full_address' => 'nullable|string',
            'pancake_shop_id' => 'nullable|exists:pancake_shops,id',
            'pancake_page_id' => 'nullable|exists:pancake_pages,id',
            'transfer_money' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.code' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.pancake_product_id' => 'nullable|string',
            'items.*.pancake_variation_id' => 'nullable|string',
            'items.*.image_url' => 'nullable|string|url',
            'billing_name' => 'nullable|string|max:255',
            'billing_phone' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email|max:255',
            'shipping_length' => 'nullable|numeric|min:0',
            'shipping_width' => 'nullable|numeric|min:0',
            'shipping_height' => 'nullable|numeric|min:0',
            'pancake_shop_id' => 'nullable|exists:pancake_shops,id',
            'source' => 'nullable|string|exists:pancake_product_sources,pancake_id',
        ]);

        // ---- START: Construct full_address -----
        $fullAddressParts = [];
        if (!empty($validatedData['street_address'])) {
            $fullAddressParts[] = $validatedData['street_address'];
        }
        if (!empty($validatedData['ward_code'])) {
            $ward = Ward::where('code', $validatedData['ward_code'])->first();
            if ($ward && !empty($ward->name)) {
                $fullAddressParts[] = $ward->name;
            }
        }
        if (!empty($validatedData['district_code'])) {
            $district = District::where('code', $validatedData['district_code'])->first();
            if ($district && !empty($district->name)) {
                $fullAddressParts[] = $district->name;
            }
        }
        if (!empty($validatedData['province_code'])) {
            $province = Province::where('code', $validatedData['province_code'])->first();
            if ($province && !empty($province->name)) {
                $fullAddressParts[] = $province->name;
            }
        }
        $fullAddress = implode(', ', array_filter($fullAddressParts));
        // ---- END: Construct full_address -----

        $user_id = User::where('pancake_uuid', $validatedData['assigning_seller_id'])->first()->id;
        // Get warehouse code and pancake_id
        $warehouse = Warehouse::findOrFail($validatedData['warehouse_id']);

        // Get shipping provider pancake_id if available
        $pancakeShippingProviderId = null;
        if (!empty($validatedData['shipping_provider_id'])) {
            $shippingProvider = ShippingProvider::find($validatedData['shipping_provider_id']);
            if ($shippingProvider && ($shippingProvider->pancake_id || $shippingProvider->pancake_partner_id)) {
                $pancakeShippingProviderId = $shippingProvider->pancake_partner_id ?? $shippingProvider->pancake_id;
            }
        }

        $totalValue = 0;
        $pancakeItemsPayload = []; // Chuẩn bị mảng để lưu các item theo cấu trúc Pancake

        foreach ($validatedData['items'] as $item) {
            $totalValue += $item['price'] * $item['quantity'];
            $pancakeItemsPayload[] = [
                // Các trường theo cấu trúc mới bạn cung cấp
                'id'                    => null, // ID dòng sản phẩm, sẽ null khi tạo mới từ CRM
                'product_id'            => $item['pancake_product_id'] ?? null,
                'variation_id'          => $item['pancake_variation_id'] ?? ($item['code'] ?? null),
                'quantity'              => (int)($item['quantity'] ?? 1),
                'added_to_cart_quantity'=> (int)($item['quantity'] ?? 1), // Mặc định bằng quantity
                'components'            => null,
                'composite_item_id'     => null,
                'discount_each_product' => 0,
                'exchange_count'        => 0,
                'is_bonus_product'      => false,
                'is_composite'          => null,
                'is_discount_percent'   => false,
                'is_wholesale'          => false,
                'measure_group_id'      => null,
                'note'                  => null,
                'one_time_product'      => false,
                'return_quantity'       => 0,
                'returned_count'        => 0,
                'returning_quantity'    => 0,
                'total_discount'        => 0,
                'variation_info'        => [
                    'barcode'           => $item['code'] ?? null,
                    'brand_id'          => null, // Cần lấy từ nguồn khác nếu có
                    'category_ids'      => [],   // Cần lấy từ nguồn khác nếu có
                    'detail'            => null,
                    'display_id'        => $item['code'] ?? null, // Hoặc một mã hiển thị khác
                    'exact_price'       => 0,    // Giá chính xác, có thể khác retail_price
                    'fields'            => null,
                    'last_imported_price' => 0,
                    'measure_info'      => null,
                    'name'              => $item['name'] ?? 'N/A',
                    'product_display_id'=> $item['code'] ?? null, // Hoặc một mã hiển thị khác
                    'retail_price'      => (float)($item['price'] ?? 0),
                    'weight'            => (int)($item['weight'] ?? 0), // Cân nặng tính bằng gram
                    // 'image_url'      => $item['image_url'] ?? null, // image_url không có trong variation_info của bạn
                ]
            ];
        }

        // Add shipping fee to total value
        $totalValue += $validatedData['shipping_fee'] ?? 0;

        $assigningSellerId = $validatedData['assigning_seller_id'];
        $assigningSellerName = null;
        if ($assigningSellerId) {
            $seller = User::where('pancake_uuid', $assigningSellerId)->pluck('name')->first();

            if ($seller) {
                $assigningSellerName = $seller;
            } else {
                // This case should ideally not happen due to 'exists' validation
                $assigningSellerId = null;
            }
        }

        $order = Order::create([
            'order_code' => $request->input('order_code', 'ORD-' . time() . rand(1000, 9999)),
            'customer_id' => $validatedData['customer_id'],
            'customer_name' => $validatedData['customer_name'],
            'customer_phone' => $validatedData['customer_phone'],
            'customer_email' => $validatedData['customer_email'] ?? null,


            // Billing information
            'bill_full_name' => $request->filled('billing_name') ? $validatedData['billing_name'] : $validatedData['customer_name'],
            'bill_phone_number' => $request->filled('billing_phone') ? $validatedData['billing_phone'] : $validatedData['customer_phone'],
            'bill_email' => $request->filled('billing_email') ? $validatedData['billing_email'] : ($validatedData['customer_email'] ?? null),

            // Shipping and payment information
            'shipping_fee' => $validatedData['shipping_fee'] ?? 0,
            'payment_method' => $validatedData['payment_method'] ?? null,
            'shipping_provider_id' => $validatedData['shipping_provider_id'] ?? null,
            'pancake_shipping_provider_id' => $pancakeShippingProviderId,

            // Status and notes
            'internal_status' => $validatedData['internal_status'] ?? 'new',
            'notes' => $validatedData['notes'] ?? null,
            'additional_notes' => $validatedData['additional_notes'] ?? null,
            'total_value' => $totalValue,
            'status' => $validatedData['status'] ?? 'new',

            // Assignment information
            'marketer_id' => $validatedData['marketer_id'] ?? null,
            'assigning_seller_name' => $assigningSellerName,
            'user_id' => $user_id,
            'created_by' => Auth::id(),

            // Address information
            'province_code' => $validatedData['province_code'] ?? null,
            'district_code' => $validatedData['district_code'] ?? null,
            'ward_code' => $validatedData['ward_code'] ?? null,
            'street_address' => $validatedData['street_address'] ?? null,
            'full_address' => $fullAddress,
            'shipping_address' => $fullAddress, // Add shipping_address field

            // Warehouse information
            'warehouse_id' => $validatedData['warehouse_id'],
            'warehouse_code' => $warehouse->code ?? null,
            'pancake_warehouse_id' => $warehouse->pancake_id ?? null,

            // Pancake related fields
            'pancake_shop_id' => $validatedData['pancake_shop_id'] ?? null,
            'pancake_page_id' => $validatedData['pancake_page_id'] ?? null,
            'pancake_order_id' => null,
            'pancake_push_status' => null,

            // Financial information
            'transfer_money' => $validatedData['transfer_money'] ?? 0,
            'partner_fee' => $request->input('partner_fee', 0),

            // Flags
            'is_free_shipping' => $request->has('is_free_shipping'),
            'is_livestream' => $request->has('is_livestream'),
            'is_live_shopping' => $request->has('is_live_shopping'),
            'customer_pay_fee' => $request->has('customer_pay_fee'),

            // Additional information
            'returned_reason' => $request->input('returned_reason'),
            'products_data' => json_encode($pancakeItemsPayload),

            // Package dimensions
            'shipping_length' => $validatedData['shipping_length'] ?? null,
            'shipping_width' => $validatedData['shipping_width'] ?? null,
            'shipping_height' => $validatedData['shipping_height'] ?? null,

            // Timestamps
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Log các giá trị để debug
        Log::info('Debug assigning values:', [
            'validatedData[assigning_seller_id]' => $validatedData['assigning_seller_id'] ?? 'not set',
            'assigningSellerId' => $assigningSellerId ?? 'not set',
            'validatedData[assigning_care_id]' => $validatedData['assigning_care_id'] ?? 'not set'
        ]);

        // Gán giá trị trực tiếp và save
        $order->assigning_seller_id = $validatedData['assigning_seller_id'] ?? $assigningSellerId;
        $order->assigning_care_id = $validatedData['assigning_care_id'] ?? $assigningSellerId;
        $order->source = $validatedData['source'] ?? null;
        $order->save();

        // Log giá trị sau khi save để kiểm tra
        Log::info('Order values after save:', [
            'order_id' => $order->id,
            'assigning_seller_id' => $order->assigning_seller_id,
            'assigning_care_id' => $order->assigning_care_id
        ]);

        // Create order items with all details
        foreach ($pancakeItemsPayload as $structuredItem) {
            $order->items()->create([
                'name' => $structuredItem['variation_info']['name'] ?? 'N/A', // Lấy tên từ variation_info
                'product_name' => $structuredItem['variation_info']['name'] ?? 'N/A', // Có thể dùng chung
                'code' => $structuredItem['variation_info']['barcode'] ?? ($structuredItem['variation_info']['display_id'] ?? null), // Ưu tiên barcode, fallback display_id
                'product_code' => $structuredItem['variation_info']['barcode'] ?? ($structuredItem['variation_info']['product_display_id'] ?? null), // Ưu tiên barcode, fallback product_display_id
                'quantity' => $structuredItem['quantity'],
                'price' => $structuredItem['variation_info']['retail_price'] ?? 0, // Lấy giá từ variation_info
                'weight' => $structuredItem['variation_info']['weight'] ?? 0, // Lấy cân nặng từ variation_info
                'pancake_product_id' => $structuredItem['product_id'], // ID sản phẩm gốc từ Pancake
                'pancake_variation_id' => $structuredItem['variation_id'], // ID biến thể từ Pancake
                'product_info' => $structuredItem, // Store the Pancake-structured item data
            ]);
        }

        // Sau khi tạo xong, chuyển hướng sang trang sửa đơn hàng
        return redirect()->route('orders.edit', $order->id)->with('success', 'Đơn hàng đã được tạo thành công.');
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        // Get all pancake pages with their shop_id for dynamic dropdown
        $allPancakePages = PancakePage::select('id', 'name', 'pancake_shop_table_id as shop_id')->get()->groupBy('shop_id');

        // Get active product sources
        $productSources = \App\Models\PancakeProductSource::where('is_active', true)
            ->orderBy('name')
            ->get();

        $data = [
            'order' => $order,
            'provinces' => Province::pluck('name', 'code'),
            'districts' => $order->province_code ? District::where('province_code', $order->province_code)->pluck('name', 'code') : collect(),
            'wards' => $order->district_code ? Ward::where('district_code', $order->district_code)->pluck('name', 'code') : collect(),
            'statuses' => Order::getAllStatuses(),
            'pancakeShops' => PancakeShop::pluck('name', 'id'),
            'pancakePages' => $order->pancake_shop_id ? PancakePage::where('pancake_shop_table_id', $order->pancake_shop_id)->get() : collect(), // Current pages for selected shop
            'allPancakePages' => $allPancakePages, // Pass all pages grouped by shop_id
            'warehouses' => Warehouse::pluck('name', 'id'),
            'shippingProviders' => ShippingProvider::pluck('name', 'id'),
            'users' => User::whereNotNull('pancake_uuid')->orWhereNotNull('pancake_care_uuid')->get(),
            'productSources' => $productSources
        ];

        return view('orders.edit', $data);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('orders.edit');

        // Ensure customer_phone is available for validation
        if ($request->filled('billing_phone') && !$request->filled('customer_phone')) {
            $request->merge(['customer_phone' => $request->input('billing_phone')]);
        }

        // Nếu không có customer_phone trong request, lấy từ order hiện tại
        if (!$request->filled('customer_phone') && $order->customer_phone) {
            $request->merge(['customer_phone' => $order->customer_phone]);
        }

        // START CUSTOMER RESOLUTION LOGIC
        $customerPhoneToUse = $request->input('customer_phone');

        // Tìm customer dựa trên số điện thoại
        if ($customerPhoneToUse) {
            $customer = \App\Models\Customer::where('phone', $customerPhoneToUse)->first();

            // Nếu tìm thấy customer, merge customer_id vào request
            if ($customer) {
                $request->merge(['customer_id' => $customer->id]);
            } else {
                // Nếu không tìm thấy customer, tạo mới
                $customer = \App\Models\Customer::create([
                    'phone' => $customerPhoneToUse,
                    'name' => $request->input('customer_name', $order->customer_name),
                    'email' => $request->input('customer_email', $order->customer_email)
                ]);
                $request->merge(['customer_id' => $customer->id]);
            }
        } else {
            // Nếu không có số điện thoại trong request, sử dụng customer_id từ order hiện tại
            if ($order->customer_id) {
                $request->merge(['customer_id' => $order->customer_id]);
            }
        }

        // Build pancakeItemsPayload first
        $pancakeItemsPayload = [];
        $totalValue = 0;

        foreach ($request->input('items', []) as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $totalValue += $itemTotal;

            $pancakeItemsPayload[] = [
                'id' => null,
                'product_id' => $item['pancake_product_id'] ?? null,
                'variation_id' => $item['pancake_variation_id'] ?? ($item['code'] ?? null),
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
                    'barcode' => $item['code'] ?? null,
                    'brand_id' => null,
                    'category_ids' => [],
                    'detail' => null,
                    'display_id' => $item['code'] ?? null,
                    'exact_price' => 0,
                    'fields' => null,
                    'last_imported_price' => 0,
                    'measure_info' => null,
                    'name' => $item['name'] ?? 'N/A',
                    'product_display_id' => $item['code'] ?? null,
                    'retail_price' => (float)($item['price'] ?? 0),
                    'weight' => (int)($item['weight'] ?? 0),
                ]
            ];
        }

        // Merge products_data into request before validation
        $request->merge(['products_data' => $pancakeItemsPayload]);

        // Validation with complete rules
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'items' => 'required|array|min:1',
            'items.*.code' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.pancake_product_id' => 'nullable|string',
            'items.*.pancake_variation_id' => 'nullable|string',
            'shipping_fee' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'shipping_provider_id' => 'nullable|exists:shipping_providers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'pancake_shop_id' => 'nullable|exists:pancake_shops,id',
            'pancake_page_id' => 'nullable|exists:pancake_pages,id',
            'status' => ['nullable', Rule::in(Order::getAllStatuses())],
            'products_data' => 'required|array|min:1',
        ]);

        // Lưu old data trước khi update
        $oldData = $order->toArray();
        $oldItems = $order->items->toArray();
        $oldData['items'] = $oldItems;

        try {
            DB::beginTransaction();

            // Update order fields
            $orderDataToFill = $request->except(['items', 'products_data', 'assigning_seller_id', 'status', 'tags', 'status_id', 'billing_name', 'billing_phone', 'billing_email']);

            // Default billing information if not provided
            $orderDataToFill['billing_name'] = $request->filled('billing_name') ? $request->billing_name : $validatedData['customer_name'];
            $orderDataToFill['billing_phone'] = $request->filled('billing_phone') ? $request->billing_phone : $validatedData['customer_phone'];
            $orderDataToFill['billing_email'] = $request->filled('billing_email') ? $request->billing_email : ($validatedData['customer_email'] ?? null);

            $order->fill($orderDataToFill);
            $order->save();

            // Handle assigning_seller_id to user_id mapping
            if (isset($validatedData['assigning_seller_id']) && !empty($validatedData['assigning_seller_id'])) {
                $sellerUser = User::where('pancake_uuid', $validatedData['assigning_seller_id'])->first();
                if ($sellerUser) {
                    $order->user_id = $sellerUser->id;
                    $order->assigning_seller_id = $validatedData['assigning_seller_id'];
                    $order->assigning_seller_name = $sellerUser->name;
                    $order->save();
                }
            }

            $order->source = $validatedData['source'] ?? null;
            $order->save();

            // Delete existing items
            $order->items()->delete();

            // Process items and create new ones
            foreach ($validatedData['items'] as $item) {
                $order->items()->create([
                    'name' => $item['name'],
                    'product_name' => $item['name'],
                    'code' => $item['code'],
                    'product_code' => $item['code'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'weight' => $item['weight'] ?? 0,
                    'pancake_product_id' => $item['pancake_product_id'] ?? null,
                    'pancake_variation_id' => $item['pancake_variation_id'] ?? null,
                ]);
            }

            // Add shipping fee to total value
            $totalValue += $validatedData['shipping_fee'] ?? 0;

            // Update order's total value and products_data
            $order->total_value = $totalValue;
            $order->products_data = json_encode($pancakeItemsPayload);
            $order->save();

            // Log the changes
            LogHelper::log('update', $order, $oldData, $order->fresh()->toArray());

            DB::commit();

            return redirect()->route('orders.index')
                ->with('success', 'Đơn hàng đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật đơn hàng: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        $this->authorize('orders.delete');
        $old = $order->toArray();
        $order->delete();
        LogHelper::log('delete', $order, $old, null);
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }

    /**
     * Show the form for assigning the order to another user.
     */
    public function assign(Order $order)
    {
        $this->authorize('teams.assign');
        $assignableUsersList = User::whereNotNull('pancake_uuid')
                                    ->where('pancake_uuid', '!=', '')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
        return view('orders.assign', compact('order', 'assignableUsersList'));
    }

    /**
     * Hiển thị danh sách đơn hàng đã xóa (thùng rác)
     */
    public function trashed()
    {
        $orders = \App\Models\Order::onlyTrashed()->paginate(15);
        return view('orders.trashed', compact('orders'));
    }

    /**
     * Lưu log cuộc gọi từ popup giả lập.
     */
    public function logCall(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Cách mới/khuyến nghị
        if (!$user) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $order = Order::find($request->order_id);
        $call = \App\Models\Call::create([
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'customer_name' => $order ? $order->customer_name : '',
            'phone_number' => $request->phone_number,
            'call_duration' => $request->call_duration,
            'call_time' => now(),
            'recording_url' => null,
        ]);
        LogHelper::log('call', $call, null, $call->toArray());
        return response()->json(['success' => true, 'call_id' => $call->id]);
    }

    /**
     * Update the assignment of the specified order.
     */
    public function updateAssignment(Request $request, Order $order)
    {
        // Sử dụng quyền 'teams.assign' thay vì 'orders.edit'
        $this->authorize('teams.assign');

        // Validate assigning_seller_id against users table
        $validated = $request->validate([
            'assigning_seller_id' => 'required|exists:users,id',
        ]);

        // Lấy dữ liệu cũ để log
        $old = $order->toArray();

        $assigningSellerId = $validated['assigning_seller_id'];
        $assigningSellerName = null;
        $seller = User::find($assigningSellerId);

        if ($seller) {
            $assigningSellerName = $seller->name;
        } else {
            // This case should ideally not happen due to 'exists:users,id' validation
            return redirect()->route('orders.index')->with('error', 'Không tìm thấy nhân viên đã chọn.');
        }

        $updateData = [
            'assigning_seller_id' => $assigningSellerId,
            'assigning_seller_name' => $assigningSellerName,
        ];

        $order->update($updateData);

        // Ghi log thay đổi assignment
        LogHelper::log('assign_seller', $order, $old, $order->fresh()->toArray());

        // Trả về trang danh sách với thông báo thành công
        return redirect()->route('orders.index')->with('success', 'Đã gán nhân viên sale cho đơn hàng thành công!');
    }

    // Add the new method here
    public function fetchVoipHistory(Request $request, Order $order)
    {
        // Sử dụng key trực tiếp theo yêu cầu (LƯU Ý: KHÔNG AN TOÀN CHO PRODUCTION)
        $apiKey = "86ebf05ccb501545971c4b5a391c459d69e637f0";
        $apiSecret = "47bb8c9514278af82c18c4173fda14ad94a60c78";

        Log::info('Attempting Voip24h Authentication with hardcoded keys.');

        // 1. Authenticate with Voip24h
        $authResponse = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post('https://api.voip24h.vn/v3/authentication', [
                'apiKey' => $apiKey,
                'apiSecret' => $apiSecret,
                'isLonglive' => 'true',
            ]);

        // Log the full response from authentication API for detailed debugging
        Log::info('Voip24h Authentication API Response Status:', ['status' => $authResponse->status()]);
        Log::info('Voip24h Authentication API Response Body:', ['body' => $authResponse->body()]);
        $authData = $authResponse->json();
        Log::info('Voip24h Authentication API Response JSON (as array/object):', ['json_data' => $authData]);

        if (!$authResponse->successful()) {
            Log::error('Voip24h Authentication request returned HTTP error.', ['status' => $authResponse->status(), 'response_body' => $authResponse->body()]);
            return response()->json($authData ?? ['error' => 'HTTP request failed', 'status_code' => $authResponse->status(), 'raw_body' => $authResponse->body()], $authResponse->status());
        }

        $accessToken = $authData['data']['token'];
        Log::info('Voip24h Authentication successful. Token extracted.');

        // ----- KHÔI PHỤC PHẦN LẤY LỊCH SỬ CUỘC GỌI -----
        // 2. Fetch Call History from Voip24h
        $dateStart = $order->created_at->subDays(7)->format('Y-m-d');
        $dateEnd = now()->format('Y-m-d');
        $customerPhone = $order->customer_phone;

        // Sử dụng callee và caller theo thay đổi của bạn
        $historyParams = [
            'callee' => $customerPhone,
            'limit' => 50,
            'offset' => 0,
        ];

        Log::info('Attempting to fetch Voip24h call history with params: ', $historyParams);
        $historyResponse = Http::withHeaders(['Authorization' => $accessToken])
            ->get('https://api.voip24h.vn/v3/call/history', $historyParams);

        Log::info('Voip24h Call History API Response Status:', ['status' => $historyResponse->status()]);
        Log::info('Voip24h Call History API Response Body:', ['body' => $historyResponse->body()]);
        $historyData = $historyResponse->json();
        Log::info('Voip24h Call History API Response JSON (as array/object):', ['json_data' => $historyData]);

        if (!$historyResponse->successful()) {
            Log::error('Failed to fetch Voip24h call history (HTTP Error).', ['status' => $historyResponse->status(), 'response_body' => $historyResponse->body()]);
            return response()->json($historyData ?? ['error' => 'Failed to fetch call history', 'status_code' => $historyResponse->status(), 'raw_body' => $historyResponse->body()], $historyResponse->status());
        }

        // ----- KHÔI PHỤC PHẦN XỬ LÝ DATA LỊCH SỬ VÀ LƯU DB -----
        // Kiểm tra cấu trúc phản hồi: $historyData['data'] là mảng các cuộc gọi
        if (!isset($historyData['status']) || $historyData['status'] != 200 || !isset($historyData['data']) || !is_array($historyData['data'])) {
            Log::error('Voip24h call history response error or unexpected structure (expected data as an array).', ['response' => $historyData]);
            $histErrorMessage = $historyData['message'] ?? 'Lỗi khi lấy lịch sử cuộc gọi từ Voip24h hoặc cấu trúc dữ liệu không đúng (data is not an array or missing).';
            return response()->json(['success' => false, 'message' => $histErrorMessage, 'voip_response' => $historyData], 500);
        }

        $voipCalls = $historyData['data']; // Lấy danh sách cuộc gọi trực tiếp từ 'data' theo yêu cầu
        $newCallsCount = 0;

        // Nếu $voipCalls không phải là một mảng (ví dụ API trả về lỗi nhưng status vẫn 200 và có key 'data' nhưng không phải mảng)
        // điều này thực ra đã được kiểm tra bởi is_array($historyData['data']) ở trên.
        // Tuy nhiên, để chắc chắn hơn trước khi lặp:
        if (!is_array($voipCalls)) {
            Log::error('Voip24h $voipCalls is not an array after assignment.', ['voip_calls_type' => gettype($voipCalls), 'history_data' => $historyData]);
            return response()->json(['success' => false, 'message' => 'Dữ liệu cuộc gọi nhận được không phải là một danh sách hợp lệ.', 'voip_response' => $historyData], 500);
        }

        if (empty($voipCalls)) {
             return response()->json(['success' => true, 'message' => 'Không tìm thấy cuộc gọi mới nào từ Voip24h cho số ' . $customerPhone . '.', 'html' => '<tr><td colspan="5" class="text-center">Không tìm thấy cuộc gọi mới nào.</td></tr>']);
        }

        foreach ($voipCalls as $voipCall) {
            if (!isset($voipCall['id'])) {
                Log::warning('Voip24h call data missing ID, skipping.', ['call_data' => $voipCall]);
                continue;
            }
            $existingCall = CallLog::where('voip_call_id', $voipCall['id'])->first();
            if ($existingCall) {
                continue;
            }

            $recordingUrl = null;
            // Priority 1: Use recordingFile from history if it's a full URL
            if (!empty($voipCall['recordingFile']) && filter_var($voipCall['recordingFile'], FILTER_VALIDATE_URL)) {
                $recordingUrl = $voipCall['recordingFile'];
                Log::info("Using recordingFile directly from history for voip_call_id: {$voipCall['id']}", ['url' => $recordingUrl]);
            }
            // Priority 2: If no full URL from history, and we have a call ID (voipCall['id']), try fetching from recording endpoint
            else if (!empty($voipCall['id'])) {
                Log::info("recordingFile from history is not a full URL or is empty for voip_call_id: {$voipCall['id']}. Attempting to fetch from /v3/call/recording.");
                try {
                    $directRecordingApiResponse = Http::withHeaders([
                        'Authorization' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])->get('https://api.voip24h.vn/v3/call/recording', [
                        'callId' => $voipCall['id'],
                    ]);

                    if ($directRecordingApiResponse->successful()) {
                        $directRecordingData = $directRecordingApiResponse->json();
                        if (isset($directRecordingData['status']) && $directRecordingData['status'] == 200 && !empty($directRecordingData['data']['media']['wav'])) {
                            $recordingUrl = $directRecordingData['data']['media']['wav'];
                            Log::info("Successfully fetched recording URL during initial processing for voip_call_id: {$voipCall['id']}", ['url' => $recordingUrl]);
                        } else {
                            Log::warning("Voip24h Recording API (called during initial processing) did not return a valid recording URL for voip_call_id {$voipCall['id']}. Message: " . ($directRecordingData['message'] ?? 'N/A'));
                        }
                    } else {
                        Log::error("Failed to fetch recording from Voip24h (called during initial processing) for voip_call_id {$voipCall['id']}. Status: " . $directRecordingApiResponse->status(), [
                            'response_body' => $directRecordingApiResponse->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Exception while fetching recording (during initial processing) for voip_call_id {$voipCall['id']}: " . $e->getMessage());
                }
            }
            // Fallback: If recordingFile was just a name and no base URL is configured, or fetch failed.
            else if (!empty($voipCall['recordingFile'])) { // This means it was not a URL and voipCall['id'] was empty for direct fetch
                 Log::warning('recordingFile present but it is not a full URL, and voip_call_id was missing for direct fetch attempt. recordingFile: ' . $voipCall['recordingFile']);
            }

            try {
                CallLog::create([
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'voip_call_id' => $voipCall['id'],
                    'sip_extension' => $voipCall['extension'] ?? ($voipCall['caller'] ?? null),
                    'caller_number' => $voipCall['caller'] ?? null,
                    'destination_number' => $voipCall['callee'] ?? $customerPhone,
                    'call_status' => $voipCall['status'] ?? 'UNKNOWN',
                    'call_type' => $voipCall['type'] ?? null,
                    'start_time' => isset($voipCall['callDate']) ? new \DateTime($voipCall['callDate']) : null,
                    'duration_seconds' => $voipCall['billsec'] ?? ($voipCall['duration'] ?? 0),
                    'recording_url' => $recordingUrl,
                    'raw_voip_data' => $voipCall, // Lưu toàn bộ dữ liệu cuộc gọi từ API
                ]);
                $newCallsCount++;
            } catch (\Exception $e) {
                Log::error('Error creating CallLog entry: ' . $e->getMessage(), ['voip_call_data' => $voipCall, 'exception' => $e]);
            }
        }

        // === NEW LOGIC: Fetch and Update Recording URLs for calls missing them ===
        Log::info("Starting process to update missing recording URLs for order ID: {$order->id}");
        $callsMissingRecording = CallLog::where('order_id', $order->id)
                                        ->whereNotNull('voip_call_id')
                                        ->where(function ($query) {
                                            $query->whereNull('recording_url')
                                                  ->orWhere('recording_url', ''); // Also check for empty strings
                                        })
                                        ->get();

        $recordingsUpdatedCount = 0;
        $recordingsFailedCount = 0;
        $recordingUpdateMessages = [];

        if ($callsMissingRecording->isNotEmpty()) {

            Log::info("Found {$callsMissingRecording->count()} calls for order ID: {$order->id} that need recording URL check.");
            foreach ($callsMissingRecording as $callToUpdate) {
                if (empty(trim($callToUpdate->voip_call_id))) {
                    Log::warning("Skipping CallLog ID {$callToUpdate->id} due to empty voip_call_id for order {$order->id}.");
                    continue;
                }

                try {
                    Log::info("Fetching recording for CallLog ID: {$callToUpdate->id}, voip_call_id: {$callToUpdate->voip_call_id}");

                    $recordingApiResponse = Http::withHeaders([
                        'Authorization' => $accessToken, // Use the token obtained earlier
                        'Content-Type' => 'application/json',
                    ])->get('https://api.voip24h.vn/v3/call/recording', [
                        'callId' => $callToUpdate->voip_call_id,
                    ]);

                    Log::info("Voip24h Recording API Response Status for voip_call_id {$callToUpdate->voip_call_id}:", ['status' => $recordingApiResponse->status()]);
                    Log::info("Voip24h Recording API Response Body for voip_call_id {$callToUpdate->voip_call_id}:", ['body' => $recordingApiResponse->body()]);

                    if ($recordingApiResponse->successful()) {
                        $recordingData = $recordingApiResponse->json();
                        if (isset($recordingData['status']) && $recordingData['status'] == 200 && !empty($recordingData['data']['media']['wav'])) {
                            $callToUpdate->recording_url = $recordingData['data']['media']['wav'];
                            $callToUpdate->save();
                            $recordingsUpdatedCount++;
                            Log::info("Successfully updated recording_url for CallLog ID: {$callToUpdate->id}, voip_call_id: {$callToUpdate->voip_call_id}");
                        } else {
                            $recordingsFailedCount++;
                            Log::warning("Voip24h Recording API did not return a valid recording URL for voip_call_id {$callToUpdate->voip_call_id} (CallLog ID: {$callToUpdate->id}). Message: " . ($recordingData['message'] ?? 'N/A'), ['response' => $recordingData]);
                        }
                    } else {
                        $recordingsFailedCount++;
                        Log::error("Failed to fetch recording from Voip24h for voip_call_id {$callToUpdate->voip_call_id} (CallLog ID: {$callToUpdate->id}). Status: " . $recordingApiResponse->status(), [
                            'response_body' => $recordingApiResponse->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    $recordingsFailedCount++;
                    Log::error("Exception while fetching recording for voip_call_id {$callToUpdate->voip_call_id} (CallLog ID: {$callToUpdate->id}): " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } else {
            Log::info("No calls found for order ID: {$order->id} that require recording URL update (all have URLs or no voip_call_id).");
        }

        if ($recordingsUpdatedCount > 0) {
            $recordingUpdateMessages[] = "Đã cập nhật thành công {$recordingsUpdatedCount} file ghi âm.";
        }
        if ($recordingsFailedCount > 0) {
            $recordingUpdateMessages[] = "Không thể cập nhật {$recordingsFailedCount} file ghi âm (kiểm tra logs để biết thêm chi tiết).";
        }
        if (empty($recordingUpdateMessages) && $callsMissingRecording->isNotEmpty()) {
            $recordingUpdateMessages[] = "Không có file ghi âm mới nào được tìm thấy hoặc cập nhật.";
        }
        // === END OF NEW LOGIC ===

        $order->load('calls'); // Reload calls relation to include any newly updated recording_urls
        $baseMessage = $newCallsCount > 0 ? "Đã đồng bộ và tìm thấy {$newCallsCount} cuộc gọi mới." : "Không có cuộc gọi mới nào từ Voip24h (có thể tất cả đã được lưu trước đó).";

        $finalMessage = $baseMessage;
        if (!empty($recordingUpdateMessages)) {
            $finalMessage .= ' ' . implode(' ', $recordingUpdateMessages);
        }

        return response()->json(['success' => true, 'message' => $finalMessage]);
    }

    /**
     * Get HTML table rows for an order's call history.
     * Used for AJAX updates.
     */
    public function getCallHistoryTableRows(Order $order)
    {
        $this->authorize('orders.view'); // Or a more specific permission if needed

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Authorization: Ensure the user has permission to view this order's calls
        // This logic might be similar to what's in your show() method
        if ($user->hasRole('manager') && $order->user?->team_id != $user->manages_team_id) {
            return response()->json(['error' => 'You do not have permission to view call history for this order (different team).'], 403);
        }
        if ($user->hasRole('staff') && $order->user_id != $user->id) {
             return response()->json(['error' => 'You do not have permission to view call history for this order (not assigned to you).'], 403);
        }

        // Fetch calls, ordered by start_time descending.
        // Ensure related user (for caller name) is loaded if needed by the partial.
        // The existing $order->calls relationship in show() loads 'calls.user',
        // so we should be consistent.
        $calls = $order->calls()->with('user')->orderBy('start_time', 'desc')->get();

        return view('orders.partials.call_history_table_rows', compact('calls'))->render();
    }

    // API Methods for address dropdowns
    public function getDistricts(Request $request)
    {
        $request->validate(['province_code' => 'required|string|exists:provinces,code']);
        $districts = District::where('province_code', $request->province_code)
                               ->orderBy('name')
                               ->pluck('name', 'code');
        return response()->json($districts);
    }

    public function getWards(Request $request)
    {
        $request->validate(['district_code' => 'required|string|exists:districts,code']);
        $wards = Ward::where('district_code', $request->district_code)
                           ->orderBy('name')
                           ->pluck('name', 'code');
        return response()->json($wards);
    }

    public function getPancakePagesForShop(Request $request)
    {
        $request->validate([
            // Validate based on the actual primary key or relevant field of pancake_shops table
            // Assuming pancake_shop_id is sent from frontend and it refers to `id` in `pancake_shops` table
            'pancake_shop_id' => ['required', Rule::exists('pancake_shops', 'id')]
        ]);

        $pancakeShopId = $request->input('pancake_shop_id');

        // Fetch pages that belong to the pancake_shop_table_id (which is the PK of pancake_shops table)
        // and also include the pancake_page_id (actual ID from Pancake API) for display if needed.
        $pages = PancakePage::where('pancake_shop_table_id', $pancakeShopId)
                            ->orderBy('name')
                            ->select('id', 'name', 'pancake_page_id') // Select specific fields
                            ->get();

        return response()->json($pages);
    }

    /**
     * Push a specific order to Pancake API.
     *
     * @param Request $request
     * @param Order $order
     * @param PancakeSyncController $pancakeSyncController Injected by Laravel
     * @return \Illuminate\Http\JsonResponse
     */
    public function pushToPancake(Request $request, Order $order, PancakeSyncController $pancakeSyncController)
    {
        $this->authorize('orders.push_to_pancake');

        // Log the attempt to push
        Log::info("Attempting to push order {$order->id} to Pancake.", [
            'order_id' => $order->id,
            'user_id' => Auth::id(),
        ]);

        // It's better to inject PancakeSyncController through the method parameters
        // $pancakeSyncController = new PancakeSyncController(); // OLD WAY - CAUSES ERROR
        // $pancakeSyncController = app(PancakeSyncController::class); // ALTERNATIVE if method injection is not used

            $result = $pancakeSyncController->pushOrderToPancake($order);

            if ($result['success']) {
            Log::info("Successfully pushed order {$order->id} to Pancake.", [
                'order_id' => $order->id,
                'response' => $result
            ]);
                return response()->json([
                    'success' => true,
                'message' => $result['message'] ?? 'Đơn hàng đã được đẩy lên Pancake thành công.',
                'pancake_data' => $result['data'] ?? null
                ]);
            } else {
            Log::error("Failed to push order {$order->id} to Pancake.", [
                'order_id' => $order->id,
                'error_message' => $result['message'] ?? 'Lỗi không xác định.',
                'error_details' => $result['errors'] ?? ($result['data'] ?? null) // Include more error details if available
            ]);
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Đẩy đơn hàng lên Pancake thất bại.',
                'errors' => $result['errors'] ?? null // Pass along specific errors if provided by the service
            ], 400); // Use a 400 or 500 status code depending on the error type
        }
    }

    /**
     * Display a consolidated view of all orders with sync capabilities
     *
     * @return \Illuminate\View\View
     */
    public function consolidated()
    {
        // Authorize the user
        $this->authorize('orders.view');

        // Get all orders with pagination
        $query = Order::with(['items', 'user', 'creator', 'pancakeShop', 'pancakePage', 'warehouse', 'shippingProvider'])
            ->latest();

        // Filter by status if provided
        if (request()->has('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }

        // Filter by Pancake sync status
        if (request()->has('pancake_status')) {
            $pancake_status = request('pancake_status');

            if ($pancake_status === 'pushed') {
                $query->whereNotNull('pancake_order_id');
            } elseif ($pancake_status === 'not_pushed') {
                $query->whereNull('pancake_order_id');
            } elseif ($pancake_status === 'failed') {
                $query->where('pancake_push_status', 'failed');
            }
        }

        // Search functionality
        if (request()->has('search') && !empty(request('search'))) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        // Get all possible statuses for filter
        $statuses = Order::getAllStatuses();

        // Get Pancake config
        $pancakeConfig = [
            'api_key' => config('pancake.api_key'),
            'shop_id' => config('pancake.shop_id'),
            'webhook_enabled' => (bool) config('pancake.webhook_secret')
        ];

        return view('orders.consolidated', compact('orders', 'statuses', 'pancakeConfig'));
    }

    protected function processOrderItems($order, $items)
    {
        foreach ($items as $itemData) {
            // Decode product_data if it exists
            $productData = !empty($itemData['product_data']) ?
                (is_string($itemData['product_data']) ? json_decode($itemData['product_data'], true) : $itemData['product_data'])
                : [];

            // Create or update order item
            $orderItem = $order->items()->updateOrCreate(
                [
                    'id' => $itemData['id'] ?? null,
                ],
                [
                    'product_name' => $itemData['name'] ?? 'Unknown Product',
                    'quantity' => $itemData['quantity'] ?? 1,
                    'price' => $itemData['price'] ?? 0,
                    'code' => $itemData['code'] ?? null,
                    'product_code' => $itemData['product_code'] ?? null,
                    'product_data' => $productData, // Store the complete product data
                ]
            );
        }

        // Remove items that are no longer in the request
        $itemIds = array_filter(array_column($items, 'id'));
        if (!empty($itemIds)) {
            $order->items()->whereNotIn('id', $itemIds)->delete();
        }
    }

    protected function recalculateOrderTotal(Order $order)
    {
        $total = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Add shipping fee if exists
        $total += $order->shipping_fee ?? 0;

        $order->update(['total_value' => $total]);
    }

    /**
     * Update an order on Pancake API.
     *
     * @param Request $request
     * @param Order $order
     * @param PancakeSyncController $pancakeSyncController
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOnPancake(Request $request, Order $order, PancakeSyncController $pancakeSyncController)
    {
        $this->authorize('orders.push_to_pancake');

        if (empty($order->pancake_order_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này chưa được đẩy lên Pancake. Vui lòng đẩy đơn hàng lên Pancake trước khi cập nhật.'
            ], 400);
        }

        Log::info("Attempting to update order {$order->id} on Pancake.", [
            'order_id' => $order->id,
            'pancake_order_id' => $order->pancake_order_id,
            'user_id' => Auth::id(),
        ]);

        $result = $pancakeSyncController->updateOrderOnPancake($order);

        if ($result['success']) {
            Log::info("Successfully updated order {$order->id} on Pancake.", [
                'order_id' => $order->id,
                'response' => $result
            ]);
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Đơn hàng đã được cập nhật thành công trên Pancake.',
                'pancake_data' => $result['data'] ?? null
            ]);
        } else {
            Log::error("Failed to update order {$order->id} on Pancake.", [
                'order_id' => $order->id,
                'error_message' => $result['message'] ?? 'Lỗi không xác định.',
                'error_details' => $result['errors'] ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Cập nhật đơn hàng lên Pancake thất bại.',
                'errors' => $result['errors'] ?? null
            ], 400);
        }
    }
}
