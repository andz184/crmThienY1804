<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Báo Cáo Khách Hàng</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="customer-date-range">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Tổng Khách Hàng</h5>
                                <h3 id="total-customers">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Khách Hàng Mới</h5>
                                <h3 id="new-customers">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Khách Hàng Cũ</h5>
                                <h3 id="returning-customers">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Phân Bổ Khách Hàng</h5>
                        <canvas id="customer-distribution-chart" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Doanh Thu Theo Loại Khách Hàng</h5>
                        <canvas id="customer-revenue-chart" height="300"></canvas>
                    </div>
                </div>

                <ul class="nav nav-tabs mt-4" id="customerTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="new-customer-tab" data-toggle="tab" href="#new-customer-content" role="tab" aria-controls="new-customer-content" aria-selected="true">Khách Hàng Mới (Đơn Đầu Tiên)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="returning-customer-tab" data-toggle="tab" href="#returning-customer-content" role="tab" aria-controls="returning-customer-content" aria-selected="false">Khách Hàng Cũ (Đơn Thứ 2+)</a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="customerTabsContent">
                    <!-- New Customer Tab -->
                    <div class="tab-pane fade show active" id="new-customer-content" role="tabpanel" aria-labelledby="new-customer-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="new-customer-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Khách Hàng</th>
                                        <th>Điện Thoại</th>
                                        <th>Ngày Đặt</th>
                                        <th>Giá Trị Đơn</th>
                                        <th>Nguồn</th>
                                        <th>Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody id="new-customer-table-body">
                                    <!-- Dữ liệu sẽ được thêm từ AJAX -->
                                    <tr>
                                        <td colspan="6" class="text-center">Đang tải dữ liệu...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Returning Customer Tab -->
                    <div class="tab-pane fade" id="returning-customer-content" role="tabpanel" aria-labelledby="returning-customer-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="returning-customer-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Khách Hàng</th>
                                        <th>Điện Thoại</th>
                                        <th>Ngày Đặt</th>
                                        <th>Giá Trị Đơn</th>
                                        <th>Số Đơn</th>
                                        <th>Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody id="returning-customer-table-body">
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

