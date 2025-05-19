<?php

namespace App\Services;

use App\Models\CampaignReport;
use App\Models\ProductGroupReport;
use App\Models\LiveSessionReport;
use App\Models\CustomerOrderReport;
use App\Models\PaymentReport;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Models\DailyRevenueAggregate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Lấy tổng doanh thu
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds IDs của người dùng cần lọc (null nếu không lọc)
     * @return float
     */
    public function getTotalRevenue($startDate = null, $endDate = null, $userIds = null)
    {
        $query = Order::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->sum('total_amount');
    }

    /**
     * Lấy doanh thu theo ngày
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds IDs của người dùng cần lọc
     * @return array
     */
    public function getDailyRevenue($startDate = null, $endDate = null, $userIds = null)
    {
        // Nếu không có ngày bắt đầu, mặc định là 30 ngày trước
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
        }

        // Nếu không có ngày kết thúc, mặc định là hiện tại
        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay();
        }

        $query = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'));

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get()->toArray();
    }

    /**
     * Lấy báo cáo theo chiến dịch (bài post)
     *
     * @param int|null $postId
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCampaignReport($postId = null, $startDate = null, $endDate = null, $userIds = null)
    {
        $query = CampaignReport::query();

        if ($postId) {
            $query->where('post_id', $postId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    /**
     * Lấy danh sách sản phẩm trong chiến dịch
     *
     * @param int $postId
     * @return array
     */
    public function getCampaignProducts($postId)
    {
        // Tìm chiến dịch
        $campaign = CampaignReport::where('post_id', $postId)->first();

        if (!$campaign) {
            return [];
        }

        // Lấy danh sách sản phẩm có trong đơn hàng của chiến dịch này
        $products = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.campaign_id', $campaign->id)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->get();

        return $products->toArray();
    }

    /**
     * Lấy báo cáo theo nhóm hàng hóa
     *
     * @param string|null $groupName
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductGroupReport($groupName = null, $startDate = null, $endDate = null, $userIds = null)
    {
        $query = ProductGroupReport::query();

        if ($groupName) {
            $query->where('group_name', $groupName);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    /**
     * Lấy báo cáo phiên live
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLiveSessionReport($startDate = null, $endDate = null, $userIds = null)
    {
        $query = LiveSessionReport::query();

        if ($startDate) {
            $query->where('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('end_time', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    /**
     * Lấy chi tiết phiên live
     *
     * @param int $sessionId
     * @return array
     */
    public function getLiveSessionDetail($sessionId)
    {
        $session = LiveSessionReport::find($sessionId);

        if (!$session) {
            return [];
        }

        // Lấy thông tin chi tiết về phiên live
        $orders = Order::where('live_session_id', $sessionId)
            ->with(['items', 'customer', 'user'])
            ->get();

        $productsData = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.live_session_id', $sessionId)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->get();

        return [
            'session' => $session,
            'orders' => $orders,
            'products' => $productsData
        ];
    }

    /**
     * Lấy báo cáo đơn hàng của khách hàng (mới/cũ)
     *
     * @param bool|null $isFirstOrder - true: KH mới, false: KH cũ, null: tất cả
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerOrderReport($isFirstOrder = null, $startDate = null, $endDate = null, $userIds = null)
    {
        $query = CustomerOrderReport::query();

        if ($isFirstOrder !== null) {
            $query->where('is_first_order', $isFirstOrder);
        }

        if ($startDate) {
            $query->where('order_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('order_date', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->with('customer')->get();
    }

    /**
     * Lấy báo cáo tỷ lệ chốt đơn
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return array
     */
    public function getConversionReport($startDate = null, $endDate = null, $userIds = null)
    {
        // Lấy thông tin về số lượt tiếp cận và số đơn hàng chốt được
        $query = DB::table('campaign_views')
            ->leftJoin('orders', 'campaign_views.campaign_id', '=', 'orders.campaign_id')
            ->select(
                'campaign_views.campaign_id',
                DB::raw('COUNT(DISTINCT campaign_views.id) as total_views'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_revenue')
            )
            ->groupBy('campaign_views.campaign_id');

        if ($startDate) {
            $query->where('campaign_views.created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('campaign_views.created_at', '<=', $endDate);
        }

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $query->whereIn('orders.user_id', $userIds);
        }

        $campaignData = $query->get();

        // Tính toán tỷ lệ chốt đơn cho từng chiến dịch
        $results = [];
        foreach ($campaignData as $data) {
            $conversionRate = 0;
            $revenuePerView = 0;

            if ($data->total_views > 0) {
                $conversionRate = ($data->total_orders / $data->total_views) * 100;
                $revenuePerView = $data->total_revenue / $data->total_views;
            }

            $results[] = [
                'campaign_id' => $data->campaign_id,
                'total_views' => $data->total_views,
                'total_orders' => $data->total_orders,
                'total_revenue' => $data->total_revenue,
                'conversion_rate' => round($conversionRate, 2),
                'revenue_per_view' => round($revenuePerView, 2)
            ];
        }

        return $results;
    }

    /**
     * Tính tỷ lệ chốt đơn cho một chiến dịch cụ thể
     *
     * @param int $campaignId
     * @return float Tỷ lệ chốt đơn (%)
     */
    public function calculateConversionRate($campaignId)
    {
        $campaign = CampaignReport::find($campaignId);
        if (!$campaign) {
            return 0;
        }

        $totalViews = DB::table('campaign_views')
            ->where('campaign_id', $campaignId)
            ->count();

        if ($totalViews === 0) {
            return 0;
        }

        return ($campaign->total_orders / $totalViews) * 100;
    }

    /**
     * Lấy báo cáo chi tiết (nhiều loại dữ liệu)
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return array
     */
    public function getDetailReport($startDate = null, $endDate = null, $userIds = null)
    {
        // Nếu không có ngày bắt đầu, mặc định là 30 ngày trước
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
        }

        // Nếu không có ngày kết thúc, mặc định là hiện tại
        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay();
        }

        // Truy vấn cơ bản với các ràng buộc chung
        $baseQuery = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $baseQuery->whereIn('user_id', $userIds);
        }

        // 1. Tổng quan
        $overview = [
            'total_orders' => (clone $baseQuery)->count(),
            'total_revenue' => (clone $baseQuery)->sum('total_amount'),
            'avg_order_value' => (clone $baseQuery)->avg('total_amount') ?? 0,
        ];

        // 2. Báo cáo theo ngày
        $dailyData = (clone $baseQuery)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // 3. Báo cáo theo tháng
        $monthlyData = (clone $baseQuery)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // 4. Báo cáo theo trạng thái
        $statusData = (clone $baseQuery)
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('status')
            ->get();

        // 5. Báo cáo theo nguồn
        $sourceData = (clone $baseQuery)
            ->select(
                'source',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('source')
            ->get();

        return [
            'overview' => $overview,
            'daily' => $dailyData,
            'monthly' => $monthlyData,
            'status' => $statusData,
            'source' => $sourceData
        ];
    }

    /**
     * Tạo báo cáo hàng ngày
     *
     * @param array|null $userIds
     * @return array
     */
    public function generateDailyReport($userIds = null)
    {
        $today = Carbon::today();

        // Truy vấn cơ bản lọc theo ngày hôm nay
        $baseQuery = Order::query()
            ->whereDate('created_at', $today);

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $baseQuery->whereIn('user_id', $userIds);
        }

        // Lấy tổng doanh thu cho hôm nay
        $totalRevenue = (clone $baseQuery)->sum('total_amount');

        // Lấy khách hàng mới (đơn đầu tiên)
        $newCustomers = CustomerOrderReport::where('is_first_order', true)
            ->whereDate('order_date', $today);

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $newCustomers->whereIn('user_id', $userIds);
        }

        $newCustomersCount = $newCustomers->count();

        // Lấy khách hàng cũ (đơn thứ 2+)
        $returningCustomers = CustomerOrderReport::where('is_first_order', false)
            ->whereDate('order_date', $today);

        // Lọc theo user_id nếu được chỉ định
        if ($userIds !== null) {
            $returningCustomers->whereIn('user_id', $userIds);
        }

        $returningCustomersCount = $returningCustomers->count();

        return [
            'date' => $today->format('Y-m-d'),
            'total_revenue' => $totalRevenue,
            'new_customers' => $newCustomersCount,
            'returning_customers' => $returningCustomersCount
        ];
    }

    /**
     * Lấy báo cáo thanh toán
     *
     * @param string|null $paymentMethod
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param array|null $userIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPaymentReport($paymentMethod = null, $startDate = null, $endDate = null, $userIds = null)
    {
        $query = PaymentReport::query();

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($startDate) {
            $query->where('report_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('report_date', '<=', $endDate);
        }

        return $query->orderBy('report_date', 'desc')
                    ->orderBy('payment_method', 'asc')
                    ->get();
    }

    /**
     * Tạo báo cáo thanh toán theo ngày
     *
     * @param Carbon $date
     * @return array
     */
    public function generatePaymentReportForDate(Carbon $date)
    {
        // Danh sách phương thức thanh toán
        $paymentMethods = ['cod', 'banking', 'momo', 'zalopay', 'other'];
        $results = [];

        foreach ($paymentMethods as $method) {
            // Lấy tất cả đơn hàng có phương thức thanh toán này trong ngày
            $orders = Order::where('payment_method', $method)
                ->whereDate('created_at', $date)
                ->get();

            // Nếu không có đơn hàng nào, bỏ qua
            if ($orders->isEmpty()) {
                continue;
            }

            // Tổng số đơn hàng
            $totalOrders = $orders->count();

            // Tổng doanh thu
            $totalRevenue = $orders->sum('total_value');

            // Đơn hàng đã hoàn thành (đã thu tiền)
            $completedOrders = $orders->filter(function ($order) {
                return $order->status === Order::STATUS_DA_THU_TIEN;
            });

            // Số đơn hàng đã thu tiền
            $completedOrdersCount = $completedOrders->count();

            // Doanh thu từ đơn đã thu tiền
            $completedRevenue = $completedOrders->sum('total_value');

            // Tỷ lệ hoàn thành
            $completionRate = $totalOrders > 0 ? ($completedOrdersCount / $totalOrders) * 100 : 0;

            // Giá trị trung bình mỗi đơn
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Lưu báo cáo vào database
            $paymentReport = PaymentReport::updateOrCreate(
                [
                    'payment_method' => $method,
                    'report_date' => $date->format('Y-m-d'),
                ],
                [
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'completed_orders' => $completedOrdersCount,
                    'completed_revenue' => $completedRevenue,
                    'completion_rate' => $completionRate,
                    'average_order_value' => $averageOrderValue,
                ]
            );

            $results[] = $paymentReport;
        }

        return $results;
    }

    /**
     * Tạo lại báo cáo thanh toán cho khoảng thời gian
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function regeneratePaymentReports(Carbon $startDate, Carbon $endDate)
    {
        $allReports = [];
        $currentDate = clone $startDate;

        while ($currentDate->lte($endDate)) {
            $reports = $this->generatePaymentReportForDate($currentDate);
            $allReports = array_merge($allReports, $reports);
            $currentDate->addDay();
        }

        return $allReports;
    }

    /**
     * Lấy tổng quan báo cáo thanh toán
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getPaymentReportOverview($startDate = null, $endDate = null)
    {
        // Mặc định 30 ngày gần nhất nếu không có khoảng thời gian
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30);
        }

        if (!$endDate) {
            $endDate = Carbon::now();
        }

        // Lấy tổng số đơn hàng theo phương thức thanh toán
        $ordersByMethod = DB::table('orders')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_value) as revenue'))
            ->whereNotNull('payment_method')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        // Lấy tổng số đơn hàng đã thu tiền theo phương thức
        $completedByMethod = DB::table('orders')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_value) as revenue'))
            ->whereNotNull('payment_method')
            ->where('status', Order::STATUS_DA_THU_TIEN)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get();

        // Tính toán tỷ lệ hoàn thành
        $results = [];
        foreach ($ordersByMethod as $orderData) {
            $paymentMethod = $orderData->payment_method;
            $completedData = $completedByMethod->where('payment_method', $paymentMethod)->first();

            $completedCount = $completedData ? $completedData->count : 0;
            $completedRevenue = $completedData ? $completedData->revenue : 0;
            $completionRate = $orderData->count > 0 ? ($completedCount / $orderData->count) * 100 : 0;

            $results[] = [
                'payment_method' => $paymentMethod,
                'total_orders' => $orderData->count,
                'total_revenue' => $orderData->revenue,
                'completed_orders' => $completedCount,
                'completed_revenue' => $completedRevenue,
                'completion_rate' => round($completionRate, 2),
                'average_order_value' => $orderData->count > 0 ? $orderData->revenue / $orderData->count : 0
            ];
        }

        return $results;
    }
}
