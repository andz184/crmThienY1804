@extends('adminlte::page')

@section('title', 'Báo cáo doanh thu live')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Báo cáo doanh thu live</h1>
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpModal">
        <i class="fas fa-question-circle"></i> Hướng dẫn
    </button>
</div>
@stop

@section('content')
<!-- Modal Hướng dẫn -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="fas fa-question-circle mr-2"></i>
                    Hướng dẫn sử dụng báo cáo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Báo cáo này giúp bạn theo dõi hiệu quả kinh doanh của các phiên livestream.
                </div>

                <h5 class="text-primary mb-3">
                    <i class="fas fa-chart-line mr-2"></i>
                    Các chỉ số quan trọng:
                </h5>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-info">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Doanh thu
                        </h6>
                        <ul class="list-unstyled ml-4">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <strong>Doanh thu dự kiến:</strong> Tổng giá trị của tất cả đơn hàng (bao gồm cả đơn đang xử lý và đơn hủy)
                    </li>
                    <li>
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <strong>Doanh thu thực tế:</strong> Tổng giá trị của các đơn hàng đã giao thành công và thanh toán đủ
                    </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-info">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Đơn hàng
                        </h6>
                        <ul class="list-unstyled ml-4">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <strong>Tổng đơn:</strong> Số lượng tất cả đơn hàng được tạo
                    </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <strong>Đơn chốt:</strong> Số đơn đã giao hàng và thanh toán thành công
                    </li>
                    <li>
                                <i class="fas fa-times-circle text-danger mr-2"></i>
                                <strong>Đơn hủy:</strong> Số đơn bị hủy (bởi khách hàng hoặc shop)
                    </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-info">
                            <i class="fas fa-percentage mr-2"></i>
                            Tỷ lệ hiệu quả
                        </h6>
                        <ul class="list-unstyled ml-4">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success mr-2"></i>
                                <strong>Tỷ lệ chốt đơn:</strong> Phần trăm đơn hàng thành công trên tổng số đơn đã xử lý
                    </li>
                    <li>
                                <i class="fas fa-times-circle text-danger mr-2"></i>
                                <strong>Tỷ lệ hủy:</strong> Phần trăm đơn hàng bị hủy trên tổng số đơn
                    </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="text-info">
                            <i class="fas fa-users mr-2"></i>
                            Khách hàng
                        </h6>
                        <ul class="list-unstyled ml-4">
                            <li class="mb-2">
                                <i class="fas fa-star text-warning mr-2"></i>
                                <strong>Khách mới:</strong> Số khách hàng lần đầu mua hàng trong phiên live
                    </li>
                    <li>
                                <i class="fas fa-heart text-danger mr-2"></i>
                                <strong>Khách cũ:</strong> Số khách hàng đã từng mua hàng trước đó
                    </li>
                </ul>
            </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-header.bg-gradient-info {
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
}
.modal-body .card {
    border: none;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}
.modal-body .card:hover {
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}
.modal-body .list-unstyled li {
    padding: 5px 0;
}
.modal-body .alert-info {
    background-color: rgba(139, 92, 246, 0.1);
    border-color: rgba(139, 92, 246, 0.2);
    color: #8b5cf6;
}
</style>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="form-group">
            <label>Khoảng thời gian</label>
            <div class="input-group">
                <input type="text" class="form-control" id="daterange" value="{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    <button type="button" class="btn btn-theme" id="filter-btn" style="background-color:rgba(0, 0, 0, 0.1); border-color: rgba(0, 0, 0, 0.1);">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-right">
        <div class="btn-group mt-4">
            {{-- <button type="button" class="btn btn-default" id="refresh-btn" title="Làm mới dữ liệu">
                <i class="fas fa-sync"></i>
            </button> --}}
        </div>
    </div>
</div>

