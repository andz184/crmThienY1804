@extends('adminlte::page')

@section('title', 'Báo Cáo Thanh Toán')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Báo Cáo Thanh Toán</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="sync-data">
                <i class="fas fa-sync-alt"></i> Đồng Bộ Dữ Liệu
            </button>
            <button type="button" class="btn btn-success" id="export-data">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Lọc Dữ Liệu</h5>
                    <div class="input-group date-range-container" style="width: 300px;">
                        <input type="text" class="form-control date-picker" id="payment-date-range">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment-method-filter">Phương thức thanh toán</label>
                                <select id="payment-method-filter" class="form-control">
                                    <option value="">Tất cả</option>
                                    <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                                    <option value="banking">Chuyển khoản ngân hàng</option>
                                    <option value="momo">Ví MoMo</option>
                                    <option value="zalopay">ZaloPay</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-primary btn-block" id="apply-filter">
                                    <i class="fas fa-filter"></i> Áp Dụng Bộ Lọc
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-warning btn-block" id="regenerate-reports">
                                    <i class="fas fa-redo"></i> Tạo Lại Báo Cáo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tổng Đơn Hàng</h5>
                    <h3 id="total-orders">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tổng Doanh Thu</h5>
                    <h3 id="total-revenue">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tỷ Lệ Hoàn Thành</h5>
                    <h3 id="completion-rate">0%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Trung Bình Đơn Hàng</h5>
                    <h3 id="average-order-value">0</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Tổng Quan Theo Phương Thức Thanh Toán</h5>
                </div>
                <div class="card-body">
                    <div class="chart">
                        <canvas id="payment-method-chart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Báo Cáo Chi Tiết</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="payment-report-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Phương Thức</th>
                                    <th>Tổng Đơn</th>
                                    <th>Doanh Thu</th>
                                    <th>Đơn Hoàn Thành</th>
                                    <th>DT Hoàn Thành</th>
                                    <th>Tỷ Lệ Hoàn Thành</th>
                                    <th>TB Đơn Hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dữ liệu sẽ được nạp từ API -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        .card-title {
            margin-bottom: 0;
        }
        .date-range-container {
            max-width: 300px;
        }
        .loading {
            position: relative;
        }
        .loading:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        .card {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 1rem;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let startDate;
            let endDate;
            let dataTable;
            let paymentMethodChart;

            // Khởi tạo date picker
            $('#payment-date-range').daterangepicker({
                opens: 'left',
                startDate: moment().subtract(30, 'days'),
                endDate: moment(),
                ranges: {
                   'Hôm nay': [moment(), moment()],
                   'Hôm qua': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   '7 ngày qua': [moment().subtract(6, 'days'), moment()],
                   '30 ngày qua': [moment().subtract(29, 'days'), moment()],
                   'Tháng này': [moment().startOf('month'), moment().endOf('month')],
                   'Tháng trước': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
                    customRangeLabel: 'Tùy chỉnh',
                }
            }, function(start, end) {
                startDate = start.format('YYYY-MM-DD');
                endDate = end.format('YYYY-MM-DD');
            });

            // Khởi tạo giá trị mặc định
            startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
            endDate = moment().format('YYYY-MM-DD');

            // Khởi tạo DataTable
            dataTable = $('#payment-report-table').DataTable({
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json'
                },
                columnDefs: [
                    {
                        targets: [2, 3, 4, 5, 6, 7],
                        className: 'text-right'
                    }
                ]
            });

            // Khởi tạo biểu đồ
            const ctx = document.getElementById('payment-method-chart').getContext('2d');
            paymentMethodChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Tổng Doanh Thu',
                            data: [],
                            backgroundColor: 'rgba(60, 141, 188, 0.7)',
                            borderColor: 'rgba(60, 141, 188, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Doanh Thu Hoàn Thành',
                            data: [],
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
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
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND',
                                        maximumFractionDigits: 0
                                    }).format(context.raw);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Hàm tải dữ liệu báo cáo
            function loadReportData(regenerate = false) {
                $('.card').addClass('loading');

                const paymentMethod = $('#payment-method-filter').val();

                $.ajax({
                    url: '/api/reports/payment',
                    method: 'GET',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        payment_method: paymentMethod,
                        regenerate: regenerate ? 'true' : 'false'
                    },
                    success: function(response) {
                        if (response.success) {
                            updateDashboard(response.data);
                            updateReportTable(response.data.reports);
                            updateChart(response.data.overview);
                        }
                        $('.card').removeClass('loading');
                    },
                    error: function(error) {
                        console.error('Error loading payment report data:', error);
                        $('.card').removeClass('loading');
                        alert('Đã xảy ra lỗi khi tải dữ liệu báo cáo. Vui lòng thử lại sau.');
                    }
                });
            }

            // Cập nhật bảng báo cáo
            function updateReportTable(reports) {
                dataTable.clear();

                reports.forEach(function(report) {
                    dataTable.row.add([
                        moment(report.report_date).format('DD/MM/YYYY'),
                        report.formatted_payment_method,
                        new Intl.NumberFormat('vi-VN').format(report.total_orders),
                        new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND',
                            maximumFractionDigits: 0
                        }).format(report.total_revenue),
                        new Intl.NumberFormat('vi-VN').format(report.completed_orders),
                        new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND',
                            maximumFractionDigits: 0
                        }).format(report.completed_revenue),
                        new Intl.NumberFormat('vi-VN', {
                            style: 'percent',
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(report.completion_rate / 100),
                        new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND',
                            maximumFractionDigits: 0
                        }).format(report.average_order_value)
                    ]);
                });

                dataTable.draw();
            }

            // Cập nhật bảng điều khiển
            function updateDashboard(data) {
                let totalOrders = 0;
                let totalRevenue = 0;
                let completedOrders = 0;
                let completedRevenue = 0;

                // Tính tổng các thống kê
                data.overview.forEach(function(item) {
                    totalOrders += item.total_orders;
                    totalRevenue += parseFloat(item.total_revenue);
                    completedOrders += item.completed_orders;
                    completedRevenue += parseFloat(item.completed_revenue);
                });

                // Tính toán tỷ lệ hoàn thành và giá trị trung bình mỗi đơn
                const completionRate = totalOrders > 0 ? (completedOrders / totalOrders) * 100 : 0;
                const averageOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

                // Cập nhật giao diện
                $('#total-orders').text(new Intl.NumberFormat('vi-VN').format(totalOrders));
                $('#total-revenue').text(new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND',
                    maximumFractionDigits: 0
                }).format(totalRevenue));
                $('#completion-rate').text(new Intl.NumberFormat('vi-VN', {
                    style: 'percent',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(completionRate / 100));
                $('#average-order-value').text(new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND',
                    maximumFractionDigits: 0
                }).format(averageOrderValue));
            }

            // Cập nhật biểu đồ
            function updateChart(overview) {
                const labels = [];
                const totalRevenue = [];
                const completedRevenue = [];

                // Tạo dữ liệu cho biểu đồ
                overview.forEach(function(item) {
                    let methodLabel;
                    switch(item.payment_method) {
                        case 'cod': methodLabel = 'COD'; break;
                        case 'banking': methodLabel = 'Chuyển khoản'; break;
                        case 'momo': methodLabel = 'MoMo'; break;
                        case 'zalopay': methodLabel = 'ZaloPay'; break;
                        default: methodLabel = 'Khác';
                    }

                    labels.push(methodLabel);
                    totalRevenue.push(parseFloat(item.total_revenue));
                    completedRevenue.push(parseFloat(item.completed_revenue));
                });

                // Cập nhật dữ liệu biểu đồ
                paymentMethodChart.data.labels = labels;
                paymentMethodChart.data.datasets[0].data = totalRevenue;
                paymentMethodChart.data.datasets[1].data = completedRevenue;
                paymentMethodChart.update();
            }

            // Xử lý sự kiện áp dụng bộ lọc
            $('#apply-filter').on('click', function() {
                loadReportData();
            });

            // Xử lý sự kiện tạo lại báo cáo
            $('#regenerate-reports').on('click', function() {
                if (confirm('Bạn có chắc chắn muốn tạo lại báo cáo cho khoảng thời gian đã chọn?')) {
                    loadReportData(true);
                }
            });

            // Xử lý sự kiện đồng bộ dữ liệu
            $('#sync-data').on('click', function() {
                if (confirm('Bạn có muốn đồng bộ dữ liệu từ Pancake trước khi xem báo cáo?')) {
                    $(this).prop('disabled', true);
                    $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');

                    $.ajax({
                        url: '/reports/sync-from-pancake',
                        method: 'POST',
                        data: {
                            start_date: startDate,
                            end_date: endDate,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Đồng bộ dữ liệu thành công!');
                                loadReportData(true);
                            } else {
                                alert('Đồng bộ thất bại: ' + response.message);
                            }
                            $('#sync-data').prop('disabled', false);
                            $('#sync-data').html('<i class="fas fa-sync-alt"></i> Đồng Bộ Dữ Liệu');
                        },
                        error: function(error) {
                            console.error('Error syncing data:', error);
                            alert('Đã xảy ra lỗi khi đồng bộ dữ liệu. Vui lòng thử lại sau.');
                            $('#sync-data').prop('disabled', false);
                            $('#sync-data').html('<i class="fas fa-sync-alt"></i> Đồng Bộ Dữ Liệu');
                        }
                    });
                }
            });

            // Xử lý sự kiện xuất báo cáo
            $('#export-data').on('click', function() {
                alert('Tính năng xuất báo cáo đang được phát triển.');
            });

            // Tải dữ liệu ban đầu
            loadReportData();
        });
    </script>
@stop
