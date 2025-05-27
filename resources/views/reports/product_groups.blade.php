@extends('adminlte::page')

@section('title', 'Báo cáo theo Nhóm Hàng Hóa')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Báo cáo theo Nhóm Hàng Hóa</h1>
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpModal">
            <i class="fas fa-question-circle mr-1"></i> Hướng dẫn
        </button>
        {{-- Optional: Add a back button if you have a general reports index --}}
        {{-- <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a> --}}
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Main row -->
        <div class="row">
            <div class="col-md-12">
                <!-- Help Modal -->
                <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="helpModalLabel">
                                    <i class="fas fa-question-circle mr-1"></i>
                                    Hướng dẫn đọc báo cáo
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-muted font-weight-bold mb-3">Giải thích các chỉ số:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-primary mr-2"></i>
                                                <strong>Tổng Doanh Thu:</strong><br>
                                                <span class="text-muted ml-4">Tổng giá trị đơn hàng của tất cả sản phẩm trong nhóm hàng hóa.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-success mr-2"></i>
                                                <strong>Số Lượng Đơn Hàng:</strong><br>
                                                <span class="text-muted ml-4">Số lượng đơn hàng có chứa ít nhất một sản phẩm thuộc nhóm hàng hóa.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-info mr-2"></i>
                                                <strong>Số Lượng Sản Phẩm Bán:</strong><br>
                                                <span class="text-muted ml-4">Tổng số lượng sản phẩm đã bán ra từ nhóm hàng hóa.</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-muted font-weight-bold mb-3">Lưu ý quan trọng:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-3">
                                                <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                                                <strong>Hiển thị biểu đồ:</strong><br>
                                                <span class="text-muted ml-4">Biểu đồ chỉ hiển thị top 10 nhóm hàng có doanh thu cao nhất.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                                                <strong>Bảng chi tiết:</strong><br>
                                                <span class="text-muted ml-4">Bảng chi tiết bên dưới hiển thị đầy đủ tất cả các nhóm hàng.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                                                <strong>Đơn hàng và nhóm hàng:</strong><br>
                                                <span class="text-muted ml-4">Một đơn hàng có thể chứa nhiều sản phẩm từ nhiều nhóm hàng khác nhau.</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-1"></i>
                            Bộ lọc
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.product_groups') }}" class="mb-0">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="date_range">Khoảng thời gian:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>
                                            <input type="text" name="date_range" id="date_range" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search mr-1"></i> Lọc
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng Doanh Thu</span>
                                <span class="info-box-number">{{ number_format($totalRevenueAllGroups ?? 0, 0, ',', '.') }} VND</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng Số Đơn Hàng</span>
                                <span class="info-box-number">{{ number_format($totalOrdersAllGroups ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cubes"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng Số Lượng Sản Phẩm</span>
                                <span class="info-box-number">{{ number_format($totalQuantityAllGroups ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($categoryData))
                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-bar mr-1"></i>
                                        Top 10 Doanh Thu theo Nhóm Hàng Hóa
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueByGroupChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-line mr-1"></i>
                                        Top 10 Số Lượng Đơn Hàng theo Nhóm
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="orderCountByGroupChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Groups Table -->
                    <div class="card">
                        <div class="card-header border-0">
                            <h3 class="card-title">
                                <i class="fas fa-table mr-1"></i>
                                Chi Tiết theo Nhóm Hàng Hóa
                            </h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-striped" id="productGroupsTable">
                                <thead>
                                    <tr>
                                        <th>ID Nhóm</th>
                                        <th>Tên Nhóm Hàng Hóa</th>
                                        <th class="text-right">Tổng Doanh Thu (VND)</th>
                                        <th class="text-right">Số Lượng Đơn Hàng</th>
                                        <th class="text-right">Số Lượng Sản Phẩm Bán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryData as $category)
                                        <tr>
                                            <td>{{ $category['id'] }}</td>
                                            <td>{{ $category['name'] }}</td>
                                            <td class="text-right">{{ number_format($category['total_revenue'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_orders'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_quantity_sold'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Thông báo!</h5>
                        Không có dữ liệu nhóm hàng hóa cho khoảng thời gian đã chọn.
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> --}}
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .card {
            margin-bottom: 1rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .card-header {
            background-color: transparent;
            padding: 1rem;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0;
        }
        .info-box {
            min-height: 80px;
        }
        .info-box-icon {
            width: 70px;
            font-size: 30px;
            line-height: 70px;
        }
        .info-box-content {
            padding: 5px 10px;
            margin-left: 70px;
        }
        .table th {
            border-top: 0;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Date Range Picker
            let startDate = "{{ $startDate->format('m/d/Y') }}";
            let endDate = "{{ $endDate->format('m/d/Y') }}";

            $('#date_range').daterangepicker({
                startDate: moment(startDate, 'MM/DD/YYYY'),
                endDate: moment(endDate, 'MM/DD/YYYY'),
                locale: {
                    format: 'MM/DD/YYYY',
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
                    fromLabel: 'Từ',
                    toLabel: 'Đến',
                    customRangeLabel: 'Tùy chọn',
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

            // DataTable
            $('#productGroupsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Vietnamese.json"
                },
                "order": [[ 2, "desc" ]] // Default sort by revenue descending
            });

            // Number formatting function (from live_sessions)
            function number_format(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

            // Chart.js Global Defaults (from live_sessions)
            Chart.defaults.font.family = "'Source Sans Pro', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'";
            Chart.defaults.color = '#6c757d';

            // Common Chart Options (inspired by live_sessions)
            const commonChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null && context.parsed.y !== undefined) {
                                    label += number_format(context.parsed.y) + (context.dataset.label && context.dataset.label.toLowerCase().includes('doanh thu') ? ' VND' : '');
                                } else if (context.parsed !== null && context.parsed !== undefined) { // For pie/doughnut charts
                                    label += number_format(context.parsed) + (context.dataset.label && context.dataset.label.toLowerCase().includes('doanh thu') ? ' VND' : '');
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: { // Common scales for bar/line charts
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 15 // Limit number of x-axis ticks if too many categories
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#efefef',
                            drawBorder: false,
                        },
                        ticks: {
                            callback: function(value, index, values) {
                                if (value >= 1000000) return (value / 1000000) + ' Tr';
                                if (value >= 1000) return (value / 1000) + ' K';
                                return number_format(value);
                            }
                        }
                    }
                }
            };

            const pieChartOptions = {
                 responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null && context.parsed !== undefined) {
                                    label += number_format(context.parsed);
                                    if (context.dataset.data && context.dataset.data.length > 0){
                                        const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                        if (total > 0) {
                                            const percentage = (context.parsed / total * 100).toFixed(1);
                                            label += ' (' + percentage + '%) ';
                                        }
                                    }
                                }
                                return label;
                            }
                        }
                    }
                }
            };

            // Color Palette (from live_sessions)
            const chartColors = {
                primary: '#007bff',
                success: '#28a745',
                info: '#17a2b8',
                warning: '#ffc107',
                danger: '#dc3545',
                secondary: '#6c757d',
                light_blue: '#36A2EB',
                light_green: '#73D673',
                light_orange: '#FFCE56',
                light_red: '#FF6384',
                purple: '#9966FF',
                grey: '#C9CBCF'
            };
            const colorArray = Object.values(chartColors);

            function getColors(count) {
                const colors = [];
                for (let i = 0; i < count; i++) {
                    colors.push(colorArray[i % colorArray.length]);
                }
                return colors;
            }

            function renderChartOrNoDataMessage(canvasId, chartConfig) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                let hasData = false;
                if (chartConfig.data && chartConfig.data.datasets) {
                    chartConfig.data.datasets.forEach(dataset => {
                        if (dataset.data && dataset.data.length > 0 && dataset.data.some(d => d !== 0 && d !== null && d !== undefined && !isNaN(d)) ) {
                            hasData = true;
                        }
                    });
                }
                 // For pie charts, data is directly in data.datasets[0].data and labels are in data.labels
                if (!hasData && chartConfig.type === 'pie' && chartConfig.data && chartConfig.data.labels && chartConfig.data.labels.length > 0 && chartConfig.data.datasets && chartConfig.data.datasets[0] && chartConfig.data.datasets[0].data.some(d => d > 0)){
                    hasData = true;
                }

                if (window.existingCharts && window.existingCharts[canvasId]) {
                    window.existingCharts[canvasId].destroy();
                }
                window.existingCharts = window.existingCharts || {};

                if (hasData) {
                    window.existingCharts[canvasId] = new Chart(ctx, chartConfig);
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#6c757d';
                    ctx.font = "16px 'Source Sans Pro'";
                    ctx.fillText('Không có dữ liệu để hiển thị.', canvas.width / 2, canvas.height / 2);
                }
            }

            // Prepare data from Blade
            const categoryNames = @json($chartCategoryNames ?? []);
            const revenueByGroupData = @json($chartRevenueData ?? []);
            const orderCountByGroupData = @json($chartOrderCountData ?? []);
            const quantitySoldByGroupData = @json($chartQuantityData ?? []);

            // 1. Revenue by Product Group (Bar Chart)
            renderChartOrNoDataMessage('revenueByGroupChart', {
                type: 'bar',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        label: 'Doanh Thu theo Nhóm Hàng',
                        data: revenueByGroupData,
                        backgroundColor: chartColors.primary,
                        borderColor: chartColors.primary,
                        borderWidth: 1,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                },
                options: { ...commonChartOptions }
            });

            // 2. Order Count by Product Group (Bar Chart)
            // We can make this one a line chart if preferred for variety
            renderChartOrNoDataMessage('orderCountByGroupChart', {
                type: 'line', // Changed to Line for variety, like daily orders on live sessions
                data: {
                    labels: categoryNames,
                    datasets: [{
                        label: 'Số Lượng Đơn Hàng',
                        data: orderCountByGroupData,
                        backgroundColor: 'rgba(40, 167, 69, 0.2)', // success with alpha
                        borderColor: chartColors.success,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { ...commonChartOptions }
            });

            // 3. Quantity Sold by Product Group (Bar Chart)
            renderChartOrNoDataMessage('quantitySoldByGroupChart', {
                type: 'bar',
                data: {
                    labels: categoryNames,
                    datasets: [{
                        label: 'Số Lượng Sản Phẩm Bán',
                        data: quantitySoldByGroupData,
                        backgroundColor: chartColors.info,
                        borderColor: chartColors.info,
                        borderWidth: 1,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                },
                options: { ...commonChartOptions }
            });

            // 4. Optional: Order Distribution by Product Group (Pie Chart)
            // This requires an additional canvas in the HTML, e.g., id="orderDistributionByGroupChart"
            // For now, let's assume it's not added yet to avoid errors.
            // If you add a <canvas id="orderDistributionByGroupChart"></canvas> in a new card,
            // you can uncomment and use the following:
            /*
            if (document.getElementById('orderDistributionByGroupChart') && orderCountByGroupData.length > 0) {
                const totalOrdersForPie = orderCountByGroupData.reduce((sum, val) => sum + val, 0);
                if (totalOrdersForPie > 0) {
                     renderChartOrNoDataMessage('orderDistributionByGroupChart', {
                        type: 'pie',
                        data: {
                            labels: categoryNames,
                            datasets: [{
                                label: 'Phân Bổ Đơn Hàng theo Nhóm',
                                data: orderCountByGroupData,
                                backgroundColor: getColors(categoryNames.length),
                                hoverOffset: 4
                            }]
                        },
                        options: { ...pieChartOptions } // Use specific pie chart options
                    });
                }
            }
            */

        });
    </script>
@stop
