<!-- Báo cáo phiên live - Sử dụng dữ liệu từ notes của đơn hàng -->
<style>
    .stats-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    .stats-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    .chart-container {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 15px;
        background-color: #fff;
        margin-bottom: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .chart-container:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .date-range-container {
        position: relative;
    }
    .date-picker {
        cursor: pointer;
        background-color: #fff !important;
    }
    .refresh-btn {
        transition: all 0.3s ease;
    }
    .refresh-btn:hover {
        transform: rotate(180deg);
    }
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }
    .table thead th {
        position: sticky;
        top: 0;
        background-color: #343a40;
        color: #fff;
    }
    .session-name {
        font-weight: bold;
        color: #3490dc;
    }
    .detail-btn {
        border-radius: 20px;
        padding: 0.25rem 0.75rem;
        transition: all 0.2s ease;
    }
    .detail-btn:hover {
        transform: scale(1.05);
    }
    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }
    .modal-header {
        background: linear-gradient(135deg, #3490dc, #6574cd);
        color: #fff;
    }
    .modal-body h6 {
        font-weight: bold;
        color: #3490dc;
        margin-top: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    /* Hiệu ứng gradient cho các card màu */
    .bg-info {
        background: linear-gradient(135deg, #3498db, #2980b9) !important;
    }
    .bg-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60) !important;
    }
    .bg-warning {
        background: linear-gradient(135deg, #f39c12, #f1c40f) !important;
    }
    .bg-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    }
    .bg-primary {
        background: linear-gradient(135deg, #9b59b6, #8e44ad) !important;
    }
    .bg-secondary {
        background: linear-gradient(135deg, #34495e, #2c3e50) !important;
    }
    .bg-dark {
        background: linear-gradient(135deg, #7f8c8d, #95a5a6) !important;
    }
    .custom-tooltip {
        position: absolute;
        background: rgba(0,0,0,0.8);
        color: #fff;
        border-radius: 4px;
        padding: 5px 10px;
        display: none;
        z-index: 10;
        font-size: 12px;
        pointer-events: none;
    }
    .animated-progress .progress-bar {
        position: relative;
        overflow: hidden;
        animation: progress-animation 1s;
    }
    @keyframes progress-animation {
        from {
            width: 0%;
        }
    }
    .chart-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .chart-title i {
        margin-right: 8px;
        opacity: 0.8;
    }
    .legend-item {
        display: flex;
        align-items: center;
        margin-right: 15px;
        margin-bottom: 8px;
    }
    .legend-color {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        margin-right: 5px;
    }
    
    /* Heat Map Calendar Styles */
    .calendar-scale {
        display: flex;
        align-items: center;
        height: 15px;
    }
    .scale-item {
        width: 15px;
        height: 15px;
    }
    .legend-container {
        font-size: 12px;
        color: #666;
    }
    .cal-heatmap-container rect.highlight {
        stroke: #444;
        stroke-width: 1;
    }
    .cal-heatmap-container .graph-label {
        font-size: 11px;
        fill: #666;
    }
    .cal-heatmap-container .graph-rect {
        transition: stroke 0.2s ease;
    }
    .cal-heatmap-container .graph-rect:hover {
        stroke: #333;
        stroke-width: 1;
    }
</style>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card stats-card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-video mr-2 text-primary"></i>
                    Báo Cáo Phiên Live
                </h5>
                <div class="d-flex align-items-center">
                    <div class="form-group mr-2 mb-0">
                        <select class="form-control form-control-sm" id="period-filter">
                            <option value="day">Theo ngày</option>
                            <option value="month">Theo tháng</option>
                            <option value="year">Theo năm</option>
                        </select>
                    </div>
                    <div class="input-group date-range-container" style="width: 230px;">
                        <input type="text" class="form-control form-control-sm date-picker" id="live-session-date-range">
                    <div class="input-group-append">
                            <span class="input-group-text bg-light"><i class="fa fa-calendar"></i></span>
                    </div>
                    </div>
                    <button id="refresh-live-data" class="btn btn-sm btn-primary ml-2 refresh-btn" title="Làm mới dữ liệu" data-toggle="tooltip" data-placement="bottom">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Thẻ thống kê tổng quan -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-info text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Phiên Live</h5>
                                    <h3 id="total-live-sessions" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Doanh Thu</h5>
                                    <h3 id="total-live-revenue" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Đơn/Phiên</h5>
                                    <h3 id="avg-orders-per-session" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Tỷ Lệ Chốt Đơn</h5>
                                    <h3 id="conversion-rate" class="mb-0">0%</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thẻ thống kê bổ sung -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Khách Hàng</h5>
                                    <h3 id="total-customers" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-secondary text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Đơn Thành Công</h5>
                                    <h3 id="successful-orders" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-dark text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Đơn Hàng Hủy</h5>
                                    <h3 id="canceled-orders" class="mb-0">0</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Tỷ Lệ Hủy</h5>
                                    <h3 id="cancellation-rate" class="mb-0">0%</h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-ban"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Live Session Calendar Heat Map -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <h6 class="chart-title"><i class="fas fa-calendar-alt"></i>Phân Bố Phiên Live Theo Lịch</h6>
                            <div id="live-session-calendar" style="height: 200px;"></div>
                            <div class="d-flex justify-content-center mt-3">
                                <div class="d-flex align-items-center legend-container">
                                    <span>Hiệu suất: </span>
                                    <div class="calendar-scale ml-2">
                                        <div class="scale-item" style="background-color: rgba(240, 240, 240, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(230, 238, 255, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(198, 219, 239, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(158, 202, 225, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(107, 174, 214, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(66, 146, 198, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(33, 113, 181, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(8, 81, 156, 1)"></div>
                                        <div class="scale-item" style="background-color: rgba(8, 48, 107, 1)"></div>
                                    </div>
                                    <span class="ml-2">Thấp → Cao</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ tỷ lệ chốt đơn và hủy đơn -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container h-100">
                            <h6 class="chart-title"><i class="fas fa-chart-pie"></i>Tỷ Lệ Chốt Đơn & Hủy Đơn</h6>
                            <div style="position: relative; height:250px;">
                                <canvas id="conversion-rate-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container h-100">
                            <h6 class="chart-title"><i class="fas fa-chart-line"></i>Xu Hướng Khách Hàng Theo Phiên Live</h6>
                            <div style="position: relative; height:250px;">
                                <canvas id="customer-trend-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ doanh thu theo phiên live -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <h6 class="chart-title"><i class="fas fa-chart-bar"></i>Phân Tích Doanh Thu Theo Phiên Live</h6>
                            <div style="position: relative; height:300px;">
                                <canvas id="live-sessions-overview-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- So sánh hiệu suất phiên live -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h6 class="chart-title"><i class="fas fa-balance-scale"></i>So Sánh Hiệu Suất Phiên Live</h6>
                            <div style="position: relative; height:300px;">
                                <canvas id="live-performance-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h6 class="chart-title"><i class="fas fa-hand-holding-usd"></i>Doanh Thu Trung Bình Theo Khách Hàng</h6>
                            <div style="position: relative; height:300px;">
                                <canvas id="revenue-per-customer-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New addition: Live Session Performance Trend Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <h6 class="chart-title"><i class="fas fa-chart-line"></i>Trend Phân Tích Hiệu Suất Live Sessions</h6>
                            <div style="position: relative; height:300px;">
                                <canvas id="performance-trend-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng dữ liệu -->
                <div class="table-responsive chart-container">
                    <h6 class="chart-title mb-3"><i class="fas fa-list mr-2"></i>Danh Sách Phiên Live</h6>
                    <table class="table table-bordered table-striped table-hover" id="live-session-table">
                        <thead class="thead-dark">
                            <tr>
                             
                                <th>Ngày</th>   <th>Phiên Live</th>
                                <th>Tổng Đơn</th>
                                <th>Đơn Thành Công</th>
                                <th>Đơn Hủy</th>
                                <th>Tỷ Lệ Chốt</th>
                                <th>Tỷ Lệ Hủy</th>
                                <th>Khách Hàng</th>
                                <th>Doanh Thu</th>
                                <th>Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody id="live-session-table-body">
                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                            <tr>
                                <td colspan="10" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Đang tải...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Session Details Modal -->
<div class="modal fade" id="liveSessionDetailModal" tabindex="-1" role="dialog" aria-labelledby="liveSessionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="liveSessionDetailModalLabel">
                    <i class="fas fa-video mr-2"></i>
                    Chi Tiết Phiên Live
                </h5>
                <div class="modal-actions mr-3">
                    <button id="refresh-detail-data" class="btn btn-sm btn-outline-primary" title="Làm mới dữ liệu chi tiết" data-toggle="tooltip" data-placement="bottom">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-info-circle mr-2"></i>Thông Tin Phiên</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th width="40%">ID:</th>
                                            <td><span id="live-session-id" class="badge badge-light"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Tên phiên:</th>
                                            <td><span id="live-session-name" class="font-weight-bold text-primary"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Ngày diễn ra:</th>
                                            <td><span id="live-session-date"></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-chart-pie mr-2"></i>Hiệu Suất</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th width="40%">Tổng đơn hàng:</th>
                                            <td><span id="live-session-orders" class="badge badge-info"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Doanh thu:</th>
                                            <td><span id="live-session-revenue" class="font-weight-bold text-success"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Tổng khách hàng:</th>
                                            <td><span id="live-session-customers"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Tỷ lệ chốt đơn:</th>
                                            <td><span id="live-session-success-rate" class="badge badge-success"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Tỷ lệ hủy đơn:</th>
                                            <td><span id="live-session-cancel-rate" class="badge badge-danger"></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Đồ thị trạng thái đơn hàng -->
                    <div class="col-md-4">
                        <div class="chart-container h-100">
                            <h6><i class="fas fa-chart-pie mr-2"></i>Trạng Thái Đơn Hàng</h6>
                            <div style="position: relative; height:250px;">
                                <canvas id="order-status-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Đồ thị top 5 sản phẩm -->
                    <div class="col-md-4">
                        <div class="chart-container h-100">
                            <h6><i class="fas fa-trophy mr-2"></i>Top 5 Sản Phẩm</h6>
                            <div style="position: relative; height:250px;">
                                <canvas id="top-products-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Đồ thị phân bổ doanh thu -->
                    <div class="col-md-4">
                        <div class="chart-container h-100">
                            <h6><i class="fas fa-funnel-dollar mr-2"></i>Phân Bổ Doanh Thu</h6>
                            <div style="position: relative; height:250px;">
                                <canvas id="revenue-distribution-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Performance Radar Chart -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container h-100">
                            <h6><i class="fas fa-chart-radar mr-2"></i>Chỉ Số Hiệu Suất (Radar)</h6>
                            <div style="position: relative; height:280px;">
                                <canvas id="performance-radar-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container h-100">
                            <h6><i class="fas fa-chart-bar mr-2"></i>So Sánh Hiệu Suất</h6>
                            <div style="position: relative; height:280px;">
                                <div id="performance-comparison" class="d-flex flex-column justify-content-center align-items-start" style="height:100%">
                                    <div class="w-100 mb-3">
                                        <label>Tỷ lệ chốt đơn</label>
                                        <div class="progress" style="height: 25px;">
                                            <div id="comparison-conversion" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small id="avg-conversion-text" class="text-muted">So với trung bình: 0%</small>
                                    </div>
                                    <div class="w-100 mb-3">
                                        <label>Doanh thu</label>
                                        <div class="progress" style="height: 25px;">
                                            <div id="comparison-revenue" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small id="avg-revenue-text" class="text-muted">So với trung bình: 0 VNĐ</small>
                                    </div>
                                    <div class="w-100 mb-3">
                                        <label>Số lượng khách hàng</label>
                                        <div class="progress" style="height: 25px;">
                                            <div id="comparison-customers" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small id="avg-customers-text" class="text-muted">So với trung bình: 0</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Product Performance Comparison Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <h6><i class="fas fa-chart-bar mr-2"></i>Hiệu Suất Sản Phẩm</h6>
                            <div style="position: relative; height:280px;">
                                <canvas id="product-performance-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách đơn hàng -->
                <div class="chart-container">
                    <h6><i class="fas fa-box mr-2"></i>Sản Phẩm Đã Bán</h6>
                <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm" id="live-session-products-table">
                        <thead class="thead-dark">
                            <tr>
                                    <th width="35%">Sản Phẩm</th>
                                    <th width="15%">Mã SP</th>
                                    <th width="15%">Số Lượng</th>
                                    <th width="20%">Doanh Thu</th>
                                    <th width="15%">Tỷ Lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody id="live-session-products-body">
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="sr-only">Đang tải...</span>
                                        </div>
                                    </td>
                                </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="export-detail-btn">
                    <i class="fas fa-file-download mr-1"></i> Xuất CSV
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
<!-- Add Cal-Heatmap Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://unpkg.com/cal-heatmap/dist/cal-heatmap.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/cal-heatmap/dist/cal-heatmap.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"], [title]').tooltip();
    
    // Khởi tạo date range picker
    $('#live-session-date-range').daterangepicker({
        startDate: moment().subtract(30, 'days'),
        endDate: moment(),
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Áp dụng',
            cancelLabel: 'Hủy',
            fromLabel: 'Từ',
            toLabel: 'Đến',
            customRangeLabel: 'Tùy chỉnh',
            daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
            ],
            firstDay: 1
        },
        ranges: {
           'Hôm nay': [moment(), moment()],
           'Hôm qua': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           '7 ngày qua': [moment().subtract(6, 'days'), moment()],
           '30 ngày qua': [moment().subtract(29, 'days'), moment()],
           'Tháng này': [moment().startOf('month'), moment().endOf('month')],
           'Tháng trước': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });
    
    // Biểu đồ tổng quan
    let overviewChart = null;
    let orderStatusChart = null;
    let topProductsChart = null;

    // Format datetime
    function formatDate(dateString) {
        return moment(dateString).format('DD/MM/YYYY');
    }

    // Format currency
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND',
            maximumFractionDigits: 0 
        }).format(value);
    }

    // Format number with thousand separator
    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(value);
    }

    // Format percentage
    function formatPercent(value) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value/100);
    }

    // Load live session report data with loading indicator
    function loadLiveSessionData(startDate, endDate, forceRefresh = false) {
        // Show loading spinner in table
        $('#live-session-table-body').html(`
            <tr>
                <td colspan="10" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải dữ liệu...</span>
                    </div>
                    <p class="mt-2">Đang tải dữ liệu phiên live...</p>
                </td>
            </tr>
        `);

        // Reset summary cards to show loading state
        $('.stats-card .stats-icon').html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Remove existing status indicator if any
        $('#data-status-indicator').remove();
        
        $.ajax({
            url: '/api/reports/live-session',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                force_refresh: forceRefresh
            },
            success: function(response) {
                if (response.success) {
                    updateLiveSessionSummary(response.data);
                    updateLiveSessionTable(response.data);
                    updateOverviewChart(response.data);
                    
                    // Add cache status indicator
                    let statusClass = response.from_cache ? 'text-success' : 'text-primary';
                    let statusIcon = response.from_cache ? 'database' : 'sync';
                    let statusText = response.from_cache ? 'Dữ liệu từ cache' : 'Dữ liệu mới được tính toán';
                    
                    $('.card-header').append(`
                        <small id="data-status-indicator" class="${statusClass}" style="position: absolute; right: 80px; top: 50%; transform: translateY(-50%);">
                            <i class="fas fa-${statusIcon} mr-1"></i> ${statusText}
                        </small>
                    `);
                }
            },
            error: function(error) {
                console.error('Error loading live session data:', error);
                $('#live-session-table-body').html(`
                    <tr>
                        <td colspan="10" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại sau.
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Update live session summary statistics
    function updateLiveSessionSummary(data) {
        const totalSessions = data.length;
        const totalRevenue = data.reduce((sum, session) => sum + session.revenue, 0);
        const totalOrders = data.reduce((sum, session) => sum + session.total_orders, 0);
        const totalCustomers = data.reduce((sum, session) => sum + session.total_customers, 0);
        
        // Tính toán số đơn hủy và thành công
        let successfulOrders = 0;
        let canceledOrders = 0;
        
        data.forEach(session => {
            successfulOrders += session.successful_orders || 0;
            canceledOrders += session.canceled_orders || 0;
        });
        
        // Tính tỷ lệ hủy và thành công
        const cancellationRate = totalOrders > 0 ? (canceledOrders / totalOrders * 100) : 0;
        const conversionRate = totalOrders > 0 ? (successfulOrders / totalOrders * 100) : 0;

        const avgOrdersPerSession = totalSessions > 0 ? (totalOrders / totalSessions).toFixed(2) : 0;

        // Cập nhật card thống kê
        $('#total-live-sessions').text(formatNumber(totalSessions));
        $('#total-live-revenue').text(formatCurrency(totalRevenue));
        $('#avg-orders-per-session').text(formatNumber(avgOrdersPerSession));
        $('#total-customers').text(formatNumber(totalCustomers));
        $('#successful-orders').text(formatNumber(successfulOrders));
        $('#canceled-orders').text(formatNumber(canceledOrders));
        $('#cancellation-rate').text(formatPercent(cancellationRate));
        $('#conversion-rate').text(formatPercent(conversionRate));

        // Cập nhật icon cho các card
        $('.stats-card').find('.stats-icon').each(function() {
            const icon = $(this).data('icon') || $(this).find('i').attr('class');
            if (icon.includes('fa-spinner')) {
                // Restore original icon if showing loading spinner
                $(this).html('<i class="' + icon.replace(' fa-spin', '') + '"></i>');
            }
        });

        // Create performance trend chart
        createPerformanceTrendChart(data);
    }

    // Create/update the overview chart
    function updateOverviewChart(data) {
        // Group data by month 
        const monthlyData = {};
        
        data.forEach(session => {
            const month = moment(session.session_date).format('MM/YYYY');
            if (!monthlyData[month]) {
                monthlyData[month] = {
                    revenue: 0,
                    orders: 0,
                    successful_orders: 0,
                    canceled_orders: 0,
                    sessions: 0
                };
            }
            
            monthlyData[month].revenue += session.revenue;
            monthlyData[month].orders += session.total_orders;
            monthlyData[month].successful_orders += session.successful_orders || 0;
            monthlyData[month].canceled_orders += session.canceled_orders || 0;
            monthlyData[month].sessions++;
        });
        
        // Convert to arrays for chart
        const months = Object.keys(monthlyData).sort((a, b) => {
            const [aMonth, aYear] = a.split('/').map(Number);
            const [bMonth, bYear] = b.split('/').map(Number);
            
            if (aYear !== bYear) return aYear - bYear;
            return aMonth - bMonth;
        });
        
        const revenues = months.map(month => monthlyData[month].revenue);
        const orders = months.map(month => monthlyData[month].orders);
        const successfulOrders = months.map(month => monthlyData[month].successful_orders);
        const canceledOrders = months.map(month => monthlyData[month].canceled_orders);
        
        // Destroy existing chart if it exists
        if (overviewChart) {
            overviewChart.destroy();
        }
        
        // Create new chart
        const ctx = document.getElementById('live-sessions-overview-chart').getContext('2d');
        overviewChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Doanh thu',
                        data: revenues,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y-revenue'
                    },
                    {
                        label: 'Đơn thành công',
                        data: successfulOrders,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'y-orders'
                    },
                    {
                        label: 'Đơn hủy',
                        data: canceledOrders,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
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
                        grid: {
                            display: false
                        }
                    },
                    'y-revenue': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Doanh thu (VND)'
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
                    'y-orders': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Số đơn hàng'
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
                                if (context.datasetIndex === 0) {
                                    return label + formatCurrency(context.raw);
                                } else {
                                    return label + formatNumber(context.raw);
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    // Update live session table
    function updateLiveSessionTable(data) {
        const tableBody = $('#live-session-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="10" class="text-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Không có dữ liệu phiên live trong khoảng thời gian này
                    </td>
                </tr>
            `);
            return;
        }
        
        // Create calendar heat map data
        createCalendarHeatMap(data);

        data.forEach(function(session) {
            // Format status percentage rates
            const successRate = session.conversion_rate ? formatPercent(session.conversion_rate) : '0%';
            const cancelRate = session.cancellation_rate ? formatPercent(session.cancellation_rate) : '0%';
            
            tableBody.append(`
                <tr class="session-row" data-id="${session.id}">
                    <td><span class="session-name">${session.name}</span></td>
                    <td>${formatDate(session.session_date)}</td>
                    <td>${formatNumber(session.total_orders)}</td>
                    <td>
                        <span class="badge badge-success">${formatNumber(session.successful_orders)}</span>
                    </td>
                    <td>
                        <span class="badge badge-danger">${formatNumber(session.canceled_orders)}</span>
                    </td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: ${session.conversion_rate}%;" 
                                aria-valuenow="${session.conversion_rate}" aria-valuemin="0" aria-valuemax="100">
                                ${successRate}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                style="width: ${session.cancellation_rate}%;" 
                                aria-valuenow="${session.cancellation_rate}" aria-valuemin="0" aria-valuemax="100">
                                ${cancelRate}
                            </div>
                        </div>
                    </td>
                    <td>${formatNumber(session.total_customers)}</td>
                    <td>${formatCurrency(session.revenue)}</td>
                    <td>
                        <button class="btn btn-sm btn-info detail-btn view-live-session-detail" data-id="${session.id}">
                            <i class="fa fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `);
        });

        // Initialize DataTable with Vietnamese localization
        if ($.fn.DataTable.isDataTable('#live-session-table')) {
            $('#live-session-table').DataTable().destroy();
        }

        $('#live-session-table').DataTable({
            "order": [[1, "desc"], [0, "asc"]], // Sort by date then by session name
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            },
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]]
        });
    }

    // Load live session detail
    function loadLiveSessionDetail(sessionId, forceRefresh = false) {
        // Show loading indicators
        $('#live-session-products-body').html(`
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        `);
        
        // Hiển thị modal ngay để tạo trải nghiệm tốt hơn cho người dùng
        $('#liveSessionDetailModal').modal('show');
        
        // Remove existing cache indicator
        $('#detail-cache-indicator').remove();
        
        $.ajax({
            url: '/api/reports/live-session-detail',
            method: 'GET',
            data: {
                session_id: sessionId,
                force_refresh: forceRefresh
            },
            success: function(response) {
                if (response.success) {
                    showLiveSessionDetail(response.data);
                    
                    // Add cache status indicator
                    let statusClass = response.from_cache ? 'text-success' : 'text-primary';
                    let statusIcon = response.from_cache ? 'database' : 'sync';
                    let statusText = response.from_cache ? 'Dữ liệu từ cache' : 'Dữ liệu mới được tính toán';
                    
                    $('.modal-title').append(`
                        <small id="detail-cache-indicator" class="${statusClass}" style="font-size: 0.7rem; margin-left: 10px;">
                            <i class="fas fa-${statusIcon} mr-1"></i> ${statusText}
                        </small>
                    `);
                } else {
                    $('#live-session-products-body').html(`
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                ${response.message || 'Có lỗi xảy ra khi tải dữ liệu chi tiết.'}
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(error) {
                console.error('Error loading live session detail:', error);
                $('#live-session-products-body').html(`
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Không thể tải thông tin chi tiết phiên live.
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Show live session detail in modal
    function showLiveSessionDetail(data) {
        const session = data.session;
        const orders = data.orders;
        const products = data.products;

        $('#live-session-id').text(session.id);
        $('#live-session-name').text(session.name);
        $('#live-session-date').text(formatDate(session.session_date));
        $('#live-session-orders').text(formatNumber(session.total_orders));
        $('#live-session-revenue').text(formatCurrency(session.revenue));
        $('#live-session-customers').text(formatNumber(session.total_customers || 0));
        $('#live-session-success-rate').text(formatPercent(session.conversion_rate));
        $('#live-session-cancel-rate').text(formatPercent(session.cancellation_rate));

        // Create/update order status chart
        createOrderStatusChart(session.orders_by_status);
        
        // Create/update top products chart
        createTopProductsChart(products);
        
        // Create/update revenue distribution chart
        createRevenueDistributionChart(products);

        // Create/update performance radar chart
        createPerformanceRadarChart(session);
        
        // Create performance comparison with averages
        updatePerformanceComparison(session);

        // Create/update product performance chart
        createProductPerformanceChart(products);

        // Update products table
        updateLiveSessionProductsTable(products);
    }

    // Create order status pie chart
    function createOrderStatusChart(ordersByStatus) {
        // Convert status data for chart
        const labels = [];
        const data = [];
        const backgroundColors = [];
        
        // Define colors for each status
        const statusColors = {
            'moi': 'rgba(54, 162, 235, 0.8)',
            'can_xu_ly': 'rgba(255, 206, 86, 0.8)',
            'cho_hang': 'rgba(153, 102, 255, 0.8)',
            'cho_chuyen_hang': 'rgba(153, 102, 255, 0.8)',
            'da_dat_hang': 'rgba(255, 159, 64, 0.8)',
            'da_gui_hang': 'rgba(255, 159, 64, 0.8)',
            'da_giao': 'rgba(75, 192, 192, 0.8)',
            'da_nhan': 'rgba(75, 192, 192, 0.8)',
            'da_nhan_doi': 'rgba(75, 192, 192, 0.8)',
            'da_thu_tien': 'rgba(75, 192, 192, 0.8)',
            'hoan_thanh': 'rgba(75, 192, 192, 0.8)',
            'thanh_cong': 'rgba(75, 192, 192, 0.8)',
            'huy': 'rgba(255, 99, 132, 0.8)',
            'da_huy': 'rgba(255, 99, 132, 0.8)',
        };
        
        // Map status codes to readable names
        const statusNames = {
            'moi': 'Mới',
            'can_xu_ly': 'Cần xử lý',
            'cho_hang': 'Chờ hàng',
            'cho_chuyen_hang': 'Chờ chuyển',
            'da_dat_hang': 'Đã đặt hàng',
            'da_gui_hang': 'Đã gửi hàng',
            'da_giao': 'Đã giao',
            'da_nhan': 'Đã nhận',
            'da_nhan_doi': 'Đã nhận đổi',
            'da_thu_tien': 'Đã thu tiền',
            'hoan_thanh': 'Hoàn thành',
            'thanh_cong': 'Thành công',
            'huy': 'Hủy',
            'da_huy': 'Đã hủy',
        };
        
        // Group order status by success, canceled, processing
        const groupedStatus = {
            'Thành công': 0,
            'Đang xử lý': 0,
            'Đã hủy': 0
        };

        // Populate chart data
        for (const status in ordersByStatus) {
            const statusCount = ordersByStatus[status];
            const statusName = statusNames[status] || status;
            
            // Group by status type
            if (['thanh_cong', 'hoan_thanh', 'da_giao', 'da_nhan', 'da_thu_tien'].includes(status)) {
                groupedStatus['Thành công'] += statusCount;
            } else if (['huy', 'da_huy'].includes(status)) {
                groupedStatus['Đã hủy'] += statusCount;
        } else {
                groupedStatus['Đang xử lý'] += statusCount;
            }
            
            // Keep detailed status for hover tooltip
            labels.push(statusName);
            data.push(statusCount);
            backgroundColors.push(statusColors[status] || 'rgba(153, 102, 255, 0.8)');
        }
        
        // Create or update chart
        const ctx = document.getElementById('order-status-chart').getContext('2d');
        
        if (orderStatusChart) {
            orderStatusChart.destroy();
        }
        
        // Use simpler grouped chart for better visualization
        const groupedLabels = Object.keys(groupedStatus);
        const groupedData = Object.values(groupedStatus);
        const groupedColors = [
            'rgba(75, 192, 192, 0.8)',  // Thành công - Green
            'rgba(54, 162, 235, 0.8)',  // Đang xử lý - Blue
            'rgba(255, 99, 132, 0.8)'   // Đã hủy - Red
        ];
        
        orderStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: groupedLabels,
                datasets: [{
                    data: groupedData,
                    backgroundColor: groupedColors,
                    borderColor: groupedColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? (value / total * 100).toFixed(2) + '%' : '0%';
                                return `${label}: ${value} (${percentage})`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Create top products chart
    function createTopProductsChart(products) {
        // Get top 5 products by revenue
        const topProducts = products.slice(0, 5);
        
        const labels = topProducts.map(product => {
            // Truncate long product names
            return product.name.length > 20 ? product.name.substring(0, 20) + '...' : product.name;
        });
        const data = topProducts.map(product => product.total_revenue);
        const quantities = topProducts.map(product => product.total_quantity);
        
        // Create or update chart
        const ctx = document.getElementById('top-products-chart').getContext('2d');
        
        if (topProductsChart) {
            topProductsChart.destroy();
        }
        
        topProductsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-revenue'
                },
                {
                    label: 'Số lượng',
                    data: quantities,
                    backgroundColor: 'rgba(255, 206, 86, 0.8)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-quantity'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    'y-revenue': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Doanh thu (VND)'
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
                    'y-quantity': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Số lượng'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
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
                                if (context.datasetIndex === 0) {
                                    return label + formatCurrency(context.raw);
                                } else {
                                    return label + formatNumber(context.raw);
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    // Create product performance chart
    function createProductPerformanceChart(products) {
        // Get top 5 products by revenue
        const topProducts = products.slice(0, 5);
        
        // Calculate revenue per quantity for each product
        const productNames = topProducts.map(product => {
            // Truncate long product names
            return product.name.length > 15 ? product.name.substring(0, 15) + '...' : product.name;
        });
        
        const revenues = topProducts.map(product => product.total_revenue);
        const quantities = topProducts.map(product => product.total_quantity);
        const avgPrices = topProducts.map(product => 
            product.total_quantity > 0 ? product.total_revenue / product.total_quantity : 0
        );
        const revenuePerCustomer = topProducts.map(product => 
            product.customer_count > 0 ? product.total_revenue / product.customer_count : 0
        );
        
        // Create or update chart
        const ctx = document.getElementById('product-performance-chart').getContext('2d');
        
        if (window.productPerformanceChart) {
            window.productPerformanceChart.destroy();
        }
        
        // Custom stacked horizontal bar chart with multiple metrics
        window.productPerformanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: productNames,
                datasets: [
                    {
                        label: 'Tỷ lệ doanh thu (%)',
                        data: topProducts.map(product => {
                            const totalRevenue = products.reduce((sum, p) => sum + p.total_revenue, 0);
                            return totalRevenue > 0 ? (product.total_revenue / totalRevenue * 100).toFixed(2) : 0;
                        }),
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Giá trị trung bình (triệu VND)',
                        data: avgPrices.map(price => price / 1000000),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    },
                    {
                        label: 'Số lượng bán',
                        data: quantities,
                        backgroundColor: 'rgba(255, 205, 86, 0.7)',
                        borderColor: 'rgba(255, 205, 86, 1)',
                        borderWidth: 1,
                        barPercentage: 0.7,
                        categoryPercentage: 0.8
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true, 
                            text: 'Giá trị'
                        },
                        stacked: false
                    },
                    y: {
                        stacked: false
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'start'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                
                                if (context.datasetIndex === 0) {
                                    return label + context.raw + '%';
                                } else if (context.datasetIndex === 1) {
                                    return label + formatCurrency(context.raw * 1000000);
                                } else {
                                    return label + formatNumber(context.raw);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // Update products table in detail modal
    function updateLiveSessionProductsTable(products) {
        const tableBody = $('#live-session-products-body');
        tableBody.empty();

        if (products.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="5" class="text-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        Không có sản phẩm nào được bán trong phiên live này
                    </td>
                </tr>
            `);
            return;
        }

        // Calculate total revenue for percentage
        const totalRevenue = products.reduce((sum, product) => sum + product.total_revenue, 0);

        products.forEach(function(product, index) {
            const revenuePercentage = totalRevenue > 0 ? 
                ((product.total_revenue / totalRevenue) * 100).toFixed(2) : 0;
            
            // Add highlight class for top 3 products
            const rowClass = index < 3 ? 'table-success' : '';

            tableBody.append(`
                <tr class="${rowClass}">
                    <td>${product.name}</td>
                    <td>${product.sku || 'N/A'}</td>
                    <td>${formatNumber(product.total_quantity)}</td>
                    <td>${formatCurrency(product.total_revenue)}</td>
                    <td>${revenuePercentage}%</td>
                </tr>
            `);
        });
    }

    // Create performance trend chart for live sessions
    function createPerformanceTrendChart(data) {
        // Sort data by date (ascending)
        data.sort((a, b) => moment(a.session_date).diff(moment(b.session_date)));
        
        const sessionNames = data.map(session => session.name);
        const revenues = data.map(session => session.revenue);
        const conversionRates = data.map(session => session.conversion_rate);
        const customersCount = data.map(session => session.total_customers);
        
        const ctx = document.getElementById('performance-trend-chart').getContext('2d');
        
        if (window.performanceTrendChart) {
            window.performanceTrendChart.destroy();
        }
        
        window.performanceTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: sessionNames,
                datasets: [
                    {
                        label: 'Doanh Thu (triệu VND)',
                        data: revenues.map(value => value / 1000000), // Convert to millions
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y-revenue',
                        tension: 0.4
                    },
                    {
                        label: 'Khách Hàng',
                        data: customersCount,
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)', 
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y-customers',
                        tension: 0.4
                    },
                    {
                        label: 'Tỷ Lệ Chốt Đơn (%)',
                        data: conversionRates,
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y-rate',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    'y-revenue': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Doanh Thu (triệu VND)'
                        }
                    },
                    'y-customers': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Số Lượng Khách'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    'y-rate': {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Tỷ Lệ Chốt (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        },
                        offset: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    return label + formatCurrency(context.raw * 1000000);
                                } else if (context.datasetIndex === 1) {
                                    return label + formatNumber(context.raw);
                                } else {
                                    return label + formatPercent(context.raw);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // Create calendar heat map
    function createCalendarHeatMap(data) {
        // Process data for calendar visualization
        const calendarData = {};
        
        data.forEach(session => {
            // Convert to timestamp (seconds)
            const date = new Date(session.session_date);
            const timestamp = Math.floor(date.getTime() / 1000);
            
            // Calculate performance score (0-100 based on revenue and conversion rate)
            // Higher is better
            const maxRevenue = Math.max(...data.map(s => s.revenue));
            const revenueScore = maxRevenue > 0 ? (session.revenue / maxRevenue) * 50 : 0;
            const conversionScore = session.conversion_rate ? session.conversion_rate / 2 : 0;
            const score = Math.min(Math.round(revenueScore + conversionScore), 8); // Scale from 0-8 for heat map
            
            calendarData[timestamp] = score;
        });
        
        // Clear previous calendar if any
        document.getElementById('live-session-calendar').innerHTML = '';
        
        // Calculate date range
        const dates = Object.keys(calendarData).map(ts => new Date(parseInt(ts) * 1000));
        const minDate = dates.length ? new Date(Math.min(...dates.map(d => d.getTime()))) : new Date();
        const maxDate = dates.length ? new Date(Math.max(...dates.map(d => d.getTime()))) : new Date();
        
        // Set start date to first day of month from minDate
        const startDate = new Date(minDate.getFullYear(), minDate.getMonth(), 1);
        
        // Set end date to last day of month from maxDate
        const endDate = new Date(maxDate.getFullYear(), maxDate.getMonth() + 3, 0);
        
        // Initialize the calendar heat map
        const cal = new CalHeatmap();
        cal.init({
            itemSelector: '#live-session-calendar',
            domain: 'month',
            subDomain: 'day',
            data: calendarData,
            start: startDate,
            cellSize: 15,
            range: 3,
            legend: [1, 2, 3, 4, 5, 6, 7, 8],
            legendColors: {
                min: "rgba(240, 240, 240, 1)",
                max: "rgba(8, 48, 107, 1)",
                empty: "rgba(240, 240, 240, 1)"
            },
            tooltip: true,
            displayLegend: false,
            itemName: ["phiên live", "phiên live"],
            subDomainTextFormat: "%d",
            domainLabelFormat: "%B %Y",
            highlight: "now",
            legendTitleFormat: {
                lower: "Thấp",
                upper: "Cao"
            },
            onClick: function(date, value) {
                if (value) {
                    // Find session(s) that happened on this day
                    const clickedDate = new Date(date * 1000).toISOString().split('T')[0]; // Format: YYYY-MM-DD
                    const sessionsOnDay = data.filter(session => session.session_date.startsWith(clickedDate));
                    
                    if (sessionsOnDay.length > 0) {
                        // If only one session on that day, show details
                        if (sessionsOnDay.length === 1) {
                            loadLiveSessionDetail(sessionsOnDay[0].id);
                        } else {
                            // If multiple sessions, highlight them in the table
                            $('.session-row').removeClass('table-active');
                            sessionsOnDay.forEach(session => {
                                $(`.session-row[data-id="${session.id}"]`).addClass('table-active');
                            });
                            
                            // Scroll to the first one
                            const firstRow = $(`.session-row[data-id="${sessionsOnDay[0].id}"]`);
                            if (firstRow.length) {
                                $('html, body').animate({
                                    scrollTop: firstRow.offset().top - 200
                                }, 500);
                            }
                        }
                    }
                }
            }
        });
    }

    // Create revenue distribution chart
    function createRevenueDistributionChart(products) {
        // Group products by categories (use first word of product name as pseudo-category)
        const categories = {};
        let totalRevenue = 0;
        
        // Calculate total revenue
        products.forEach(product => {
            totalRevenue += product.total_revenue;
        });
        
        // Group by product categories (simplified by using first word of product name)
        products.forEach(product => {
            // Extract first word or use "Other" as fallback
            const firstWord = product.name.split(' ')[0] || 'Other';
            const category = firstWord.length > 3 ? firstWord : 'Other';
            
            if (!categories[category]) {
                categories[category] = {
                    revenue: 0,
                    products: []
                };
            }
            
            categories[category].revenue += product.total_revenue;
            categories[category].products.push(product.name);
        });
        
        // Convert to array and sort by revenue
        const categoryData = Object.keys(categories).map(category => ({
            name: category,
            revenue: categories[category].revenue,
            percentage: (categories[category].revenue / totalRevenue * 100).toFixed(1),
            products: categories[category].products
        })).sort((a, b) => b.revenue - a.revenue);
        
        // Take top 5 categories and group the rest as "Other"
        let chartData = [];
        let chartLabels = [];
        let chartColors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)'
        ];
        
        if (categoryData.length <= 5) {
            chartLabels = categoryData.map(cat => cat.name);
            chartData = categoryData.map(cat => cat.revenue);
        } else {
            // Take top 4 and group others
            for (let i = 0; i < 4; i++) {
                chartLabels.push(categoryData[i].name);
                chartData.push(categoryData[i].revenue);
            }
            
            // Sum the rest as "Other"
            const otherRevenue = categoryData.slice(4).reduce((sum, cat) => sum + cat.revenue, 0);
            chartLabels.push('Khác');
            chartData.push(otherRevenue);
        }
        
        // Create or update chart
        const ctx = document.getElementById('revenue-distribution-chart').getContext('2d');
        
        if (window.revenueDistChart) {
            window.revenueDistChart.destroy();
        }
        
        window.revenueDistChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderColor: chartColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 11
                            },
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = totalRevenue > 0 ? 
                                    (value / totalRevenue * 100).toFixed(1) + '%' : '0%';
                                return `${label}: ${formatCurrency(value)} (${percentage})`;
                            }
                        }
                    }
                }
            }
        });
    }

// Create performance radar chart
    function createPerformanceRadarChart(session) {
        // Define metrics to show in radar chart
        const metrics = [
            { key: 'conversion_rate', label: 'Tỷ Lệ Chốt Đơn', max: 100, format: 'percent' },
            { key: 'total_orders', label: 'Số Đơn Hàng', max: 100, format: 'number' },
            { key: 'successful_orders', label: 'Đơn Thành Công', max: 80, format: 'number' },
            { key: 'total_customers', label: 'Số Khách Hàng', max: 50, format: 'number' },
            { key: 'revenue', label: 'Doanh Thu', max: 50000000, format: 'currency' }
        ];
        
        // Convert values to percentage of max for radar chart
        const radarData = metrics.map(metric => {
            const value = session[metric.key] || 0;
            // Convert to percentage of max value for even scaling
            return (value / metric.max) * 100;
        });
        
        const ctx = document.getElementById('performance-radar-chart').getContext('2d');
        
        if (window.performanceRadarChart) {
            window.performanceRadarChart.destroy();
        }
        
        window.performanceRadarChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: metrics.map(m => m.label),
                datasets: [
                    {
                        label: 'Hiệu Suất',
                        data: radarData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(54, 162, 235, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        angleLines: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 20,
                            backdropColor: 'transparent'
                        },
                        grid: {
                            circular: true
                        },
                        pointLabels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                const metric = metrics[index];
                                const actualValue = session[metric.key] || 0;
                                
                                let formattedValue = '';
                                if (metric.format === 'percent') {
                                    formattedValue = formatPercent(actualValue);
                                } else if (metric.format === 'currency') {
                                    formattedValue = formatCurrency(actualValue);
                                } else {
                                    formattedValue = formatNumber(actualValue);
                                }
                                
                                return `${metric.label}: ${formattedValue}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Update performance comparison with averages
    function updatePerformanceComparison(session) {
        // Get stored average values from localStorage or use defaults
        const avgConversionRate = parseFloat(localStorage.getItem('avg_conversion_rate') || '50');
        const avgRevenue = parseFloat(localStorage.getItem('avg_revenue') || '10000000');
        const avgCustomers = parseFloat(localStorage.getItem('avg_customers') || '20');
        
        // Calculate percentages for progress bars (capped at 100%)
        const conversionPercent = Math.min(100, (session.conversion_rate / avgConversionRate) * 100);
        const revenuePercent = Math.min(100, (session.revenue / avgRevenue) * 100);
        const customersPercent = Math.min(100, (session.total_customers / avgCustomers) * 100);
        
        // Update progress bars
        $('#comparison-conversion')
            .css('width', conversionPercent + '%')
            .attr('aria-valuenow', conversionPercent)
            .text(formatPercent(session.conversion_rate));
            
        $('#comparison-revenue')
            .css('width', revenuePercent + '%')
            .attr('aria-valuenow', revenuePercent)
            .text(formatCurrency(session.revenue));
            
        $('#comparison-customers')
            .css('width', customersPercent + '%')
            .attr('aria-valuenow', customersPercent)
            .text(formatNumber(session.total_customers));
            
        // Update comparison texts
        $('#avg-conversion-text').text('So với trung bình: ' + formatPercent(avgConversionRate));
        $('#avg-revenue-text').text('So với trung bình: ' + formatCurrency(avgRevenue));
        $('#avg-customers-text').text('So với trung bình: ' + formatNumber(avgCustomers));
    }

    // Event handlers
    $('#live-session-date-range').on('apply.daterangepicker', function(ev, picker) {
        // Use cached data if available
        loadLiveSessionData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD'),
            false // Don't force refresh
        );
    });

    // View live session detail
    $(document).on('click', '.view-live-session-detail', function() {
        const sessionId = $(this).data('id');
        loadLiveSessionDetail(sessionId);
        // Store the current session ID for refresh button
        $('#refresh-detail-data').data('session-id', sessionId);
    });
    
    // Refresh detail data
    $(document).on('click', '#refresh-detail-data', function() {
        const sessionId = $(this).data('session-id');
        if (sessionId) {
            loadLiveSessionDetail(sessionId, true); // Force refresh
            
            // Add rotate animation when clicked
            $(this).find('i').addClass('fa-spin');
            setTimeout(() => {
                $(this).find('i').removeClass('fa-spin');
            }, 1000);
        }
    });

    // Refresh data
    $('#refresh-live-data').on('click', function() {
        const picker = $('#live-session-date-range').data('daterangepicker');
        loadLiveSessionData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD'),
            true // Force refresh from database
        );
        
        // Add rotate animation when clicked
        $(this).find('i').addClass('fa-spin');
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
        
        // Show tooltip message
        const $refreshBtn = $(this);
        $refreshBtn.attr('data-original-title', 'Dữ liệu được tải mới từ cơ sở dữ liệu');
        $refreshBtn.tooltip('show');
        
        setTimeout(() => {
            $refreshBtn.tooltip('hide');
            $refreshBtn.attr('data-original-title', 'Làm mới dữ liệu');
        }, 2000);
    });

    // Export to CSV button
    $('#export-detail-btn').on('click', function() {
        const sessionId = $('#live-session-id').text();
        
        // Show loading state while downloading
        const originalText = $(this).html();
        $(this).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xuất...');
        
        // Open in new tab
        window.open(`/api/reports/live-session-export?session_id=${sessionId}`, '_blank');
        
        // Restore button text after a short delay
        setTimeout(() => {
            $(this).html(originalText);
        }, 2000);
    });

    // Period filter change
    $('#period-filter').on('change', function() {
        const period = $(this).val();
        const now = moment();
        let startDate, endDate;
        
        switch(period) {
            case 'day':
                // No change to daterangepicker, just keep it as is
                break;
            case 'month':
                // Group by month in current year
                startDate = moment().startOf('year');
                endDate = moment();
                break;
            case 'year':
                // Last 3 years
                startDate = moment().subtract(3, 'years').startOf('year');
                endDate = moment();
                break;
        }
        
        if (startDate && endDate) {
            $('#live-session-date-range').data('daterangepicker').setStartDate(startDate);
            $('#live-session-date-range').data('daterangepicker').setEndDate(endDate);
            
            // Use cached data if available (don't force refresh)
            loadLiveSessionData(startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'), false);
            
            // Show message about cached data
            const $message = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true">')
                .html(`
                    <div class="toast-header bg-info text-white">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong class="mr-auto">Thông báo</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        Đã áp dụng bộ lọc mới. Dữ liệu được tải từ cache nếu có, hoặc tính toán mới nếu chưa có.
                    </div>
                `)
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'z-index': '9999',
                    'min-width': '300px'
                })
                .appendTo('body');
            
            $message.toast({
                delay: 3000,
                autohide: true
            }).toast('show');
        }
    });

    // Initial load with a brief message explaining cache behavior
    const $initialMessage = $('<div class="alert alert-info alert-dismissible fade show" role="alert">')
        .html(`
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Thông báo:</strong> Lần đầu truy cập, dữ liệu sẽ được tính toán và lưu vào cache. 
            Các lần sau dữ liệu sẽ được tải từ cache để tối ưu hiệu suất. 
            Bạn có thể nhấn nút <i class="fas fa-sync-alt"></i> để làm mới dữ liệu nếu cần.
        `)
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'z-index': '9999',
            'max-width': '400px'
        })
        .appendTo('body');
    
    setTimeout(() => {
        $initialMessage.alert('close');
    }, 8000);
    
    const picker = $('#live-session-date-range').data('daterangepicker');
    loadLiveSessionData(
        picker.startDate.format('YYYY-MM-DD'),
        picker.endDate.format('YYYY-MM-DD')
    );
});
</script>
