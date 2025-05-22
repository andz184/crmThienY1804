<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Traits\PancakeApi;
use App\Services\PancakeService;
use App\Models\CustomerPhone;

class CustomerController extends Controller
{
    use PancakeApi;

    protected $pancakeService;

    public function __construct(PancakeService $pancakeService)
    {
        $this->pancakeService = $pancakeService;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $this->authorize('customers.view');

        $filters = [
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'min_orders' => $request->input('min_orders'),
            'max_orders' => $request->input('max_orders'),
            'min_spent' => $request->input('min_spent'),
            'max_spent' => $request->input('max_spent'),
            'last_order_status' => $request->input('last_order_status'),
            'tag' => $request->input('tag'),
            'quick_filter' => $request->input('quick_filter')
        ];

        $query = Customer::query();

        // Apply quick filters
        if ($request->has('quick_filter')) {
            switch ($request->input('quick_filter')) {
                case 'new':
                    // Khách hàng mới (trong 30 ngày gần đây)
                    $query->whereDate('created_at', '>=', now()->subDays(30));
                    break;
                case 'repeat':
                    // Khách hàng mua lại (có nhiều hơn 1 đơn hàng)
                    $query->where('total_orders_count', '>', 1);
                    break;
                case 'vip':
                    // Khách VIP (căn cứ vào tag hoặc tổng chi tiêu)
                    $query->where(function($q) {
                        $q->whereJsonContains('tags', 'VIP')
                          ->orWhere('total_spent', '>=', 5000000); // 5 triệu đồng
                    });
                    break;
                case 'inactive':
                    // Khách không hoạt động (không mua hàng trong 90 ngày)
                    $query->whereDate('last_order_date', '<=', now()->subDays(90))
                          ->orWhereNull('last_order_date');
                    break;
            }
        }

        if ($request->filled('search')) {
            $searchTerm = strtolower($request->input('search'));
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('phones', function($q) use ($searchTerm) {
                      $q->where('phone_number', 'like', "%{$searchTerm}%");
                  })
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('min_orders')) {
            $query->where('total_orders_count', '>=', $request->input('min_orders'));
        }

        if ($request->filled('max_orders')) {
            $query->where('total_orders_count', '<=', $request->input('max_orders'));
        }

        if ($request->filled('min_spent')) {
            $query->where('total_spent', '>=', $request->input('min_spent'));
        }

        if ($request->filled('max_spent')) {
            $query->where('total_spent', '<=', $request->input('max_spent'));
        }
        
        if ($request->filled('last_order_status')) {
            $status = $request->input('last_order_status');
            $query->whereHas('orders', function($q) use ($status) {
                $q->where('status', $status)
                  ->whereRaw('orders.id = (SELECT MAX(id) FROM orders WHERE customer_id = customers.id)');
            });
        }
        
        if ($request->filled('tag')) {
            $tag = $request->input('tag');
            $query->whereJsonContains('tags', $tag);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('customers._customers_table_body', compact('customers'))->render(),
                'pagination_html' => $customers->appends(request()->query())->links()->toHtml()
            ]);
        }

        return view('customers.index', compact('customers', 'filters'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $this->authorize('customers.create');
        return view('customers.create');
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $this->authorize('customers.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customer_phones,phone_number',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'fb_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'full_address' => 'nullable|string|max:500',
            'province' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:VIP,Khách quen,Khách mới'
        ]);

        try {
            // Format data for Pancake API
            $pancakeData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'fb_id' => $validated['fb_id'],
                'note' => $validated['notes'],
                'tags' => $validated['tags'] ?? [],
                'addresses' => [
                    [
                        'address' => $validated['street_address'],
                        'province' => $validated['province'],
                        'district' => $validated['district'],
                        'ward' => $validated['ward'],
                        'full_address' => $validated['full_address'],
                        'is_default' => true
                    ]
                ]
            ];

            // Create customer in Pancake
            $pancakeResponse = $this->pancakeService->createCustomer($pancakeData);

            if (!$pancakeResponse['success']) {
                return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra khi tạo khách hàng trên Pancake: ' . ($pancakeResponse['message'] ?? 'Unknown error')]);
            }

            DB::beginTransaction();
            try {
                // Create customer in local database
                $customerData = array_merge(
                    array_diff_key($validated, ['phone' => '']), // Remove phone from validated data
                    ['pancake_id' => $pancakeResponse['data']['id']]
                );

                $customer = Customer::create($customerData);

                // Create phone number
                $customer->phones()->create([
                    'phone_number' => $validated['phone'],
                    'is_primary' => true,
                    'type' => 'mobile'
                ]);

                DB::commit();
                return redirect()->route('customers.show', $customer)->with('success', 'Tạo khách hàng thành công!');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra khi tạo khách hàng. Vui lòng thử lại.']);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('customers.view');

        try {
            // Get latest data from Pancake only if pancake_id exists
            if ($customer->pancake_id) {
                $pancakeResponse = $this->pancakeService->getCustomer($customer->pancake_id);

                if ($pancakeResponse['success']) {
                    // Update local data with latest from Pancake
                    $pancakeData = $pancakeResponse['data'];
                    $customer->update([
                        'name' => $pancakeData['name'],
                        'email' => $pancakeData['email'],
                        'date_of_birth' => $pancakeData['date_of_birth'],
                        'gender' => $pancakeData['gender'],
                        'fb_id' => $pancakeData['fb_id'],
                        'notes' => $pancakeData['note'],
                        'tags' => $pancakeData['tags'] ?? [],
                        // Update other fields as needed
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching customer from Pancake: ' . $e->getMessage());
            // Continue showing local data even if Pancake sync fails
        }

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $this->authorize('customers.edit');
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('customers.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customer_phones,phone_number,' . $customer->primaryPhone?->id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'fb_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'full_address' => 'nullable|string|max:500',
            'province' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:VIP,Khách quen,Khách mới'
        ]);

        try {
            // Format data for Pancake API
            $pancakeData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'fb_id' => $validated['fb_id'],
                'note' => $validated['notes'],
                'tags' => $validated['tags'] ?? [],
                'addresses' => [
                    [
                        'address' => $validated['street_address'],
                        'province' => $validated['province'],
                        'district' => $validated['district'],
                        'ward' => $validated['ward'],
                        'full_address' => $validated['full_address'],
                        'is_default' => true
                    ]
                ]
            ];

            // Update customer in Pancake
            $pancakeResponse = $this->pancakeService->updateCustomer($customer->pancake_id, $pancakeData);

            if (!$pancakeResponse['success']) {
                return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật khách hàng trên Pancake: ' . ($pancakeResponse['message'] ?? 'Unknown error')]);
            }

            DB::beginTransaction();
            try {
                // Update customer in local database
                $customerData = array_diff_key($validated, ['phone' => '']); // Remove phone from validated data
                $customer->update($customerData);

                // Update or create primary phone
                if ($customer->primaryPhone) {
                    $customer->primaryPhone->update([
                        'phone_number' => $validated['phone']
                    ]);
                } else {
                    $customer->phones()->create([
                        'phone_number' => $validated['phone'],
                        'is_primary' => true,
                        'type' => 'mobile'
                    ]);
                }

                DB::commit();
                return redirect()->route('customers.show', $customer)->with('success', 'Cập nhật thông tin khách hàng thành công!');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error updating customer: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật thông tin khách hàng. Vui lòng thử lại.']);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('customers.delete');

        try {
            // Delete customer in Pancake if it exists
            if ($customer->pancake_id) {
                $pancakeResponse = $this->pancakeService->deleteCustomer($customer->pancake_id);
                if (!$pancakeResponse['success']) {
                    return back()->withErrors(['error' => 'Có lỗi xảy ra khi xóa khách hàng trên Pancake: ' . ($pancakeResponse['message'] ?? 'Unknown error')]);
                }
            }

            // Delete customer in local database
            $customer->delete();

            return redirect()->route('customers.index')->with('success', 'Xóa khách hàng thành công!');
        } catch (\Exception $e) {
            Log::error('Error deleting customer: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi xóa khách hàng. Vui lòng thử lại.']);
        }
    }

    /**
     * Display customer's order history.
     */
    public function orders(Customer $customer)
    {
        $this->authorize('customers.view');
        $orders = $customer->orders()->orderBy('created_at', 'desc')->get();
        return view('customers.orders', compact('customer', 'orders'));
    }

    /**
     * Display latest customers.
     */
    public function latest()
    {
        $this->authorize('customers.view');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Customer::query();

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $teamId = $user->manages_team_id;
            if ($teamId) {
                $teamMemberIds = \App\Models\User::where('team_id', $teamId)->pluck('id');
                $query->whereHas('orders', function($q) use ($teamMemberIds) {
                    $q->whereIn('user_id', $teamMemberIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('staff')) {
            $query->whereHas('orders', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('customers.latest', compact('customers'));
    }

    public function syncFromOrders(Request $request)
    {
        dd(1);
        // Increase execution time limit to 2 hours and memory limit to 1GB
        set_time_limit(7200);
        ini_set('memory_limit', '1024M');
        
        $this->authorize('customers.sync');
        $user = Auth::user();
        $isAjax = $request->ajax();

        // Define the base query for orders based on user role
        $orderQueryBase = Order::query(); // Applied to the specific user/role
        $orderQueryGlobal = Order::query(); // Unscoped, for fetching order details if needed beyond user scope for a phone

        if ($user->can('manage_team')) {
            $teamId = $user->manages_team_id;
            $teamMemberIds = $teamId ? \App\Models\User::where('team_id', $teamId)->pluck('id') : collect();
            if ($teamMemberIds->isEmpty() && $teamId) {
                 return $isAjax ? response()->json(['done' => true, 'progress' => 100, 'message' => 'Không có nhân viên trong nhóm hoặc không tìm thấy nhóm.', 'created' => 0]) : redirect()->route('customers.index')->with('info', 'Không có nhân viên trong nhóm hoặc không tìm thấy nhóm để đồng bộ.');
            }
            $orderQueryBase->whereIn('user_id', $teamMemberIds);
        } elseif ($user->can('staff_access')) {
            $orderQueryBase->where('user_id', $user->id);
        } elseif (!$user->can('admin_access')) {
            return $isAjax ? response()->json(['error' => 'Không có quyền đồng bộ!'], 403) : redirect()->route('customers.index')->with('error', 'Không có quyền đồng bộ!');
        }

        // Get all distinct phone numbers from the relevant orders (within user's scope)
        $allPhonesFromScopedOrders = $orderQueryBase->clone()->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->distinct()
            ->pluck('customer_phone');

        if ($allPhonesFromScopedOrders->isEmpty()) {
            return $isAjax ? response()->json(['done' => true, 'progress' => 100, 'message' => 'Không tìm thấy đơn hàng nào (trong phạm vi của bạn) để đồng bộ khách hàng.', 'created' => 0]) : redirect()->route('customers.index')->with('info', 'Không tìm thấy đơn hàng nào (trong phạm vi của bạn) để đồng bộ khách hàng.');
        }

        // Get all phone numbers that ALREADY exist in the customers table through customer_phones
        $existingCustomerPhones = CustomerPhone::whereIn('phone_number', $allPhonesFromScopedOrders->all())->pluck('phone_number');

        // Determine which phone numbers are new
        $newPhonesToCreate = $allPhonesFromScopedOrders->diff($existingCustomerPhones);

        $createdCount = 0;

        if ($newPhonesToCreate->isEmpty()) {
             return $isAjax ? response()->json(['done' => true, 'progress' => 100, 'message' => 'Không có khách hàng mới nào để thêm từ đơn hàng (trong phạm vi của bạn).', 'created' => 0]) : redirect()->route('customers.index')->with('info', 'Không có khách hàng mới nào để thêm từ đơn hàng (trong phạm vi của bạn).');
        }

        foreach ($newPhonesToCreate as $phone) {
            // For a new customer, we want their aggregates based on *all* their orders in the system,
            // not just those visible to the current syncing user (especially if super-admin syncs).
            // So, we use an unscoped query for calculating aggregates for the new customer.
            $allOrdersForThisPhone = $orderQueryGlobal->clone()->where('customer_phone', $phone)->orderBy('created_at', 'asc')->get();

            if ($allOrdersForThisPhone->isEmpty()) continue; // Should ideally not happen

            $latestOrder = $allOrdersForThisPhone->last(); // Get details from the absolute latest order for this phone
            $firstOrder = $allOrdersForThisPhone->first();

            $totalSpent = $allOrdersForThisPhone->where('status', 'completed')->sum('total_value');
            $totalOrdersCount = $allOrdersForThisPhone->count();

            $customerData = [
                'name' => $latestOrder->customer_name,
                'email' => $latestOrder->customer_email,
                'full_address' => $latestOrder->full_address,
                'province' => $latestOrder->province,
                'district' => $latestOrder->district,
                'ward' => $latestOrder->ward,
                'street_address' => $latestOrder->street_address,
                'first_order_date' => $firstOrder->created_at->toDateString(),
                'last_order_date' => $latestOrder->created_at->toDateString(),
                'total_orders_count' => $totalOrdersCount,
                'total_spent' => $totalSpent,
                // 'notes' can be set from latestOrder->notes if desired, or left null/default
            ];

            DB::beginTransaction();
            try {
                // Create new customer
                $newCustomer = Customer::create($customerData);

                // Create primary phone number
                $newCustomer->phones()->create([
                    'phone_number' => $phone,
                    'is_primary' => true,
                    'type' => 'mobile'
                ]);

                // Update customer_id in all orders with this phone number
                Order::where('customer_phone', $phone)->update(['customer_id' => $newCustomer->id]);

                $createdCount++;
                LogHelper::log('customer_sync_create_if_new', $newCustomer, null, $newCustomer->toArray());
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating new customer for phone ' . $phone . ' during sync: ' . $e->getMessage());
            }
        }

        $message = "Đồng bộ hoàn tất. Đã tạo mới {$createdCount} khách hàng.";
        if ($isAjax) {
            // Simplified AJAX response. Real chunking for create-only could be done with $newPhonesToCreate.
            return response()->json(['done' => true, 'progress' => 100, 'message' => $message, 'created' => $createdCount]);
        }

        return redirect()->route('customers.index')->with('success', $message);
    }

    /**
     * Perform bulk soft deletion of customers.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDestroy(Request $request)
    {
        $this->authorize('customers.delete');

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => 'Vui lòng chọn ít nhất một khách hàng để xóa.'], 400);
        }

        DB::beginTransaction();
        try {
            $deletedCount = Customer::whereIn('id', $ids)->delete(); // This performs soft delete due to SoftDeletes trait
            // Log each deletion if necessary, or a single bulk log event
            // For simplicity, a general log. For detailed audit, loop and log each.
            if ($deletedCount > 0) {
                LogHelper::log('customer_bulk_delete', null, ['ids' => $ids, 'count' => $deletedCount], null);
            }
            DB::commit();
            return response()->json(['message' => "Đã xóa mềm thành công {$deletedCount} khách hàng."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during bulk customer delete: ' . $e->getMessage(), ['ids' => $ids]);
            return response()->json(['message' => 'Có lỗi xảy ra trong quá trình xóa. Vui lòng thử lại.'], 500);
        }
    }


    public function trashedIndex(): \Illuminate\View\View
    {

        $this->authorize('customers.view_trashed');
        $trashedCustomers = Customer::onlyTrashed()->orderByDesc('deleted_at')->paginate(15);
        return view('customers.trashed', compact('trashedCustomers'));
    }

    /**
     * Restore the specified soft-deleted customer.
     *
     * @param int $id The ID of the customer to restore.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('customers.restore');
        $customer = Customer::onlyTrashed()->findOrFail($id);
        DB::beginTransaction();
        try {
            $customer->restore();
            LogHelper::log('customer_restore', $customer, null, $customer->toArray());
            DB::commit();
            return redirect()->route('customers.trashed')->with('success', "Khách hàng '{$customer->name}' đã được khôi phục thành công.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error restoring customer {$id}: " . $e->getMessage());
            return redirect()->route('customers.trashed')->with('error', 'Lỗi khôi phục khách hàng. Vui lòng thử lại.');
        }
    }

    /**
     * Permanently delete the specified soft-deleted customer.
     *
     * @param int $id The ID of the customer to force delete.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('customers.force_delete');
        $customer = Customer::onlyTrashed()->findOrFail($id);
        DB::beginTransaction();
        try {
            $oldData = $customer->toArray(); // For logging before it's gone
            $customerName = $customer->name;
            $customer->forceDelete();
            LogHelper::log('customer_force_delete', null, $oldData, ['name' => $customerName, 'id' => $id]); // Log with old data
            DB::commit();
            return redirect()->route('customers.trashed')->with('success', "Khách hàng '{$customerName}' đã được xóa vĩnh viễn.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error force deleting customer {$id}: " . $e->getMessage());
            return redirect()->route('customers.trashed')->with('error', 'Lỗi xóa vĩnh viễn khách hàng. Vui lòng thử lại.');
        }
    }

    public function syncFromPancake(Request $request)
    {
        try {
            // Set execution time
            set_time_limit(7200); // 2 hours execution time limit
            ini_set('memory_limit', '1024M'); // 1GB memory limit

            $result = $this->pancakeService->syncCustomers();

            if ($result['success']) {
                $stats = $result['stats'];
                return response()->json([
                    'success' => true,
                    'message' => 'Đồng bộ khách hàng từ Pancake thành công!',
                    'pancake_data' => [
                        'total' => $stats['total'],
                        'synced' => $stats['synced'],
                        'failed' => $stats['failed'],
                        'errors' => $stats['errors']
                    ]
                ]);
            }

            // Nếu có lỗi từ Pancake API
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ không thành công: ' . ($result['error'] ?? 'Lỗi không xác định'),
                'pancake_error' => $result['error'] ?? null,
                'details' => $result['details'] ?? null,
                'errors' => $result['errors'] ?? ['Không thể kết nối với Pancake API hoặc có lỗi trong quá trình đồng bộ.']
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error syncing customers from Pancake: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra trong quá trình đồng bộ.',
                'pancake_error' => $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Start the synchronization process
     */
    public function startSync()
    {
        try {
            // Increase execution time limit to 2 hours and memory limit to 1GB
            set_time_limit(7200);
            ini_set('memory_limit', '1024M');
            
            // Bắt đầu đồng bộ trong queue
            dispatch(function() {
                $this->pancakeService->syncCustomers();
            })->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Bắt đầu đồng bộ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể bắt đầu đồng bộ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current sync progress
     */
    public function getSyncProgress()
    {
        return response()->json($this->pancakeService->getProgress());
    }

    /**
     * Cancel the current sync process
     */
    public function cancelSync()
    {
        $this->pancakeService->cancelSync();
        return response()->json([
            'success' => true,
            'message' => 'Đã hủy đồng bộ'
        ]);
    }
}

