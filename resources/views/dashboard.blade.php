@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard - Thống kê</h1>
@stop

@section('content')

{{-- Filter Section --}}
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">Bộ lọc Thống kê</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
         <form id="stats-filter-form">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Khoảng thời gian:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" class="form-control form-control-sm float-right" id="date-range-picker">
                            <input type="hidden" name="start_date" id="start_date">
                            <input type="hidden" name="end_date" id="end_date">
                        </div>
                    </div>
                </div>
                {{-- Filter Sale --}}
                @if($filterableStaff->isNotEmpty())
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="stats_sale_id">Lọc theo Sale:</label>
                            <select name="sale_id" id="stats_sale_id" class="form-control form-control-sm select2" data-placeholder="-- Chọn Sale --">
                                <option value="">-- Tất cả Sale ({{ $filterableStaff->count() }}) --</option>
                                @foreach($filterableStaff as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                {{-- Filter Manager --}}
                @if($filterableManagers->isNotEmpty())
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="stats_manager_id">Lọc theo Quản lý:</label>
                            <select name="manager_id" id="stats_manager_id" class="form-control form-control-sm select2" data-placeholder="-- Chọn Quản lý --">
                                <option value="">-- Tất cả Quản lý ({{ $filterableManagers->count() }}) --</option>
                                @foreach($filterableManagers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="button" id="apply-filter-btn" class="btn btn-sm btn-primary">Lọc</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary ml-1">Xóa lọc</a>
                </div>
            </div>
            <div id="stats-loading" class="mt-2" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu biểu đồ...</div>
         </form>
    </div>
</div>

{{-- Stats Boxes (Updated) --}}
 <div class="row">
        {{-- Box 1: Today's Revenue --}}
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="small-box bg-gradient-success">
                <div class="inner">
                    <h3>{{ $stats['today_revenue_formatted'] ?? '0' }}</h3>
                    <p>Doanh thu Hôm nay (Completed)</p>
                </div>
                <div class="icon"><i class="ion ion-cash"></i></div>
                {{-- <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> --}}
            </div>
        </div>

        {{-- Box 2: Monthly Revenue --}}
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="small-box bg-gradient-warning">
                <div class="inner">
                    <h3>{{ $stats['monthly_revenue_formatted'] ?? '0' }}</h3>
                    <p>Doanh thu Tháng này (Completed)</p>
                </div>
                <div class="icon"><i class="ion ion-calendar"></i></div>
                {{-- <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> --}}
            </div>
        </div>

        {{-- Box 3: Today's Completed Orders --}}
        <div class="col-lg-4 col-md-6 col-sm-12">
             <div class="small-box bg-gradient-info">
                <div class="inner">
                     <h3>{{ $stats['today_completed_orders'] ?? 0 }}</h3>
                     <p>Số đơn Hoàn thành Hôm nay</p>
                </div>
                <div class="icon"><i class="ion ion-checkmark-circled"></i></div>
                 {{-- <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> --}}
            </div>
        </div>
    </div>

    {{-- New Total Filtered Revenue Box --}}
    <div class="col-lg-4 col-md-6 col-sm-12">
        <div class="small-box bg-gradient-purple">
            <div class="inner">
                <h3 id="total-filtered-revenue-value">0</h3>
                <p>Tổng DT theo bộ lọc (Completed)</p>
            </div>
            <div class="icon"><i class="ion ion-stats-bars"></i></div>
        </div>
    </div>
</div>

{{-- Staff Specific Stats Boxes - Revised for custom date range by Super Admin --}}
@if(Auth::user()->hasRole(['admin', 'super-admin']) || (Auth::user()->hasRole('manager') && Auth::user()->manages_team_id))
<div class="row" id="staff-specific-stats-row" style="display: none;">
    <div class="col-12 mb-2">
        <h4 id="staff-specific-stats-title" class="text-info">Thống kê chi tiết</h4> {{-- Title will be updated by JS --}}
    </div>
    {{-- Box 1: Total Revenue in Selected Range --}}
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="small-box bg-gradient-success"> {{-- Changed color --}}
            <div class="inner">
                <h3 id="staff-custom-range-revenue-value">0</h3>
                <p>Tổng Doanh thu (Trong khoảng đã chọn)</p>
            </div>
            <div class="icon"><i class="ion ion-cash"></i></div>
        </div>
    </div>

    {{-- Box 2: Total Orders in Selected Range --}}
    <div class="col-lg-6 col-md-6 col-sm-12">
         <div class="small-box bg-gradient-purple"> {{-- Changed color --}}
            <div class="inner">
                 <h3 id="staff-custom-range-orders-value">0</h3>
                 <p>Tổng Số đơn Hoàn thành (Trong khoảng đã chọn)</p>
            </div>
            <div class="icon"><i class="ion ion-ios-cart-outline"></i></div> {{-- Changed icon --}}
        </div>
    </div>
</div>
@endif

{{-- New Charts Section --}}
<div class="row">
    {{-- Revenue Daily Chart --}}
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="far fa-chart-bar"></i> Doanh thu theo Ngày (Completed)</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
            </div>
            <div class="card-body">
                <div class="chart"><canvas id="revenueDailyChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas></div>
            </div>
        </div>
    </div>
     {{-- Order Status Pie Chart --}}
     <div class="col-lg-4">
        <div class="card card-danger card-outline">
            <div class="card-header">
                 <h3 class="card-title"><i class="fas fa-chart-pie"></i> Tỷ lệ Đơn hàng theo Trạng thái</h3>
                 <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
            </div>
             <div class="card-body">
                <canvas id="orderStatusPieChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="row">
     {{-- Revenue Monthly Chart --}}
     <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="far fa-calendar-alt"></i> Doanh thu theo Tháng (Completed)</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
            </div>
            <div class="card-body">
                <div class="chart"><canvas id="revenueMonthlyChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas></div>
            </div>
        </div>
    </div>
</div>

{{-- Staff Revenue Chart - Added by AI --}}
<div class="row">
    <div class="col-md-12" id="staffRevenueChartContainer" style="display:none;">
        <div class="card card-success card-outline"> {{-- Changed color for distinction --}}
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Doanh thu theo Nhân viên (Trong khoảng lọc)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="staffRevenueChart" style="min-height: 250px; height: 250px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bỏ bảng doanh thu theo Sale cũ --}}
{{-- @canany(['view orders', 'view team orders'])
<div class="card">
    ...
</div>
@endcanany --}}

@stop

@section('plugins.Chartjs', true)
@section('plugins.DateRangePicker', true)
@section('plugins.Select2', true) {{-- Add Select2 for better dropdowns --}}

@section('css')
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    {{-- Add any custom CSS here --}}
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px); /* Match form-control-sm height */
        }
    </style>
@stop

@section('js')
<script>
$(function () {
    // --- Store initial staff list for Admin/Super-Admin ---
    let initialStaffOptions = [];
    @if(Auth::user()->hasRole(['admin', 'super-admin']))
        $('#stats_sale_id option').each(function() {
            initialStaffOptions.push({ value: $(this).val(), text: $(this).text() });
        });
    @endif

    // --- Initialize Select2 --- (Optional, but recommended)
    $('.select2').select2({
        allowClear: true // Add a clear button
    });

    // --- Date Range Picker Setup --- (Keep existing logic)
    var start = moment().subtract(29, 'days');
    var end = moment();

    function cb(start, end) {
        // $('#date-range-picker span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        // Update the input field display
        $('#date-range-picker').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
    }

    $('#date-range-picker').daterangepicker({
        startDate: start,
        endDate: end,
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
            separator: ' - ',
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

    cb(start, end); // Set initial display

    // --- Chart Instances --- (Initialize empty charts)
    let revenueDailyChart = null;
    let revenueMonthlyChart = null;
    let orderStatusPieChart = null;
    let staffRevenueChartInstance = null; // Added for the new chart

    const chartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: { display: false }, // Hide legend by default, enable if needed
        scales: {
            yAxes: [{ ticks: { beginAtZero: true } }],
             xAxes: [{ ticks: { autoSkip: true, maxTicksLimit: 15 } }] // Adjust tick skipping
        }
    };

    function initCharts() {
        // Revenue Daily Chart (Line)
        if (revenueDailyChart) revenueDailyChart.destroy();
        var revenueDailyCtx = $('#revenueDailyChart').get(0).getContext('2d');
        revenueDailyChart = new Chart(revenueDailyCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Doanh thu Ngày', data: [], backgroundColor: 'rgba(54, 162, 235, 0.5)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }] },
            options: chartOptions
        });

        // Revenue Monthly Chart (Bar)
        if (revenueMonthlyChart) revenueMonthlyChart.destroy();
        var revenueMonthlyCtx = $('#revenueMonthlyChart').get(0).getContext('2d');
        revenueMonthlyChart = new Chart(revenueMonthlyCtx, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Doanh thu Tháng', data: [], backgroundColor: 'rgba(75, 192, 192, 0.5)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 }] },
            options: chartOptions
        });

        // Order Status Pie Chart (Doughnut)
        if (orderStatusPieChart) orderStatusPieChart.destroy();
        var orderStatusPieCtx = $('#orderStatusPieChart').get(0).getContext('2d');
        orderStatusPieChart = new Chart(orderStatusPieCtx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
            options: { maintainAspectRatio: false, responsive: true, legend: { position: 'right' } } // Show legend for pie
        });

        // Staff Revenue Chart (Bar) - Added by AI
        if (staffRevenueChartInstance) staffRevenueChartInstance.destroy();
        var staffRevenueCtx = $('#staffRevenueChart').get(0).getContext('2d');
        staffRevenueChartInstance = new Chart(staffRevenueCtx, {
            type: 'bar', // Or 'horizontalBar'
            data: {
                labels: [],
                datasets: [{
                    label: 'Doanh thu Nhân viên',
                    data: [],
                    backgroundColor: 'rgba(153, 102, 255, 0.6)', // Purple color example
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: { display: false },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
                            }
                        }
                    }],
                    xAxes: [{ ticks: { autoSkip: true, maxTicksLimit: 20 } }] // Adjust if many staff
                },
                tooltips: { // For Chart.js v2, use tooltips for custom formatting
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(tooltipItem.yLabel);
                            return label;
                        }
                    }
                }
            }
        });
    }

    // --- AJAX Function to Update Charts --- (Updated)
    function updateCharts(startDate, endDate, saleId, managerId) {
        $('#stats-loading').show();
        $.ajax({
            url: '{{ route("dashboard.chartData") }}',
            method: 'GET', // Use GET method
            data: {
                start_date: startDate,
                end_date: endDate,
                sale_id: saleId,
                manager_id: managerId,
                _token: '{{ csrf_token() }}' // Include CSRF token if using POST/PUT/DELETE
            },
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                     console.error("Server Error:", data.error);
                     alert(data.error);
                     $('#stats-loading').hide();
                     return;
                }
                // Update Revenue Daily Chart
                if (revenueDailyChart && data.revenueDaily) {
                    revenueDailyChart.data.labels = data.revenueDaily.labels;
                    revenueDailyChart.data.datasets[0].data = data.revenueDaily.data;
                    revenueDailyChart.update();
                }

                // Update Revenue Monthly Chart
                if (revenueMonthlyChart && data.revenueMonthly) {
                    revenueMonthlyChart.data.labels = data.revenueMonthly.labels;
                    revenueMonthlyChart.data.datasets[0].data = data.revenueMonthly.data;
                    revenueMonthlyChart.update();
                }

                // Update Order Status Pie Chart
                 if (orderStatusPieChart && data.orderStatusPie) {
                    orderStatusPieChart.data.labels = data.orderStatusPie.labels;
                    orderStatusPieChart.data.datasets[0].data = data.orderStatusPie.data;
                    orderStatusPieChart.data.datasets[0].backgroundColor = data.orderStatusPie.colors;
                    orderStatusPieChart.update();
                }

                // Update Staff Revenue Chart - Added by AI
                const staffRevenueData = data.staffRevenueDetails;
                const staffRevenueChartContainer = $('#staffRevenueChartContainer'); // Use jQuery selector

                if (staffRevenueData && staffRevenueData.should_display) {
                    staffRevenueChartContainer.show();
                    if (staffRevenueChartInstance) {
                        staffRevenueChartInstance.data.labels = staffRevenueData.labels;
                        staffRevenueChartInstance.data.datasets[0].data = staffRevenueData.data;
                        staffRevenueChartInstance.update();
                    }
                } else {
                    staffRevenueChartContainer.hide();
                    // Optionally clear data if hidden, though destroy/re-init covers it
                    if (staffRevenueChartInstance) {
                        staffRevenueChartInstance.data.labels = [];
                        staffRevenueChartInstance.data.datasets[0].data = [];
                        staffRevenueChartInstance.update();
                    }
                }

                // Update Staff Specific Stats Boxes - Added by AI
                const staffStatsData = data.staff_specific_stats;
                const staffStatsRow = $('#staff-specific-stats-row');
                const staffStatsTitle = $('#staff-specific-stats-title');

                if (staffStatsData && staffStatsData.show) {
                    staffStatsTitle.html('Thống kê: <strong class="text-primary">' + staffStatsData.entity_name + '</strong> (Giai đoạn: ' + staffStatsData.period_label + ')');
                    $('#staff-custom-range-revenue-value').text(staffStatsData.total_revenue_formatted);
                    $('#staff-custom-range-orders-value').text(staffStatsData.total_completed_orders);
                    staffStatsRow.show();
                } else {
                    staffStatsRow.hide();
                }

                // Update Total Filtered Revenue Box
                if (data.totalFilteredRevenueFormatted) {
                    $('#total-filtered-revenue-value').text(data.totalFilteredRevenueFormatted);
                } else {
                    $('#total-filtered-revenue-value').text('0'); // Default if not present
                }

                $('#stats-loading').hide();
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                $('#stats-loading').hide();
                alert('Lỗi khi tải dữ liệu biểu đồ. Vui lòng kiểm tra console.');
            }
        });
    }

    // --- Filter Logic --- (Slight adjustments)
    function applyFilters() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var saleId = $('#stats_sale_id').val() || null; // Use null if empty
        var managerId = $('#stats_manager_id').val() || null; // Use null if empty
        updateCharts(startDate, endDate, saleId, managerId);
    }

    // Initial chart load
    initCharts();
    applyFilters();

    // --- Event Listeners --- (Update trigger)
    $('#apply-filter-btn').on('click', applyFilters);

    // --- Dependent dropdown for Manager -> Sale ---
    $('#stats_manager_id').on('change', function() {
        const managerId = $(this).val();
        const $saleSelect = $('#stats_sale_id');

        // Clear current sale filter if a manager is chosen, to avoid conflicting filters initially
        // $saleSelect.val(null).trigger('change.select2');
        // Let's not auto-clear, user might want to see if their specific sale choice is under the new manager

        if (managerId) {
            // Fetch staff for the selected manager
            $.ajax({
                url: '{{ url("/ajax/staff-by-manager") }}/' + managerId, // Use url() helper for base path
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $saleSelect.prop('disabled', true).empty().append(new Option('-- Đang tải Sale... --', ''));
                    if ($.fn.select2) $saleSelect.trigger('change.select2');
                },
                success: function(data) {
                    $saleSelect.prop('disabled', false).empty();
                    $saleSelect.append(new Option('-- Tất cả Sale thuộc Quản lý này --', '')); // Default option

                    if (data.staff && data.staff.length > 0) {
                        $.each(data.staff, function(index, staffMember) {
                            $saleSelect.append(new Option(staffMember.name, staffMember.id));
                        });
                    } else if(data.error) {
                         $saleSelect.append(new Option(data.error, '')); // Show error if any
                    } else {
                        $saleSelect.append(new Option('-- Không có Sale nào --', ''));
                    }
                    if ($.fn.select2) $saleSelect.trigger('change.select2');
                },
                error: function(xhr) {
                    console.error("AJAX Error fetching staff for manager:", xhr.responseText);
                    $saleSelect.prop('disabled', false).empty();
                    $saleSelect.append(new Option('-- Lỗi tải Sale --', ''));
                    // Optionally, revert to initial list on error if it makes sense
                    if ($.fn.select2) $saleSelect.trigger('change.select2');
                }
            });
        } else {
            // Manager filter cleared, revert to initial staff list (primarily for Admin/Super-Admin)
            $saleSelect.prop('disabled', false).empty();
            if (initialStaffOptions.length > 0) {
                $.each(initialStaffOptions, function(index, option) {
                    $saleSelect.append(new Option(option.text, option.value));
                });
            } else {
                // Fallback or for non-admins if initialStaffOptions wasn't populated for them specifically
                // This case should ideally be handled by the initial page load for $filterableStaff for a manager
                $saleSelect.append(new Option('-- Chọn Sale --', ''));
            }
            if ($.fn.select2) $saleSelect.trigger('change.select2');
        }
    });

    // Update on date range apply
    $('#date-range-picker').on('apply.daterangepicker', function(ev, picker) {
        cb(picker.startDate, picker.endDate);
        // No need to call applyFilters here if using the button
    });

    // Khởi tạo biểu đồ doanh thu theo ngày
    var revenueCtx = document.getElementById('revenueDailyChart').getContext('2d');
    var revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: [], // Sẽ được cập nhật bằng dữ liệu thực
            datasets: [{
                label: 'Doanh thu',
                data: [], // Sẽ được cập nhật bằng dữ liệu thực
                borderColor: '#28a745',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(value);
                        }
                    }
                }
            }
        }
    });

    // Khởi tạo biểu đồ trạng thái đơn hàng
    var statusCtx = document.getElementById('orderStatusPieChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Hoàn thành', 'Đang xử lý', 'Hủy'],
            datasets: [{
                data: [0, 0, 0], // Sẽ được cập nhật bằng dữ liệu thực
                backgroundColor: ['#28a745', '#17a2b8', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Khởi tạo biểu đồ doanh thu theo tháng
    var monthlyCtx = document.getElementById('revenueMonthlyChart').getContext('2d');
    var monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: [], // Sẽ được cập nhật bằng dữ liệu thực
            datasets: [{
                label: 'Doanh thu theo tháng',
                data: [], // Sẽ được cập nhật bằng dữ liệu thực
                backgroundColor: '#17a2b8'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(value);
                        }
                    }
                }
            }
        }
    });

    // Hàm cập nhật biểu đồ
    function updateCharts(data) {
        // Cập nhật biểu đồ doanh thu theo ngày
        revenueChart.data.labels = data.daily_revenue.labels;
        revenueChart.data.datasets[0].data = data.daily_revenue.data;
        revenueChart.update();

        // Cập nhật biểu đồ trạng thái đơn hàng
        statusChart.data.datasets[0].data = [
            data.order_status.completed,
            data.order_status.processing,
            data.order_status.cancelled
        ];
        statusChart.update();

        // Cập nhật biểu đồ doanh thu theo tháng
        monthlyChart.data.labels = data.monthly_revenue.labels;
        monthlyChart.data.datasets[0].data = data.monthly_revenue.data;
        monthlyChart.update();
    }

    // Xử lý sự kiện khi nhấn nút lọc
    $('#apply-filter-btn').click(function() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var saleId = $('#stats_sale_id').val();
        var managerId = $('#stats_manager_id').val();

        $('#stats-loading').show();

        // Gọi API để lấy dữ liệu
        $.ajax({
            url: '/api/dashboard/stats',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                sale_id: saleId,
                manager_id: managerId
            },
            success: function(response) {
                updateCharts(response);
                $('#stats-loading').hide();
            },
            error: function() {
                alert('Có lỗi xảy ra khi tải dữ liệu');
                $('#stats-loading').hide();
            }
        });
    });

    // Tự động load dữ liệu khi trang được tải
    $('#apply-filter-btn').trigger('click');
});
</script>
@stop
