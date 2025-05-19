@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Báo cáo đơn hàng</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-filter"></i>
                                        Bộ lọc
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form id="reportFilterForm" class="form-horizontal">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="start_date">Từ ngày</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="end_date">Đến ngày</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="status">Trạng thái</label>
                                                <select class="form-control" id="status" name="status">
                                                    <option value="all">Tất cả trạng thái</option>
                                                    @foreach($statuses as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="user_id">Nhân viên</label>
                                                <select class="form-control" id="user_id" name="user_id">
                                                    <option value="">Tất cả nhân viên</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="pancake_status">Trạng thái Pancake</label>
                                                <select class="form-control" id="pancake_status" name="pancake_status">
                                                    <option value="">Tất cả</option>
                                                    <option value="pushed">Đã đồng bộ</option>
                                                    <option value="not_pushed">Chưa đồng bộ</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="page_id">Trang Pancake</label>
                                                <select class="form-control" id="page_id" name="page_id">
                                                    <option value="">Tất cả trang</option>
                                                    @foreach($pancakePages as $page)
                                                        <option value="{{ $page->id }}">{{ $page->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> Áp dụng bộ lọc
                                                </button>
                                                <button type="button" id="exportReport" class="btn btn-success ml-2">
                                                    <i class="fas fa-file-excel"></i> Xuất Excel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div id="loadingIndicator" class="text-center my-4 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải dữ liệu báo cáo...</p>
                    </div>

                    <!-- Report Content -->
                    <div id="reportContent" class="d-none">
                        <!-- Summary Cards -->
                        <div class="row">
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3 id="totalOrders">0</h3>
                                        <p>Tổng số đơn hàng</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3 id="totalValue">0</h3>
                                        <p>Tổng giá trị (VNĐ)</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3 id="totalShipping">0</h3>
                                        <p>Phí vận chuyển (VNĐ)</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3 id="pancakeSync">0%</h3>
                                        <p>Đồng bộ Pancake</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-sync"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Biểu đồ đơn hàng theo ngày</h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="dailyOrdersChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Phân bố đơn hàng theo trạng thái</h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="statusPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Tables -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Đơn hàng theo trạng thái</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Trạng thái</th>
                                                    <th>Số lượng</th>
                                                    <th>Phần trăm</th>
                                                    <th>Giá trị</th>
                                                </tr>
                                            </thead>
                                            <tbody id="statusTableBody">
                                                <!-- Status data will be inserted here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Đơn hàng theo nhân viên</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Nhân viên</th>
                                                    <th>Số lượng</th>
                                                    <th>Phần trăm</th>
                                                    <th>Giá trị</th>
                                                </tr>
                                            </thead>
                                            <tbody id="userTableBody">
                                                <!-- User data will be inserted here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Data Message -->
                    <div id="noDataMessage" class="text-center my-4 d-none">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Không có dữ liệu cho các điều kiện đã chọn.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Declare charts as global variables
    let dailyOrdersChart = null;
    let statusPieChart = null;

    // Load report data on page load
    loadReportData();

    // Load data when form is submitted
    $('#reportFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadReportData();
    });

    // Export to Excel
    $('#exportReport').on('click', function() {
        // Implement Excel export functionality
        alert('Chức năng xuất Excel sẽ được triển khai sau.');
    });

    function loadReportData() {
        // Show loading indicator
        $('#loadingIndicator').removeClass('d-none');
        $('#reportContent').addClass('d-none');
        $('#noDataMessage').addClass('d-none');

        // Get form data
        const formData = $('#reportFilterForm').serialize();

        // Make AJAX request
        $.ajax({
            url: '{{ route("reports.orders.data") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Check if we have data
                    if (data.totalOrders === 0) {
                        $('#loadingIndicator').addClass('d-none');
                        $('#noDataMessage').removeClass('d-none');
                        return;
                    }

                    // Update summary cards
                    $('#totalOrders').text(data.totalOrders.toLocaleString());
                    $('#totalValue').text(data.totalValue.toLocaleString());
                    $('#totalShipping').text(data.totalShippingFee.toLocaleString());

                    // Calculate Pancake sync percentage
                    let pushedCount = 0;
                    let totalCount = 0;

                    for (const status in data.totalsByStatus) {
                        totalCount += data.totalsByStatus[status].count;
                    }

                    const syncPercentage = Math.round((pushedCount / (totalCount || 1)) * 100);
                    $('#pancakeSync').text(syncPercentage + '%');

                    // Update status table
                    updateStatusTable(data.totalsByStatus, data.totalOrders);

                    // Update user table
                    updateUserTable(data.salesByUser);

                    // Draw charts
                    updateDailyChart(data.dailyData);
                    updateStatusPieChart(data.totalsByStatus);

                    // Show report content
                    $('#loadingIndicator').addClass('d-none');
                    $('#reportContent').removeClass('d-none');
                } else {
                    alert('Có lỗi khi tải dữ liệu báo cáo: ' + response.message);
                    $('#loadingIndicator').addClass('d-none');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Có lỗi khi tải dữ liệu báo cáo.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                $('#loadingIndicator').addClass('d-none');
            }
        });
    }

    function updateStatusTable(statusData, totalOrders) {
        const tableBody = $('#statusTableBody');
        tableBody.empty();

        for (const status in statusData) {
            const data = statusData[status];
            const percentage = ((data.count / totalOrders) * 100).toFixed(2);

            tableBody.append(`
                <tr>
                    <td><span class="badge badge-pill badge-${getStatusColor(status)}">${data.name}</span></td>
                    <td>${data.count.toLocaleString()}</td>
                    <td>${percentage}%</td>
                    <td>${data.value.toLocaleString()} ₫</td>
                </tr>
            `);
        }
    }

    function updateUserTable(userData) {
        const tableBody = $('#userTableBody');
        tableBody.empty();

        userData.forEach(user => {
            tableBody.append(`
                <tr>
                    <td>${user.name}</td>
                    <td>${user.count.toLocaleString()}</td>
                    <td>${user.percentage}%</td>
                    <td>${user.value.toLocaleString()} ₫</td>
                </tr>
            `);
        });
    }

    function updateDailyChart(dailyData) {
        const ctx = document.getElementById('dailyOrdersChart').getContext('2d');

        // Destroy existing chart if it exists
        if (dailyOrdersChart) {
            dailyOrdersChart.destroy();
        }

        // Prepare data
        const labels = dailyData.map(item => formatDate(item.date));
        const counts = dailyData.map(item => item.count);
        const values = dailyData.map(item => item.value);

        // Create new chart
        dailyOrdersChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Số lượng đơn',
                        data: counts,
                        backgroundColor: 'rgba(60, 141, 188, 0.7)',
                        borderColor: 'rgba(60, 141, 188, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Giá trị (VNĐ)',
                        data: values,
                        type: 'line',
                        backgroundColor: 'transparent',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        borderWidth: 2,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Số lượng đơn'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Giá trị (VNĐ)'
                        },
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function updateStatusPieChart(statusData) {
        const ctx = document.getElementById('statusPieChart').getContext('2d');

        // Destroy existing chart if it exists
        if (statusPieChart) {
            statusPieChart.destroy();
        }

        // Prepare data
        const labels = [];
        const data = [];
        const colors = [];

        for (const status in statusData) {
            labels.push(statusData[status].name);
            data.push(statusData[status].count);
            colors.push(getStatusChartColor(status));
        }

        // Create new chart
        statusPieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'decimal',
            maximumFractionDigits: 0
        }).format(value);
    }

    function getStatusColor(status) {
        const colors = {
            'moi': 'primary',
            'can_xu_ly': 'warning',
            'cho_hang': 'info',
            'da_dat_hang': 'purple',
            'cho_chuyen_hang': 'info',
            'da_gui_hang': 'indigo',
            'da_nhan': 'success',
            'da_nhan_doi': 'success',
            'da_thu_tien': 'success',
            'da_hoan': 'secondary',
            'da_huy': 'danger',
            'xoa_gan_day': 'dark'
        };

        return colors[status] || 'light';
    }

    function getStatusChartColor(status) {
        const colors = {
            'moi': '#007bff',
            'can_xu_ly': '#ffc107',
            'cho_hang': '#17a2b8',
            'da_dat_hang': '#6f42c1',
            'cho_chuyen_hang': '#20c997',
            'da_gui_hang': '#6610f2',
            'da_nhan': '#28a745',
            'da_nhan_doi': '#5cb85c',
            'da_thu_tien': '#198754',
            'da_hoan': '#6c757d',
            'da_huy': '#dc3545',
            'xoa_gan_day': '#343a40'
        };

        return colors[status] || '#f8f9fa';
    }
});
</script>
@endsection
