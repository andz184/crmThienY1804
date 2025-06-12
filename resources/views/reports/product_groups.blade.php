@extends('adminlte::page')

@section('title', 'Báo cáo theo Nhóm Hàng Hóa')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Báo cáo /</span> Nhóm Hàng Hóa
        </h4>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#helpModal">
            <i class="fas fa-question-circle me-1"></i> Hướng dẫn
        </button>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Help Modal -->
        <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="helpModalLabel">
                            <i class="fas fa-question-circle me-1"></i>
                            Hướng dẫn đọc báo cáo
                        </h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-muted fw-bold mb-3">Giải thích các chỉ số:</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        <strong>Tổng Doanh Thu:</strong><br>
                                        <span class="text-muted ms-4">Tổng giá trị đơn hàng của tất cả sản phẩm trong nhóm hàng hóa (chỉ tính đơn hàng đã giao thành công).</span>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-info-circle text-success me-2"></i>
                                        <strong>Số Lượng Đơn Hàng:</strong><br>
                                        <span class="text-muted ms-4">Số lượng đơn hàng thành công có chứa ít nhất một sản phẩm thuộc nhóm hàng hóa.</span>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        <strong>Số Lượng Sản Phẩm Bán:</strong><br>
                                        <span class="text-muted ms-4">Tổng số lượng sản phẩm đã bán ra từ nhóm hàng hóa trong các đơn hàng thành công.</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-muted fw-bold mb-3">Hướng dẫn sử dụng:</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <i class="fas fa-chart-bar text-primary me-2"></i>
                                        <strong>Biểu đồ doanh thu:</strong><br>
                                        <span class="text-muted ms-4">Hiển thị top 10 nhóm hàng có doanh thu cao nhất. Di chuột qua cột để xem chi tiết.</span>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-chart-pie text-success me-2"></i>
                                        <strong>Biểu đồ phân bổ:</strong><br>
                                        <span class="text-muted ms-4">Thể hiện tỷ lệ đóng góp doanh thu của từng nhóm hàng. Di chuột qua để xem phần trăm.</span>
                                    </li>
                                    <li class="mb-3">
                                        <i class="fas fa-table text-info me-2"></i>
                                        <strong>Bảng chi tiết:</strong><br>
                                        <span class="text-muted ms-4">Hiển thị thông tin chi tiết của tất cả nhóm hàng. Nhấn vào nút "sản phẩm" để xem chi tiết từng sản phẩm.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Bộ lọc
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.product_groups') }}" class="mb-0">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label for="date_range" class="form-label">Khoảng thời gian:</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="far fa-calendar-alt"></i>
                                    </span>
                                    <input type="text" name="date_range" id="date_range" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0 d-flex">
                                <button type="submit" class="btn btn-primary d-flex align-items-center me-3">
                                    <i class="fas fa-search me-2"></i> Lọc
                                </button>
                                <button type="button"  style="margin-left: 20px" class="btn btn-secondary d-flex align-items-center" onclick="window.location.href='{{ route('reports.product_groups') }}'">
                                    <i class="fas fa-sync me-2"></i> Đặt lại
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
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="fw-semibold d-block mb-1">Tổng Doanh Thu</span>
                                <h3 class="card-title mb-0">{{ number_format($totalRevenueAllGroups ?? 0, 0, ',', '.') }} VND</h3>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="fw-semibold d-block mb-1">Tổng Số Đơn Hàng</span>
                                <h3 class="card-title mb-0">{{ number_format($totalOrdersAllGroups ?? 0, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="fas fa-shopping-cart"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="fw-semibold d-block mb-1">Tổng Số Lượng Sản Phẩm</span>
                                <h3 class="card-title mb-0">{{ number_format($totalQuantityAllGroups ?? 0, 0, ',', '.') }}</h3>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="fas fa-cubes"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($categoryData))
            <!-- Charts Row -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Doanh Thu theo Nhóm Hàng Hóa (Top 10)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div id="revenueByGroupChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Phân Bổ Doanh Thu theo Nhóm
                            </h5>
                        </div>
                        <div class="card-body" style="background-color: #233446;">
                            <div class="chart-container">
                                <div id="revenueDistributionChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Groups Table -->
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        Chi Tiết theo Nhóm Hàng Hóa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productGroupsTable">
                            <thead>
                                <tr>
                                    <th>ID Nhóm</th>
                                    <th>Tên Nhóm Hàng Hóa</th>
                                    <th class="text-end">Tổng Doanh Thu (VND)</th>
                                    <th class="text-end">Số Lượng Đơn Hàng</th>
                                    <th class="text-end">Số Lượng Sản Phẩm Bán</th>
                                    <th class="text-end">Giá Trị TB/Đơn (VND)</th>
                                    <th class="text-end">Số Lượng TB/Đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryData as $category)
                                    <tr>
                                        <td>{{ $category['id'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <strong>{{ $category['name'] }}</strong>
                                                @if(!empty($category['products']))
                                                    <button type="button" class="btn btn-sm btn-info ms-2" data-toggle="collapse" data-target="#products-{{ $category['id'] }}">
                                                        <i class="fas fa-list"></i> {{ count($category['products']) }} sản phẩm
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">{{ number_format($category['total_revenue'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($category['total_orders'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($category['total_quantity_sold'], 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($category['total_orders'] > 0 ? $category['total_revenue'] / $category['total_orders'] : 0, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($category['total_orders'] > 0 ? $category['total_quantity_sold'] / $category['total_orders'] : 0, 1, ',', '.') }}</td>
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
                                                                    <th class="text-end">Số lượng bán</th>
                                                                    <th class="text-end">Doanh thu</th>
                                                                    <th class="text-end">Giá TB/Sản phẩm</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($category['products'] as $product)
                                                                    <tr>
                                                                        <td>{{ $product['id'] }}</td>
                                                                        <td>{{ $product['name'] }}</td>
                                                                        <td class="text-end">{{ number_format($product['total_quantity'], 0, ',', '.') }}</td>
                                                                        <td class="text-end">{{ number_format($product['total_revenue'], 0, ',', '.') }}</td>
                                                                        <td class="text-end">{{ number_format($product['total_quantity'] > 0 ? $product['total_revenue'] / $product['total_quantity'] : 0, 0, ',', '.') }}</td>
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
                <div class="card-footer d-flex justify-content-center">
                    @if(isset($categoryData) && is_object($categoryData) && method_exists($categoryData, 'links'))
                        {{ $categoryData->links('pagination::bootstrap-4') }}
                    @endif
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6 class="alert-heading fw-bold mb-1">
                            <i class="fas fa-info-circle me-2"></i>
                            Thông báo
                        </h6>
                        <span>Không có dữ liệu nhóm hàng hóa cho khoảng thời gian đã chọn.</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
@stop

@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        :root {
            --bs-blue: #696cff;
            --bs-primary: #696cff;
            --bs-success: #71dd37;
            --bs-info: #03c3ec;
            --bs-warning: #ffab00;
            --bs-danger: #ff3e1d;
            --bs-gray: #8592a3;
            --bs-gray-dark: #233446;
            --bs-gray-25: rgba(67, 89, 113, 0.025);
            --bs-gray-50: rgba(67, 89, 113, 0.05);
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .card {
            background: #fff;
            border-radius: 0.375rem;
            border: 0 solid #d9dee3;
            box-shadow: 0 0 0.375rem 0.25rem var(--bs-gray-25);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid #d9dee3;
            padding: 1.5rem;
        }

        .card-title {
            color: #566a7f;
            font-size: 1.125rem;
            font-weight: 500;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 0.5rem;
            font-size: 1.125rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            font-size: 1.125rem;
        }

        .avatar-initial {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border-radius: 0.375rem;
        }

        .bg-label-primary {
            background-color: #e7e7ff !important;
            color: var(--bs-primary) !important;
        }

        .bg-label-success {
            background-color: #e8fadf !important;
            color: var(--bs-success) !important;
        }

        .bg-label-info {
            background-color: #d7f5fc !important;
            color: var(--bs-info) !important;
        }

        .fw-semibold {
            font-weight: 600 !important;
        }

        .fw-bold {
            font-weight: 700 !important;
        }

        .text-muted {
            color: #a1acb8 !important;
        }

        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover {
            background-color: #5f65f4;
            border-color: #5f65f4;
        }

        .btn-info {
            background-color: var(--bs-info);
            border-color: var(--bs-info);
        }

        .table th {
            color: #566a7f;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
            padding: 0.625rem 1.25rem;
            background-color: #f5f5f9;
            border-top: 0;
        }

        .table td {
            color: #697a8d;
            padding: 1rem 1.25rem;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: var(--bs-gray-25);
        }

        .form-label {
            color: #566a7f;
            font-size: 0.9375rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: transparent;
            border-color: #d9dee3;
        }

        .form-control {
            border-color: #d9dee3;
            padding: 0.4375rem 0.875rem;
            font-size: 0.9375rem;
            border-radius: 0.375rem;
        }

        .form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0.25rem 0.05rem rgba(105, 108, 255, 0.1);
        }

        .alert {
            border: 0;
            border-radius: 0.375rem;
            padding: 1rem 1.5rem;
        }

        .alert-info {
            background-color: #d7f5fc;
            color: #03c3ec;
        }

        .alert-heading {
            color: inherit;
        }

        .modal-content {
            border: 0;
            box-shadow: 0 2px 16px 0 rgba(67, 89, 113, 0.45);
        }

        .modal-header {
            padding: 1.5rem;
            background-color: var(--bs-primary);
            color: #fff;
        }

        .modal-title {
            color: #fff;
        }

        .btn-close {
            background-color: #fff;
            opacity: 1;
        }

        .apexcharts-tooltip {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 0 1px 0 var(--bs-gray-400), 0 2px 4px -2px var(--bs-gray-400);
        }

        .apexcharts-tooltip-title {
            background: #f5f5f9 !important;
            border-bottom: 1px solid #e9ecef;
            padding: 0.5rem 1rem;
            margin: 0 !important;
        }

        .apexcharts-xaxistooltip {
            background: #fff;
            border-radius: 0.375rem;
            box-shadow: 0 0 1px 0 var(--bs-gray-400), 0 2px 4px -2px var(--bs-gray-400);
            color: #697a8d;
        }

        .apexcharts-legend-text {
            color: #697a8d !important;
            font-size: 0.9375rem !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: #fff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #5f65f4 !important;
            border-color: #5f65f4 !important;
            color: #fff !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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

            // Number formatting function
            function number_format(number, decimals = 0, dec_point = ',', thousands_sep = '.') {
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

            // Colors
            const colors = {
                primary: '#696cff',
                secondary: '#8592a3',
                success: '#71dd37',
                info: '#03c3ec',
                warning: '#ffab00',
                danger: '#ff3e1d',
                dark: '#233446',
                white: '#fff',
                gray: '#eceef1'
            };

            // Revenue by Product Group Chart
            if (document.getElementById('revenueByGroupChart')) {
                const categoryNames = @json($chartCategoryNames ?? []);
                const revenueData = @json($chartRevenueData ?? []);
                const orderCountData = @json($chartOrderCountData ?? []);
                const quantityData = @json($chartQuantityData ?? []);

                const revenueChartOptions = {
                    series: [
                        {
                            name: 'Doanh Thu',
                            type: 'column',
                            data: revenueData
                        },
                        {
                            name: 'Số Đơn Hàng',
                            type: 'line',
                            data: orderCountData
                        },
                        {
                            name: 'Số Lượng Sản Phẩm',
                            type: 'line',
                            data: quantityData
                        }
                    ],
                    chart: {
                        height: 400,
                        type: 'line',
                        stacked: false,
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: true,
                                zoom: true,
                                zoomin: true,
                                zoomout: true,
                                pan: true,
                                reset: true
                            }
                        }
                    },
                    stroke: {
                        width: [0, 2, 2],
                        curve: 'smooth'
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '50%',
                            borderRadius: 5
                        }
                    },
                    colors: [colors.primary, colors.success, colors.warning],
                    fill: {
                        opacity: [0.85, 1, 1],
                        gradient: {
                            inverseColors: false,
                            shade: 'light',
                            type: "vertical",
                            opacityFrom: 0.85,
                            opacityTo: 0.55,
                            stops: [0, 100, 100, 100]
                        }
                    },
                    markers: {
                        size: 0
                    },
                    xaxis: {
                        type: 'category',
                        categories: categoryNames,
                        labels: {
                            style: {
                                colors: colors.secondary,
                                fontSize: '13px',
                                fontFamily: 'Public Sans'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: [
                        {
                            title: {
                                text: "Doanh Thu (VND)",
                                style: {
                                    color: colors.primary
                                }
                            },
                            labels: {
                                formatter: function(val) {
                                    return number_format(val);
                                },
                                style: {
                                    colors: colors.primary
                                }
                            }
                        },
                        {
                            opposite: true,
                            title: {
                                text: "Số lượng",
                                style: {
                                    color: colors.success
                                }
                            },
                            labels: {
                                formatter: function(val) {
                                    return number_format(val, 0);
                                },
                                style: {
                                    colors: colors.success
                                }
                            }
                        }
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function(y, { seriesIndex }) {
                                if (seriesIndex === 0) {
                                    return number_format(y) + ' VND';
                                }
                                return number_format(y, 0);
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'center',
                        offsetY: 0,
                        markers: {
                            width: 8,
                            height: 8,
                            radius: 12
                        },
                        itemMargin: {
                            horizontal: 15
                        }
                    },
                    grid: {
                        borderColor: colors.gray,
                        padding: {
                            top: 0,
                            bottom: -8,
                            left: 20,
                            right: 20
                        }
                    }
                };

                const revenueChart = new ApexCharts(document.querySelector("#revenueByGroupChart"), revenueChartOptions);
                revenueChart.render();
            }

            // Revenue Distribution Chart
            if (document.getElementById('revenueDistributionChart')) {
                const categoryNames = @json($chartCategoryNames ?? []);
                const revenueData = @json($chartRevenueData ?? []);

                const distributionChartOptions = {
                    series: revenueData,
                    chart: {
                        height: 400,
                        type: 'donut',
                        foreColor: '#ffffff'
                    },
                    labels: categoryNames,
                    colors: [
                        colors.primary,
                        colors.success,
                        colors.warning,
                        colors.info,
                        colors.danger,
                        colors.secondary
                    ],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Tổng Doanh Thu',
                                        color: '#ffffff',
                                        formatter: function(w) {
                                            return number_format(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) + ' VND';
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            colors: ['#ffffff']
                        },
                        formatter: function(val) {
                            return number_format(val, 1) + '%';
                        }
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        labels: {
                            colors: '#ffffff'
                        },
                        markers: {
                            width: 8,
                            height: 8,
                            radius: 10
                        },
                        itemMargin: {
                            horizontal: 15,
                            vertical: 5
                        }
                    },
                    tooltip: {
                        theme: 'light',
                        y: {
                            formatter: function(value) {
                                return number_format(value) + ' VND';
                            }
                        }
                    }
                };

                const distributionChart = new ApexCharts(document.querySelector("#revenueDistributionChart"), distributionChartOptions);
                distributionChart.render();
            }
        });
    </script>
@stop
