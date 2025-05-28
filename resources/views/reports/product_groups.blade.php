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
                                                <span class="text-muted ml-4">Tổng giá trị đơn hàng của tất cả sản phẩm trong nhóm hàng hóa (chỉ tính đơn hàng đã giao thành công).</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-success mr-2"></i>
                                                <strong>Số Lượng Đơn Hàng:</strong><br>
                                                <span class="text-muted ml-4">Số lượng đơn hàng thành công có chứa ít nhất một sản phẩm thuộc nhóm hàng hóa.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-info mr-2"></i>
                                                <strong>Số Lượng Sản Phẩm Bán:</strong><br>
                                                <span class="text-muted ml-4">Tổng số lượng sản phẩm đã bán ra từ nhóm hàng hóa trong các đơn hàng thành công.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-warning mr-2"></i>
                                                <strong>Giá Trị TB/Đơn:</strong><br>
                                                <span class="text-muted ml-4">Giá trị trung bình của mỗi đơn hàng trong nhóm hàng (Tổng doanh thu / Số lượng đơn).</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-info-circle text-danger mr-2"></i>
                                                <strong>Số Lượng TB/Đơn:</strong><br>
                                                <span class="text-muted ml-4">Số lượng sản phẩm trung bình trong mỗi đơn hàng (Tổng số lượng / Số lượng đơn).</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-muted font-weight-bold mb-3">Hướng dẫn sử dụng:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-3">
                                                <i class="fas fa-chart-bar text-primary mr-2"></i>
                                                <strong>Biểu đồ doanh thu:</strong><br>
                                                <span class="text-muted ml-4">Hiển thị top 10 nhóm hàng có doanh thu cao nhất. Di chuột qua cột để xem chi tiết.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-chart-pie text-success mr-2"></i>
                                                <strong>Biểu đồ phân bổ:</strong><br>
                                                <span class="text-muted ml-4">Thể hiện tỷ lệ đóng góp doanh thu của từng nhóm hàng. Di chuột qua để xem phần trăm.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-table text-info mr-2"></i>
                                                <strong>Bảng chi tiết:</strong><br>
                                                <span class="text-muted ml-4">Hiển thị thông tin chi tiết của tất cả nhóm hàng. Nhấn vào nút "sản phẩm" để xem chi tiết từng sản phẩm.</span>
                                            </li>
                                            <li class="mb-3">
                                                <i class="fas fa-filter text-warning mr-2"></i>
                                                <strong>Bộ lọc thời gian:</strong><br>
                                                <span class="text-muted ml-4">Chọn khoảng thời gian để xem báo cáo. Có sẵn các mốc thời gian phổ biến hoặc tùy chọn tự do.</span>
                                            </li>
                                        </ul>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-lightbulb mr-2"></i>
                                            <strong>Mẹo:</strong> Bạn có thể sắp xếp bảng theo bất kỳ cột nào bằng cách nhấp vào tiêu đề cột.
                                        </div>
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            <strong>Lưu ý:</strong> Báo cáo chỉ tính các đơn hàng có trạng thái Pancake là "Đã giao hàng" (status = 6).
                                        </div>
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
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-bar mr-1"></i>
                                        Doanh Thu theo Nhóm Hàng Hóa (Top 10)
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueByGroupChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-pie mr-1"></i>
                                        Phân Bổ Doanh Thu theo Nhóm
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueDistributionChart"></canvas>
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
                                        <th class="text-right">Giá Trị TB/Đơn (VND)</th>
                                        <th class="text-right">Số Lượng TB/Đơn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryData as $category)
                                        <tr>
                                            <td>{{ $category['id'] }}</td>
                                            <td>
                                                <strong>{{ $category['name'] }}</strong>
                                                @if(!empty($category['products']))
                                                    <button type="button" class="btn btn-xs btn-info ml-2" data-toggle="collapse" data-target="#products-{{ $category['id'] }}">
                                                        <i class="fas fa-list"></i> {{ count($category['products']) }} sản phẩm
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format($category['total_revenue'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_orders'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_quantity_sold'], 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_orders'] > 0 ? $category['total_revenue'] / $category['total_orders'] : 0, 0, ',', '.') }}</td>
                                            <td class="text-right">{{ number_format($category['total_orders'] > 0 ? $category['total_quantity_sold'] / $category['total_orders'] : 0, 1, ',', '.') }}</td>
                                        </tr>
                                        @if(!empty($category['products']))
                                            <tr>
                                                <td colspan="7" class="p-0">
                                                    <div id="products-{{ $category['id'] }}" class="collapse">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered bg-light mb-0">
                                                                <thead>
                                                                    <tr class="bg-secondary">
                                                                        <th>ID Sản phẩm</th>
                                                                        <th>Tên sản phẩm</th>
                                                                        <th class="text-right">Số lượng bán</th>
                                                                        <th class="text-right">Doanh thu</th>
                                                                        <th class="text-right">Giá TB/Sản phẩm</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($category['products'] as $product)
                                                                        <tr>
                                                                            <td>{{ $product['id'] }}</td>
                                                                            <td>{{ $product['name'] }}</td>
                                                                            <td class="text-right">{{ number_format($product['total_quantity'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ number_format($product['total_revenue'], 0, ',', '.') }}</td>
                                                                            <td class="text-right">{{ number_format($product['total_quantity'] > 0 ? $product['total_revenue'] / $product['total_quantity'] : 0, 0, ',', '.') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
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
                    datasets: [
                        {
                            label: 'Doanh Thu (VND)',
                        data: revenueByGroupData,
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: chartColors.primary,
                            borderWidth: 2,
                        barPercentage: 0.6,
                            categoryPercentage: 0.7,
                            hoverBackgroundColor: 'rgba(0, 123, 255, 0.4)',
                            hoverBorderColor: chartColors.primary,
                            hoverBorderWidth: 3,
                            order: 1
                        },
                        {
                            label: 'Số Đơn Hàng',
                        data: orderCountByGroupData,
                            type: 'line',
                        borderColor: chartColors.success,
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointBackgroundColor: chartColors.success,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y1',
                            order: 0
                        },
                        {
                            label: 'Số Lượng Sản Phẩm',
                            data: quantitySoldByGroupData,
                            type: 'line',
                            borderColor: chartColors.warning,
                            backgroundColor: 'transparent',
                        borderWidth: 2,
                            pointBackgroundColor: chartColors.warning,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            yAxisID: 'y1',
                            order: 0
                        }
                    ]
                },
                options: {
                    ...commonChartOptions,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Doanh Thu (VND)',
                                color: chartColors.primary,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' Tỷ';
                                    if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Tr';
                                    if (value >= 1000) return (value / 1000).toFixed(1) + ' K';
                                    return number_format(value);
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Số lượng',
                                color: chartColors.success,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
                                    return number_format(value);
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        ...commonChartOptions.plugins,
                        tooltip: {
                            ...commonChartOptions.plugins.tooltip,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.dataset.yAxisID === 'y') {
                                        if (context.parsed.y >= 1000000000) {
                                            label += (context.parsed.y / 1000000000).toFixed(1) + ' Tỷ VND';
                                        } else if (context.parsed.y >= 1000000) {
                                            label += (context.parsed.y / 1000000).toFixed(1) + ' Triệu VND';
                                        } else {
                                            label += number_format(context.parsed.y) + ' VND';
                                        }
                                    } else {
                                        if (context.parsed.y >= 1000) {
                                            label += (context.parsed.y / 1000).toFixed(1) + 'K';
                                        } else {
                                            label += number_format(context.parsed.y);
                                        }
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // 2. Revenue Distribution by Product Group (Pie Chart)
            if (document.getElementById('revenueDistributionChart') && revenueByGroupData.length > 0) {
                const totalRevenue = revenueByGroupData.reduce((sum, val) => sum + val, 0);
                if (totalRevenue > 0) {
                    renderChartOrNoDataMessage('revenueDistributionChart', {
                        type: 'doughnut', // Changed to doughnut for better visualization
                data: {
                    labels: categoryNames,
                    datasets: [{
                                label: 'Phân Bổ Doanh Thu',
                                data: revenueByGroupData,
                                backgroundColor: getColors(categoryNames.length).map(color => {
                                    // Make colors slightly transparent
                                    return color.replace('rgb', 'rgba').replace(')', ', 0.8)');
                                }),
                                borderColor: getColors(categoryNames.length),
                        borderWidth: 1,
                                hoverOffset: 8,
                                hoverBorderWidth: 2
                            }]
                        },
                        options: {
                            ...pieChartOptions,
                            cutout: '60%', // Makes the doughnut thinner
                            radius: '90%', // Makes the chart slightly smaller to fit labels
                            plugins: {
                                ...pieChartOptions.plugins,
                                legend: {
                                    position: 'right',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    ...pieChartOptions.plugins.tooltip,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    titleFont: {
                                        size: 13,
                                        weight: 'bold'
                                    },
                                    bodyFont: {
                                        size: 12
                                    },
                                    padding: 12,
                                    callbacks: {
                                        label: function(context) {
                                            const value = context.raw;
                                            const percentage = ((value / totalRevenue) * 100).toFixed(1);
                                            return [
                                                `Doanh thu: ${number_format(value)} VND`,
                                                `Tỷ lệ: ${percentage}%`
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

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
