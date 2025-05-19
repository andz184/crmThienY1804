<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Báo Cáo Theo Nhóm Hàng Hóa</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="product-group-date-range">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <select class="form-control" id="product-group-select">
                            <option value="">Tất cả nhóm hàng</option>
                            <!-- Sẽ được thêm từ AJAX -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" id="product-group-filter">Lọc</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <canvas id="product-group-chart" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Nhóm Hàng</th>
                                        <th>Số Lượng Bán</th>
                                        <th>Doanh Thu</th>
                                        <th>Tỷ Lệ (%)</th>
                                    </tr>
                                </thead>
                                <tbody id="product-group-table-body">
                                    <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                    <tr>
                                        <td colspan="4" class="text-center">Đang tải dữ liệu...</td>
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
    var ctx = document.getElementById('product-group-chart').getContext('2d');
    var productGroupChart = new Chart(ctx, {
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
                            return `${label}: ${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Load product groups for the dropdown
    function loadProductGroups() {
        $.ajax({
            url: '/api/products/groups',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#product-group-select');
                    select.find('option:not(:first)').remove();

                    response.data.forEach(function(group) {
                        select.append(`<option value="${group.id}">${group.name}</option>`);
                    });
                }
            },
            error: function(error) {
                console.error('Error loading product groups:', error);
            }
        });
    }

    // Load product group report data
    function loadProductGroupData(startDate, endDate, groupId = '') {
        $.ajax({
            url: '/api/reports/product-group',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                group_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    updateProductGroupChart(response.data);
                    updateProductGroupTable(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading product group data:', error);
            }
        });
    }

    // Update chart with product group data
    function updateProductGroupChart(data) {
        const labels = data.map(item => item.group_name);
        const values = data.map(item => item.revenue);

        productGroupChart.data.labels = labels;
        productGroupChart.data.datasets[0].data = values;
        productGroupChart.update();
    }

    // Update table with product group data
    function updateProductGroupTable(data) {
        const totalRevenue = data.reduce((sum, item) => sum + item.revenue, 0);
        const tableBody = $('#product-group-table-body');

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
            const percentage = totalRevenue > 0 ? ((item.revenue / totalRevenue) * 100).toFixed(2) : 0;

            tableBody.append(`
                <tr>
                    <td>${item.group_name}</td>
                    <td>${item.quantity}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.revenue)}</td>
                    <td>${percentage}%</td>
                </tr>
            `);
        });
    }

    // Initialize
    loadProductGroups();

    // Initialize with last 30 days
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Initial data load
    loadProductGroupData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#product-group-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadProductGroupData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD'),
            $('#product-group-select').val()
        );
    });

    // Filter button click
    $('#product-group-filter').on('click', function() {
        const dateRange = $('#product-group-date-range').data('daterangepicker');
        loadProductGroupData(
            dateRange.startDate.format('YYYY-MM-DD'),
            dateRange.endDate.format('YYYY-MM-DD'),
            $('#product-group-select').val()
        );
    });
});
</script>
