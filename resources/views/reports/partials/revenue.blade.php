<div class="row">
    <div class="col-md-12 mb-4">
        <div class="date-range-container">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="far fa-calendar-alt"></i>
                    </span>
                </div>
                <input type="text" class="form-control" id="date-range-picker" placeholder="Chọn khoảng thời gian">
                <div class="input-group-append">
                    <button class="btn btn-primary" id="apply-date-filter">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="total-orders">0</h3>
                <p>Tổng đơn hàng</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-revenue">0 ₫</h3>
                <p>Tổng doanh thu</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="avg-order">0 ₫</h3>
                <p>Giá trị đơn trung bình</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Biểu đồ doanh thu theo ngày</h3>
            </div>
            <div class="card-body">
                <canvas id="revenue-chart" style="min-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo date range picker
    $('#date-range-picker').daterangepicker({
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
            customRangeLabel: 'Tùy chọn',
        }
    });

    // Biến lưu trữ chart
    let revenueChart;

    // Hàm tải dữ liệu doanh thu
    function loadRevenueData() {
        const dateRange = $('#date-range-picker').val();
        const dates = dateRange.split(' - ');
        const startDate = moment(dates[0], 'DD/MM/YYYY').format('YYYY-MM-DD');
        const endDate = moment(dates[1], 'DD/MM/YYYY').format('YYYY-MM-DD');

        // Hiển thị loading
        $('#total-orders, #total-revenue, #avg-order').html('<i class="fas fa-spinner fa-spin"></i>');

        // Gửi AJAX request
        $.ajax({
            url: '{{ route("api.reports.daily-revenue") }}',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateRevenueStats(response.data);
                    updateRevenueChart(response.data);
                }
            },
            error: function(error) {
                console.error('Error fetching revenue data', error);
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Không thể tải dữ liệu doanh thu.',
                    icon: 'error'
                });
            }
        });
    }

    // Cập nhật thống kê doanh thu
    function updateRevenueStats(data) {
        let totalOrders = 0;
        let totalRevenue = 0;

        // Tính tổng đơn hàng và doanh thu
        data.forEach(item => {
            totalOrders += item.orders_count;
            totalRevenue += parseFloat(item.revenue);
        });

        // Tính giá trị đơn trung bình
        const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;

        // Cập nhật UI
        $('#total-orders').text(totalOrders.toLocaleString('vi-VN'));
        $('#total-revenue').text(totalRevenue.toLocaleString('vi-VN') + ' ₫');
        $('#avg-order').text(avgOrderValue.toLocaleString('vi-VN', { maximumFractionDigits: 0 }) + ' ₫');
    }

    // Cập nhật biểu đồ doanh thu
    function updateRevenueChart(data) {
        // Sắp xếp dữ liệu theo ngày
        data.sort((a, b) => new Date(a.date) - new Date(b.date));

        // Chuẩn bị dữ liệu cho biểu đồ
        const labels = data.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('vi-VN');
        });

        const revenueData = data.map(item => parseFloat(item.revenue));
        const ordersData = data.map(item => parseInt(item.orders_count));

        // Hủy biểu đồ cũ nếu có
        if (revenueChart) {
            revenueChart.destroy();
        }

        // Tạo biểu đồ mới
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Doanh thu (₫)',
                        data: revenueData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Số đơn hàng',
                        data: ordersData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderDash: [5, 5],
                        fill: true,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Doanh thu (₫)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Số đơn hàng'
                        }
                    }
                }
            }
        });
    }

    // Xử lý sự kiện lọc
    $('#apply-date-filter').click(function() {
        loadRevenueData();
    });

    // Tải dữ liệu ban đầu
    loadRevenueData();
});
</script>
