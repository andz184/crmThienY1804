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
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Hướng dẫn sử dụng báo cáo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5>Giải thích các chỉ số:</h5>
                <ul>
                    <li><strong>Doanh thu dự kiến:</strong> Tổng giá trị của tất cả đơn hàng (bao gồm cả đơn hủy và đang giao)</li>
                    <li><strong>Doanh thu thực tế:</strong> Tổng giá trị của các đơn hàng đã chốt thành công</li>
                    <li><strong>Tổng đơn:</strong> Tổng số đơn hàng nhận được trong phiên live</li>
                    <li><strong>Đơn chốt:</strong> Số đơn hàng đã xác nhận thành công (đã giao hàng và thanh toán)</li>
                    <li><strong>Đơn hủy:</strong> Số đơn hàng đã bị hủy vì bất kỳ lý do gì</li>
                    <li><strong>Tỷ lệ chốt (%):</strong> = (Đơn chốt / (Tổng đơn - Đơn đang giao)) × 100</li>
                    <li><strong>Tỷ lệ hủy (%):</strong> = (Đơn hủy / Tổng đơn) × 100</li>
                    <li><strong>Khách mới:</strong> Số khách hàng lần đầu mua hàng trong phiên live</li>
                    <li><strong>Khách cũ:</strong> Số khách hàng đã từng mua hàng trước đó</li>
                </ul>

                <h5>Lưu ý quan trọng:</h5>
                <ul>
                    <li>Đơn đang giao không được tính vào tỷ lệ chốt đơn</li>
                    <li>Tỷ lệ chốt được tính trên số đơn đã có kết quả cuối cùng (loại trừ đơn đang giao)</li>
                    <li>Doanh thu thực tế chỉ tính các đơn đã chốt thành công</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="form-group">
            <label>Khoảng thời gian</label>
            <div class="input-group">
                <input type="text" class="form-control" id="daterange" value="{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    <button type="button" class="btn btn-default" id="reset-date-btn" title="Đặt lại">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="filter-btn">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-right">
        <div class="btn-group mt-4">
            <button type="button" class="btn btn-default" id="refresh-btn">
                <i class="fas fa-sync"></i>
            </button>
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    Thêm lọc
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">Theo sản phẩm</a>
                    <a class="dropdown-item" href="#">Theo khu vực</a>
                </div>
            </div>
            <button type="button" class="btn btn-default">
                <i class="fas fa-cog"></i>
            </button>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tổng hàng chốt -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Tổng hàng chốt</h5>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="mb-0">{{ number_format($summary['expected_revenue'] ?? 0, 0, ',', '.') }} đ</h3>
                        <small class="text-{{ $revenueChangeRate >= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $revenueChangeRate >= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($revenueChangeRate), 2, ',', '.') }}%
                        </small>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ number_format($summary['successful_orders'] ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-{{ $ordersChangeRate >= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $ordersChangeRate >= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($ordersChangeRate), 2, ',', '.') }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tổng hàng hoàn -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Tổng hàng hoàn</h5>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="mb-0">{{ number_format($summary['canceled_orders'] ?? 0, 0, ',', '.') }} đ</h3>
                        <small class="text-{{ $canceledOrdersChangeRate <= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $canceledOrdersChangeRate <= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($canceledOrdersChangeRate), 2, ',', '.') }}%
                        </small>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ number_format($summary['canceled_orders'] ?? 0, 0, ',', '.') }}</h4>
                        <small class="text-{{ $canceledOrdersChangeRate <= 0 ? 'success' : 'danger' }}">
                            <i class="fas fa-arrow-{{ $canceledOrdersChangeRate <= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($canceledOrdersChangeRate), 2, ',', '.') }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Khách hàng -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Khách hàng</h5>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1">Khách mới</p>
                        <h4>{{ number_format($summary['new_customers'] ?? 0, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-6">
                        <p class="mb-1">Khách cũ</p>
                        <h4>{{ number_format($summary['returning_customers'] ?? 0, 0, ',', '.') }}</h4>
                    </div>
                </div>
                <small class="text-muted">Tổng khách: {{ number_format($summary['total_customers'] ?? 0, 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tổng cộng -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Tổng cộng</h5>
                <h3>{{ number_format($summary['actual_revenue'], 0, ',', '.') }} đ</h3>
                <small class="text-{{ $revenueChangeRate >= 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $revenueChangeRate >= 0 ? 'up' : 'down' }}"></i>
                    {{ number_format(abs($revenueChangeRate), 2, ',', '.') }}%
                </small>
                <p class="mb-0 mt-2">Đơn chốt: {{ number_format($summary['successful_orders'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Tỷ lệ chốt đơn -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Tỷ lệ chốt đơn</h5>
                <h3>{{ number_format($summary['conversion_rate'] ?? 0, 1, ',', '.') }}%</h3>
                <small class="text-{{ $successRateChange >= 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $successRateChange >= 0 ? 'up' : 'down' }}"></i>
                    {{ number_format(abs($successRateChange), 1, ',', '.') }}%
                </small>
                <p class="mb-0 mt-2">Tổng đơn: {{ number_format($summary['total_orders'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Tỷ lệ hủy đơn -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Tỷ lệ hủy đơn</h5>
                <h3>{{ number_format($summary['cancellation_rate'] ?? 0, 1, ',', '.') }}%</h3>
                <small class="text-{{ $canceledOrdersChangeRate <= 0 ? 'success' : 'danger' }}">
                    <i class="fas fa-arrow-{{ $canceledOrdersChangeRate <= 0 ? 'up' : 'down' }}"></i>
                    {{ number_format(abs($canceledOrdersChangeRate), 1, ',', '.') }}%
                </small>
                <p class="mb-0 mt-2">Đơn hủy: {{ number_format($summary['canceled_orders'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Bảng thống kê chi tiết -->
<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Doanh số</th>
                    <th>Doanh thu</th>
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
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Thống kê theo tỉnh thành</h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 400px;">
                    <canvas id="provinceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top 5 sản phẩm bán chạy -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top 5 sản phẩm bán chạy</h3>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết phiên live -->
<div class="card">
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
                    <td>{{ $session['date']->format('d/m/Y') }}</td>
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
<style>
    .card {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        margin-bottom: 1rem;
    }
    .table td small {
        display: block;
    }
    .chart-container {
        position: relative;
        margin: auto;
    }
    #live-sessions-table {
        font-size: 0.9rem;
    }
    #live-sessions-table th, #live-sessions-table td {
        vertical-align: middle;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
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
        endDate: moment('{{ $endDate->format("Y-m-d") }}')
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
            borderColor: '#ffc107',
            tension: 0.1,
            fill: false
        },
        {
            label: 'Doanh thu thực tế',
            data: Object.values(chartData).map(data => data.actual_revenue),
            borderColor: '#28a745',
            tension: 0.1,
            fill: false
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
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000000) {
                                return (value / 1000000000).toFixed(1) + 'B';
                            }
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'K';
                            }
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
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
                        padding: 20
                    }
                }
            }
        }
    });

    // Province Chart
    const provinceCtx = document.getElementById('provinceChart').getContext('2d');
    const provinceStats = @json($provinceStats ?? []);

    new Chart(provinceCtx, {
        type: 'bar',
        data: {
            labels: provinceStats.map(stat => stat.name),
            datasets: [
                {
                    label: 'Doanh thu',
                    data: provinceStats.map(stat => stat.revenue),
                    backgroundColor: '#007bff',
                    yAxisID: 'y'
                },
                {
                    label: 'Số đơn',
                    data: provinceStats.map(stat => stat.orders),
                    backgroundColor: '#28a745',
                    yAxisID: 'y1'
                }
            ]
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
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.yAxisID === 'y') {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND',
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y);
                            } else {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat('vi-VN').format(context.parsed.y);
                            }
                        }
                    }
                }
            }
        }
    });

    // Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProducts = @json($topProducts ?? []);

    new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: topProducts.map(product => product.name),
            datasets: [
                {
                    label: 'Doanh thu',
                    data: topProducts.map(product => product.revenue),
                    backgroundColor: '#007bff'
                },
                {
                    label: 'Số lượng',
                    data: topProducts.map(product => product.quantity),
                    backgroundColor: '#28a745'
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND',
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.x);
                            } else {
                                return context.dataset.label + ': ' +
                                    new Intl.NumberFormat('vi-VN').format(context.parsed.x);
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: false
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
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        display: false
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

    // Initialize DataTable for live sessions
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
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        }
    });
});
</script>
@stop
