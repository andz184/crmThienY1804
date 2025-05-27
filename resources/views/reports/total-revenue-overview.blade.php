@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tổng Quan Doanh Thu</h3>
                    <div class="card-tools">
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control">
                            <input type="date" id="end_date" class="form-control ml-2">
                            <button type="button" id="filter-btn" class="btn btn-primary ml-2">Lọc</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Thống kê tổng quan -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng Doanh Thu</span>
                                    <span class="info-box-number" id="total-revenue">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng Đơn Hàng</span>
                                    <span class="info-box-number" id="total-orders">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Giá Trị Đơn Trung Bình</span>
                                    <span class="info-box-number" id="avg-order-value">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Số Khách Hàng</span>
                                    <span class="info-box-number" id="unique-customers">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ doanh thu theo tháng -->
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Doanh Thu Theo Tháng</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenue-chart" style="height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top Sản Phẩm Bán Chạy</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table" id="top-products">
                                            <thead>
                                                <tr>
                                                    <th>Sản Phẩm</th>
                                                    <th>Số Lượng</th>
                                                    <th>Doanh Thu</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let revenueChart = null;

function loadRevenueData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    fetch(`/reports/total-revenue-overview-data?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateDashboard(data.data);
            } else {
                console.error('Error loading data:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateDashboard(data) {
    // Update stats
    document.getElementById('total-revenue').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(data.overall_stats.total_revenue);
    document.getElementById('total-orders').textContent = data.overall_stats.total_orders;
    document.getElementById('avg-order-value').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(data.overall_stats.average_order_value);
    document.getElementById('unique-customers').textContent = data.overall_stats.unique_customers;

    // Update chart
    const chartLabels = data.monthly_revenue.map(item => item.month);
    const chartData = data.monthly_revenue.map(item => item.revenue);

    if (revenueChart) {
        revenueChart.destroy();
    }

    revenueChart = new Chart(document.getElementById('revenue-chart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Doanh Thu',
                data: chartData,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Update top products table
    const tbody = document.querySelector('#top-products tbody');
    tbody.innerHTML = '';
    data.top_products.forEach(product => {
        const row = tbody.insertRow();
        row.insertCell(0).textContent = product.product_name;
        row.insertCell(1).textContent = product.total_quantity;
        row.insertCell(2).textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.total_revenue);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    document.getElementById('start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];

    loadRevenueData();

    document.getElementById('filter-btn').addEventListener('click', loadRevenueData);
});
</script>
@endpush
