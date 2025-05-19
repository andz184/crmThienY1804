<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Báo Cáo Chi Tiết</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="detail-date-range">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Tổng Đơn Hàng</h5>
                                <h3 id="total-detail-orders">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Tổng Doanh Thu</h5>
                                <h3 id="total-detail-revenue">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Giá Trị Đơn TB</h5>
                                <h3 id="avg-order-value">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Đơn Hàng/Ngày</h5>
                                <h3 id="orders-per-day">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <ul class="nav nav-tabs" id="detailTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="daily-tab" data-toggle="tab" href="#daily" role="tab" aria-controls="daily" aria-selected="true">Theo Ngày</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="weekly-tab" data-toggle="tab" href="#weekly" role="tab" aria-controls="weekly" aria-selected="false">Theo Tuần</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="monthly-tab" data-toggle="tab" href="#monthly" role="tab" aria-controls="monthly" aria-selected="false">Theo Tháng</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="status-tab" data-toggle="tab" href="#status" role="tab" aria-controls="status" aria-selected="false">Theo Trạng Thái</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="source-tab" data-toggle="tab" href="#source" role="tab" aria-controls="source" aria-selected="false">Theo Nguồn</a>
                            </li>
                        </ul>

                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="detailTabsContent">
                            <!-- Daily Tab -->
                            <div class="tab-pane fade show active" id="daily" role="tabpanel" aria-labelledby="daily-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <canvas id="daily-chart" height="300"></canvas>
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped" id="daily-table">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Số Đơn Hàng</th>
                                                <th>Doanh Thu</th>
                                                <th>Giá Trị Đơn TB</th>
                                            </tr>
                                        </thead>
                                        <tbody id="daily-table-body">
                                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Weekly Tab -->
                            <div class="tab-pane fade" id="weekly" role="tabpanel" aria-labelledby="weekly-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <canvas id="weekly-chart" height="300"></canvas>
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped" id="weekly-table">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Tuần</th>
                                                <th>Số Đơn Hàng</th>
                                                <th>Doanh Thu</th>
                                                <th>Giá Trị Đơn TB</th>
                                            </tr>
                                        </thead>
                                        <tbody id="weekly-table-body">
                                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Monthly Tab -->
                            <div class="tab-pane fade" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <canvas id="monthly-chart" height="300"></canvas>
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-striped" id="monthly-table">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Tháng</th>
                                                <th>Số Đơn Hàng</th>
                                                <th>Doanh Thu</th>
                                                <th>Giá Trị Đơn TB</th>
                                            </tr>
                                        </thead>
                                        <tbody id="monthly-table-body">
                                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Status Tab -->
                            <div class="tab-pane fade" id="status" role="tabpanel" aria-labelledby="status-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <canvas id="status-chart" height="300"></canvas>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="status-table">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Trạng Thái</th>
                                                        <th>Số Đơn Hàng</th>
                                                        <th>Doanh Thu</th>
                                                        <th>Tỷ Lệ (%)</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="status-table-body">
                                                    <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Source Tab -->
                            <div class="tab-pane fade" id="source" role="tabpanel" aria-labelledby="source-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <canvas id="source-chart" height="300"></canvas>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="source-table">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th>Nguồn</th>
                                                        <th>Số Đơn Hàng</th>
                                                        <th>Doanh Thu</th>
                                                        <th>Tỷ Lệ (%)</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="source-table-body">
                                                    <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    var dailyChartCtx = document.getElementById('daily-chart').getContext('2d');
    var weeklyChartCtx = document.getElementById('weekly-chart').getContext('2d');
    var monthlyChartCtx = document.getElementById('monthly-chart').getContext('2d');
    var statusChartCtx = document.getElementById('status-chart').getContext('2d');
    var sourceChartCtx = document.getElementById('source-chart').getContext('2d');

    // Daily chart
    var dailyChart = new Chart(dailyChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Doanh Thu',
                yAxisID: 'y',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                data: []
            }, {
                label: 'Số Đơn Hàng',
                yAxisID: 'y1',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                data: []
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Doanh Thu'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                notation: 'compact',
                                compactDisplay: 'short'
                            }).format(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số Đơn Hàng'
                    },
                    grid: {
                        drawOnChartArea: false
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
                            if (context.dataset.yAxisID === 'y') {
                                return label + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                            } else {
                                return label + context.raw;
                            }
                        }
                    }
                }
            }
        }
    });

    // Weekly chart (similar to daily with different data)
    var weeklyChart = new Chart(weeklyChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Doanh Thu',
                yAxisID: 'y',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                data: []
            }, {
                label: 'Số Đơn Hàng',
                yAxisID: 'y1',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                data: []
            }]
        },
        options: dailyChart.options // Use the same options
    });

    // Monthly chart (similar to daily with different data)
    var monthlyChart = new Chart(monthlyChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Doanh Thu',
                yAxisID: 'y',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                data: []
            }, {
                label: 'Số Đơn Hàng',
                yAxisID: 'y1',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                data: []
            }]
        },
        options: dailyChart.options // Use the same options
    });

    // Status chart
    var statusChart = new Chart(statusChartCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)', // success
                    'rgba(0, 123, 255, 0.7)', // primary
                    'rgba(255, 193, 7, 0.7)', // warning
                    'rgba(220, 53, 69, 0.7)', // danger
                    'rgba(108, 117, 125, 0.7)' // secondary
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Source chart (similar to status chart with different data)
    var sourceChart = new Chart(sourceChartCtx, {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: statusChart.options // Use the same options
    });

    // Load detailed report data
    function loadDetailData(startDate, endDate) {
        $.ajax({
            url: '/api/reports/detail',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateDetailSummary(response.data.summary);
                    updateDailyReport(response.data.daily);
                    updateWeeklyReport(response.data.weekly);
                    updateMonthlyReport(response.data.monthly);
                    updateStatusReport(response.data.by_status);
                    updateSourceReport(response.data.by_source);
                }
            },
            error: function(error) {
                console.error('Error loading detail data:', error);
            }
        });
    }

    // Update detail summary statistics
    function updateDetailSummary(summary) {
        $('#total-detail-orders').text(summary.total_orders.toLocaleString('vi-VN'));
        $('#total-detail-revenue').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(summary.total_revenue));
        $('#avg-order-value').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(summary.avg_order_value));
        $('#orders-per-day').text(summary.orders_per_day.toFixed(2));
    }

    // Update daily report
    function updateDailyReport(data) {
        const labels = data.map(item => formatDate(item.date));
        const revenue = data.map(item => item.revenue);
        const orders = data.map(item => item.orders);

        // Update chart
        dailyChart.data.labels = labels;
        dailyChart.data.datasets[0].data = revenue;
        dailyChart.data.datasets[1].data = orders;
        dailyChart.update();

        // Update table
        const tableBody = $('#daily-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(item) {
            const avgValue = item.orders > 0 ? item.revenue / item.orders : 0;

            tableBody.append(`
                <tr>
                    <td>${formatDate(item.date)}</td>
                    <td>${item.orders.toLocaleString('vi-VN')}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(avgValue)}</td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#daily-table')) {
            $('#daily-table').DataTable().destroy();
        }

        $('#daily-table').DataTable({
            "order": [[0, "desc"]], // Sort by date by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Update weekly report (similar to daily)
    function updateWeeklyReport(data) {
        const labels = data.map(item => `Tuần ${item.week} (${item.year})`);
        const revenue = data.map(item => item.revenue);
        const orders = data.map(item => item.orders);

        // Update chart
        weeklyChart.data.labels = labels;
        weeklyChart.data.datasets[0].data = revenue;
        weeklyChart.data.datasets[1].data = orders;
        weeklyChart.update();

        // Update table
        const tableBody = $('#weekly-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(item) {
            const avgValue = item.orders > 0 ? item.revenue / item.orders : 0;

            tableBody.append(`
                <tr>
                    <td>Tuần ${item.week} (${item.year})</td>
                    <td>${item.orders.toLocaleString('vi-VN')}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(avgValue)}</td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#weekly-table')) {
            $('#weekly-table').DataTable().destroy();
        }

        $('#weekly-table').DataTable({
            "order": [[0, "desc"]], // Sort by week by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Update monthly report (similar to daily and weekly)
    function updateMonthlyReport(data) {
        const labels = data.map(item => `${formatMonth(item.month)} ${item.year}`);
        const revenue = data.map(item => item.revenue);
        const orders = data.map(item => item.orders);

        // Update chart
        monthlyChart.data.labels = labels;
        monthlyChart.data.datasets[0].data = revenue;
        monthlyChart.data.datasets[1].data = orders;
        monthlyChart.update();

        // Update table
        const tableBody = $('#monthly-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(item) {
            const avgValue = item.orders > 0 ? item.revenue / item.orders : 0;

            tableBody.append(`
                <tr>
                    <td>${formatMonth(item.month)} ${item.year}</td>
                    <td>${item.orders.toLocaleString('vi-VN')}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(avgValue)}</td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#monthly-table')) {
            $('#monthly-table').DataTable().destroy();
        }

        $('#monthly-table').DataTable({
            "order": [[0, "desc"]], // Sort by month by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Update status report
    function updateStatusReport(data) {
        const labels = data.map(item => item.status);
        const counts = data.map(item => item.count);

        // Update chart
        statusChart.data.labels = labels;
        statusChart.data.datasets[0].data = counts;
        statusChart.update();

        // Update table
        const tableBody = $('#status-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        const totalOrders = data.reduce((sum, item) => sum + item.count, 0);

        data.forEach(function(item) {
            const percentage = totalOrders > 0 ? ((item.count / totalOrders) * 100).toFixed(2) : 0;

            tableBody.append(`
                <tr>
                    <td>${item.status}</td>
                    <td>${item.count.toLocaleString('vi-VN')}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${percentage}%</td>
                </tr>
            `);
        });
    }

    // Update source report (similar to status report)
    function updateSourceReport(data) {
        const labels = data.map(item => item.source);
        const counts = data.map(item => item.count);

        // Update chart
        sourceChart.data.labels = labels;
        sourceChart.data.datasets[0].data = counts;
        sourceChart.update();

        // Update table
        const tableBody = $('#source-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        const totalOrders = data.reduce((sum, item) => sum + item.count, 0);

        data.forEach(function(item) {
            const percentage = totalOrders > 0 ? ((item.count / totalOrders) * 100).toFixed(2) : 0;

            tableBody.append(`
                <tr>
                    <td>${item.source}</td>
                    <td>${item.count.toLocaleString('vi-VN')}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${percentage}%</td>
                </tr>
            `);
        });
    }

    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    // Helper function to format month name
    function formatMonth(month) {
        const monthNames = [
            'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
            'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
        ];
        return monthNames[month - 1];
    }

    // Initialize with last 30 days
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Initial data load
    loadDetailData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#detail-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadDetailData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD')
        );
    });

    // Show appropriate tab based on URL hash or default to daily
    const hash = window.location.hash;
    if (hash) {
        $(`#detailTabs a[href="${hash}"]`).tab('show');
    }

    // Update URL hash when tab changes
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        history.pushState(null, null, e.target.hash);
    });
});
</script>
