@extends('adminlte::page')

@section('title', 'Báo cáo theo Chiến dịch')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Báo cáo theo Chiến dịch (ID Bài Post)</h1>
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
                <div class="row">
                    <div class="col-md-6">
                        <h5>Thông tin chung:</h5>
                        <ul>
                            <li>Báo cáo chỉ hiển thị các đơn hàng có trạng thái Pancake là "Đã giao hàng" (status = 6)</li>
                            <li>Doanh thu được tính dựa trên giá trị thực tế của đơn hàng</li>
                            <li>Biểu đồ thể hiện doanh thu theo từng ID bài post</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Cách sử dụng:</h5>
                        <ul>
                            <li>Sử dụng bộ lọc thời gian để xem dữ liệu theo khoảng thời gian mong muốn</li>
                            <li>Lọc theo cửa hàng và trang Facebook để xem chi tiết từng nguồn</li>
                            <li>Biểu đồ cột thể hiện tổng doanh thu của từng bài post</li>
                            <li>Bảng chi tiết bên dưới hiển thị thông tin cụ thể về số đơn và doanh thu của từng bài post</li>
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

<div class="container-fluid">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Bộ lọc</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reports.campaigns') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_range">Khoảng thời gian:</label>
                            <input type="text" name="date_range" id="date_range_campaign" class="form-control" value="{{ $startDate->format('m/d/Y') }} - {{ $endDate->format('m/d/Y') }}"/>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @if(!empty($campaignsData))
        <!-- Campaign Performance Overview Chart -->
        <div class="row">
            <div class="col-12">
                <div class="card card-info shadow mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i>Tổng Quan Hiệu Suất Chiến Dịch (Theo Doanh Thu)</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 400px;">
                            <canvas id="campaignPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h4 class="mb-3">Chi Tiết Từng Chiến Dịch:</h4>

        <div class="row">
            @foreach($campaignsData as $campaign)
                <div class="col-md-6 col-lg-4">
                    <div class="card card-widget widget-user-2 shadow-sm mb-4">
                        <div class="widget-user-header bg-info">
                            <div class="widget-user-image">
                                {{-- You can add a generic campaign icon here if desired --}}
                                {{-- <img class="img-circle elevation-2" src="..." alt="Campaign Icon"> --}}
                                <i class="fas fa-bullhorn fa-3x text-white-50" style="padding: 10px; opacity: 0.7;"></i>
                            </div>
                            <h5 class="widget-user-username" style="font-size: 1.1rem;">Bài Post ID: {{ $campaign['post_id'] }}</h5>
                            {{-- <h6 class="widget-user-desc">Mô tả chiến dịch (nếu có)</h6> --}}
                        </div>
                        <div class="card-footer p-0">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        Tổng Đơn Hàng <span class="float-right badge bg-primary">{{ number_format($campaign['total_orders']) }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        Tổng Doanh Thu <span class="float-right badge bg-success">{{ number_format($campaign['total_revenue'], 0, ',', '.') }} VND</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        Giá Trị TB/Đơn <span class="float-right badge bg-warning">{{ number_format($campaign['average_order_value'], 0, ',', '.') }} VND</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination Links --}}
        <div class="d-flex justify-content-center mt-4">
            {{ $campaignsData->appends(request()->query())->links() }}
        </div>
    @else
        <div class="alert alert-info text-center" role="alert">
            Không có dữ liệu chiến dịch cho khoảng thời gian và bộ lọc đã chọn.
        </div>
    @endif
</div>
@stop

@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .widget-user-2 .widget-user-header { padding: 1rem; }
        .widget-user-2 .widget-user-username { font-size: 1.25rem; margin-top: 5px; margin-bottom: 5px; }
        .widget-user-2 .widget-user-desc { margin-top: 0; }
        .table-sm th, .table-sm td { padding: .3rem .5rem; }
        .chart-container {
            position: relative;
            height: 400px; /* Default height */
            width: 100%;
        }
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px); /* Match AdminLTE input height */
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();

            // Initialize Campaign Performance Chart
            if (document.getElementById('campaignPerformanceChart')) {
                const ctx = document.getElementById('campaignPerformanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($chartCampaignLabels) !!},
                        datasets: [{
                            label: 'Doanh Thu (VND)',
                            data: {!! json_encode($chartCampaignRevenue) !!},
                            backgroundColor: 'rgba(60, 141, 188, 0.8)',
                            borderColor: 'rgba(60, 141, 188, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('vi-VN').format(value) + ' VND';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Doanh Thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' VND';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Date Range Picker
            $('#date_range_campaign').daterangepicker({
                locale: {
                    format: 'MM/DD/YYYY',
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
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

            // AJAX for Page filter
            $('#pancake_shop_id_campaign').on('change', function() {
                const shopId = $(this).val();
                const pageSelect = $('#pancake_page_id_campaign');
                pageSelect.empty().append('<option value="">Tất cả trang</option>').trigger('change');

                if (shopId) {
                    $.get("{{ route('ajax.pancakePagesForShop') }}", { shop_id: shopId }, function(data) {
                        if (data) {
                            data.forEach(function(page) {
                                pageSelect.append(new Option(page.name, page.id, false, false));
                            });
                            pageSelect.val("{{ request('pancake_page_id', '') }}").trigger('change');
                        }
                    });
                }
            });
            // Trigger change on load if a shop is pre-selected (e.g. from previous filter)
            if ($('#pancake_shop_id_campaign').val()) {
                $('#pancake_shop_id_campaign').trigger('change');
            }

            // Number formatting function
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

            // Chart.js Global Defaults
            Chart.defaults.font.family = "'Source Sans Pro', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'";
            Chart.defaults.color = '#6c757d';

            const commonChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += number_format(context.parsed.y) + ' VND';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#efefef', drawBorder: false },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Tr';
                                if (value >= 1000) return (value / 1000) + ' K';
                                return number_format(value);
                            }
                        }
                    }
                }
            };

            const chartColors = {
                primary: '#007bff', success: '#28a745', info: '#17a2b8', warning: '#ffc107',
                danger: '#dc3545', teal: '#20c997', purple: '#6f42c1', orange: '#fd7e14'
            };
            const colorArray = Object.values(chartColors);

            function renderChartOrNoDataMessage(canvasId, chartConfig) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                let hasData = chartConfig.data && chartConfig.data.datasets && chartConfig.data.datasets.some(ds => ds.data && ds.data.length > 0 && ds.data.some(d => d > 0));

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

            // Campaign Performance Chart
            const campaignLabels = @json($chartCampaignLabels ?? []);
            const campaignRevenueData = @json($chartCampaignRevenue ?? []);

            if (campaignLabels.length > 0) {
                renderChartOrNoDataMessage('campaignPerformanceChart', {
                    type: 'line',
                    data: {
                        labels: campaignLabels,
                        datasets: [{
                            label: 'Tổng Doanh Thu',
                            data: campaignRevenueData,
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            borderColor: chartColors.primary,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: $.extend(true, {}, commonChartOptions, {
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    autoSkip: campaignLabels.length > 15,
                                    maxRotation: campaignLabels.length > 10 ? 45 : 0,
                                    minRotation: 0
                                }
                            }
                        }
                    })
                });
            } else {
                 renderChartOrNoDataMessage('campaignPerformanceChart', {data: {labels:[], datasets:[]}}); // Render no data message
            }

        });
    </script>
@stop
