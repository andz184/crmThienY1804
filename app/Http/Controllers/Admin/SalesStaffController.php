<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Log;

class SalesStaffController extends Controller
{
    /**
     * Display a listing of the sales staff.
     */
    public function index(Request $request)
    {
        $this->authorize('settings.manage');

        // Get filter parameters
        $search = $request->input('search');
        $activeFilter = $request->input('active_status'); // Can be 'active', 'inactive', or null (all)
        $orderCountMin = $request->input('order_count_min');
        $orderCountMax = $request->input('order_count_max');
        $hasPancake = $request->input('has_pancake'); // Can be 'yes', 'no', or null (all)

        // Base query for sales staff with order counts
        $query = User::role('staff')
            ->select('users.id', 'users.name', 'users.email', 'users.pancake_uuid', 'users.is_active')
            ->selectRaw('COUNT(DISTINCT orders.id) as total_orders_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN orders.status IN (?, ?, ?) THEN orders.id END) as processing_orders_count', 
                [Order::STATUS_MOI, Order::STATUS_CAN_XU_LY, Order::STATUS_CHO_HANG])
            ->leftJoin('orders', 'users.pancake_uuid', '=', 'orders.assigning_seller_id');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.pancake_uuid', 'like', "%{$search}%");
            });
        }

        if ($activeFilter === 'active') {
            $query->where('users.is_active', true);
        } elseif ($activeFilter === 'inactive') {
            $query->where('users.is_active', false);
        }

        if ($hasPancake === 'yes') {
            $query->whereNotNull('users.pancake_uuid');
        } elseif ($hasPancake === 'no') {
            $query->whereNull('users.pancake_uuid');
        }

        // Get the results
        $salesStaff = $query->groupBy('users.id', 'users.name', 'users.email', 'users.pancake_uuid', 'users.is_active')
            ->orderBy('users.name')
            ->get();

        // Apply post-query filters for order counts (since these might be aggregations)
        if ($orderCountMin !== null) {
            $salesStaff = $salesStaff->filter(function ($staff) use ($orderCountMin) {
                return $staff->total_orders_count >= $orderCountMin;
            });
        }

        if ($orderCountMax !== null) {
            $salesStaff = $salesStaff->filter(function ($staff) use ($orderCountMax) {
                return $staff->total_orders_count <= $orderCountMax;
            });
        }

        return view('admin.sales-staff.index', [
            'salesStaff' => $salesStaff,
            'filters' => [
                'search' => $search,
                'active_status' => $activeFilter,
                'order_count_min' => $orderCountMin,
                'order_count_max' => $orderCountMax,
                'has_pancake' => $hasPancake
            ]
        ]);
    }

    /**
     * Toggle the active status of a sales staff member via AJAX.
     */
    public function toggleActive(Request $request, User $user)
    {
        $this->authorize('settings.manage');

        try {
            $user->is_active = !$user->is_active;
            $user->save();

            $statusText = $user->is_active ? 'đã kích hoạt' : 'đã tắt';
            
            return response()->json([
                'success' => true,
                'message' => "Trạng thái nhận đơn hàng của {$user->name} {$statusText}."
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling staff status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Có lỗi xảy ra khi cập nhật trạng thái của {$user->name}."
            ], 500);
        }
    }

    /**
     * Reassign all unprocessed orders from an inactive staff member to other active staff.
     */
    public function reassignOrders(Request $request, User $user)
    {
        $this->authorize('settings.manage');

        $success = $this->redistributeOrdersFromStaff($user);

        if ($success) {
            return redirect()->route('admin.sales-staff.index')
                ->with('success', "Đơn hàng của {$user->name} đã được phân phối lại.");
        } else {
            return redirect()->route('admin.sales-staff.index')
                ->with('error', "Không thể phân phối lại đơn hàng của {$user->name}.");
        }
    }

    /**
     * Auto distribute new unassigned orders.
     */
    public function distributeNewOrders()
    {
        $this->authorize('settings.manage');

        // Get unassigned orders
        $unassignedOrders = Order::whereNull('assigning_seller_id')
            ->whereIn('status', [Order::STATUS_MOI, Order::STATUS_CAN_XU_LY])
            ->orderBy('created_at')
            ->get();

        $count = count($unassignedOrders);
        
        if ($count === 0) {
            return redirect()->route('admin.sales-staff.index')
                ->with('info', "Không có đơn hàng nào cần phân phối.");
        }

        $success = $this->distributeOrders($unassignedOrders);
        
        if ($success) {
            return redirect()->route('admin.sales-staff.index')
                ->with('success', "Đã phân phối {$count} đơn hàng mới.");
        } else {
            return redirect()->route('admin.sales-staff.index')
                ->with('error', "Không thể phân phối đơn hàng mới.");
        }
    }

    /**
     * Helper method to redistribute orders from a specific staff member.
     * 
     * @param User $user The staff member whose orders need to be redistributed
     * @return bool Success status
     */
    private function redistributeOrdersFromStaff(User $user)
    {
        // Get orders that need to be reassigned
        $ordersToReassign = Order::where('assigning_seller_id', $user->pancake_uuid)
            ->whereIn('status', [Order::STATUS_MOI, Order::STATUS_CAN_XU_LY, Order::STATUS_CHO_HANG])
            ->get();

        if ($ordersToReassign->isEmpty()) {
            return true; // No orders to reassign
        }

        return $this->distributeOrders($ordersToReassign);
    }

    /**
     * Helper method to distribute orders to active staff members.
     * 
     * @param \Illuminate\Support\Collection $orders The orders to distribute
     * @return bool Success status
     */
    private function distributeOrders($orders)
    {
        if ($orders->isEmpty()) {
            return true;
        }

        // Get active sales staff
        $activeStaff = User::role('staff')
            ->where('is_active', true)
            ->whereNotNull('pancake_uuid')
            ->get();

        if ($activeStaff->isEmpty()) {
            Log::error('No active sales staff found for order distribution');
            return false;
        }

        // Get distribution settings
        $distributionType = WebsiteSetting::get('order_distribution_type', 'sequential');
        $distributionPattern = explode(',', WebsiteSetting::get('order_distribution_pattern', '1,1,1'));
        
        try {
            DB::beginTransaction();

            if ($distributionType === 'sequential') {
                // Sequential distribution - assign each order to next staff in rotation
                $staffCount = $activeStaff->count();
                $staffIndex = 0;
                
                foreach ($orders as $order) {
                    $staff = $activeStaff[$staffIndex % $staffCount];
                    $order->assigning_seller_id = $staff->pancake_uuid;
                    $order->assigning_seller_name = $staff->name;
                    $order->save();
                    
                    $staffIndex++;
                }
            } else {
                // Batch distribution - assign orders in batches defined by pattern
                $totalOrders = $orders->count();
                $patternSum = array_sum($distributionPattern);
                $staffCount = $activeStaff->count();
                $orderIndex = 0;
                $staffIndex = 0;
                
                // Calculate how many passes through the pattern we'll need
                $patternPasses = ceil($totalOrders / $patternSum);
                
                for ($pass = 0; $pass < $patternPasses; $pass++) {
                    foreach ($distributionPattern as $batch) {
                        $staff = $activeStaff[$staffIndex % $staffCount];
                        $staffIndex++;
                        
                        // Assign this batch of orders to the staff member
                        for ($i = 0; $i < $batch && $orderIndex < $totalOrders; $i++) {
                            $order = $orders[$orderIndex];
                            $order->assigning_seller_id = $staff->pancake_uuid;
                            $order->assigning_seller_name = $staff->name;
                            $order->save();
                            
                            $orderIndex++;
                        }
                        
                        // Break if we've assigned all orders
                        if ($orderIndex >= $totalOrders) {
                            break;
                        }
                    }
                }
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error distributing orders: ' . $e->getMessage());
            return false;
        }
    }
}
