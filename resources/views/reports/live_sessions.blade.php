@extends('adminlte::page')

@section('title', 'Báo Cáo Phiên Live')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Báo Cáo Phiên Live</h1>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Báo cáo phiên live - Hiển thị trực tiếp -->

                    <!-- Include CSS styles from the partial -->
                    @include('reports.partials.live_session_styles')

                    <!-- Filter section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card stats-card">
                                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-video mr-2 text-primary"></i>
                                        Báo Cáo Phiên Live
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <form action="{{ route('reports.live_sessions') }}" method="GET" class="d-flex">
                                            <div class="form-group mr-2 mb-0">
                                                <select class="form-control form-control-sm" id="period-filter" name="period">
                                                    <option value="day">Theo ngày</option>
                                                    <option value="month">Theo tháng</option>
                                                    <option value="year">Theo năm</option>
                                                </select>
                                            </div>
                                            <div class="input-group date-range-container" style="width: 230px;">
                                                <input type="text" class="form-control form-control-sm date-picker" id="date-range"
                                                       name="date_range" value="{{ isset($startDate) && is_object($startDate) ? $startDate->format('m/d/Y') : (isset($startDate) ? $startDate : '') }} - {{ isset($endDate) && is_object($endDate) ? $endDate->format('m/d/Y') : (isset($endDate) ? $endDate : '') }}">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-light"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary ml-2">
                                                <i class="fas fa-search"></i> Lọc
                                            </button>
                                            <a href="{{ route('reports.live_sessions') }}" class="btn btn-sm btn-outline-secondary ml-2">
                                                <i class="fas fa-sync"></i> Đặt lại
                                            </a>
                                        </form>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <!-- Thống kê tổng quan -->
                                    <div class="row mb-4">
                                        <div class="col-md-4"> {{-- Card 1: Phiên Live --}}
                                            <div class="card stats-card bg-info text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Phiên Live</h5>
                                                        <h3 id="total-live-sessions" class="mb-0">{{ number_format($totalSessions) }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-video"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 2: Doanh Thu --}}
                                            <div class="card stats-card bg-success text-white mb-3"> {{-- mb-4 to mb-3 --}}
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Doanh Thu (Đơn Thành Công)</h5>
                                                        <h3 id="total-live-revenue" class="mb-0">{{ number_format($totalRevenueAll) }} VND</h3>
                                                    </div>
                                                    {{-- <div class="stats-icon">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </div> --}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 3: Đơn/Phiên --}}
                                            <div class="card stats-card bg-warning text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Đơn/Phiên</h5>
                                                        <h3 id="avg-orders-per-session" class="mb-0">{{ $totalSessions > 0 ? number_format($totalOrdersAll / $totalSessions, 2) : 0 }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2 -->
                                    <div class="row mb-4">
                                        <div class="col-md-4"> {{-- Card 4: Tỷ Lệ Đơn Live Giao Thành Công --}}
                                            <div class="card stats-card bg-danger text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Tỷ Lệ Đơn Live Giao Thành Công</h5>
                                                        <h3 id="success-rate" class="mb-0">{{ isset($overallSuccessRate) ? number_format($overallSuccessRate, 1) : "0.0" }}%</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-chart-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 5: Khách Hàng --}}
                                            <div class="card stats-card bg-primary text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Khách Hàng</h5>
                                                        <h3 id="total-customers" class="mb-0">{{ number_format($totalUniqueCustomers) }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 6: Đơn Thành Công --}}
                                            <div class="card stats-card bg-secondary text-white mb-3"> {{-- Was bg-dark, changed to bg-secondary for variety. Consider a specific dark blue like #343a40 or #454d55 if needed --}}
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Đơn Thành Công</h5>
                                                        <h3 id="successful-orders" class="mb-0">{{ number_format($totalSuccessfulOrdersAll) }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 3 -->
                                    <div class="row mb-4">
                                        <div class="col-md-4"> {{-- Card 7: Đơn Đang Giao --}}
                                            <div class="card stats-card bg-info text-white mb-3"> {{-- Reused bg-info, consider another blue or cyan --}}
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Đơn Đang Giao</h5>
                                                        <h3 id="delivering-orders" class="mb-0">{{ number_format($totalDeliveringOrdersAll ?? 0) }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-shipping-fast"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 8: Đơn Hàng Hủy --}}
                                             <div class="card stats-card bg-dark text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Đơn Hàng Hủy</h5>
                                                        <h3 id="canceled-orders" class="mb-0">{{ number_format($totalCanceledOrdersAll) }}</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-times-circle"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4"> {{-- Card 9: Tỷ Lệ Hủy --}}
                                            <div class="card stats-card bg-danger text-white mb-3">
                                                <div class="card-body d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="card-title mb-1">Tỷ Lệ Hủy</h5>
                                                        <h3 id="cancellation-rate" class="mb-0">{{ number_format($overallCancellationRate, 1) }}%</h3>
                                                    </div>
                                                    <div class="stats-icon">
                                                        <i class="fas fa-ban"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Daily Orders Line Chart - Chi tiết theo ngày -->
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-chart-line"></i> Biểu Đồ Đơn Hàng Theo Ngày</h6>
                                                <canvas id="daily-orders-chart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Daily Revenue Line Chart -->
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-dollar-sign"></i> Doanh Thu Theo Ngày</h6>
                                                <canvas id="daily-revenue-chart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Revenue Chart by Month -->
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-chart-line"></i>Xu Hướng Doanh Thu (Đơn Thành Công) Theo Tháng</h6>
                                                <canvas id="revenue-trend-chart" height="300"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-chart-pie"></i>Phân Bổ Đơn Hàng (Live Sessions Trong Kỳ)</h6>
                                                <canvas id="order-distribution-chart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Performance Comparison -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-users"></i>Khách Hàng Mới vs Khách Hàng Cũ</h6>
                                                {{-- <canvas id="customer-type-chart" height="300"></canvas> --}}
                                                {{-- Displaying as text for now, chart can be added if complex viz is needed --}}
                                                <div class="d-flex justify-content-around align-items-center" style="height: 300px; flex-direction: column;">
                                                    <div>
                                                        <h4>Khách Hàng Mới (Trong Kỳ)</h4>
                                                        <h3 class="text-success">{{ number_format($totalNewCustomersAll ?? 0) }}</h3>
                                                    </div>
                                                    <div>
                                                        <h4>Khách Hàng Quay Lại (Trong Kỳ)</h4>
                                                        <h3 class="text-info">{{ number_format($totalReturningCustomersAll ?? 0) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-tags"></i>Top 5 Sản Phẩm Nổi Bật (Live Sessions)</h6>
                                                <canvas id="top-products-chart" height="300"></canvas> {{-- Keep this canvas for a bar chart --}}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-map-marked-alt"></i>Đơn Hàng Theo Tỉnh/Thành Phố (Live Sessions)</h6>
                                                <canvas id="province-orders-chart" height="350"></canvas> {{-- New canvas for province chart --}}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-dollar-sign"></i>Doanh Thu Theo Tỉnh/Thành Phố (Live Sessions)</h6>
                                                <canvas id="province-revenue-chart" height="350"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Evaluation Rates Line Chart -->
                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="chart-container">
                                                <h6 class="chart-title"><i class="fas fa-chart-line"></i>Tỷ Lệ Đơn Live Giao Thành Công & Hủy Đơn Theo Tháng</h6>
                                                <canvas id="evaluation-rates-chart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keep the modal for viewing session details -->
                    @include('reports.partials.live_session_detail_modal')
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css" />
<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
        flex-direction: column;
    }
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 2s linear infinite;
        margin-bottom: 10px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const numberFormat = (value) => value.toLocaleString('vi-VN');
        const numberFormatShort = (value) => {
            if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Tr';
            if (value >= 1000) return (value / 1000).toFixed(1) + ' K';
            return value;
        };

        // Prepare data from Blade
        const dailyData = @json($dailyChartData ?? []);
        const monthlyData = @json($monthlyChartData ?? []);
        const topProductsData = @json($topProducts ?? []);
        const provinceData = @json($provinceDataForChart ?? []);
        const provinceRevenueData = @json($provinceRevenueDataForChart ?? []); // Data for new chart

        const totalSuccessfulOrdersAll = {{ $totalSuccessfulOrdersAll ?? 0 }};
        const totalDeliveringOrdersAll = {{ $totalDeliveringOrdersAll ?? 0 }};
        const totalCanceledOrdersAll = {{ $totalCanceledOrdersAll ?? 0 }};

        const defaultChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { // Added for better tooltip responsiveness
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return numberFormatShort(value); }
                    }
                }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += numberFormat(context.parsed.y);
                                if (context.dataset.unit) {
                                    label += context.dataset.unit;
                                }
                            }
                            return label;
                        }
                    }
                }
            }
        };

        // 1. Daily Orders Chart (3 lines: Total, Successful, Delivering, Cancelled)
        const dailyOrdersChartCtx = document.getElementById('daily-orders-chart');
        if (dailyOrdersChartCtx && dailyData.length > 0) {
            new Chart(dailyOrdersChartCtx.getContext('2d'), {
        type: 'line',
        data: {
                    labels: dailyData.map(d => d.date_label),
            datasets: [
                {
                            label: 'Tổng Đơn',
                            data: dailyData.map(d => d.total_orders),
                            borderColor: 'rgba(54, 162, 235, 1)', // Blue
                            backgroundColor: 'rgba(54, 162, 235, 0.1)', // Lighter for fill
                            fill: true, // Changed to true
                            tension: 0.1
                        },
                        {
                            label: 'Đơn Thành Công',
                            data: dailyData.map(d => d.successful_orders),
                            borderColor: 'rgba(75, 192, 192, 1)', // Green
                            backgroundColor: 'rgba(75, 192, 192, 0.1)', // Lighter for fill
                            fill: true, // Changed to true
                            tension: 0.1
                        },
                        {
                            label: 'Đơn Đang Giao',
                            data: dailyData.map(d => d.delivering_orders),
                            borderColor: 'rgba(255, 159, 64, 1)', // Orange
                            backgroundColor: 'rgba(255, 159, 64, 0.1)', // Lighter for fill
                            fill: true, // Changed to true
                            tension: 0.1
                        },
                        {
                            label: 'Đơn Hủy',
                            data: dailyData.map(d => d.canceled_orders),
                            borderColor: 'rgba(255, 99, 132, 1)', // Red
                            backgroundColor: 'rgba(255, 99, 132, 0.1)', // Lighter for fill
                            fill: true, // Changed to true
                            tension: 0.1
                        }
                    ]
                },
                options: { ...defaultChartOptions, ...{
                    plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Biểu Đồ Đơn Hàng Theo Ngày' }
                    },
                    scales: {
                        y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Số Lượng Đơn' } }
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'daily-orders-chart' not found or no data.");
            if (dailyOrdersChartCtx) dailyOrdersChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 2. Daily Revenue Chart (3 lines: Total Potential, Successful, Cancelled)
        const dailyRevenueChartCtx = document.getElementById('daily-revenue-chart');
        if (dailyRevenueChartCtx && dailyData.length > 0) {
            new Chart(dailyRevenueChartCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date_label),
                    datasets: [
                        {
                            label: 'Doanh Thu Tiềm Năng (Tất cả đơn)',
                            data: dailyData.map(d => d.total_revenue_potential),
                            borderColor: 'rgba(54, 162, 235, 1)', // Blue
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: false,
                            tension: 0.1,
                            unit: ' VND'
                        },
                        {
                            label: 'Doanh Thu Đơn Thành Công',
                            data: dailyData.map(d => d.successful_revenue),
                            borderColor: 'rgba(75, 192, 192, 1)', // Green
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: false,
                            tension: 0.1,
                            unit: ' VND'
                        },
                        {
                            label: 'Doanh Thu Đơn Hủy (Thất thoát)',
                            data: dailyData.map(d => d.canceled_revenue),
                            borderColor: 'rgba(255, 99, 132, 1)', // Red
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: false,
                            tension: 0.1,
                            unit: ' VND'
                        }
                    ]
                },
                options: { ...defaultChartOptions, ...{
                     plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Biểu Đồ Doanh Thu Theo Ngày' }
                    },
                    scales: {
                        y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Doanh Thu (VND)' } }
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'daily-revenue-chart' not found or no data.");
            if (dailyRevenueChartCtx) dailyRevenueChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 3. Monthly Revenue Trend (Successful Live Orders)
        const revenueTrendChartCtx = document.getElementById('revenue-trend-chart');
        if (revenueTrendChartCtx && monthlyData.length > 0) {
            new Chart(revenueTrendChartCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: monthlyData.map(d => d.month_label),
                    datasets: [{
                        label: 'Doanh Thu Đơn Live Thành Công',
                        data: monthlyData.map(d => d.total_revenue_successful_live),
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        unit: ' VND',
                        barPercentage: 0.5,
                        categoryPercentage: 0.8
                    }]
                },
                options: { ...defaultChartOptions, ...{
                    plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Xu Hướng Doanh Thu (Đơn Live Thành Công) Theo Tháng' }
                    },
                    scales: {
                        y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Doanh Thu (VND)' } }
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'revenue-trend-chart' not found or no data.");
            if (revenueTrendChartCtx) revenueTrendChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 4. Monthly Live Order Success & Cancellation Rate Chart
        const evaluationRatesChartCtx = document.getElementById('evaluation-rates-chart');
        if (evaluationRatesChartCtx && monthlyData.length > 0) {
            new Chart(evaluationRatesChartCtx.getContext('2d'), {
        type: 'bar',
        data: {
                    labels: monthlyData.map(d => d.month_label),
            datasets: [
                {
                            label: 'Tỷ Lệ Giao Thành Công (Live)',
                            data: monthlyData.map(d => d.live_success_rate),
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            unit: '%',
                            barPercentage: 0.4,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Tỷ Lệ Hủy Đơn (Live)',
                            data: monthlyData.map(d => d.live_cancellation_rate),
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            unit: '%',
                            barPercentage: 0.4,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: { ...defaultChartOptions, ...{
                    plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Tỷ Lệ Đơn Live Giao Thành Công & Hủy Đơn Theo Tháng' }
                    },
                    scales: {
                        y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Tỷ Lệ (%)' }, min: 0, max: 100 }
                    }
                }}
            });
                            } else {
            console.error("Canvas with id 'evaluation-rates-chart' not found or no data.");
            if (evaluationRatesChartCtx) evaluationRatesChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 5. Top 5 Products (Bar Chart)
        const topProductsChartCtx = document.getElementById('top-products-chart');
        // topProductsData is an object, convert to array
        const topProductsArray = Object.values(topProductsData);
        if (topProductsChartCtx && topProductsArray.length > 0) {
            new Chart(topProductsChartCtx.getContext('2d'), {
                type: 'bar',
        data: {
                    labels: topProductsArray.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name), // Shorten long names
            datasets: [{
                        label: 'Doanh Thu Sản Phẩm',
                        data: topProductsArray.map(p => p.revenue),
                backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1,
                        unit: ' VND'
                    }]
                },
                options: { ...defaultChartOptions, ...{
                    indexAxis: 'y', // Horizontal bar chart
            plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Top 5 Sản Phẩm Nổi Bật (Live Sessions)' },
                        legend: { display: false } // Hide legend for bar chart with single dataset
                    },
                    scales: {
                        x: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Doanh Thu (VND)'} }, // Y becomes X for horizontal
                        y: { ticks: { autoSkip: false } } // Ensure all labels are shown
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'top-products-chart' not found or no data.");
            if (topProductsChartCtx) topProductsChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 6. Orders by Province/City (Bar Chart)
        const provinceOrdersChartCtx = document.getElementById('province-orders-chart');
        // provinceData is an object { provinceName: count }, convert to arrays for Chart.js
        const provinceLabels = Object.keys(provinceData);
        const provinceCounts = Object.values(provinceData);

        if (provinceOrdersChartCtx && provinceLabels.length > 0) {
            new Chart(provinceOrdersChartCtx.getContext('2d'), {
                type: 'bar',
        data: {
                    labels: provinceLabels,
            datasets: [{
                        label: 'Số Lượng Đơn Hàng',
                        data: provinceCounts,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)', // Purple
                        borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
                options: { ...defaultChartOptions, ...{
            plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Đơn Hàng Theo Tỉnh/Thành Phố (Live Sessions)' },
                        legend: { display: false }
                    },
                    scales: {
                         y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Số Lượng Đơn Hàng'} },
                         x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } } // Rotate labels if many provinces
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'province-orders-chart' not found or no data.");
            if (provinceOrdersChartCtx) provinceOrdersChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // 7. Revenue by Province/City (Bar Chart)
        const provinceRevenueChartCtx = document.getElementById('province-revenue-chart');
        const provinceRevenueLabels = Object.keys(provinceRevenueData);
        const provinceRevenueCounts = Object.values(provinceRevenueData);

        if (provinceRevenueChartCtx && provinceRevenueLabels.length > 0) {
            new Chart(provinceRevenueChartCtx.getContext('2d'), {
                type: 'bar', // Changed from horizontal to vertical
        data: {
                    labels: provinceRevenueLabels,
            datasets: [{
                label: 'Doanh Thu (VND)',
                        data: provinceRevenueCounts,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)', // Light purple, same as province-orders-chart
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        unit: ' VND'
                    }]
                },
                options: { ...defaultChartOptions, ...{
                    // indexAxis: 'y', // Removed for vertical bar chart
                    plugins: {
                        ...defaultChartOptions.plugins,
                        title: { display: true, text: 'Doanh Thu Theo Tỉnh/Thành Phố (Live Sessions)' },
                        legend: { display: false }
                    },
                    scales: {
                         y: { ...defaultChartOptions.scales.y, title: { display: true, text: 'Doanh Thu (VND)'} },
                         x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } } // Rotate labels if many provinces
                    }
                }}
            });
        } else {
            console.error("Canvas with id 'province-revenue-chart' not found or no data.");
            if (provinceRevenueChartCtx) provinceRevenueChartCtx.getContext('2d').fillText('Không có dữ liệu để hiển thị.', 10, 50);
        }

        // Remove old/mocked Order Distribution Chart if it exists
        // const orderDistributionChartCtx = document.getElementById('order-distribution-chart');
        // if (orderDistributionChartCtx) {
        //     const chartInstance = Chart.getChart(orderDistributionChartCtx);
        //     if (chartInstance) {
        //         chartInstance.destroy();
        //     }
        //     // Optionally hide or remove the canvas element
        //     // orderDistributionChartCtx.style.display = 'none';
        //     const parentContainer = orderDistributionChartCtx.closest('.col-md-4');
        //     if (parentContainer) {
        //          // If this was the only chart in its column, and the column is no longer needed.
        //          // For now, let's just clear it. If the column should be removed, that needs specific logic.
        //         // parentContainer.innerHTML = '<p class="text-center text-muted p-5">Biểu đồ Phân Bổ Đơn Hàng đã được loại bỏ.</p>';
        //     }
        // }

        // 8. New Order Distribution Pie Chart
        const orderDistributionChartCtxNew = document.getElementById('order-distribution-chart');
        if (orderDistributionChartCtxNew && (totalSuccessfulOrdersAll > 0 || totalDeliveringOrdersAll > 0 || totalCanceledOrdersAll > 0)) {
            new Chart(orderDistributionChartCtxNew.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Thành Công', 'Đang Giao', 'Đã Hủy'],
                    datasets: [{
                        label: 'Phân Bổ Đơn Hàng Live',
                        data: [totalSuccessfulOrdersAll, totalDeliveringOrdersAll, totalCanceledOrdersAll],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',  // Green for Successful
                            'rgba(255, 159, 64, 0.7)', // Orange for Delivering
                            'rgba(255, 99, 132, 0.7)'   // Red for Cancelled
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Phân Bổ Đơn Hàng Live (Trong Kỳ)'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += numberFormat(context.parsed);
                                        const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                        const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                        label += ` (${percentage}%)`;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else if (orderDistributionChartCtxNew) {
            const ctx = orderDistributionChartCtxNew.getContext('2d');
            ctx.clearRect(0, 0, orderDistributionChartCtxNew.width, orderDistributionChartCtxNew.height); // Clear previous content
            ctx.textAlign = 'center';
            ctx.font = '16px Arial';
            ctx.fillStyle = '#6c757d'; // text-muted color
            ctx.fillText('Không có dữ liệu phân bổ đơn hàng.', orderDistributionChartCtxNew.width / 2, orderDistributionChartCtxNew.height / 2);
             console.log("Order distribution chart: No data to display (all counts are zero).");
        } else {
            console.error("Canvas with id 'order-distribution-chart' not found.");
        }

        // Date Range Picker Initialization
        const dateRangePicker = $('input[name="date_range"]');
        if (dateRangePicker.length > 0) {
            const currentStartDate = "{{ $startDate && is_object($startDate) ? $startDate->format('m/d/Y') : '' }}";
            const currentEndDate = "{{ $endDate && is_object($endDate) ? $endDate->format('m/d/Y') : '' }}";

            let initialStartDate = moment().startOf('month');
            let initialEndDate = moment();

            if (currentStartDate && currentEndDate) {
                initialStartDate = moment(currentStartDate, 'MM/DD/YYYY');
                initialEndDate = moment(currentEndDate, 'MM/DD/YYYY');
            }


            dateRangePicker.daterangepicker({
                opens: 'left',
                startDate: initialStartDate,
                endDate: initialEndDate,
                locale: {
                    format: 'MM/DD/YYYY',
                    separator: ' - ',
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
                    fromLabel: 'Từ',
                    toLabel: 'Đến',
                    customRangeLabel: 'Tùy chọn',
                    weekLabel: 'W',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    firstDay: 1
                },
                ranges: {
                   'Hôm nay': [moment(), moment()],
                   'Hôm qua': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   '7 ngày qua': [moment().subtract(6, 'days'), moment()],
                   '30 ngày qua': [moment().subtract(29, 'days'), moment()],
                   'Tháng này': [moment().startOf('month'), moment().endOf('month')],
                   'Tháng trước': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });
        } else {
            console.error("Date range picker element not found.");
        }

        // Period filter (Day, Month, Year) - Example of how you might handle it if needed
        $('#period-filter').on('change', function() {
            const selectedPeriod = $(this).val();
            // You can add logic here if the period filter affects other elements or requires a page reload/AJAX call
            console.log('Period selected:', selectedPeriod);
        });

        // Handle Recalculate Revenue Button (already in place, just for context)
        $('#calculate-revenue-btn').on('click', function(e) {
            // It's an <a> tag, so default behavior is to navigate.
            // If you wanted to add a confirmation or AJAX, you'd e.preventDefault() here.
            console.log('Recalculate revenue button clicked. Navigating to:', $(this).attr('href'));
        });

        // Table and Modal functionality (should remain as is or be reviewed separately if issues)
        // ... (existing modal and table interaction JS if any) ...
});
</script>
@stop
