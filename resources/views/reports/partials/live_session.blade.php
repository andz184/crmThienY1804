<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Báo Cáo Phiên Live</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="live-session-date-range">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tổng Số Phiên Live</h5>
                                <h3 id="total-live-sessions">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tổng Doanh Thu</h5>
                                <h3 id="total-live-revenue">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Trung Bình Đơn/Phiên</h5>
                                <h3 id="avg-orders-per-session">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Trung Bình DT/Phiên</h5>
                                <h3 id="avg-revenue-per-session">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="live-session-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID Phiên</th>
                                <th>Tên Phiên</th>
                                <th>Thời Gian Bắt Đầu</th>
                                <th>Thời Gian Kết Thúc</th>
                                <th>Tổng Đơn Hàng</th>
                                <th>Doanh Thu</th>
                                <th>Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody id="live-session-table-body">
                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                            <tr>
                                <td colspan="7" class="text-center">Đang tải dữ liệu...</td>
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="liveSessionDetailModalLabel">Chi Tiết Phiên Live</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Thông Tin Phiên</h6>
                        <p><strong>ID:</strong> <span id="live-session-id"></span></p>
                        <p><strong>Tên phiên:</strong> <span id="live-session-name"></span></p>
                        <p><strong>Bắt đầu:</strong> <span id="live-session-start"></span></p>
                        <p><strong>Kết thúc:</strong> <span id="live-session-end"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Hiệu Suất</h6>
                        <p><strong>Tổng đơn hàng:</strong> <span id="live-session-orders"></span></p>
                        <p><strong>Doanh thu:</strong> <span id="live-session-revenue"></span></p>
                        <p><strong>Thời gian:</strong> <span id="live-session-duration"></span></p>
                    </div>
                </div>

                <h6>Sản Phẩm Đã Bán</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="live-session-products-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>Sản Phẩm</th>
                                <th>Số Lượng</th>
                                <th>Doanh Thu</th>
                                <th>Tỷ Lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody id="live-session-products-body">
                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                        </tbody>
                    </table>
                </div>

                <h6 class="mt-4">Ghi Chú Phiên Live</h6>
                <div id="live-session-notes" class="p-3 border rounded">
                    <!-- Ghi chú sẽ được hiển thị ở đây -->
                    <p class="text-muted">Không có ghi chú.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load live session report data
    function loadLiveSessionData(startDate, endDate) {
        $.ajax({
            url: '/api/reports/live-session',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateLiveSessionSummary(response.data);
                    updateLiveSessionTable(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading live session data:', error);
            }
        });
    }

    // Update live session summary statistics
    function updateLiveSessionSummary(data) {
        const totalSessions = data.length;
        const totalRevenue = data.reduce((sum, session) => sum + session.revenue, 0);
        const totalOrders = data.reduce((sum, session) => sum + session.total_orders, 0);

        const avgOrdersPerSession = totalSessions > 0 ? (totalOrders / totalSessions).toFixed(2) : 0;
        const avgRevenuePerSession = totalSessions > 0 ? (totalRevenue / totalSessions) : 0;

        $('#total-live-sessions').text(totalSessions);
        $('#total-live-revenue').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalRevenue));
        $('#avg-orders-per-session').text(avgOrdersPerSession);
        $('#avg-revenue-per-session').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(avgRevenuePerSession));
    }

    // Update live session table
    function updateLiveSessionTable(data) {
        const tableBody = $('#live-session-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(session) {
            tableBody.append(`
                <tr>
                    <td>${session.id}</td>
                    <td>${session.name}</td>
                    <td>${formatDateTime(session.start_time)}</td>
                    <td>${formatDateTime(session.end_time)}</td>
                    <td>${session.total_orders}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(session.revenue)}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-live-session-detail" data-id="${session.id}">
                            <i class="fa fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#live-session-table')) {
            $('#live-session-table').DataTable().destroy();
        }

        $('#live-session-table').DataTable({
            "order": [[5, "desc"]], // Sort by revenue column by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Load live session detail
    function loadLiveSessionDetail(sessionId) {
        $.ajax({
            url: '/api/reports/live-session-detail',
            method: 'GET',
            data: {
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    showLiveSessionDetail(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading live session detail:', error);
            }
        });
    }

    // Show live session detail in modal
    function showLiveSessionDetail(sessionData) {
        const session = sessionData.session;

        $('#live-session-id').text(session.id);
        $('#live-session-name').text(session.name);
        $('#live-session-start').text(formatDateTime(session.start_time));
        $('#live-session-end').text(formatDateTime(session.end_time));
        $('#live-session-orders').text(session.total_orders);
        $('#live-session-revenue').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(session.revenue));

        // Calculate duration
        const start = new Date(session.start_time);
        const end = new Date(session.end_time);
        const durationMs = end - start;
        const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
        const durationMinutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));

        $('#live-session-duration').text(`${durationHours} giờ ${durationMinutes} phút`);

        // Display notes
        if (session.notes && session.notes.trim() !== '') {
            $('#live-session-notes').html(`<p>${session.notes.replace(/\n/g, '<br>')}</p>`);
        } else {
            $('#live-session-notes').html(`<p class="text-muted">Không có ghi chú.</p>`);
        }

        // Update products table
        updateLiveSessionProductsTable(sessionData.products);

        // Show modal
        $('#liveSessionDetailModal').modal('show');
    }

    // Update live session products table
    function updateLiveSessionProductsTable(data) {
        const tableBody = $('#live-session-products-body');
        tableBody.empty();

        if (!data || data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="4" class="text-center">Không có dữ liệu sản phẩm</td>
                </tr>
            `);
            return;
        }

        const totalRevenue = data.reduce((sum, item) => sum + item.revenue, 0);

        data.forEach(function(product) {
            const percentage = totalRevenue > 0 ? ((product.revenue / totalRevenue) * 100).toFixed(2) : 0;

            tableBody.append(`
                <tr>
                    <td>${product.product_name}</td>
                    <td>${product.quantity}</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.revenue)}</td>
                    <td>${percentage}%</td>
                </tr>
            `);
        });
    }

    // Format date and time for display
    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';

        const date = new Date(dateTimeString);
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
    loadLiveSessionData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#live-session-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadLiveSessionData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD')
        );
    });

    // View live session detail
    $(document).on('click', '.view-live-session-detail', function() {
        const sessionId = $(this).data('id');
        loadLiveSessionDetail(sessionId);
    });
});
</script>
