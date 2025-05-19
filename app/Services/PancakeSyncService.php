<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\DailyRevenueAggregate;
use App\Models\CustomerOrderReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PancakeSyncService
{
    /**
     * API endpoint của Pancake
     */
    protected $apiUrl;

    /**
     * API key của Pancake
     */
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('pancake.api_url');
        $this->apiKey = config('pancake.api_key');
    }

    /**
     * Đồng bộ đơn hàng từ Pancake cho mục đích báo cáo
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function syncOrdersForReports($startDate = null, $endDate = null)
    {
        // Nếu không có ngày bắt đầu, mặc định là 30 ngày trước
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
        }

        // Nếu không có ngày kết thúc, mặc định là hiện tại
        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay();
        }

        $result = [
            'orders_synced' => 0,
            'revenue_synced' => 0,
            'customers_synced' => 0,
            'live_sessions_synced' => 0,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];

        try {
            // Gọi API Pancake để lấy đơn hàng trong khoảng thời gian
            $orders = $this->fetchOrdersFromPancake($startDate, $endDate);

            // Bắt đầu giao dịch DB
            DB::beginTransaction();

            foreach ($orders as $orderData) {
                // Kiểm tra xem đơn hàng đã tồn tại trong hệ thống chưa
                $existingOrder = Order::where('pancake_id', $orderData['id'])->first();

                // Kiểm tra thông tin phiên live từ ghi chú
                $liveSessionInfo = $this->extractLiveSessionInfo($orderData['notes'] ?? '');

                if ($existingOrder) {
                    // Cập nhật đơn hàng hiện có
                    $existingOrder->update([
                        'total_amount' => $orderData['total'],
                        'status' => $this->mapPancakeStatus($orderData['status']),
                        'pancake_synced_at' => Carbon::now(),
                        'notes' => $orderData['notes'] ?? $existingOrder->notes,
                        'live_session_id' => $liveSessionInfo ? $liveSessionInfo['id'] : $existingOrder->live_session_id,
                        'live_session_date' => $liveSessionInfo ? $liveSessionInfo['date'] : $existingOrder->live_session_date
                    ]);
                } else {
                    // Tạo đơn hàng mới từ dữ liệu Pancake
                    $customer = $this->syncCustomer($orderData['customer']);

                    $newOrder = Order::create([
                        'pancake_id' => $orderData['id'],
                        'order_number' => $orderData['order_number'] ?? 'P' . $orderData['id'],
                        'customer_id' => $customer->id,
                        'user_id' => $orderData['assigned_user_id'] ?? null,
                        'total_amount' => $orderData['total'],
                        'status' => $this->mapPancakeStatus($orderData['status']),
                        'source' => $orderData['source'] ?? 'pancake',
                        'notes' => $orderData['notes'] ?? null,
                        'live_session_id' => $liveSessionInfo ? $liveSessionInfo['id'] : null,
                        'live_session_date' => $liveSessionInfo ? $liveSessionInfo['date'] : null,
                        'created_at' => Carbon::parse($orderData['created_at']),
                        'pancake_synced_at' => Carbon::now()
                    ]);

                    // Đồng bộ các items của đơn hàng
                    if (isset($orderData['items']) && is_array($orderData['items'])) {
                        $this->syncOrderItems($newOrder, $orderData['items']);
                    }

                    $result['orders_synced']++;
                    $result['revenue_synced'] += $orderData['total'];

                    // Đếm số phiên live đã đồng bộ
                    if ($liveSessionInfo) {
                        $result['live_sessions_synced']++;
                    }
                }
            }

            // Cập nhật thông tin báo cáo
            $this->updateReportData($startDate, $endDate);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi đồng bộ dữ liệu từ Pancake: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lấy dữ liệu đơn hàng từ Pancake API
     */
    protected function fetchOrdersFromPancake($startDate, $endDate)
    {
        // Trong trường hợp thực tế, đây sẽ là một cuộc gọi API đến Pancake
        // Ví dụ mẫu:
        /*
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->apiUrl . '/orders', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        if ($response->successful()) {
            return $response->json('data', []);
        }
        */

        // Trả về mảng rỗng cho tài liệu
        Log::info('Fetching orders from Pancake API for date range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
        return [];
    }

    /**
     * Đồng bộ và cập nhật thông tin khách hàng
     */
    protected function syncCustomer($customerData)
    {
        // Tìm khách hàng theo phone hoặc email
        $customer = Customer::where('phone', $customerData['phone'])
            ->orWhere('email', $customerData['email'])
            ->first();

        if (!$customer) {
            // Tạo khách hàng mới nếu chưa tồn tại
            $customer = Customer::create([
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'],
                'address' => $customerData['address'] ?? null,
                'pancake_id' => $customerData['id'] ?? null,
                'pancake_synced_at' => Carbon::now()
            ]);
        } else {
            // Cập nhật thông tin khách hàng
            $customer->update([
                'name' => $customerData['name'],
                'address' => $customerData['address'] ?? $customer->address,
                'pancake_id' => $customerData['id'] ?? $customer->pancake_id,
                'pancake_synced_at' => Carbon::now()
            ]);
        }

        return $customer;
    }

    /**
     * Đồng bộ các items của đơn hàng
     */
    protected function syncOrderItems($order, $items)
    {
        foreach ($items as $itemData) {
            $order->items()->create([
                'product_id' => $this->getOrCreateProduct($itemData),
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'total' => $itemData['total'] ?? ($itemData['price'] * $itemData['quantity'])
            ]);
        }
    }

    /**
     * Lấy hoặc tạo sản phẩm từ dữ liệu Pancake
     */
    protected function getOrCreateProduct($itemData)
    {
        // Tìm sản phẩm theo sku hoặc tên
        $product = Product::where('sku', $itemData['sku'] ?? '')
            ->orWhere('name', $itemData['name'])
            ->first();

        if (!$product) {
            // Tạo sản phẩm mới nếu chưa tồn tại
            $product = Product::create([
                'name' => $itemData['name'],
                'sku' => $itemData['sku'] ?? null,
                'price' => $itemData['price'],
                'pancake_id' => $itemData['product_id'] ?? null,
                'pancake_synced_at' => Carbon::now()
            ]);
        }

        return $product->id;
    }

    /**
     * Chuyển đổi trạng thái từ Pancake sang trạng thái của hệ thống
     */
    protected function mapPancakeStatus($pancakeStatus)
    {
        $statusMap = [
            'pending' => 'new',
            'processing' => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'on-hold' => 'on_hold',
            'refunded' => 'refunded'
        ];

        return $statusMap[$pancakeStatus] ?? 'new';
    }

    /**
     * Cập nhật dữ liệu báo cáo dựa trên dữ liệu mới đồng bộ
     */
    protected function updateReportData($startDate, $endDate)
    {
        // Cập nhật dữ liệu cho báo cáo tổng doanh thu
        $this->updateRevenueReports($startDate, $endDate);

        // Cập nhật dữ liệu cho báo cáo theo chiến dịch
        $this->updateCampaignReports($startDate, $endDate);

        // Cập nhật dữ liệu cho báo cáo theo nhóm sản phẩm
        $this->updateProductGroupReports($startDate, $endDate);

        // Cập nhật dữ liệu cho báo cáo khách hàng mới/cũ
        $this->updateCustomerReports($startDate, $endDate);
    }

    /**
     * Cập nhật báo cáo doanh thu
     */
    protected function updateRevenueReports($startDate, $endDate)
    {
        // Xóa dữ liệu báo cáo cũ trong khoảng thời gian này (nếu cần)
        // DB::table('daily_revenue_aggregates')->whereBetween('date', [$startDate, $endDate])->delete();

        // Tổng hợp lại dữ liệu từ đơn hàng
        $dailyRevenue = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        // Lưu vào bảng tổng hợp (nếu có)
        foreach ($dailyRevenue as $data) {
            DailyRevenueAggregate::updateOrCreate(
                ['date' => $data->date],
                [
                    'revenue' => $data->revenue,
                    'orders_count' => $data->orders_count
                ]
            );
        }
    }

    /**
     * Cập nhật báo cáo theo chiến dịch
     */
    protected function updateCampaignReports($startDate, $endDate)
    {
        // Xử lý logic cập nhật báo cáo theo chiến dịch
        // Code tùy thuộc vào cấu trúc của bảng CampaignReport

        // Cập nhật báo cáo phiên live - gọi phương thức mới
        $this->updateLiveSessionReports($startDate, $endDate);
    }

    /**
     * Cập nhật báo cáo phiên live từ đơn hàng
     */
    protected function updateLiveSessionReports($startDate, $endDate)
    {
        // Tìm tất cả đơn hàng có thông tin phiên live trong khoảng thời gian
        $liveSessions = DB::table('orders')
            ->select(
                'live_session_id',
                DB::raw('MIN(live_session_date) as session_date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('COUNT(DISTINCT customer_id) as total_customers')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('live_session_id')
            ->whereNotNull('live_session_date')
            ->groupBy('live_session_id')
            ->get();

        // Lưu hoặc cập nhật báo cáo phiên live
        foreach ($liveSessions as $session) {
            // Kiểm tra xem phiên live đã có trong báo cáo chưa
            $liveSessionReport = DB::table('live_session_reports')
                ->where('live_session_id', $session->live_session_id)
                ->first();

            $reportData = [
                'live_session_id' => $session->live_session_id,
                'session_date' => $session->session_date,
                'total_orders' => $session->total_orders,
                'total_revenue' => $session->total_revenue,
                'total_customers' => $session->total_customers,
                'updated_at' => Carbon::now()
            ];

            if (!$liveSessionReport) {
                // Tạo mới báo cáo
                DB::table('live_session_reports')->insert(array_merge($reportData, [
                    'created_at' => Carbon::now()
                ]));
            } else {
                // Cập nhật báo cáo hiện có
                DB::table('live_session_reports')
                    ->where('live_session_id', $session->live_session_id)
                    ->update($reportData);
            }
        }

        // Tính thêm thông tin sản phẩm bán được trong mỗi phiên live
        $liveSessionsWithProducts = $this->getLiveSessionProductStats($startDate, $endDate);

        // Cập nhật thông tin sản phẩm bán được
        foreach ($liveSessionsWithProducts as $sessionId => $productStats) {
            DB::table('live_session_reports')
                ->where('live_session_id', $sessionId)
                ->update([
                    'top_products' => json_encode($productStats, JSON_UNESCAPED_UNICODE),
                    'updated_at' => Carbon::now()
                ]);
        }
    }

    /**
     * Lấy thống kê sản phẩm bán được trong phiên live
     */
    protected function getLiveSessionProductStats($startDate, $endDate)
    {
        // Kết quả chứa thông tin về sản phẩm bán được trong mỗi phiên live
        $results = [];

        // Tìm tất cả đơn hàng có thông tin phiên live trong khoảng thời gian
        $liveSessions = DB::table('orders')
            ->select('id', 'live_session_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('live_session_id')
            ->get();

        // Tạo danh sách ID đơn hàng theo phiên live
        $orderIdsBySession = [];
        foreach ($liveSessions as $order) {
            $orderIdsBySession[$order->live_session_id][] = $order->id;
        }

        // Truy vấn thông tin sản phẩm bán được trong mỗi phiên live
        foreach ($orderIdsBySession as $sessionId => $orderIds) {
            $productStats = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
                )
                ->whereIn('order_items.order_id', $orderIds)
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_quantity')
                ->limit(10) // Chỉ lấy 10 sản phẩm bán chạy nhất
                ->get()
                ->toArray();

            $results[$sessionId] = $productStats;
        }

        return $results;
    }

    /**
     * Cập nhật báo cáo theo nhóm sản phẩm
     */
    protected function updateProductGroupReports($startDate, $endDate)
    {
        // Xử lý logic cập nhật báo cáo theo nhóm sản phẩm
        // Code tùy thuộc vào cấu trúc của bảng ProductGroupReport
    }

    /**
     * Cập nhật báo cáo khách hàng mới/cũ
     */
    protected function updateCustomerReports($startDate, $endDate)
    {
        // Lấy danh sách đơn hàng trong khoảng thời gian
        $orders = Order::with('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($orders as $order) {
            // Kiểm tra xem đây là đơn đầu tiên của khách hàng hay không
            $isFirstOrder = $order->customer->orders()
                ->where('id', '<', $order->id)
                ->count() == 0;

            // Cập nhật hoặc tạo báo cáo khách hàng
            CustomerOrderReport::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id
                ],
                [
                    'user_id' => $order->user_id,
                    'order_date' => $order->created_at,
                    'amount' => $order->total_amount,
                    'is_first_order' => $isFirstOrder
                ]
            );
        }
    }

    /**
     * Phân tích thông tin phiên live từ ghi chú đơn hàng
     * Format: "LIVE1 17/5/2025" -> phiên live số 1 ngày 17/5/2025
     *
     * @param string $notes
     * @return array|null Thông tin phiên live (id, date) hoặc null nếu không tìm thấy
     */
    protected function extractLiveSessionInfo($notes)
    {
        // Pattern để tìm thông tin phiên live: LIVE{số} {ngày/tháng/năm}
        preg_match('/LIVE(\d+)\s+(\d{1,2}\/\d{1,2}\/\d{4})/', $notes, $matches);

        if (count($matches) >= 3) {
            $liveSessionId = (int)$matches[1];
            try {
                $liveSessionDate = Carbon::createFromFormat('d/m/Y', $matches[2])->startOfDay();
                return [
                    'id' => $liveSessionId,
                    'date' => $liveSessionDate,
                    'raw' => $matches[0]
                ];
            } catch (\Exception $e) {
                Log::warning('Không thể phân tích ngày phiên live từ ghi chú: ' . $notes);
                return null;
            }
        }

        return null;
    }
}
