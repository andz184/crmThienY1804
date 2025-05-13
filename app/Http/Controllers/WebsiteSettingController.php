<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use App\Models\User;
use App\Models\Order;
use App\Services\OrderDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteSettingController extends Controller
{
    protected $orderDistributionService;

    public function __construct(OrderDistributionService $orderDistributionService)
    {
        $this->orderDistributionService = $orderDistributionService;
        $this->middleware('auth');
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        // Get staff members
        $staffMembers = User::role('staff')->get();

        // Get staff statistics
        $staffStats = User::role('staff')
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(CASE WHEN orders.status IN (?, ?, ?) THEN 1 END) as processing_orders_count',
                [Order::STATUS_MOI, Order::STATUS_CAN_XU_LY, Order::STATUS_CHO_HANG])
            ->selectRaw('COUNT(orders.id) as total_orders_count')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->groupBy('users.id', 'users.name')
            ->get();

        $settings = WebsiteSetting::getByGroup('orders');

        return view('settings.index', compact('settings', 'staffMembers', 'staffStats'));
    }

    /**
     * Update the order distribution settings
     */
    public function updateOrderDistribution(Request $request)
    {
        $request->validate([
            'distribution_type' => 'required|in:sequential,batch',
            'distribution_pattern' => 'required|string'
        ]);

        // Validate pattern format
        if (!$this->orderDistributionService->validatePattern($request->distribution_pattern)) {
            return back()->with('error', 'Định dạng mẫu phân phối không hợp lệ. Vui lòng sử dụng các số nguyên dương phân cách bằng dấu phẩy.');
        }

        // Get active staff count
        $activeStaffCount = User::role('staff')->where('status', 'active')->count();

        // Validate pattern against staff count
        $numbers = array_map('intval', explode(',', $request->distribution_pattern));
        if (count($numbers) > $activeStaffCount) {
            return back()->with('error', 'Số lượng số trong mẫu phân phối (' . count($numbers) . ') không thể lớn hơn số lượng nhân viên đang hoạt động (' . $activeStaffCount . ').');
        }

        // Update settings
        WebsiteSetting::set('order_distribution_type', $request->distribution_type);
        WebsiteSetting::set('order_distribution_pattern', $request->distribution_pattern);

        // Distribute any unassigned orders
        $unassignedOrders = Order::whereNull('user_id')
            ->where('status', Order::STATUS_MOI)
            ->get();

        if ($unassignedOrders->isNotEmpty()) {
            $staffMembers = User::role('staff')
                ->where('status', 'active')
                ->get();

            if ($staffMembers->isNotEmpty()) {
                $this->orderDistributionService->distributeOrders(
                    $unassignedOrders->pluck('id')->toArray(),
                    $staffMembers->pluck('id')->toArray()
                );
            }
        }

        return back()->with('success', 'Cập nhật cài đặt phân phối đơn hàng thành công.');
    }
}
