<!-- Live Session Details Modal -->
<div class="modal fade" id="liveSessionDetailModal" tabindex="-1" role="dialog" aria-labelledby="liveSessionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="liveSessionDetailModalLabel">
                    <i class="fas fa-video mr-2"></i>
                    Chi Tiết Phiên Live
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center loading-indicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải dữ liệu chi tiết...</p>
                </div>
                
                <div class="session-detail-content" style="display: none;">
                    <!-- Summary Stats -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-info-circle mr-2"></i>Thông Tin Phiên</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <th width="40%">ID:</th>
                                                <td><span id="detail-session-id" class="badge badge-light"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Tên phiên:</th>
                                                <td><span id="detail-session-name" class="font-weight-bold text-primary"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Ngày diễn ra:</th>
                                                <td><span id="detail-session-date"></span></td>
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
                                                <td><span id="detail-session-orders" class="badge badge-info"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Doanh thu:</th>
                                                <td><span id="detail-session-revenue" class="font-weight-bold text-success"></span></td>
                                            </tr>
                                            <tr>
                                                <th>Tỷ lệ chốt đơn:</th>
                                                <td>
                                                    <div class="progress" style="height: 15px;">
                                                        <div id="detail-conversion-rate" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Performance Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-left-info">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tổng Khách Hàng</div>
                                            <div id="detail-total-customers" class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-success">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Doanh Thu/KH</div>
                                            <div id="detail-revenue-per-customer" class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-warning">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Tỷ Lệ Hoàn</div>
                                            <div id="detail-cancellation-rate" class="h5 mb-0 font-weight-bold text-gray-800">0%</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-undo-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-danger">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Giá Trị TB/Đơn</div>
                                            <div id="detail-avg-order-value" class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-chart-pie mr-2"></i>Tỷ lệ đơn hàng</h6>
                                    <canvas id="detail-order-status-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-chart-bar mr-2"></i>Phân tích sản phẩm</h6>
                                    <canvas id="detail-product-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Analysis -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-box mr-2"></i>Phân Tích Sản Phẩm</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover" id="detail-products-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th>Mã SP</th>
                                                    <th>Số lượng</th>
                                                    <th>Doanh Thu</th>
                                                    <th>Tỷ Lệ</th>
                                                    <th>Khách Hàng</th>
                                                    <th>Hiệu Suất</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-products-tbody">
                                                <!-- Dữ liệu sản phẩm sẽ được thêm vào đây -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-shopping-cart mr-2"></i>Danh Sách Đơn Hàng</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover" id="detail-orders-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Khách hàng</th>
                                                    <th>SĐT</th>
                                                    <th>Sản phẩm</th>
                                                    <th>Tổng tiền</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-orders-tbody">
                                                <!-- Dữ liệu đơn hàng sẽ được thêm vào đây -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="export-detail-btn" href="#" class="btn btn-primary">
                    <i class="fas fa-file-export mr-1"></i> Xuất CSV
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to load detail data -->
<script>
$(document).ready(function() {
    // Charts references
    let orderStatusChart = null;
    let productChart = null;
    
        // Handle detail button click    $(document).on('click', '.view-live-session-detail', function() {        const sessionId = $(this).data('id');                // Show loading indicator        $('.loading-indicator').show();        $('.session-detail-content').hide();                console.log('Loading live session details for ID:', sessionId);                // Load data via AJAX        $.ajax({            url: '/api/reports/live-session-detail',            method: 'GET',            data: { session_id: sessionId },            success: function(response) {                console.log('API response:', response);                if (response.success) {                    updateSessionDetailModal(response.data);                } else {                    alert('Lỗi: ' + response.message);                }                                // Hide loading indicator                $('.loading-indicator').hide();                $('.session-detail-content').show();            },            error: function(error) {                console.error('Error loading session detail:', error);                alert('Có lỗi xảy ra khi tải dữ liệu chi tiết: ' + (error.responseJSON ? error.responseJSON.message : error.statusText));                                // Hide loading indicator                $('.loading-indicator').hide();            }        });                // Update export button URL        $('#export-detail-btn').attr('href', '/api/reports/live-session-export?session_id=' + sessionId);    });
    
    // Function to update modal with session detail data
        function updateSessionDetailModal(data) {        console.log('Updating session detail modal with data:', data);                if (!data || !data.session) {            console.error('Invalid data structure received:', data);            alert('Dữ liệu không hợp lệ. Vui lòng thử lại sau.');            return;        }                const session = data.session;        const orders = data.orders || [];        const products = data.products || [];                console.log('Session details:', session);        console.log('Orders count:', orders.length);        console.log('Products count:', products.length);                // Update session info        $('#detail-session-id').text(session.id);        $('#detail-session-name').text(session.name);        $('#detail-session-date').text(formatDate(session.session_date));        $('#detail-session-orders').text(session.total_orders);        $('#detail-session-revenue').text(formatCurrency(session.revenue));                // Update additional performance metrics        $('#detail-total-customers').text(session.total_customers);        $('#detail-cancellation-rate').text(formatPercent(session.cancellation_rate));                // Calculate and update revenue per customer        const revenuePerCustomer = session.total_customers > 0 ?             session.revenue / session.total_customers : 0;        $('#detail-revenue-per-customer').text(formatCurrency(revenuePerCustomer));                // Calculate and update average order value        const avgOrderValue = session.successful_orders > 0 ?             session.revenue / session.successful_orders : 0;        $('#detail-avg-order-value').text(formatCurrency(avgOrderValue));                // Update conversion rate progress bar        const conversionRate = session.conversion_rate || 0;        $('#detail-conversion-rate')            .css('width', Math.min(100, conversionRate) + '%')            .attr('aria-valuenow', conversionRate)            .text(formatPercent(conversionRate));                // Clear existing tables        $('#detail-orders-tbody').empty();        $('#detail-products-tbody').empty();                // Order Status Chart        updateOrderStatusChart(session);                // Product Analysis Chart        updateProductChart(products);                // Populate products table        if (products && products.length > 0) {
            const totalRevenue = products.reduce((sum, product) => sum + product.total_revenue, 0);
            
            products.forEach(function(product) {
                const percentage = totalRevenue > 0 ? (product.total_revenue / totalRevenue * 100) : 0;
                
                // Create performance indicator
                const performanceRatio = product.customer_count > 0 ? 
                    product.total_revenue / (product.customer_count * avgOrderValue) : 0;
                    
                let performanceClass = 'bg-warning';
                let performanceIcon = 'fa-minus';
                
                if (performanceRatio > 1.2) {
                    performanceClass = 'bg-success';
                    performanceIcon = 'fa-arrow-up';
                } else if (performanceRatio < 0.8) {
                    performanceClass = 'bg-danger';
                    performanceIcon = 'fa-arrow-down';
                }
                
                $('#detail-products-tbody').append(`
                    <tr>
                        <td><strong>${product.name}</strong></td>
                        <td>${product.sku || 'N/A'}</td>
                        <td>${product.total_quantity}</td>
                        <td>${formatCurrency(product.total_revenue)}</td>
                        <td>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                    style="width: ${Math.min(100, percentage)}%;" 
                                    aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small>${formatPercent(percentage)}</small>
                        </td>
                        <td>${product.customer_count}</td>
                        <td>
                            <span class="badge ${performanceClass}">
                                <i class="fas ${performanceIcon}"></i> ${formatPercent(performanceRatio * 100)}
                            </span>
                        </td>
                    </tr>
                `);
            });
        } else {
            $('#detail-products-tbody').append(`
                <tr>
                    <td colspan="7" class="text-center">Không có dữ liệu sản phẩm</td>
                </tr>
            `);
        }
        
        // Populate orders table        if (orders && orders.length > 0) {            console.log('Populating orders table with', orders.length, 'orders');                        orders.forEach(function(order) {                console.log('Processing order:', order);
                let productsList = '';
                
                if (order.items && order.items.length > 0) {
                    const productNames = order.items.map(function(item) {
                        return `${item.quantity}x ${item.product ? item.product.name : 'Sản phẩm không xác định'}`;
                    });
                    productsList = productNames.join(', ');
                }
                
                const customerName = order.customer ? order.customer.name : 'Không xác định';
                const customerPhone = order.customer ? order.customer.phone : '';
                
                                // Get status badge color based on Pancake status first, then fall back to regular status                let statusBadgeClass = 'badge-secondary';                                // Check Pancake status first                if (order.pancake_status === 'completed' || order.pancake_status === 'delivered') {                    statusBadgeClass = 'badge-success';                } else if (order.pancake_status === 'cancelled') {                    statusBadgeClass = 'badge-danger';                } else if (order.pancake_status === 'processing') {                    statusBadgeClass = 'badge-info';                } else if (order.pancake_status === 'new' || order.pancake_status === 'pending') {                    statusBadgeClass = 'badge-warning';                }                // Fall back to regular status if no Pancake status or need additional checks                else if (order.status === 'thanh_cong' || order.status === 'hoan_thanh' ||                     order.status === 'da_giao' || order.status === 'da_nhan' ||                     order.status === 'da_thu_tien') {                    statusBadgeClass = 'badge-success';                } else if (order.status === 'huy' || order.status === 'da_huy') {                    statusBadgeClass = 'badge-danger';                } else if (order.status === 'dang_xu_ly') {                    statusBadgeClass = 'badge-info';                } else if (order.status === 'moi') {                    statusBadgeClass = 'badge-warning';                }
                
                $('#detail-orders-tbody').append(`
                    <tr>
                        <td>${order.id}</td>
                        <td>${customerName}</td>
                        <td>${customerPhone}</td>
                        <td>${productsList}</td>
                        <td>${formatCurrency(order.total_value)}</td>
                        <td><span class="badge ${statusBadgeClass}">${order.pancake_status ? formatStatus(order.pancake_status) : formatStatus(order.status)}</span></td>
                        <td>${formatDateTime(order.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info view-order-btn" data-id="${order.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
            
            // Initialize sorting for order table
            if ($.fn.DataTable.isDataTable('#detail-orders-table')) {
                $('#detail-orders-table').DataTable().destroy();
            }
            
            $('#detail-orders-table').DataTable({
                "pageLength": 5,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
                }
            });
            
        } else {
            $('#detail-orders-tbody').append(`
                <tr>
                    <td colspan="8" class="text-center">Không có dữ liệu đơn hàng</td>
                </tr>
            `);
        }
    }
    
    // Update Order Status Chart
    function updateOrderStatusChart(session) {
        if (orderStatusChart) {
            orderStatusChart.destroy();
        }
        
        const ctx = document.getElementById('detail-order-status-chart').getContext('2d');
        orderStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Thành công', 'Đã hủy', 'Đang xử lý'],
                datasets: [{
                    data: [
                        session.successful_orders || 0,
                        session.canceled_orders || 0,
                        (session.total_orders || 0) - (session.successful_orders || 0) - (session.canceled_orders || 0)
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(231, 76, 60, 0.8)',
                        'rgba(52, 152, 219, 0.8)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(231, 76, 60, 1)',
                        'rgba(52, 152, 219, 1)'
                    ]
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
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update Product Chart
    function updateProductChart(products) {
        if (productChart) {
            productChart.destroy();
        }
        
        if (!products || products.length === 0) return;
        
        // Sort products by revenue
        const sortedProducts = [...products].sort((a, b) => b.total_revenue - a.total_revenue);
        
        // Get top 5 products
        const topProducts = sortedProducts.slice(0, 5);
        
        const ctx = document.getElementById('detail-product-chart').getContext('2d');
        productChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topProducts.map(p => p.name),
                datasets: [{
                    label: 'Doanh thu',
                    data: topProducts.map(p => p.total_revenue),
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(241, 196, 15, 0.8)',
                        'rgba(231, 76, 60, 0.8)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)',
                        'rgba(155, 89, 182, 1)',
                        'rgba(241, 196, 15, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Doanh thu: ${formatCurrency(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                }
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(1) + 'K';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }
    
        // Handle view order button click    $(document).on('click', '.view-order-btn', function() {        const orderId = $(this).data('id');                // Show order details in a modal instead of redirecting        loadOrderDetail(orderId);    });
    
    // Helper functions
    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' })
            .format(value || 0)
            .replace('₫', 'VND');
    }
    
    function formatPercent(value) {
        return (Math.round((value || 0) * 10) / 10) + '%';
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return moment(dateString).format('DD/MM/YYYY');
    }
    
    function formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        return moment(dateTimeString).format('DD/MM/YYYY HH:mm');
    }
    
        function formatStatus(status) {        // Check if we're dealing with a Pancake status        if (status === 'completed' || status === 'delivered' || status === 'cancelled' ||             status === 'processing' || status === 'new' || status === 'pending') {            const pancakeStatusMap = {                'completed': 'Hoàn thành',                'delivered': 'Đã giao',                'cancelled': 'Đã hủy',                'processing': 'Đang xử lý',                'new': 'Mới',                'pending': 'Chờ xử lý'            };            return pancakeStatusMap[status] || status;        }                // Regular status mapping        const statusMap = {            'moi': 'Mới',            'dang_xu_ly': 'Đang xử lý',            'da_giao': 'Đã giao',            'hoan_thanh': 'Hoàn thành',            'thanh_cong': 'Thành công',            'huy': 'Hủy',            'da_huy': 'Đã hủy'        };                return statusMap[status] || status;    }
});
</script> 