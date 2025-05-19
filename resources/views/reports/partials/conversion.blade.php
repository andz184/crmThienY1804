<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Tỉ Lệ Chốt Đơn</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="conversion-date-range">
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
                                <h5 class="card-title">Tổng Tiếp Cận</h5>
                                <h3 id="total-reaches">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Tổng Đơn Hàng</h5>
                                <h3 id="total-conversion-orders">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Tỉ Lệ Chốt Đơn</h5>
                                <h3 id="overall-conversion-rate">0%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Doanh Thu/Tiếp Cận</h5>
                                <h3 id="revenue-per-reach">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Tỉ Lệ Chốt Đơn Theo Kênh</h5>
                        <canvas id="conversion-by-channel-chart" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Tỉ Lệ Chốt Đơn Theo Thời Gian</h5>
                        <canvas id="conversion-by-time-chart" height="300"></canvas>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Chi Tiết Tỉ Lệ Chốt Đơn Theo Kênh</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="conversion-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Kênh</th>
                                        <th>Tiếp Cận</th>
                                        <th>Đơn Hàng</th>
                                        <th>Tỉ Lệ Chốt</th>
                                        <th>Doanh Thu</th>
                                        <th>DT/Tiếp Cận</th>
                                    </tr>
                                </thead>
                                <tbody id="conversion-table-body">
                                    <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                    <tr>
                                        <td colspan="6" class="text-center">Đang tải dữ liệu...</td>
                                    </tr>
                                </tbody>
                            </table>
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
    var channelChartCtx = document.getElementById('conversion-by-channel-chart').getContext('2d');
    var timeChartCtx = document.getElementById('conversion-by-time-chart').getContext('2d');

    var conversionByChannelChart = new Chart(channelChartCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Tỉ Lệ Chốt Đơn (%)',
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                data: []
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });

    var conversionByTimeChart = new Chart(timeChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Tỉ Lệ Chốt Đơn (%)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                data: []
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toFixed(2) + '%';
                        }
                    }
                }
            }
        }
    });

    // Load conversion rate data
    function loadConversionData(startDate, endDate) {
        $.ajax({
            url: '/api/reports/conversion',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateConversionSummary(response.data.summary);
                    updateConversionChannelChart(response.data.by_channel);
                    updateConversionTimeChart(response.data.by_time);
                    updateConversionTable(response.data.by_channel);
                }
            },
            error: function(error) {
                console.error('Error loading conversion data:', error);
            }
        });
    }

    // Update conversion summary statistics
    function updateConversionSummary(summary) {
        $('#total-reaches').text(summary.total_reaches.toLocaleString('vi-VN'));
        $('#total-conversion-orders').text(summary.total_orders.toLocaleString('vi-VN'));
        $('#overall-conversion-rate').text(summary.conversion_rate.toFixed(2) + '%');
        $('#revenue-per-reach').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(summary.revenue_per_reach));
    }

    // Update conversion by channel chart
    function updateConversionChannelChart(data) {
        const labels = data.map(item => item.channel);
        const rates = data.map(item => item.conversion_rate);

        conversionByChannelChart.data.labels = labels;
        conversionByChannelChart.data.datasets[0].data = rates;
        conversionByChannelChart.update();
    }

    // Update conversion by time chart
    function updateConversionTimeChart(data) {
        const labels = data.map(item => item.date);
        const rates = data.map(item => item.conversion_rate);

        conversionByTimeChart.data.labels = labels;
        conversionByTimeChart.data.datasets[0].data = rates;
        conversionByTimeChart.update();
    }

    // Update conversion rate table
    function updateConversionTable(data) {
        const tableBody = $('#conversion-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(item) {
            tableBody.append(`
                <tr>
                    <td>${item.channel}</td>
                    <td>${item.reaches.toLocaleString('vi-VN')}</td>
                    <td>${item.orders.toLocaleString('vi-VN')}</td>
                    <td>${item.conversion_rate.toFixed(2)}%</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue_per_reach)}</td>
                </tr>
            `);
        });

        // Initialize DataTable if not already done
        if ($.fn.DataTable.isDataTable('#conversion-table')) {
            $('#conversion-table').DataTable().destroy();
        }

        $('#conversion-table').DataTable({
            "order": [[3, "desc"]], // Sort by conversion rate by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Initialize with last 30 days
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Initial data load
    loadConversionData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#conversion-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadConversionData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD')
        );
    });
});
</script>
