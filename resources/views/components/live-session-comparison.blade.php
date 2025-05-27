@props(['currentData', 'comparisonData'])

<div class="card">
    <div class="card-header">
        <h3 class="card-title">So sánh dữ liệu</h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-chart-bar"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" data-view="table">
                        <i class="fas fa-table mr-2"></i>Bảng
                    </a>
                    <a class="dropdown-item" href="#" data-view="bar">
                        <i class="fas fa-chart-bar mr-2"></i>Biểu đồ cột
                    </a>
                    <a class="dropdown-item" href="#" data-view="line">
                        <i class="fas fa-chart-line mr-2"></i>Biểu đồ đường
                    </a>
                    <a class="dropdown-item" href="#" data-view="radar">
                        <i class="fas fa-spider mr-2"></i>Biểu đồ radar
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Table View -->
        <div id="comparison-table-view">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Chỉ số</th>
                            <th>Kỳ hiện tại</th>
                            <th>Kỳ so sánh</th>
                            <th>Thay đổi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Doanh thu</td>
                            <td>{{ number_format($currentData['summary']['total_revenue']) }} đ</td>
                            <td>{{ number_format($comparisonData['summary']['total_revenue']) }} đ</td>
                            <td>
                                @php
                                    $revenueChange = calculateChangeRate(
                                        $currentData['summary']['total_revenue'],
                                        $comparisonData['summary']['total_revenue']
                                    );
                                @endphp
                                <span class="badge badge-{{ $revenueChange >= 0 ? 'success' : 'danger' }}">
                                    {{ $revenueChange >= 0 ? '+' : '' }}{{ number_format($revenueChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Doanh số</td>
                            <td>{{ number_format($currentData['summary']['total_sales']) }}</td>
                            <td>{{ number_format($comparisonData['summary']['total_sales']) }}</td>
                            <td>
                                @php
                                    $salesChange = calculateChangeRate(
                                        $currentData['summary']['total_sales'],
                                        $comparisonData['summary']['total_sales']
                                    );
                                @endphp
                                <span class="badge badge-{{ $salesChange >= 0 ? 'success' : 'danger' }}">
                                    {{ $salesChange >= 0 ? '+' : '' }}{{ number_format($salesChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Đơn thành công</td>
                            <td>{{ number_format($currentData['summary']['successful_orders']) }}</td>
                            <td>{{ number_format($comparisonData['summary']['successful_orders']) }}</td>
                            <td>
                                @php
                                    $successChange = calculateChangeRate(
                                        $currentData['summary']['successful_orders'],
                                        $comparisonData['summary']['successful_orders']
                                    );
                                @endphp
                                <span class="badge badge-{{ $successChange >= 0 ? 'success' : 'danger' }}">
                                    {{ $successChange >= 0 ? '+' : '' }}{{ number_format($successChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Đơn hủy</td>
                            <td>{{ number_format($currentData['summary']['canceled_orders']) }}</td>
                            <td>{{ number_format($comparisonData['summary']['canceled_orders']) }}</td>
                            <td>
                                @php
                                    $cancelChange = calculateChangeRate(
                                        $currentData['summary']['canceled_orders'],
                                        $comparisonData['summary']['canceled_orders']
                                    );
                                @endphp
                                <span class="badge badge-{{ $cancelChange <= 0 ? 'success' : 'danger' }}">
                                    {{ $cancelChange >= 0 ? '+' : '' }}{{ number_format($cancelChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Khách mới</td>
                            <td>{{ number_format($currentData['summary']['new_customers']) }}</td>
                            <td>{{ number_format($comparisonData['summary']['new_customers']) }}</td>
                            <td>
                                @php
                                    $newCustomersChange = calculateChangeRate(
                                        $currentData['summary']['new_customers'],
                                        $comparisonData['summary']['new_customers']
                                    );
                                @endphp
                                <span class="badge badge-{{ $newCustomersChange >= 0 ? 'success' : 'danger' }}">
                                    {{ $newCustomersChange >= 0 ? '+' : '' }}{{ number_format($newCustomersChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Khách quay lại</td>
                            <td>{{ number_format($currentData['summary']['returning_customers']) }}</td>
                            <td>{{ number_format($comparisonData['summary']['returning_customers']) }}</td>
                            <td>
                                @php
                                    $returningCustomersChange = calculateChangeRate(
                                        $currentData['summary']['returning_customers'],
                                        $comparisonData['summary']['returning_customers']
                                    );
                                @endphp
                                <span class="badge badge-{{ $returningCustomersChange >= 0 ? 'success' : 'danger' }}">
                                    {{ $returningCustomersChange >= 0 ? '+' : '' }}{{ number_format($returningCustomersChange, 2) }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Chart Views -->
        <div id="comparison-chart-view" style="display: none;">
            <canvas id="comparison-chart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
let comparisonChart = null;

$(document).ready(function() {
    // Handle view switching
    $('.dropdown-item').click(function(e) {
        e.preventDefault();
        const view = $(this).data('view');
        switchView(view);
    });

    // Initialize with table view
    switchView('table');
});

function switchView(view) {
    // Hide all views first
    $('#comparison-table-view, #comparison-chart-view').hide();

    switch(view) {
        case 'table':
            $('#comparison-table-view').show();
            break;
        case 'bar':
            $('#comparison-chart-view').show();
            createBarChart();
            break;
        case 'line':
            $('#comparison-chart-view').show();
            createLineChart();
            break;
        case 'radar':
            $('#comparison-chart-view').show();
            createRadarChart();
            break;
    }
}

function createBarChart() {
    const ctx = document.getElementById('comparison-chart').getContext('2d');
    const currentData = @json($currentData['summary']);
    const comparisonData = @json($comparisonData['summary']);

    if (comparisonChart) {
        comparisonChart.destroy();
    }

    comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Doanh thu', 'Doanh số', 'Đơn thành công', 'Đơn hủy', 'Khách mới', 'Khách quay lại'],
            datasets: [
                {
                    label: 'Kỳ hiện tại',
                    data: [
                        currentData.total_revenue,
                        currentData.total_sales,
                        currentData.successful_orders,
                        currentData.canceled_orders,
                        currentData.new_customers,
                        currentData.returning_customers
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                },
                {
                    label: 'Kỳ so sánh',
                    data: [
                        comparisonData.total_revenue,
                        comparisonData.total_sales,
                        comparisonData.successful_orders,
                        comparisonData.canceled_orders,
                        comparisonData.new_customers,
                        comparisonData.returning_customers
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createLineChart() {
    const ctx = document.getElementById('comparison-chart').getContext('2d');
    const currentDaily = @json($currentData['daily_stats']);
    const comparisonDaily = @json($comparisonData['daily_stats']);

    if (comparisonChart) {
        comparisonChart.destroy();
    }

    // Prepare data
    const dates = [...new Set([
        ...Object.keys(currentDaily),
        ...Object.keys(comparisonDaily)
    ])].sort();

    comparisonChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Doanh thu hiện tại',
                    data: dates.map(date => currentDaily[date]?.total_revenue || 0),
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                },
                {
                    label: 'Doanh thu kỳ so sánh',
                    data: dates.map(date => comparisonDaily[date]?.total_revenue || 0),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function createRadarChart() {
    const ctx = document.getElementById('comparison-chart').getContext('2d');
    const currentData = @json($currentData['summary']);
    const comparisonData = @json($comparisonData['summary']);

    if (comparisonChart) {
        comparisonChart.destroy();
    }

    // Calculate percentages for better radar visualization
    const maxValues = {
        revenue: Math.max(currentData.total_revenue, comparisonData.total_revenue),
        sales: Math.max(currentData.total_sales, comparisonData.total_sales),
        success: Math.max(currentData.successful_orders, comparisonData.successful_orders),
        cancel: Math.max(currentData.canceled_orders, comparisonData.canceled_orders),
        new: Math.max(currentData.new_customers, comparisonData.new_customers),
        returning: Math.max(currentData.returning_customers, comparisonData.returning_customers)
    };

    comparisonChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Doanh thu', 'Doanh số', 'Đơn thành công', 'Đơn hủy', 'Khách mới', 'Khách quay lại'],
            datasets: [
                {
                    label: 'Kỳ hiện tại',
                    data: [
                        (currentData.total_revenue / maxValues.revenue) * 100,
                        (currentData.total_sales / maxValues.sales) * 100,
                        (currentData.successful_orders / maxValues.success) * 100,
                        (currentData.canceled_orders / maxValues.cancel) * 100,
                        (currentData.new_customers / maxValues.new) * 100,
                        (currentData.returning_customers / maxValues.returning) * 100
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    pointBackgroundColor: 'rgb(54, 162, 235)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(54, 162, 235)'
                },
                {
                    label: 'Kỳ so sánh',
                    data: [
                        (comparisonData.total_revenue / maxValues.revenue) * 100,
                        (comparisonData.total_sales / maxValues.sales) * 100,
                        (comparisonData.successful_orders / maxValues.success) * 100,
                        (comparisonData.canceled_orders / maxValues.cancel) * 100,
                        (comparisonData.new_customers / maxValues.new) * 100,
                        (comparisonData.returning_customers / maxValues.returning) * 100
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    pointBackgroundColor: 'rgb(255, 99, 132)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(255, 99, 132)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}
</script>
@endpush

@php
function calculateChangeRate($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}
@endphp
