<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\LiveSessionReport;
use App\Models\Order;
use App\Models\PancakeShop;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth');
    }

    /**
     * Đồng bộ dữ liệu từ Pancake trước khi tính toán báo cáo
     */
    public function syncFromPancake(Request $request)
    {
        // Kiểm tra quyền
        $this->authorize('reports.view');

        try {
            // Lấy thông tin khoảng thời gian nếu được cung cấp
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            // Gọi service để đồng bộ dữ liệu từ Pancake
            $result = app(\App\Services\PancakeSyncService::class)->syncOrdersForReports($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Đã đồng bộ dữ liệu thành công từ Pancake.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị trang báo cáo chính
     */
    public function index()
    {
        $this->authorize('reports.view');
        return view('reports.index');
    }

    /**
     * Hiển thị trang báo cáo tổng doanh thu
     */
    public function totalRevenuePage()
    {
        $this->authorize('reports.total_revenue');
        return view('reports.total_revenue');
    }

    /**
     * Hiển thị trang báo cáo chi tiết
     */
    public function detailPage()
    {
        $this->authorize('reports.detailed');
        return view('reports.detail');
    }

    /**
     * Hiển thị trang báo cáo theo nhóm hàng hóa
     */
    public function productGroupsPage()
    {
        $this->authorize('reports.product_groups');
        return view('reports.product_groups');
    }

    /**
     * Hiển thị trang báo cáo theo chiến dịch
     */
    public function campaignsPage(Request $request)
    {
        // Required permission for viewing campaign reports
        $this->authorize('reports.campaigns');

        // Date filtering
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Build query with date range
        $query = DB::table('orders')
            ->select(
                'post_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_value) as total_revenue'),
                DB::raw('AVG(total_value) as average_order_value')
            )
            ->whereNotNull('post_id')
            ->where('status', '!=', Order::STATUS_DA_HUY)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('post_id')
            ->orderByDesc('total_revenue');

        // Shop filtering
        if ($request->filled('pancake_shop_id')) {
            $query->where('pancake_shop_id', $request->input('pancake_shop_id'));
        }

        // Page filtering
        if ($request->filled('pancake_page_id')) {
            $query->where('pancake_page_id', $request->input('pancake_page_id'));
        }

        // Get the campaigns
        $campaigns = $query->get();

        // Get shops and pages for filter
        $shops = PancakeShop::orderBy('name')->get();

        return view('reports.campaigns', compact(
            'campaigns',
            'startDate',
            'endDate',
            'shops'
        ));
    }

    /**
     * Hiển thị trang báo cáo phiên live
     */
    public function liveSessionsPage()
    {
        $this->authorize('reports.live_sessions');
        return view('reports.live_sessions');
    }

    /**
     * Hiển thị trang báo cáo tỷ lệ chốt đơn
     */
    public function conversionRatesPage()
    {
        $this->authorize('reports.conversion_rates');
        return view('reports.conversion_rates');
    }

    /**
     * Hiển thị trang báo cáo khách hàng mới
     */
    public function newCustomersPage()
    {
        $this->authorize('reports.customer_new');
        return view('reports.new_customers');
    }

    /**
     * Hiển thị trang báo cáo khách hàng cũ
     */
    public function returningCustomersPage()
    {
        $this->authorize('reports.customer_returning');
        return view('reports.returning_customers');
    }

    /**
     * Lấy dữ liệu tổng doanh thu
     */
    public function getTotalRevenue(Request $request)
    {
        $this->authorize('reports.total_revenue');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $totalRevenue = $this->reportService->getTotalRevenue($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue
            ]
        ]);
    }

    /**
     * Lấy dữ liệu doanh thu theo ngày
     */
    public function getDailyRevenue(Request $request)
    {
        $this->authorize('reports.detailed');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $revenueData = $this->reportService->getDailyRevenue($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $revenueData
        ]);
    }

    /**
     * Lấy báo cáo theo chiến dịch (bài post)
     */
    public function getCampaignReport(Request $request)
    {
        $this->authorize('reports.campaigns');

        $postId = $request->input('post_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getCampaignReport($postId, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy danh sách sản phẩm trong chiến dịch
     */
    public function getCampaignProducts(Request $request)
    {
        $this->authorize('reports.campaigns');

        $postId = $request->input('post_id');
        $products = $this->reportService->getCampaignProducts($postId);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Lấy báo cáo theo nhóm hàng hóa
     */
    public function getProductGroupReport(Request $request)
    {
        $this->authorize('reports.product_groups');

        $groupId = $request->input('group_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getProductGroupReport($groupId, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy báo cáo phiên live
     */
    public function getLiveSessionReport(Request $request)
    {
        $this->authorize('reports.live_sessions');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        $liveSessionId = $request->input('live_session_id'); // ID phiên live cụ thể (nếu có)

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $query = LiveSessionReport::query();

        // Lọc theo khoảng thời gian
        if ($startDate) {
            $query->where('session_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('session_date', '<=', $endDate);
        }

        // Lọc theo ID phiên live cụ thể (nếu có)
        if ($liveSessionId) {
            $query->where('live_session_id', $liveSessionId);
        }

        // Lấy danh sách phiên live
        $liveSessions = $query->orderBy('session_date', 'desc')
                             ->orderBy('live_session_id', 'asc')
                             ->get();

        // Nếu không có phân quyền xem tất cả đơn hàng, cần lọc dữ liệu theo userIds
        if ($userIds) {
            // Khi được truy vấn dữ liệu, cần lọc thêm theo quyền của người dùng
            // Sử dụng DB để đếm đơn hàng theo user_id
            $filteredSessions = [];

            foreach ($liveSessions as $session) {
                // Đếm số đơn hàng thuộc phiên live này và nằm trong danh sách userIds
                $orderCount = DB::table('orders')
                    ->whereIn('user_id', $userIds)
                    ->where('live_session_id', $session->live_session_id)
                    ->count();

                // Chỉ hiển thị phiên live có đơn hàng thuộc về người dùng được phép xem
                if ($orderCount > 0) {
                    $filteredSessions[] = $session;
                }
            }

            $liveSessions = collect($filteredSessions);
        }

        return response()->json([
            'success' => true,
            'data' => $liveSessions
        ]);
    }

    /**
     * Lấy chi tiết phiên live
     */
    public function getLiveSessionDetail(Request $request)
    {
        $this->authorize('reports.live_sessions');

        $liveSessionId = $request->input('live_session_id');
        $sessionDate = $request->input('session_date') ? Carbon::parse($request->input('session_date')) : null;

        if (!$liveSessionId || !$sessionDate) {
            return response()->json([
                'success' => false,
                'message' => 'ID phiên live và ngày phiên live là bắt buộc'
            ], 400);
        }

        // Lấy báo cáo phiên live
        $liveSession = LiveSessionReport::where('live_session_id', $liveSessionId)
            ->where('session_date', $sessionDate)
            ->first();

        if (!$liveSession) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phiên live'
            ], 404);
        }

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        // Lấy chi tiết đơn hàng trong phiên live
        $query = Order::where('live_session_id', $liveSessionId)
            ->where('live_session_date', $sessionDate);

        // Lọc theo quyền xem của người dùng
        if ($userIds) {
            $query->whereIn('user_id', $userIds);
        }

        $orders = $query->with(['customer', 'items.product'])
            ->get();

        // Lấy danh sách sản phẩm bán được trong phiên live
        $productStats = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->where('orders.live_session_id', $liveSessionId)
            ->where('orders.live_session_date', $sessionDate);

        // Lọc theo quyền xem của người dùng
        if ($userIds) {
            $productStats->whereIn('orders.user_id', $userIds);
        }

        $products = $productStats->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $liveSession,
                'orders' => $orders,
                'products' => $products
            ]
        ]);
    }

    /**
     * Lấy báo cáo đơn hàng của khách hàng (mới/cũ)
     */
    public function getCustomerOrderReport(Request $request)
    {
        $isFirstOrder = $request->input('is_first_order');

        if ($isFirstOrder) {
            $this->authorize('reports.customer_new');
        } else {
            $this->authorize('reports.customer_returning');
        }

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $reports = $this->reportService->getCustomerOrderReport($isFirstOrder, $startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Lấy báo cáo tỷ lệ chốt đơn
     */
    public function getConversionReport(Request $request)
    {
        $this->authorize('reports.conversion_rates');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->getConversionReport($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Lấy báo cáo chi tiết (nhiều loại dữ liệu)
     */
    public function getDetailReport(Request $request)
    {
        $this->authorize('reports.detailed');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->getDetailReport($startDate, $endDate, $userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Lấy báo cáo hàng ngày
     */
    public function getDailyReport()
    {
        $this->authorize('reports.detailed');

        // Lấy ID người dùng cần xem báo cáo (dựa trên phân quyền)
        $userIds = $this->getUserIdsBasedOnPermission();

        $report = $this->reportService->generateDailyReport($userIds);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Hiển thị trang báo cáo thanh toán
     */
    public function paymentsPage()
    {
        $this->authorize('reports.view');
        return view('reports.payments');
    }

    /**
     * API lấy báo cáo thanh toán
     */
    public function getPaymentReport(Request $request)
    {
        $this->authorize('reports.view');

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
        $paymentMethod = $request->input('payment_method');

        // Tạo báo cáo mới nếu yêu cầu
        if ($request->input('regenerate') === 'true') {
            if ($startDate && $endDate) {
                $this->reportService->regeneratePaymentReports($startDate, $endDate);
            } else {
                // Mặc định tạo báo cáo cho 30 ngày gần nhất
                $endDate = Carbon::now();
                $startDate = Carbon::now()->subDays(30);
                $this->reportService->regeneratePaymentReports($startDate, $endDate);
            }
        }

        $reports = $this->reportService->getPaymentReport($paymentMethod, $startDate, $endDate);
        $overview = $this->reportService->getPaymentReportOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'reports' => $reports,
                'overview' => $overview
            ]
        ]);
    }

    /**
     * API tạo mới báo cáo thanh toán cho một ngày cụ thể
     */
    public function generatePaymentReport(Request $request)
    {
        $this->authorize('reports.view');

        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $reports = $this->reportService->generatePaymentReportForDate($date);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo báo cáo thanh toán cho ngày ' . $date->format('d/m/Y'),
            'data' => $reports
        ]);
    }

    /**
     * Xác định danh sách user IDs mà người dùng hiện tại có quyền xem báo cáo
     * Dựa trên các quyền reports.view_all, reports.view_team, reports.view_own
     */
    protected function getUserIdsBasedOnPermission()
    {
        $user = Auth::user();

        // Nếu user có quyền view_all, không cần lọc theo user_id
        if (Gate::check('reports.view_all', $user)) {
            return null; // Null means no filtering
        }

        // Nếu user có quyền view_team và là manager, lấy ids của staff trong team
        if (Gate::check('reports.view_team', $user) && $user->manages_team_id) {
            $teamUsers = \App\Models\User::where('team_id', $user->manages_team_id)->pluck('id')->toArray();
            return array_merge([$user->id], $teamUsers);
        }

        // Mặc định chỉ xem dữ liệu của chính mình
        return [$user->id];
    }

    /**
     * Display the order report page
     *
     * @return \Illuminate\View\View
     */
    public function orderReportIndex()
    {
        // Get all possible statuses for filtering
        $statuses = \App\Models\Order::getAllStatuses();

        // Get list of users (sales staff) for filtering
        $users = \App\Models\User::whereHas('roles', function($q) {
            $q->where('name', 'staff');
        })->orderBy('name')->get();

        // Get pancake pages for filtering
        $pancakePages = \App\Models\PancakePage::orderBy('name')->get();

        return view('reports.orders', compact('statuses', 'users', 'pancakePages'));
    }

    /**
     * Get order report data based on filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderReportData(Request $request)
    {
        try {
            // Parse filters
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->subDays(30);
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
            $status = $request->input('status');
            $userId = $request->input('user_id');
            $pancakeStatus = $request->input('pancake_status');
            $pageId = $request->input('page_id');

            // Base query
            $query = \App\Models\Order::with(['user', 'items'])
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply filters
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($pancakeStatus) {
                if ($pancakeStatus === 'pushed') {
                    $query->whereNotNull('pancake_order_id');
                } elseif ($pancakeStatus === 'not_pushed') {
                    $query->whereNull('pancake_order_id');
                }
            }

            if ($pageId) {
                $query->where('pancake_page_id', $pageId);
            }

            // Get data
            $orders = $query->get();

            // Calculate totals and stats
            $totalOrders = $orders->count();
            $totalValue = $orders->sum('total_value');
            $totalShippingFee = $orders->sum('shipping_fee');

            // Calculate totals by status
            $totalsByStatus = [];
            foreach (\App\Models\Order::getAllStatuses() as $statusKey => $statusName) {
                $filteredOrders = $orders->where('status', $statusKey);
                $totalsByStatus[$statusKey] = [
                    'count' => $filteredOrders->count(),
                    'value' => $filteredOrders->sum('total_value'),
                    'name' => $statusName
                ];
            }

            // Calculate daily totals for chart
            $dailyData = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $dayOrders = $orders->filter(function ($order) use ($currentDate) {
                    return $order->created_at->format('Y-m-d') === $currentDate->format('Y-m-d');
                });

                $dailyData[] = [
                    'date' => $dateString,
                    'count' => $dayOrders->count(),
                    'value' => $dayOrders->sum('total_value')
                ];

                $currentDate->addDay();
            }

            // Sales by staff
            $salesByUser = [];
            if ($totalOrders > 0) {
                $userTotals = $orders->groupBy('user_id');
                foreach ($userTotals as $userId => $userOrders) {
                    $user = \App\Models\User::find($userId);
                    $salesByUser[] = [
                        'user_id' => $userId,
                        'name' => $user ? $user->name : 'Không xác định',
                        'count' => $userOrders->count(),
                        'value' => $userOrders->sum('total_value'),
                        'percentage' => round($userOrders->count() / $totalOrders * 100, 2)
                    ];
                }
            }

            // Sort by value descending
            usort($salesByUser, function($a, $b) {
                return $b['value'] <=> $a['value'];
            });

            // Return data
            return response()->json([
                'success' => true,
                'data' => [
                    'totalOrders' => $totalOrders,
                    'totalValue' => $totalValue,
                    'totalShippingFee' => $totalShippingFee,
                    'totalsByStatus' => $totalsByStatus,
                    'dailyData' => $dailyData,
                    'salesByUser' => $salesByUser
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating order report data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
