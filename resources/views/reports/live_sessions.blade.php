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
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-light-blue">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Hướng dẫn các chỉ số</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>Doanh thu dự kiến:</strong> Tổng giá trị các đơn hàng không bao gồm các đơn ở trạng thái hủy (6, 7).</li>
                        <li><strong>Doanh thu thực tế:</strong> Tổng giá trị các đơn hàng đã được giao thành công (trạng thái 3).</li>
                        <li><strong>Đơn chốt:</strong> Tổng số đơn hàng được giao thành công (trạng thái 3).</li>
                        <li><strong>Đơn hủy:</strong> Tổng số đơn hàng bị hủy (trạng thái 6, 7).</li>
                        <li><strong>Tỷ lệ chốt:</strong> (Đơn chốt / (Tổng đơn - Đơn đang giao)) * 100.</li>
                        <li><strong>Tỷ lệ hủy:</strong> (Đơn hủy / Tổng đơn) * 100.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Báo Cáo Doanh Thu Live Sessions</h3>
        </div>
        <div class="card-body">
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
                            <div id="province-map"></div>
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
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
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
<script src="https://code.highcharts.com/maps/highmaps.js"></script>
<script src="https://code.highcharts.com/maps/modules/exporting.js"></script>
<script src="https://code.highcharts.com/maps/modules/offline-exporting.js"></script>
<script src="https://code.highcharts.com/mapdata/countries/vn/vn-all.js"></script>
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
        const provinceMapping = {
            'Hà Nội': 'vn-hn',
            'TP Hồ Chí Minh': 'vn-hc',
            'Đà Nẵng': 'vn-dn',
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
        return provinceMapping[provinceName] || '';
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

    // Chuẩn bị dữ liệu cho bản đồ từ provinceStats gốc
    const mapData = provinceStats.map(stat => ({
        'hc-key': getProvinceKey(stat.name),
        name: stat.name,
        value: parseFloat(stat.revenue) || 0,
        orders: parseInt(stat.orders) || 0,
        color: parseInt(stat.orders) > 0 ? '#74c0ff' : '#f7f7f7' // Màu xanh cho tỉnh có đơn hàng
    }));

    // Khởi tạo bản đồ với dữ liệu gốc
    Highcharts.mapChart('province-map', {
        chart: {
            map: 'countries/vn/vn-all',
            backgroundColor: 'transparent',
            height: 350,
            spacing: [0, 0, 0, 0]
        },
        title: {
            text: null
        },
        subtitle: {
            text: null
        },
        mapNavigation: {
            enabled: true,
            buttonOptions: {
                verticalAlign: 'bottom'
            }
        },
        colorAxis: {
            min: 0,
            max: 1,
            stops: [
                [0, '#f7f7f7'],    // Màu cho tỉnh không có đơn
                [1, '#74c0ff']     // Màu cho tỉnh có đơn
            ],
            labels: {
                format: '{value}',
                enabled: false
            }
        },
        legend: {
            enabled: false
        },
        series: [{
            data: mapData,
            name: 'Thông tin tỉnh/thành',
            nullColor: '#f7f7f7',
            borderColor: '#dedede',
            borderWidth: 0.5,
            states: {
                hover: {
                    brightness: 0.1,
                    borderColor: '#666'
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                style: {
                    padding: '15px'
                },
                useHTML: true,
                headerFormat: '',
                pointFormat: `
                    <div style="padding: 10px;">
                        <h6 style="margin: 0 0 10px 0; font-weight: bold; color: #333;">{point.name}</h6>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #666; display: inline-block; width: 100px;">Doanh thu:</span>
                            <b style="color: #333;">{point.value:,.0f} đ</b>
                        </div>
                        <div>
                            <span style="color: #666; display: inline-block; width: 100px;">Số đơn:</span>
                            <b style="color: #333;">{point.orders}</b>
                        </div>
                    </div>
                `,
                shadow: true,
                borderWidth: 1,
                borderColor: '#dedede'
            }
        }]
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
@stop
