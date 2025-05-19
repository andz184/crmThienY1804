<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Báo Cáo Theo Chiến Dịch (Bài Post)</h5>
                <div class="input-group date-range-container" style="width: 300px;">
                    <input type="text" class="form-control date-picker" id="campaign-date-range">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="campaign-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID Bài Post</th>
                                <th>Tiêu Đề</th>
                                <th>Ngày Đăng</th>
                                <th>Tổng Đơn Hàng</th>
                                <th>Tỷ Lệ Chốt</th>
                                <th>Doanh Thu</th>
                                <th>Chi Tiết</th>
                            </tr>
                        </thead>
                        <tbody id="campaign-table-body">
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

<!-- Campaign Details Modal -->
<div class="modal fade" id="campaignDetailModal" tabindex="-1" role="dialog" aria-labelledby="campaignDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campaignDetailModalLabel">Chi Tiết Chiến Dịch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Thông Tin Chiến Dịch</h6>
                        <p><strong>ID:</strong> <span id="campaign-id"></span></p>
                        <p><strong>Tiêu đề:</strong> <span id="campaign-title"></span></p>
                        <p><strong>Ngày đăng:</strong> <span id="campaign-date"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Hiệu Suất</h6>
                        <p><strong>Tổng đơn hàng:</strong> <span id="campaign-orders"></span></p>
                        <p><strong>Tỷ lệ chốt đơn:</strong> <span id="campaign-rate"></span></p>
                        <p><strong>Doanh thu:</strong> <span id="campaign-revenue"></span></p>
                    </div>
                </div>

                <h6>Sản Phẩm Bán Chạy</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="campaign-products-table">
                        <thead class="thead-dark">
                            <tr>
                                <th>Sản Phẩm</th>
                                <th>Số Lượng</th>
                                <th>Doanh Thu</th>
                                <th>Tỷ Lệ (%)</th>
                            </tr>
                        </thead>
                        <tbody id="campaign-products-body">
                            <!-- Dữ liệu sẽ được thêm từ AJAX -->
                        </tbody>
                    </table>
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
    // Load campaign report data
    function loadCampaignData(startDate, endDate) {
        $.ajax({
            url: '/api/reports/campaign',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                if (response.success) {
                    updateCampaignTable(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading campaign data:', error);
            }
        });
    }

    // Update campaign table
    function updateCampaignTable(data) {
        const tableBody = $('#campaign-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">Không có dữ liệu</td>
                </tr>
            `);
            return;
        }

        data.forEach(function(campaign) {
            tableBody.append(`
                <tr>
                    <td>${campaign.post_id}</td>
                    <td>${campaign.title}</td>
                    <td>${formatDate(campaign.created_at)}</td>
                    <td>${campaign.total_orders}</td>
                    <td>${campaign.conversion_rate.toFixed(2)}%</td>
                    <td>${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(campaign.revenue)}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-campaign-detail" data-id="${campaign.post_id}">
                            <i class="fa fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `);
        });

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#campaign-table')) {
            $('#campaign-table').DataTable().destroy();
        }

        $('#campaign-table').DataTable({
            "order": [[5, "desc"]], // Sort by revenue column by default
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Vietnamese.json"
            }
        });
    }

    // Load campaign detail
    function loadCampaignDetail(postId) {
        $.ajax({
            url: '/api/reports/campaign',
            method: 'GET',
            data: {
                post_id: postId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const campaign = response.data[0];
                    showCampaignDetail(campaign);
                }
            },
            error: function(error) {
                console.error('Error loading campaign detail:', error);
            }
        });
    }

    // Show campaign detail in modal
    function showCampaignDetail(campaign) {
        $('#campaign-id').text(campaign.post_id);
        $('#campaign-title').text(campaign.title);
        $('#campaign-date').text(formatDate(campaign.created_at));
        $('#campaign-orders').text(campaign.total_orders);
        $('#campaign-rate').text(campaign.conversion_rate.toFixed(2) + '%');
        $('#campaign-revenue').text(new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(campaign.revenue));

        // Load products for this campaign
        loadCampaignProducts(campaign.post_id);

        // Show modal
        $('#campaignDetailModal').modal('show');
    }

    // Load products sold in a campaign
    function loadCampaignProducts(postId) {
        $.ajax({
            url: '/api/reports/campaign-products',
            method: 'GET',
            data: {
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    updateCampaignProductsTable(response.data);
                }
            },
            error: function(error) {
                console.error('Error loading campaign products:', error);
            }
        });
    }

    // Update campaign products table
    function updateCampaignProductsTable(data) {
        const tableBody = $('#campaign-products-body');
        tableBody.empty();

        if (data.length === 0) {
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

    // Format date for display
    function formatDate(dateString) {
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
    loadCampaignData(
        thirtyDaysAgo.toISOString().split('T')[0],
        today.toISOString().split('T')[0]
    );

    // Update on date range changes
    $('#campaign-date-range').on('apply.daterangepicker', function(ev, picker) {
        loadCampaignData(
            picker.startDate.format('YYYY-MM-DD'),
            picker.endDate.format('YYYY-MM-DD')
        );
    });

    // View campaign detail
    $(document).on('click', '.view-campaign-detail', function() {
        const postId = $(this).data('id');
        loadCampaignDetail(postId);
    });
});
</script>
