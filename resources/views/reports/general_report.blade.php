@extends('adminlte::page')

@section('title', 'Báo cáo Tổng hợp')

@section('content_header')
    <h1 class="m-0 text-dark">Báo cáo Tổng hợp</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.general_report') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="date_range">Khoảng thời gian:</label>
                                <input type="text" name="date_range" class="form-control" id="date_range_picker"
                                       value="{{ $dateRange ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">Xem báo cáo</button>
                            </div>
                        </div>
                    </form>

                    <div id="report-content">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h3>1. Tổng quan Doanh thu</h3>
                                <p>Tổng doanh thu: <strong id="full-revenue">Đang tải...</strong></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <h3>2. Thống kê theo Tỉnh thành</h3>
                                <table class="table table-bordered" id="province-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Tỉnh/Thành phố</th>
                                            <th>Tổng doanh thu</th>
                                            <th>Tổng đơn hàng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="3">Đang tải...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h3>3. Thống kê theo Sản phẩm</h3>
                                <table class="table table-bordered" id="product-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Tổng doanh thu</th>
                                            <th>Số lượng bán</th>
                                            <th>Tổng đơn hàng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="4">Đang tải...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h3>4. Chi tiết Doanh thu Theo Ngày</h3>
                                <div id="daily-stats-chart-container" style="height: 400px;"></div>
                                <table class="table table-bordered mt-3" id="daily-stats-table">
                                    <thead>
                                        <tr>
                                            <th>Ngày</th>
                                            <th>Doanh thu</th>
                                            <th>Đơn hàng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                     <tr><td colspan="3">Đang tải...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <script>
        $(function () {
            // Date Range Picker
            let startDate = moment('{{ $startDate->format("Y-m-d H:i:s") }}');
            let endDate = moment('{{ $endDate->format("Y-m-d H:i:s") }}');

            function cb(start, end) {
                $('#date_range_picker').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            }

            $('#date_range_picker').daterangepicker({
                startDate: startDate,
                endDate: endDate,
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
                    applyLabel: "Áp dụng",
                    cancelLabel: "Hủy",
                    fromLabel: "Từ",
                    toLabel: "Đến",
                    customRangeLabel: "Tùy chọn",
                    daysOfWeek: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
                    monthNames: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
                    firstDay: 1
                }
            }, cb);

            // Initial call if dateRange is present
            if ($('#date_range_picker').val()){
                 cb(startDate, endDate);
            } else {
                // If no date_range is set (e.g. first load with defaults from controller), initialize with default display
                let defaultStartDate = moment().subtract(29, 'days');
                let defaultEndDate = moment();
                $('#date_range_picker').val(defaultStartDate.format('DD/MM/YYYY') + ' - ' + defaultEndDate.format('DD/MM/YYYY'));
            }

            let dailyChart;

            function loadReportData() {
                const reportStartDate = moment($('#date_range_picker').data('daterangepicker').startDate).format('YYYY-MM-DD');
                const reportEndDate = moment($('#date_range_picker').data('daterangepicker').endDate).format('YYYY-MM-DD');

                // Show loading indicators
                $('#full-revenue').text('Đang tải...');
                $('#province-stats-table tbody').html('<tr><td colspan="3">Đang tải...</td></tr>');
                $('#product-stats-table tbody').html('<tr><td colspan="4">Đang tải...</td></tr>');
                $('#daily-stats-table tbody').html('<tr><td colspan="3">Đang tải...</td></tr>');
                if(dailyChart) dailyChart.destroy();

                $.ajax({
                    url: "{{ route('reports.general_report_data') }}",
                    type: 'GET',
                    data: {
                        start_date: reportStartDate,
                        end_date: reportEndDate
                    },
                    success: function(response) {
                        // 1. Full Revenue
                        $('#full-revenue').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(response.full_revenue || 0));

                        // 2. Province Stats
                        let provinceHtml = '';
                        if (Object.keys(response.revenue_by_province).length > 0) {
                            for (const [province, data] of Object.entries(response.revenue_by_province)) {
                                provinceHtml += `<tr><td>${province}</td><td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(data.total_revenue)}</td><td>${data.total_orders}</td></tr>`;
                            }
                        } else {
                            provinceHtml = '<tr><td colspan="3">Không có dữ liệu.</td></tr>';
                        }
                        $('#province-stats-table tbody').html(provinceHtml);

                        // 3. Product Stats
                        let productHtml = '';
                        if (response.revenue_by_product && response.revenue_by_product.length > 0) {
                            response.revenue_by_product.forEach(product => {
                                productHtml += `<tr><td>${product.product_name}</td><td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.total_revenue)}</td><td>${product.total_quantity}</td><td>${product.total_orders}</td></tr>`;
                            });
                        } else {
                            productHtml = '<tr><td colspan="4">Không có dữ liệu.</td></tr>';
                        }
                        $('#product-stats-table tbody').html(productHtml);

                        // 4. Daily Stats
                        let dailyTableHtml = '';
                        const dailyLabels = [];
                        const dailyRevenueData = [];
                        const dailyOrdersData = [];

                        if (Object.keys(response.daily_stats).length > 0) {
                            for (const [date, stats] of Object.entries(response.daily_stats).sort((a,b) => new Date(a[0]) - new Date(b[0]))) {
                                dailyTableHtml += `<tr><td>${moment(date).format('DD/MM/YYYY')}</td><td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(stats.revenue)}</td><td>${stats.orders}</td></tr>`;
                                dailyLabels.push(moment(date).format('DD/MM'));
                                dailyRevenueData.push(stats.revenue);
                                dailyOrdersData.push(stats.orders);
                            }
                        } else {
                            dailyTableHtml = '<tr><td colspan="3">Không có dữ liệu.</td></tr>';
                        }
                        $('#daily-stats-table tbody').html(dailyTableHtml);

                        // Render Chart for Daily Stats
                        const ctx = document.getElementById('daily-stats-chart-container').getContext('2d');
                        dailyChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: dailyLabels,
                                datasets: [
                                    {
                                        label: 'Doanh thu (VND)',
                                        data: dailyRevenueData,
                                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 1,
                                        yAxisID: 'y-revenue'
                                    },
                                    {
                                        label: 'Số đơn hàng',
                                        data: dailyOrdersData,
                                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                        borderColor: 'rgba(255, 99, 132, 1)',
                                        borderWidth: 1,
                                        yAxisID: 'y-orders'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: {
                                        stacked: true,
                                    },
                                    'y-revenue': {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        title: {
                                            display: true,
                                            text: 'Doanh thu (VND)'
                                        },
                                        ticks: {
                                            callback: function(value, index, values) {
                                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
                                            }
                                        }
                                    },
                                    'y-orders': {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        title: {
                                            display: true,
                                            text: 'Số đơn hàng'
                                        },
                                        grid: {
                                            drawOnChartArea: false, // only draw grid for revenue axis
                                        }
                                    }
                                }
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading report data: ", error);
                        $('#report-content').html('<p class="text-danger">Lỗi tải dữ liệu báo cáo. Vui lòng thử lại.</p>');
                    }
                });
            }

            // Load data on page load
            loadReportData();

            // Reload data when form is submitted (after daterangepicker applies new dates)
            // The form submission itself handles navigation, but if we wanted to do it purely via AJAX:
            // $('form').on('submit', function(e){
            //     e.preventDefault();
            //     loadReportData();
            // });
            // For this setup, the page reloads with new date parameters, and loadReportData() will be called again.
        });
    </script>
@stop