<!-- Customer Details Modal -->
<div class="modal fade" id="customerDetailModal" tabindex="-1" role="dialog" aria-labelledby="customerDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerDetailModalLabel">Chi Tiết Khách Hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Thông Tin Khách Hàng</h6>
                        <p><strong>Tên:</strong> <span id="customer-name"></span></p>
                        <p><strong>Điện thoại:</strong> <span id="customer-phone"></span></p>
                        <p><strong>Email:</strong> <span id="customer-email"></span></p>
                        <p><strong>Địa chỉ:</strong> <span id="customer-address"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Thống Kê Đặt Hàng</h6>
                        <p><strong>Tổng số đơn:</strong> <span id="customer-total-orders"></span></p>
                        <p><strong>Tổng giá trị:</strong> <span id="customer-total-value"></span></p>
                        <p><strong>Đơn hàng đầu tiên:</strong> <span id="customer-first-order"></span></p>
                        <p><strong>Đơn hàng gần nhất:</strong> <span id="customer-latest-order"></span></p>
                    </div>
                </div>

                <h6>Lịch Sử Đơn Hàng</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="customer-orders-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>Mã Đơn</th>
                                <th>Ngày Đặt</th>
                                <th>Trạng Thái</th>
                                <th>Giá Trị</th>
                                <th>Nguồn</th>
                            </tr>
                        </thead>
                        <tbody id="customer-orders-body">
                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <a href="#" id="view-customer-link" class="btn btn-primary">Xem Chi Tiết</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    var distributionChartCtx = document.getElementById('customer-distribution-chart').getContext('2d');
    var revenueChartCtx = document.getElementById('customer-revenue-chart').getContext('2d');

    var customerDistributionChart = new Chart(distributionChartCtx, {
        type: 'pie',
        data: {
            labels: ['Khách Hàng Mới', 'Khách Hàng Cũ'],
            datasets: [{
                data: [0, 0],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
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

    var customerRevenueChart = new Chart(revenueChartCtx, {
        type: 'bar',
        data: {
            labels: ['Khách Hàng Mới', 'Khách Hàng Cũ'],
            datasets: [{
                label: 'Doanh Thu',
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1,
                data: [0, 0]
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
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND',
                                notation: 'compact',
                                compactDisplay: 'short'
                            }).format(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                        }
                    }
                }
            }
        }
    });

    // Load customer report data
    function loadCustomerData(startDate, endDate) {
        $.ajax({
            url: '/api/reports/customer-orders',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateCustomerSummary(response.data.summary);
                    updateCustomerCharts(response.data.summary);
                    updateNewCustomerTable(response.data.new_customers);
                    updateReturningCustomerTable(response.data.returning_customers);
                }
            },
            error: function(error) {
                console.error('Error loading customer data:', error);
            }
        });
    }

    // Update customer summary statistics
    function updateCustomerSummary(summary) {
        $('#total-customers').text(summary.total.toLocaleString('vi-VN'));
        $('#new-customers').text(summary.new.toLocaleString('vi-VN'));
        $('#returning-customers').text(summary.returning.toLocaleString('vi-VN'));
    }

    // Update customer distribution and revenue charts
    function updateCustomerCharts(summary) {
        // Update distribution chart
        customerDistributionChart.data.datasets[0].data = [summary.new, summary.returning];
        customerDistributionChart.update();

        // Update revenue chart
        customerRevenueChart.data.datasets[0].data = [summary.new_revenue, summary.returning_revenue];
        customerRevenueChart.update();
    }

    // Update new customer table
    function updateNewCustomerTable(data) {
        const tableBody = $('#new-customer-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(customer) {
            tableBody.append(`
                <tr>
                    <td>${customer.name}</td>
                    <td>${customer.phone}</td>
                    <td>${formatDate(customer.order_date)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(customer.order_value)}</td>
                    <td>${customer.source}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-customer-detail" data-id="${customer.id}">
                            <i class="fa fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#new-customer-table')) {
            $('#new-customer-table').DataTable().destroy();
        }

        $('#new-customer-table').DataTable({
            "order": [[2, "desc"]], // Sort by order date by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Update returning customer table
    function updateReturningCustomerTable(data) {
        const tableBody = $('#returning-customer-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="6" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(customer) {
            tableBody.append(`
                <tr>
                    <td>${customer.name}</td>
                    <td>${customer.phone}</td>
                    <td>${formatDate(customer.order_date)}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(customer.order_value)}</td>
                    <td>${customer.order_count}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-customer-detail" data-id="${customer.id}">
                            <i class="fa fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#returning-customer-table')) {
            $('#returning-customer-table').DataTable().destroy();
        }

        $('#returning-customer-table').DataTable({
            "order": [[2, "desc"]], // Sort by order date by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Load customer detail
    function loadCustomerDetail(customerId) {
        $.ajax({
            url: '/api/customers/' + customerId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    showCustomerDetail(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading customer detail:', error);
            }
        });
    }

    // Show customer detail in modal
    function showCustomerDetail(customer) {
        $('#customer-name').text(customer.name);
        $('#customer-phone').text(customer.phone);
        $('#customer-email').text(customer.email || 'N/A');
        $('#customer-address').text(customer.address || 'N/A');

        $('#customer-total-orders').text(customer.order_count);
        $('#customer-total-value').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(customer.total_value));
        $('#customer-first-order').text(formatDate(customer.first_order_date));
        $('#customer-latest-order').text(formatDate(customer.latest_order_date));

        // Set link to customer detail page
        $('#view-customer-link').attr('href', '/customers/' + customer.id);

        // Update orders table
        updateCustomerOrdersTable(customer.orders);

        // Show modal
        $('#customerDetailModal').modal('show');
    }

    // Update customer orders table
    function updateCustomerOrdersTable(orders) {
        const tableBody = $('#customer-orders-body');
        tableBody.empty();

        if (!orders || orders.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="5" class="text-center">Không có đơn hàng</td>
                </tr>
            `);
            return;
        }

        orders.forEach(function(order) {
            tableBody.append(`
                <tr>
                    <td>${order.code}</td>
                    <td>${formatDate(order.created_at)}</td>
                    <td><span class="badge badge-${getStatusBadgeClass(order.status)}">${order.status}</span></td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(order.total_amount)}</td>
                    <td>${order.source}</td>
                </tr>
            `);
        });
    }

    // Helper function to get the appropriate badge class based on order status
    function getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'completed':
            case 'hoàn thành':
                return 'success';
            case 'processing':
            case 'đang xử lý':
                return 'primary';
            case 'pending':
            case 'chờ xử lý':
                return 'warning';
            case 'cancelled':
            case 'hủy':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Format date for display
    function formatDate(dateString) {
        if (!dateString) return 'N/A';

        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Initialize with last 30 days
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Initial data load
    loadCustomerData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#customer-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadCustomerData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD')
        );
    });

    // View customer detail
    $(document).on('click', '.view-customer-detail', function() {
        const customerId = $(this).data('id');
        loadCustomerDetail(customerId);
    });
});
</script>