<!-- Live Dashboard -->
<div class="live-dashboard mb-4">
    <div class="dashboard-header p-4">
        <div class="text-center mb-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-white-50 mb-2">Doanh thu dự kiến (đ)</p>
            <h1 class="revenue-display mb-4">{{ number_format($summary['expected_revenue'] ?? 0, 0, ',', '.') }}</h1>
                </div>
                <div class="col-md-6">
                    <p class="text-white-50 mb-2">Doanh thu thực tế (đ)</p>
                    <h1 class="revenue-display mb-4">{{ number_format($summary['actual_revenue'] ?? 0, 0, ',', '.') }}</h1>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-7 mb-4">
                <div class="stat-item">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Tổng đơn</span>
                    </div>
                    <h3>{{ number_format($summary['total_orders'] ?? 0, 0, ',', '.') }}</h3>
                </div>
                <div class="stat-item">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-users"></i>
                        <span>Tổng khách</span>
                    </div>
                    <h3>{{ number_format($summary['total_customers'] ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        <div class="dashboard-stats">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Đơn chốt</div>
                        <div class="stat-value">{{ number_format($summary['successful_orders'] ?? 0, 0, ',', '.') }}</div>
                        <div class="stat-change text-{{ $ordersChangeRate >= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $ordersChangeRate >= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($ordersChangeRate), 2, ',', '.') }}%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Đơn hủy</div>
                        <div class="stat-value">{{ number_format($summary['canceled_orders'] ?? 0, 0, ',', '.') }}</div>
                        <div class="stat-change text-{{ $canceledOrdersChangeRate <= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $canceledOrdersChangeRate <= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($canceledOrdersChangeRate), 2, ',', '.') }}%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Tỷ lệ chốt</div>
                        <div class="stat-value">{{ number_format($summary['conversion_rate'] ?? 0, 1, ',', '.') }}%</div>
                        <div class="stat-change text-{{ $successRateChange >= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $successRateChange >= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($successRateChange), 1, ',', '.') }}%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-label">Khách hàng</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="customer-stat">
                                    <span class="customer-label">Mới:</span>
                                    <span class="customer-value">{{ number_format($summary['new_customers'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="customer-stat">
                                    <span class="customer-label">Cũ:</span>
                                    <span class="customer-value">{{ number_format($summary['returning_customers'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="customer-total">
                                Tổng: {{ number_format($summary['total_customers'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.live-dashboard {
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    border-radius: 16px;
    color: white;
}

.dashboard-header {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 16px;
}

.revenue-display {
    font-size: 4rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -1px;
}

.stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem 2rem;
    border-radius: 12px;
}

.stat-item span {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
}

.stat-item h3 {
    font-size: 1.75rem;
    margin: 0.5rem 0 0;
    font-weight: 600;
}

.dashboard-stats {
    margin-top: 1rem;
}

.stat-box {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    margin-bottom: 0.75rem;
    font-weight: 500;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.stat-change {
    font-size: 0.9rem;
    font-weight: 500;
}

.customer-stat {
    margin-bottom: 0.5rem;
}

.customer-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-right: 0.5rem;
}

.customer-value {
    font-size: 1.1rem;
    font-weight: 600;
}

.customer-total {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    padding-left: 1rem;
    border-left: 1px solid rgba(255, 255, 255, 0.2);
}

.text-success {
    color: #4ade80 !important;
}

.text-danger {
    color: #fb7185 !important;
}

@media (max-width: 768px) {
    .revenue-display {
        font-size: 3rem;
    }

    .stat-item {
        padding: 0.75rem 1.5rem;
    }

    .dashboard-stats .row {
        margin: 0 -0.5rem;
    }

    .dashboard-stats .col-md-3 {
        padding: 0 0.5rem;
        margin-bottom: 1rem;
    }
}
</style>

<style>
.live-dashboard {
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    border-radius: 12px;
    color: white;
}

.dashboard-header {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

.revenue-display {
    font-size: 3.5rem;
    font-weight: 700;
    margin: 0;
}

.stat-item {
    text-align: center;
    margin:  10px;
}

.stat-item span {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.stat-item h3 {
    font-size: 1.5rem;
    margin: 0.5rem 0 0;
}

.dashboard-stats {
    margin-top: 2rem;
}

.stat-box {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    height: 100%;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-change {
    font-size: 0.9rem;
}

.stat-sub {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.text-success {
    color: #4ade80 !important;
}

.text-danger {
    color: #fb7185 !important;
}
</style>

<!-- Bảng thống kê chi tiết -->
<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Doanh thu dự kiến</th>
                    <th>Doanh thu thực tế</th>
                    <th>Tỷ lệ chốt</th>
                    <th>Đơn chốt</th>
                    <th>Đơn hủy</th>
                    <th>Đơn đang giao</th>
                    <th>Khách mới</th>
                    <th>Khách cũ</th>
                    <th>Tổng khách</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($summary['expected_revenue'] ?? 0, 0, ',', '.') }} đ
                        <small class="text-{{ $revenueChangeRate >= 0 ? 'success' : 'danger' }}">
                            {{ $revenueChangeRate >= 0 ? '+' : '' }}{{ number_format($revenueChangeRate, 2, ',', '.') }}%
                        </small>
                    </td>
                    <td>{{ number_format($summary['actual_revenue'] ?? 0, 0, ',', '.') }} đ</td>
                    <td>{{ number_format($summary['conversion_rate'] ?? 0, 1, ',', '.') }}%</td>
                    <td>{{ number_format($summary['successful_orders'] ?? 0, 0, ',', '.') }}
                        <small class="text-{{ $ordersChangeRate >= 0 ? 'success' : 'danger' }}">
                            {{ $ordersChangeRate >= 0 ? '+' : '' }}{{ number_format($ordersChangeRate, 2, ',', '.') }}%
                        </small>
                    </td>
                    <td>{{ number_format($summary['canceled_orders'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary['delivering_orders'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary['new_customers'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary['returning_customers'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($summary['total_customers'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Biểu đồ doanh thu -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Biểu đồ doanh thu {{ $chartType === 'monthly' ? 'theo tháng' : ($chartType === 'hourly' ? 'theo giờ' : 'theo ngày') }}</h3>
    </div>
    <div class="card-body">
        <canvas id="revenueChart" style="min-height: 300px;"></canvas>
    </div>
</div>

<!-- Thống kê theo tỉnh thành -->
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Biểu đồ doanh thu theo tỉnh thành</h3>
            </div>
            <div class="card-body">
                <canvas id="provinceLineChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Bản đồ phân bố doanh thu</h3>
            </div>
            <div class="card-body">
                <div id="province-map" style="height: 400px;"></div>
                <!-- Xóa bảng dữ liệu tỉnh thành -->
            </div>
        </div>
    </div>
</div>

<!-- Top 5 sản phẩm bán chạy -->
<div class="row">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header">
                <h3 class="card-title">Thống kê sản phẩm bán chạy</h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="topProductsChart"></canvas>
                </div>

                <h4 class="mt-4">Chi tiết sản phẩm</h4>
                <table id="product-details-table" class="table table-sm table-bordered table-striped mt-2">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sản phẩm</th>
                            <th class="text-right">SL Đặt</th>
                            <th class="text-right">DT Dự kiến</th>
                            <th class="text-right">SL Thực tế</th>
                            <th class="text-right">DT Thực tế</th>
                            <th class="text-right">Số đơn</th>
                            <th class="text-right">Giá TB Dự kiến</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $index => $product)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $product['name'] }}
                                <small class="d-block text-muted">ID: {{ $product['id'] }}</small>
                            </td>
                            <td class="text-right">{{ number_format($product['quantity_ordered'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($product['expected_revenue'] ?? 0, 0, ',', '.') }} đ</td>
                            <td class="text-right">{{ number_format($product['quantity_actual'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($product['actual_revenue'] ?? 0, 0, ',', '.') }} đ</td>
                            <td class="text-right">{{ number_format($product['orders_count'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($product['average_price'] ?? 0, 0, ',', '.') }} đ</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu sản phẩm.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết phiên live -->
<div class="card table-card">
    <div class="card-header">
        <h3 class="card-title">Chi tiết các phiên live</h3>
    </div>
    <div class="card-body">
        <table id="live-sessions-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Phiên Live</th>
                    <th>Ngày</th>
                    <th>Doanh thu dự kiến</th>
                    <th>Doanh thu thực tế</th>
                    <th>Tổng đơn</th>
                    <th>Đơn chốt</th>
                    <th>Đơn hủy</th>
                    <th>Tỷ lệ chốt (%)</th>
                    <th>Tỷ lệ hủy (%)</th>
                    <th>Khách mới</th>
                    <th>Khách cũ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liveSessions as $session)
                <tr>
                    <td>{{ $session['live_number'] }}</td>
                    <td>{{ $session['date'] }}</td>
                    <td>{{ number_format($session['expected_revenue'], 0, ',', '.') }}</td>
                    <td>{{ number_format($session['actual_revenue'], 0, ',', '.') }}</td>
                    <td>{{ $session['total_orders'] }}</td>
                    <td>{{ $session['successful_orders'] }}</td>
                    <td>{{ $session['canceled_orders'] }}</td>
                    <td>{{ number_format($session['success_rate'], 1) }}%</td>
                    <td>{{ number_format($session['cancellation_rate'], 1) }}%</td>
                    <td>{{ $session['new_customers'] }}</td>
                    <td>{{ $session['returning_customers'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<!-- Thêm CSS cho Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    /* Custom button styling */
    .btn-theme {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-theme:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
        color: white;
    }

    .btn-theme:active {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-theme.btn-outline {
        background: transparent;
        border: 1px solid #8b5cf6;
        color: #8b5cf6;
    }

    .btn-theme.btn-outline:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        color: white;
    }

    /* Input group styling */
    .input-group {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .input-group .form-control {
        border: 1px solid rgba(139, 92, 246, 0.2);
        padding: 0.5rem 1rem;
    }

    .input-group-text {
        background-color: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        color: #8b5cf6;
    }

    /* DateRangePicker customization */
    .daterangepicker {
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .daterangepicker .calendar-table {
        border-radius: 0.5rem;
    }

    .daterangepicker td.active, .daterangepicker td.active:hover {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    .daterangepicker .ranges li.active {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    }

    /* DataTables button styling */
    .dataTables_wrapper .dt-buttons .dt-button {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        margin: 0.25rem;
    }

    .dataTables_wrapper .dt-buttons .dt-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
    }

    /* Update existing styles */
    .card {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        margin-bottom: 1rem;
    }
    .table-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        border: 0;
    }
    .table-card .card-header {
        background: transparent;
        border-bottom: 1px solid #dbdade;
        padding: 1.5rem;
    }
    .table-card .card-header h3.card-title {
        color: #566a7f;
        font-size: 1.125rem;
        font-weight: 500;
        margin: 0;
    }
    .table-card .card-body {
        padding: 0;
    }
    .table-card .table {
        margin: 0;
        color: #697a8d;
    }
    .table-card .dataTables_wrapper {
        padding: 1.5rem;
    }
    .table-card .dataTables_wrapper .dataTables_length label,
    .table-card .dataTables_wrapper .dataTables_filter label {
        color: #566a7f;
        font-size: 0.875rem;
    }
    .table-card .dataTables_length select {
        background-color: #fff;
        border: 1px solid #dbdade;
        border-radius: 0.375rem;
        padding: 0.4375rem 2rem 0.4375rem 1rem;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.53;
        color: #697a8d;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23697a8d' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        appearance: none;
    }
    .table-card .dataTables_filter input {
        border: 1px solid #dbdade;
        border-radius: 0.375rem;
        padding: 0.4375rem 0.875rem;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.53;
        color: #697a8d;
        background-color: #fff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        width: 250px;
    }
    .table-card .dataTables_filter input:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0.25rem rgba(139, 92, 246, 0.1);
        outline: 0;
    }
    .table-card .table thead th {
        background-color: #f5f5f9;
        border-bottom: 1px solid #dbdade;
        color: #566a7f;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.75rem 1.375rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    .table-card .table tbody td {
        border-bottom: 1px solid #dbdade;
        color: #697a8d;
        padding: 1rem 1.375rem;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .table-card .table tbody tr:last-child td {
        border-bottom: 0;
    }
    .table-card .table tbody tr:hover {
        background-color: rgba(67, 89, 113, 0.04);
    }
    .table-card .dataTables_paginate {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #dbdade;
    }
    .table-card .dataTables_paginate .paginate_button {
        margin: 0 0.125rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid transparent;
        color: #697a8d;
        font-size: 0.875rem;
        min-width: 2.25rem;
        text-align: center;
    }
    .table-card .dataTables_paginate .paginate_button:hover {
        background: rgba(139, 92, 246, 0.1);
        border-color: transparent;
        color: #8b5cf6 !important;
    }
    .table-card .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
        border-color: transparent;
        color: #fff !important;
        font-weight: 500;
    }
    .table-card .dataTables_paginate .paginate_button.disabled {
        color: #697a8d !important;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .table-card .dataTables_info {
        color: #697a8d;
        font-size: 0.875rem;
        padding-top: 0.75rem;
    }
    .table-card .table td small {
        color: #a1acb8;
        font-size: 85%;
    }
    .table-card .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 500;
        border-radius: 0.25rem;
    }
    .table-card .badge-success {
        background-color: rgba(113, 221, 55, 0.16);
        color: #71dd37;
    }
    .table-card .badge-danger {
        background-color: rgba(255, 62, 29, 0.16);
        color: #ff3e1d;
    }
    .table-card .badge-warning {
        background-color: rgba(255, 171, 0, 0.16);
        color: #ffab00;
    }
    .table-card .badge-info {
        background-color: rgba(3, 195, 236, 0.16);
        color: #03c3ec;
    }
    @media (max-width: 767.98px) {
        .table-card .dataTables_wrapper .dataTables_length,
        .table-card .dataTables_wrapper .dataTables_filter {
            text-align: left;
            margin-bottom: 1rem;
        }
        .table-card .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            margin-left: 0;
        }
        .table-card .dataTables_wrapper .dataTables_info,
        .table-card .dataTables_wrapper .dataTables_paginate {
            text-align: center;
            margin-top: 1rem;
        }
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<!-- Thay thế Highcharts bằng Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
$(function() {
    // Date range picker configuration
    $('#daterange').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Áp dụng',
            cancelLabel: 'Hủy',
            customRangeLabel: 'Tùy chọn'
        },
        ranges: {
            'Hôm nay': [moment(), moment()],
            'Hôm qua': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '7 ngày qua': [moment().subtract(6, 'days'), moment()],
            '30 ngày qua': [moment().subtract(29, 'days'), moment()],
            'Tháng này': [moment().startOf('month'), moment().endOf('month')],
            'Tháng trước': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment('{{ $startDate->format("Y-m-d") }}'),
        endDate: moment('{{ $endDate->format("Y-m-d") }}'),
        opens: 'left',
        drops: 'auto'
    });

    // Handle filter button click
    $('#filter-btn').on('click', function() {
        const picker = $('#daterange').data('daterangepicker');
        window.location.href = '{{ route("reports.live-sessions") }}?' + $.param({
            start_date: picker.startDate.format('YYYY-MM-DD'),
            end_date: picker.endDate.format('YYYY-MM-DD')
        });
    });

    // Handle reset button click
    $('#reset-date-btn').on('click', function() {
        window.location.href = '{{ route("reports.live-sessions") }}';
    });

    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const chartData = @json($chartData);
    const chartType = @json($chartType);

    let labels = [];
    if (chartType === 'hourly') {
        labels = Object.keys(chartData).map(hour => `${hour}:00`);
    } else if (chartType === 'monthly') {
        labels = Object.keys(chartData).map(month => moment(month).format('MM/YYYY'));
    } else {
        labels = Object.keys(chartData).map(date => moment(date).format('DD/MM/YYYY'));
    }

    const datasets = [
        {
            label: 'Doanh thu dự kiến',
            data: Object.values(chartData).map(data => data.expected_revenue),
            borderColor: '#8884d8',
            backgroundColor: 'rgba(136, 132, 216, 0.1)',
            tension: 0.3,
            fill: true
        },
        {
            label: 'Doanh thu thực tế',
            data: Object.values(chartData).map(data => data.actual_revenue),
            borderColor: '#82ca9d',
            backgroundColor: 'rgba(130, 202, 157, 0.1)',
            tension: 0.3,
            fill: true
        }
    ];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Doanh thu (VNĐ)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000000) {
                                return (value / 1000000000).toFixed(1) + ' tỷ';
                            }
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + ' triệu';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(1) + ' nghìn';
                            }
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: chartType === 'monthly' ? 'Tháng' : (chartType === 'hourly' ? 'Giờ' : 'Ngày'),
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND',
                                    maximumFractionDigits: 0
                                }).format(context.parsed.y);
                        }
                    }
                },
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Biểu đồ doanh thu theo ' + (chartType === 'monthly' ? 'tháng' : (chartType === 'hourly' ? 'giờ' : 'ngày')),
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: {
                        top: 10,
                        bottom: 30
                    }
                }
            }
        }
    });

    // Province Map
    const provinceStats = @json($provinceStats ?? []);

    // Biểu đồ đường theo tỉnh
    const provinceLineCtx = document.getElementById('provinceLineChart').getContext('2d');
    const sortedProvinceStats = provinceStats
        .filter(stat => stat.revenue > 0) // Chỉ lấy các tỉnh có doanh thu
        .sort((a, b) => b.revenue - a.revenue);

    // Hàm chuyển đổi tên tỉnh thành mã tỉnh cho Highcharts
    function getProvinceKey(provinceName) {
        // Chuẩn hóa tên tỉnh để xử lý các trường hợp có tiền tố "Tỉnh" hoặc "Thành phố"
        const normalizedName = provinceName
            .replace(/^(Thành phố|Tỉnh)\s/i, '')
            .replace('TP Hồ Chí Minh', 'Hồ Chí Minh'); // Trường hợp đặc biệt

        const provinceMapping = {
            'Hồ Chí Minh': 'vn-hc',
            'Đà Nẵng': 'vn-dn',
            'Hà Nội': 'vn-hn',
            'Hải Phòng': 'vn-hp',
            'Cần Thơ': 'vn-ct',
            'An Giang': 'vn-ag',
            'Bà Rịa - Vũng Tàu': 'vn-br',
            'Bắc Giang': 'vn-bg',
            'Bắc Kạn': 'vn-bk',
            'Bạc Liêu': 'vn-bl',
            'Bắc Ninh': 'vn-bn',
            'Bến Tre': 'vn-bt',
            'Bình Định': 'vn-bd',
            'Bình Dương': 'vn-bi',
            'Bình Phước': 'vn-bp',
            'Bình Thuận': 'vn-bu',
            'Cà Mau': 'vn-cm',
            'Cao Bằng': 'vn-cb',
            'Đắk Lắk': 'vn-da',
            'Đắk Nông': 'vn-do',
            'Điện Biên': 'vn-db',
            'Đồng Nai': 'vn-di',
            'Đồng Tháp': 'vn-dt',
            'Gia Lai': 'vn-gl',
            'Hà Giang': 'vn-hg',
            'Hà Nam': 'vn-ha',
            'Hà Tĩnh': 'vn-ht',
            'Hải Dương': 'vn-hd',
            'Hậu Giang': 'vn-hu',
            'Hòa Bình': 'vn-hb',
            'Hưng Yên': 'vn-hy',
            'Khánh Hòa': 'vn-kh',
            'Kiên Giang': 'vn-kg',
            'Kon Tum': 'vn-kt',
            'Lai Châu': 'vn-lc',
            'Lâm Đồng': 'vn-ld',
            'Lạng Sơn': 'vn-ls',
            'Lào Cai': 'vn-li',
            'Long An': 'vn-la',
            'Nam Định': 'vn-nd',
            'Nghệ An': 'vn-na',
            'Ninh Bình': 'vn-nb',
            'Ninh Thuận': 'vn-nt',
            'Phú Thọ': 'vn-pt',
            'Phú Yên': 'vn-py',
            'Quảng Bình': 'vn-qb',
            'Quảng Nam': 'vn-qm',
            'Quảng Ngãi': 'vn-qg',
            'Quảng Ninh': 'vn-qi',
            'Quảng Trị': 'vn-qt',
            'Sóc Trăng': 'vn-st',
            'Sơn La': 'vn-sl',
            'Tây Ninh': 'vn-ty',
            'Thái Bình': 'vn-tb',
            'Thái Nguyên': 'vn-tn',
            'Thanh Hóa': 'vn-th',
            'Thừa Thiên Huế': 'vn-tt',
            'Tiền Giang': 'vn-tg',
            'Trà Vinh': 'vn-tv',
            'Tuyên Quang': 'vn-tq',
            'Vĩnh Long': 'vn-vl',
            'Vĩnh Phúc': 'vn-vc',
            'Yên Bái': 'vn-yb'
        };
        return provinceMapping[normalizedName] || provinceMapping[provinceName] || '';
    }

    // Khởi tạo biểu đồ tỉnh thành
    new Chart(provinceLineCtx, {
        type: 'line',
        data: {
            labels: sortedProvinceStats.map(stat => stat.name),
            datasets: [{
                label: 'Doanh thu',
                data: sortedProvinceStats.map(stat => stat.revenue),
                borderColor: '#0066FF',
                backgroundColor: 'rgba(0, 102, 255, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0066FF',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Doanh thu theo tỉnh thành',
                    font: {
                        size: 14,
                        weight: 'bold'
                    },
                    padding: {
                        bottom: 20
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#000',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        },
                        color: '#666'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        color: '#666',
                        autoSkip: false // Hiển thị tất cả labels
                    }
                }
            }
        }
    });

        // Khởi tạo bản đồ Việt Nam với Leaflet
    const map = L.map('province-map').setView([16.0, 106.0], 5); // Tọa độ trung tâm Việt Nam

    // Thêm lớp bản đồ nền
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Dữ liệu tỉnh thành Việt Nam (tọa độ đơn giản)
    const provinces = [
        { name: 'Hà Nội', lat: 21.0285, lng: 105.8542 },
        { name: 'Hồ Chí Minh', lat: 10.8231, lng: 106.6297 },
        { name: 'Đà Nẵng', lat: 16.0544, lng: 108.2022 },
        { name: 'Hải Phòng', lat: 20.8449, lng: 106.6881 },
        { name: 'Cần Thơ', lat: 10.0452, lng: 105.7469 },
        { name: 'An Giang', lat: 10.5215, lng: 105.1258 },
        { name: 'Bà Rịa - Vũng Tàu', lat: 10.5417, lng: 107.2430 },
        { name: 'Bắc Giang', lat: 21.2731, lng: 106.1947 },
        { name: 'Bắc Kạn', lat: 22.1477, lng: 105.8349 },
        { name: 'Bạc Liêu', lat: 9.2940, lng: 105.7244 },
        { name: 'Bắc Ninh', lat: 21.1214, lng: 106.1110 },
        { name: 'Bến Tre', lat: 10.2433, lng: 106.3756 },
        { name: 'Bình Định', lat: 13.7829, lng: 109.2196 },
        { name: 'Bình Dương', lat: 11.3254, lng: 106.4772 },
        { name: 'Bình Phước', lat: 11.7511, lng: 106.7234 },
        { name: 'Bình Thuận', lat: 10.9336, lng: 108.1001 },
        { name: 'Cà Mau', lat: 9.1527, lng: 105.1960 },
        { name: 'Cao Bằng', lat: 22.6666, lng: 106.2500 },
        { name: 'Đắk Lắk', lat: 12.6667, lng: 108.0500 },
        { name: 'Đắk Nông', lat: 12.0045, lng: 107.6874 },
        { name: 'Điện Biên', lat: 21.3856, lng: 103.0169 },
        { name: 'Đồng Nai', lat: 11.1068, lng: 107.1768 },
        { name: 'Đồng Tháp', lat: 10.4938, lng: 105.6882 },
        { name: 'Gia Lai', lat: 13.9808, lng: 108.0151 },
        { name: 'Hà Giang', lat: 22.8333, lng: 104.9833 },
        { name: 'Hà Nam', lat: 20.5464, lng: 105.9131 },
        { name: 'Hà Tĩnh', lat: 18.3333, lng: 105.9000 },
        { name: 'Hải Dương', lat: 20.9400, lng: 106.3319 },
        { name: 'Hậu Giang', lat: 9.7579, lng: 105.6413 },
        { name: 'Hòa Bình', lat: 20.8133, lng: 105.3383 },
        { name: 'Hưng Yên', lat: 20.6464, lng: 106.0511 },
        { name: 'Khánh Hòa', lat: 12.2585, lng: 109.1926 },
        { name: 'Kiên Giang', lat: 10.0211, lng: 105.0909 },
        { name: 'Kon Tum', lat: 14.3500, lng: 108.0000 },
        { name: 'Lai Châu', lat: 22.3964, lng: 103.4716 },
        { name: 'Lâm Đồng', lat: 11.9465, lng: 108.4419 },
        { name: 'Lạng Sơn', lat: 21.8530, lng: 106.7610 },
        { name: 'Lào Cai', lat: 22.4833, lng: 103.9667 },
        { name: 'Long An', lat: 10.5446, lng: 106.4132 },
        { name: 'Nam Định', lat: 20.4200, lng: 106.1683 },
        { name: 'Nghệ An', lat: 19.2345, lng: 104.9200 },
        { name: 'Ninh Bình', lat: 20.2575, lng: 105.9750 },
        { name: 'Ninh Thuận', lat: 11.5608, lng: 108.9903 },
        { name: 'Phú Thọ', lat: 21.3227, lng: 105.4048 },
        { name: 'Phú Yên', lat: 13.0882, lng: 109.0928 },
        { name: 'Quảng Bình', lat: 17.5000, lng: 106.3333 },
        { name: 'Quảng Nam', lat: 15.5794, lng: 108.0150 },
        { name: 'Quảng Ngãi', lat: 15.1200, lng: 108.8000 },
        { name: 'Quảng Ninh', lat: 21.0064, lng: 107.2925 },
        { name: 'Quảng Trị', lat: 16.7500, lng: 107.2000 },
        { name: 'Sóc Trăng', lat: 9.6037, lng: 105.9811 },
        { name: 'Sơn La', lat: 21.1667, lng: 103.9000 },
        { name: 'Tây Ninh', lat: 11.3230, lng: 106.1483 },
        { name: 'Thái Bình', lat: 20.4500, lng: 106.3333 },
        { name: 'Thái Nguyên', lat: 21.5942, lng: 105.8481 },
        { name: 'Thanh Hóa', lat: 19.8066, lng: 105.7667 },
        { name: 'Thừa Thiên Huế', lat: 16.4637, lng: 107.5908 },
        { name: 'Tiền Giang', lat: 10.3542, lng: 106.3643 },
        { name: 'Trà Vinh', lat: 9.9513, lng: 106.3346 },
        { name: 'Tuyên Quang', lat: 21.8233, lng: 105.2181 },
        { name: 'Vĩnh Long', lat: 10.2537, lng: 105.9722 },
        { name: 'Vĩnh Phúc', lat: 21.3608, lng: 105.5474 },
        { name: 'Yên Bái', lat: 21.7167, lng: 104.9000 }
    ];

    // Tìm giá trị doanh thu lớn nhất để tính toán kích thước điểm
    const maxRevenue = Math.max(...provinceStats.map(p => parseFloat(p.revenue) || 0));

    // Thêm điểm đánh dấu cho mỗi tỉnh thành
    provinces.forEach(province => {
        // Tìm dữ liệu tương ứng trong provinceStats
        const provinceData = provinceStats.find(p =>
            p.name.includes(province.name) ||
            province.name.includes(p.name)
        );

        if (provinceData) {
            const revenue = parseFloat(provinceData.revenue) || 0;
            const orders = parseInt(provinceData.orders) || 0;

            // Tính kích thước điểm dựa trên doanh thu (tối thiểu 5, tối đa 30)
            const radius = revenue > 0 ? Math.max(5, Math.min(30, (revenue / maxRevenue) * 30)) : 5;

            // Tính màu dựa trên doanh thu (từ xanh nhạt đến xanh đậm)
            const intensity = revenue > 0 ? Math.min(0.9, (revenue / maxRevenue) * 0.9) : 0;
            const color = `rgba(0, 102, 255, ${intensity + 0.1})`;

            // Tạo điểm đánh dấu
            const circle = L.circle([province.lat, province.lng], {
                color: '#fff',
                fillColor: color,
                fillOpacity: 0.8,
                weight: 1,
                radius: radius * 5000 // Nhân với 5000 để thấy rõ hơn trên bản đồ
            }).addTo(map);

            // Thêm popup thông tin
            circle.bindPopup(`
                <div style="min-width: 200px; padding: 10px;">
                    <h6 style="margin: 0 0 10px 0; font-weight: bold; color: #333;">${province.name}</h6>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #666; display: inline-block; width: 100px;">Doanh thu:</span>
                        <b style="color: #333;">${new Intl.NumberFormat('vi-VN').format(revenue)} đ</b>
                        </div>
                        <div>
                            <span style="color: #666; display: inline-block; width: 100px;">Số đơn:</span>
                        <b style="color: #333;">${orders}</b>
                        </div>
                    </div>
            `);
            }
    });

    // Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProducts = @json($topProducts ?? []);

    new Chart(topProductsCtx, {
        type: 'line',
        data: {
            labels: topProducts.map(product => product.name),
            datasets: [
                {
                    label: 'Số lượng đặt',
                    data: topProducts.map(product => product.quantity_ordered),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    yAxisID: 'y',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Doanh thu dự kiến',
                    data: topProducts.map(product => product.expected_revenue),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    yAxisID: 'yRevenue',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Doanh thu thực tế',
                    data: topProducts.map(product => product.actual_revenue),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    yAxisID: 'yRevenue',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.dataset.yAxisID === 'y') {
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y);
                                } else {
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND',
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y);
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Số lượng'
                    },
                    grid: {
                        display: true
                    },
                    ticks: {
                         callback: function(value) {
                            if (Number.isInteger(value)) {
                                return value;
                            }
                        }
                    }
                },
                yRevenue: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Doanh thu (VNĐ)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'K';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });

    // Handle refresh button
    $('#refresh-btn').on('click', function() {
        const picker = $('#daterange').data('daterangepicker');
        window.location.href = '{{ route("reports.live-sessions") }}?' + $.param({
            start_date: picker.startDate.format('YYYY-MM-DD'),
            end_date: picker.endDate.format('YYYY-MM-DD')
        });
    });

    // Initialize DataTable for live sessions with custom options
    $('#live-sessions-table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[1, 'desc']], // Sắp xếp theo ngày giảm dần
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json",
            "searchPlaceholder": "Tìm kiếm...",
            "lengthMenu": "Hiển thị _MENU_ dòng",
            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
            "infoEmpty": "Hiển thị 0 đến 0 của 0 dòng",
            "infoFiltered": "(lọc từ _MAX_ dòng)",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Sau",
                "previous": "Trước"
            }
        },
        dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fas fa-download"></i> Xuất',
                className: 'btn-theme btn-outline',
                buttons: ['copy', 'excel', 'pdf']
            }
        ]
    });

    // Initialize DataTable for product details with custom options
    $('#product-details-table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[3, 'desc']], // Sort by Expected Revenue desc by default
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json",
            "searchPlaceholder": "Tìm kiếm...",
            "lengthMenu": "Hiển thị _MENU_ dòng",
            "info": "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
            "infoEmpty": "Hiển thị 0 đến 0 của 0 dòng",
            "infoFiltered": "(lọc từ _MAX_ dòng)",
            "paginate": {
                "first": "Đầu",
                "last": "Cuối",
                "next": "Sau",
                "previous": "Trước"
            }
        },
        dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fas fa-download"></i> Xuất',
                className: 'btn-theme btn-outline',
                buttons: ['copy', 'excel', 'pdf']
            }
        ]
    });
});
</script>

<!-- Script riêng cho bản đồ Leaflet -->
<script>
    $(function() {
        // Khởi tạo bản đồ Việt Nam với Leaflet
        const map = L.map('province-map').setView([16.0, 106.0], 5); // Tọa độ trung tâm Việt Nam

        // Thêm lớp bản đồ nền
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Dữ liệu tỉnh thành Việt Nam (tọa độ đơn giản)
        const provinces = [
            { name: 'Hà Nội', lat: 21.0285, lng: 105.8542 },
            { name: 'Hồ Chí Minh', lat: 10.8231, lng: 106.6297 },
            { name: 'Đà Nẵng', lat: 16.0544, lng: 108.2022 },
            { name: 'Hải Phòng', lat: 20.8449, lng: 106.6881 },
            { name: 'Cần Thơ', lat: 10.0452, lng: 105.7469 },
            { name: 'An Giang', lat: 10.5215, lng: 105.1258 },
            { name: 'Bà Rịa - Vũng Tàu', lat: 10.5417, lng: 107.2430 },
            { name: 'Bắc Giang', lat: 21.2731, lng: 106.1947 },
            { name: 'Bắc Kạn', lat: 22.1477, lng: 105.8349 },
            { name: 'Bạc Liêu', lat: 9.2940, lng: 105.7244 },
            { name: 'Bắc Ninh', lat: 21.1214, lng: 106.1110 },
            { name: 'Bến Tre', lat: 10.2433, lng: 106.3756 },
            { name: 'Bình Định', lat: 13.7829, lng: 109.2196 },
            { name: 'Bình Dương', lat: 11.3254, lng: 106.4772 },
            { name: 'Bình Phước', lat: 11.7511, lng: 106.7234 },
            { name: 'Bình Thuận', lat: 10.9336, lng: 108.1001 },
            { name: 'Cà Mau', lat: 9.1527, lng: 105.1960 },
            { name: 'Cao Bằng', lat: 22.6666, lng: 106.2500 },
            { name: 'Đắk Lắk', lat: 12.6667, lng: 108.0500 },
            { name: 'Đắk Nông', lat: 12.0045, lng: 107.6874 },
            { name: 'Điện Biên', lat: 21.3856, lng: 103.0169 },
            { name: 'Đồng Nai', lat: 11.1068, lng: 107.1768 },
            { name: 'Đồng Tháp', lat: 10.4938, lng: 105.6882 },
            { name: 'Gia Lai', lat: 13.9808, lng: 108.0151 },
            { name: 'Hà Giang', lat: 22.8333, lng: 104.9833 },
            { name: 'Hà Nam', lat: 20.5464, lng: 105.9131 },
            { name: 'Hà Tĩnh', lat: 18.3333, lng: 105.9000 },
            { name: 'Hải Dương', lat: 20.9400, lng: 106.3319 },
            { name: 'Hậu Giang', lat: 9.7579, lng: 105.6413 },
            { name: 'Hòa Bình', lat: 20.8133, lng: 105.3383 },
            { name: 'Hưng Yên', lat: 20.6464, lng: 106.0511 },
            { name: 'Khánh Hòa', lat: 12.2585, lng: 109.1926 },
            { name: 'Kiên Giang', lat: 10.0211, lng: 105.0909 },
            { name: 'Kon Tum', lat: 14.3500, lng: 108.0000 },
            { name: 'Lai Châu', lat: 22.3964, lng: 103.4716 },
            { name: 'Lâm Đồng', lat: 11.9465, lng: 108.4419 },
            { name: 'Lạng Sơn', lat: 21.8530, lng: 106.7610 },
            { name: 'Lào Cai', lat: 22.4833, lng: 103.9667 },
            { name: 'Long An', lat: 10.5446, lng: 106.4132 },
            { name: 'Nam Định', lat: 20.4200, lng: 106.1683 },
            { name: 'Nghệ An', lat: 19.2345, lng: 104.9200 },
            { name: 'Ninh Bình', lat: 20.2575, lng: 105.9750 },
            { name: 'Ninh Thuận', lat: 11.5608, lng: 108.9903 },
            { name: 'Phú Thọ', lat: 21.3227, lng: 105.4048 },
            { name: 'Phú Yên', lat: 13.0882, lng: 109.0928 },
            { name: 'Quảng Bình', lat: 17.5000, lng: 106.3333 },
            { name: 'Quảng Nam', lat: 15.5794, lng: 108.0150 },
            { name: 'Quảng Ngãi', lat: 15.1200, lng: 108.8000 },
            { name: 'Quảng Ninh', lat: 21.0064, lng: 107.2925 },
            { name: 'Quảng Trị', lat: 16.7500, lng: 107.2000 },
            { name: 'Sóc Trăng', lat: 9.6037, lng: 105.9811 },
            { name: 'Sơn La', lat: 21.1667, lng: 103.9000 },
            { name: 'Tây Ninh', lat: 11.3230, lng: 106.1483 },
            { name: 'Thái Bình', lat: 20.4500, lng: 106.3333 },
            { name: 'Thái Nguyên', lat: 21.5942, lng: 105.8481 },
            { name: 'Thanh Hóa', lat: 19.8066, lng: 105.7667 },
            { name: 'Thừa Thiên Huế', lat: 16.4637, lng: 107.5908 },
            { name: 'Tiền Giang', lat: 10.3542, lng: 106.3643 },
            { name: 'Trà Vinh', lat: 9.9513, lng: 106.3346 },
            { name: 'Tuyên Quang', lat: 21.8233, lng: 105.2181 },
            { name: 'Vĩnh Long', lat: 10.2537, lng: 105.9722 },
            { name: 'Vĩnh Phúc', lat: 21.3608, lng: 105.5474 },
            { name: 'Yên Bái', lat: 21.7167, lng: 104.9000 }
        ];

        // Lấy dữ liệu tỉnh thành từ biến PHP
        const provinceStats = @json($provinceStats ?? []);

        // Tìm giá trị doanh thu lớn nhất để tính toán kích thước điểm
        const maxRevenue = Math.max(...provinceStats.map(p => parseFloat(p.revenue) || 0));

        // Thêm điểm đánh dấu cho mỗi tỉnh thành
        provinces.forEach(province => {
            // Tìm dữ liệu tương ứng trong provinceStats
            const provinceData = provinceStats.find(p =>
                p.name.includes(province.name) ||
                province.name.includes(p.name)
            );

            if (provinceData) {
                const revenue = parseFloat(provinceData.revenue) || 0;
                const orders = parseInt(provinceData.orders) || 0;

                // Tính kích thước điểm dựa trên doanh thu (tối thiểu 5, tối đa 30)
                const radius = revenue > 0 ? Math.max(5, Math.min(30, (revenue / maxRevenue) * 30)) : 5;

                // Tính màu dựa trên doanh thu (từ xanh nhạt đến xanh đậm)
                const intensity = revenue > 0 ? Math.min(0.9, (revenue / maxRevenue) * 0.9) : 0;
                const color = `rgba(0, 102, 255, ${intensity + 0.1})`;

                // Tạo điểm đánh dấu
                const circle = L.circle([province.lat, province.lng], {
                    color: '#fff',
                    fillColor: color,
                    fillOpacity: 0.8,
                    weight: 1,
                    radius: radius * 5000 // Nhân với 5000 để thấy rõ hơn trên bản đồ
                }).addTo(map);

                // Thêm popup thông tin
                circle.bindPopup(`
                    <div style="min-width: 200px; padding: 10px;">
                        <h6 style="margin: 0 0 10px 0; font-weight: bold; color: #333;">${province.name}</h6>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #666; display: inline-block; width: 100px;">Doanh thu:</span>
                            <b style="color: #333;">${new Intl.NumberFormat('vi-VN').format(revenue)} đ</b>
                        </div>
                        <div>
                            <span style="color: #666; display: inline-block; width: 100px;">Số đơn:</span>
                            <b style="color: #333;">${orders}</b>
                        </div>
                    </div>
                `);
            }
    });
});
</script>
@stop
