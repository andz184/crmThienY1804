@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Live Session Reports</h3>
                </div>
                <div class="card-body">
                    <form id="reportForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Session ID (Optional)</label>
                                    <input type="text" class="form-control" name="session_id">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div id="reportContent" style="display: none;">
                        <!-- Summary Cards -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3 id="totalRevenue">0</h3>
                                        <p>Total Revenue</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3 id="totalOrders">0</h3>
                                        <p>Total Orders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3 id="conversionRate">0%</h3>
                                        <p>Conversion Rate</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3 id="newCustomers">0</h3>
                                        <p>New Customers</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Daily Revenue</h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Top Products</h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="productsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Stats Table -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Daily Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="statsTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Sessions</th>
                                                <th>Total Orders</th>
                                                <th>Successful Orders</th>
                                                <th>Revenue</th>
                                                <th>Top Products</th>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let revenueChart = null;
let productsChart = null;

$(document).ready(function() {
    $('#reportForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
});

function generateReport() {
    const formData = new FormData($('#reportForm')[0]);

    $.ajax({
        url: '{{ route("reports.live-sessions.data") }}',
        type: 'GET',
        data: {
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            session_id: formData.get('session_id')
        },
        success: function(response) {
            updateReport(response);
            $('#reportContent').show();
        },
        error: function(xhr) {
            alert('Error generating report: ' + xhr.responseText);
        }
    });
}

function updateReport(data) {
    // Update summary cards
    $('#totalRevenue').text(formatCurrency(data.summary.total_revenue));
    $('#totalOrders').text(data.summary.total_orders);
    $('#conversionRate').text(data.summary.avg_conversion_rate.toFixed(2) + '%');
    $('#newCustomers').text(data.summary.new_customers);

    // Update charts
    updateRevenueChart(data.daily_stats);
    updateProductsChart(data.top_products_overall);

    // Update table
    updateStatsTable(data.daily_stats);
}

function updateRevenueChart(dailyStats) {
    const dates = Object.keys(dailyStats);
    const revenues = dates.map(date => dailyStats[date].total_revenue);

    if (revenueChart) {
        revenueChart.destroy();
    }

    revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Revenue',
                data: revenues,
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
}

function updateProductsChart(products) {
    const labels = products.map(p => p.product_name);
    const revenues = products.map(p => p.total_revenue);

    if (productsChart) {
        productsChart.destroy();
    }

    productsChart = new Chart(document.getElementById('productsChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue by Product',
                data: revenues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
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
}

function updateStatsTable(dailyStats) {
    const tbody = $('#statsTable tbody');
    tbody.empty();

    Object.entries(dailyStats).forEach(([date, stats]) => {
        const row = $('<tr>');
        row.append(`<td>${date}</td>`);
        row.append(`<td>${stats.sessions.length}</td>`);
        row.append(`<td>${stats.total_orders}</td>`);
        row.append(`<td>${stats.successful_orders}</td>`);
        row.append(`<td>${formatCurrency(stats.total_revenue)}</td>`);

        const topProducts = stats.sessions
            .flatMap(s => s.top_products)
            .slice(0, 3)
            .map(p => `${p.product_name} (${formatCurrency(p.total_revenue)})`)
            .join('<br>');

        row.append(`<td>${topProducts}</td>`);
        tbody.append(row);
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}
</script>
@endpush
