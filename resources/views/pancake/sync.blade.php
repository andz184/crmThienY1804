@extends('layouts.app')

@section('title', 'Đồng bộ Pancake')

@push('styles')
<style>
    .bg-purple {
        background-color: #6f42c1 !important;
    }
    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Đồng bộ dữ liệu từ Pancake</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Thêm nút hủy đồng bộ đang treo -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-warning" id="clearStuckSyncBtn">
                            <i class="fas fa-trash"></i> Hủy đồng bộ đang treo
                        </button>
                        <small class="text-muted ml-2">Chỉ sử dụng khi đồng bộ bị treo hoặc lỗi.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Đồng bộ đơn hàng</h5>
                                </div>
                                <div class="card-body">
                                    <form id="syncOrdersForm" action="{{ route('pancake.orders.sync') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="start_date">Từ ngày</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="end_date">Đến ngày</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control">
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="syncOrdersBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ đơn hàng
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="orderSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="orderSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="card-title mb-0">Đồng bộ tất cả đơn hàng</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Cảnh báo:</strong> Chức năng này sẽ đồng bộ <u>TẤT CẢ</u> các đơn hàng từ Pancake mà không giới hạn theo thời gian. Quá trình này có thể mất nhiều thời gian và tài nguyên hệ thống.
                                    </div>

                                    <form id="syncAllOrdersForm" action="{{ route('pancake.orders.sync-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" id="syncAllOrdersBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ tất cả đơn hàng
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="allOrdersSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="allOrdersSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Đồng bộ khách hàng</h5>
                                </div>
                                <div class="card-body">
                                    <form id="syncCustomersForm" action="{{ route('customers.sync') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="customer_limit">Số lượng khách hàng tối đa</label>
                                            <input type="number" name="limit" id="customer_limit" class="form-control" value="1000" min="1" max="5000">
                                        </div>
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="force" id="force_sync" class="form-check-input" value="1">
                                            <label for="force_sync" class="form-check-label">Đồng bộ lại dữ liệu đã tồn tại</label>
                                        </div>
                                        <button type="submit" class="btn btn-success" id="syncCustomersBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ khách hàng
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="customerSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="customerSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-purple text-white">
                                    <h5 class="card-title mb-0">Đồng bộ đơn hàng từ API External</h5>
                                </div>
                                <div class="card-body">
                                    <p>Đồng bộ toàn bộ đơn hàng từ API tại http://127.0.0.1:8000/orders</p>
                                    <form id="syncOrdersFromApiForm" action="{{ route('pancake.orders.sync-from-api') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-purple" id="syncOrdersFromApiBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ đơn hàng từ API
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="apiSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-purple" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="apiSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="card-title mb-0">Đồng bộ Danh mục Sản phẩm</h5>
                                </div>
                                <div class="card-body">
                                    <p>Đồng bộ danh mục sản phẩm từ Pancake vào hệ thống.</p>
                                    <form id="syncCategoriesForm" action="{{ route('admin.sync.categories') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning" id="syncCategoriesBtn">
                                            <i class="fas fa-tags"></i> Đồng bộ Danh mục
                                        </button>
                                    </form>
                                    <div class="progress mt-3 d-none" id="categorySyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="categorySyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">Lịch sử đồng bộ</h5>
                                </div>
                                <div class="card-body">
                                    <div id="syncLogs">
                                        <p>Đang tải dữ liệu...</p>
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

@section('scripts')
<script>
    $(document).ready(function() {
        // Order synchronization
        $('#syncOrdersForm').on('submit', function(e) {
            e.preventDefault();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            if (!startDate || !endDate) {
                alert('Vui lòng chọn đủ ngày bắt đầu và kết thúc!');
                return;
            }
            // Chuyển đổi sang timestamp
            const startTimestamp = Math.floor(new Date(startDate + 'T00:00:00').getTime() / 1000);
            const endTimestamp = Math.floor(new Date(endDate + 'T23:59:59').getTime() / 1000);
            // Tạo form data
            const formData = new FormData(this);
            formData.append('startDateTime', startTimestamp);
            formData.append('endDateTime', endTimestamp);
            // Gửi AJAX
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('input[name=_token]').val()
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Đã bắt đầu đồng bộ đơn hàng!');
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra!');
                }
            });
        });

        // Clear stuck synchronization
        $('#clearStuckSyncBtn').on('click', function() {
            Swal.fire({
                title: 'Xác nhận hủy đồng bộ',
                text: 'Bạn có chắc chắn muốn hủy quá trình đồng bộ đang chạy? Chỉ sử dụng khi đồng bộ bị treo hoặc lỗi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý, hủy đồng bộ!',
                cancelButtonText: 'Không, giữ nguyên'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Gọi API để xóa cache đồng bộ
                    $.ajax({
                        url: '{{ route("pancake.sync.cancel") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            force: true,
                            sync_info: window.currentSyncInfo
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Đã hủy!',
                                    'Đã hủy quá trình đồng bộ thành công.',
                                    'success'
                                );
                                // Tải lại trang để cập nhật trạng thái
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                Swal.fire(
                                    'Lỗi!',
                                    response.message || 'Không thể hủy quá trình đồng bộ.',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Lỗi!',
                                'Đã xảy ra lỗi khi hủy quá trình đồng bộ.',
                                'error'
                            );
                        }
                    });
                }
            });
        });

        // Theo dõi tiến trình đồng bộ đơn hàng
        function trackOrderSyncProgress() {
            const progressBar = $('#orderSyncProgress');
            const resultArea = $('#orderSyncResult');
            const progress = progressBar.find('.progress-bar');

            // Hiện thanh tiến trình
            progressBar.removeClass('d-none');

            // Lấy thông tin ngày đồng bộ từ form
            const syncDate = $('#start_date').val();

            // Khởi tạo biến đếm thời gian
            let elapsedTime = 0;
            const maxTime = 300; // tối đa 5 phút

            // Cập nhật tiến trình mỗi 2 giây
            const progressInterval = setInterval(function() {
                elapsedTime += 2;

                // Kiểm tra nếu đã chạy quá lâu
                if (elapsedTime > maxTime) {
                    clearInterval(progressInterval);
                    progress.css('width', '100%');
                    resultArea.html(`<div class="alert alert-warning mt-3">Đồng bộ đang được xử lý trong nền. Có thể mất nhiều thời gian để hoàn tất.</div>`);

                    // Ẩn thanh tiến trình sau 3 giây
                    setTimeout(function() {
                        progressBar.addClass('d-none');
                    }, 3000);
                    return;
                }

                // Gọi API để kiểm tra tiến trình
                $.ajax({
                    url: '{{ route("pancake.orders.sync-progress") }}',
                    type: 'GET',
                    data: { date: syncDate },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Cập nhật thanh tiến trình
                            const progressPercent = response.progress || (elapsedTime / maxTime * 100);
                            progress.css('width', progressPercent + '%');
                            progress.text(Math.round(progressPercent) + '%');

                            // Cập nhật thông tin
                            let statusHtml = `<div class="alert alert-info mt-3">
                                <strong>${response.message || 'Đang đồng bộ...'}</strong>`;

                            if (response.total_items > 0) {
                                statusHtml += `<p class="mb-0 mt-1">Đã xử lý: ${response.processed_items || 0}/${response.total_items || 0} đơn hàng</p>`;
                            }

                            statusHtml += `</div>`;
                            resultArea.html(statusHtml);

                            // Nếu hoàn tất
                            if (progressPercent >= 100 || !response.in_progress) {
                                clearInterval(progressInterval);

                                // Hiển thị kết quả cuối cùng
                                let resultHtml = `<div class="alert alert-success mt-3">
                                    <strong>Đồng bộ hoàn tất!</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Đơn hàng mới: ${response.new_orders || 0}</li>
                                        <li>Đơn hàng cập nhật: ${response.updated_orders || 0}</li>
                                        <li>Đơn hàng lỗi: ${response.failed_orders || 0}</li>
                                    </ul>
                                </div>`;

                                resultArea.html(resultHtml);

                                // Kích hoạt lại nút đồng bộ
                                $('#syncOrdersBtn').attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ đơn hàng');

                                // Làm mới lịch sử đồng bộ
                                loadSyncStatus();
                            }
                        } else {
                            // Có lỗi - hiển thị thông báo lỗi
                            clearInterval(progressInterval);
                            progress.css('width', '100%').removeClass('bg-primary').addClass('bg-danger');
                            resultArea.html(`<div class="alert alert-danger mt-3">${response.message || 'Đã xảy ra lỗi khi đồng bộ'}</div>`);

                            // Kích hoạt lại nút đồng bộ
                            $('#syncOrdersBtn').attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ đơn hàng');
                        }
                    },
                    error: function() {
                        // Lỗi kết nối, tiếp tục theo dõi
                        console.log('Không thể kết nối đến máy chủ để kiểm tra tiến trình. Thử lại sau...');
                    }
                });
            }, 2000);
        }

        // Customer synchronization
        $('#syncCustomersForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const btn = $('#syncCustomersBtn');
            const progressBar = $('#customerSyncProgress');
            const resultArea = $('#customerSyncResult');

            btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
            progressBar.removeClass('d-none');
            progressBar.find('.progress-bar').css('width', '10%');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    progressBar.find('.progress-bar').css('width', '100%');

                    if (response.success) {
                        resultArea.html(`<div class="alert alert-success mt-3">${response.message}</div>`);
                    } else {
                        resultArea.html(`<div class="alert alert-danger mt-3">${response.message}</div>`);
                    }

                    // Start checking sync status for customers
                    checkSyncStatus();
                },
                error: function(xhr) {
                    progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-success').addClass('bg-danger');

                    let errorMessage = 'Đã xảy ra lỗi khi đồng bộ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    resultArea.html(`<div class="alert alert-danger mt-3">${errorMessage}</div>`);
                },
                complete: function() {
                    btn.attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ khách hàng');

                    // Hide progress bar after 3 seconds
                    setTimeout(function() {
                        progressBar.addClass('d-none');
                    }, 3000);
                }
            });
        });

        // Load sync status on page load
        loadSyncStatus();

        function loadSyncStatus() {
            $.ajax({
                url: '{{ route("pancake.sync.status") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateSyncLogsDisplay(response);
                    }
                },
                error: function() {
                    $('#syncLogs').html('<div class="alert alert-warning">Không thể tải thông tin đồng bộ</div>');
                }
            });
        }

        function updateSyncLogsDisplay(data) {
            if (!data.last_logs || data.last_logs.length === 0) {
                $('#syncLogs').html('<div class="alert alert-info">Chưa có thông tin đồng bộ nào</div>');
                return;
            }

            let html = '<div class="sync-status mb-3">';
            if (data.is_running) {
                html += '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...</div>';
            } else {
                const lastSyncDate = data.last_sync ? new Date(data.last_sync * 1000).toLocaleString() : 'Chưa có';
                html += `<div class="alert alert-info">Lần đồng bộ cuối: ${lastSyncDate}</div>`;
            }
            html += '</div>';

            html += '<div class="log-entries" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 10px; border-radius: 5px;">';

            data.last_logs.forEach(function(log) {
                html += `<div class="log-entry">${log}</div>`;
            });

            html += '</div>';

            $('#syncLogs').html(html);
        }

        // Customer sync status check
        function checkSyncStatus() {
            let statusCheckInterval = setInterval(function() {
                $.ajax({
                    url: '{{ route("customers.sync-progress") }}',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.running) {
                            const percent = Math.round((response.processed / response.total) * 100);
                            $('#customerSyncProgress').find('.progress-bar').css('width', percent + '%');
                            $('#customerSyncResult').html(`<div class="alert alert-info mt-3">Đã đồng bộ ${response.processed}/${response.total} khách hàng (${percent}%)</div>`);
                        } else {
                            clearInterval(statusCheckInterval);
                            loadSyncStatus();
                        }
                    },
                    error: function() {
                        clearInterval(statusCheckInterval);
                    }
                });
            }, 2000);

        // API synchronization
        $('#syncOrdersFromApiForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const btn = $('#syncOrdersFromApiBtn');
            const progressBar = $('#apiSyncProgress');
            const resultArea = $('#apiSyncResult');

            btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
            progressBar.removeClass('d-none');
            progressBar.find('.progress-bar').css('width', '10%');

            // Hiển thị thông báo cho người dùng về thời gian chờ đợi
            resultArea.html(`
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Quá trình đồng bộ đơn hàng từ Pancake có thể mất nhiều thời gian (5 phút hoặc hơn).
                    Vui lòng kiên nhẫn chờ đợi và không đóng trình duyệt.
                </div>
            `);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                timeout: 300000, // Tăng timeout lên 5 phút (300,000ms)
                success: function(response) {
                    progressBar.find('.progress-bar').css('width', '100%');

                    if (response.success) {
                        resultArea.html(`
                            <div class="alert alert-success mt-3">
                                <strong>${response.message}</strong><br>
                                <ul>
                                    <li>Tổng số đơn: ${response.stats.total}</li>
                                    <li>Đã tạo mới: ${response.stats.created}</li>
                                    <li>Đã cập nhật: ${response.stats.updated}</li>
                                    <li>Bỏ qua: ${response.stats.skipped}</li>
                                </ul>
                            </div>
                        `);
                    } else {
                        progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-purple').addClass('bg-danger');
                        resultArea.html(`<div class="alert alert-danger mt-3">${response.message}</div>`);
                    }

                    loadSyncStatus();
                },
                error: function(xhr, status, error) {
                    progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-purple').addClass('bg-danger');

                    let errorMessage = 'Đã xảy ra lỗi khi đồng bộ';
                    if (status === 'timeout') {
                        errorMessage = 'Quá trình đồng bộ đã hết thời gian chờ. Việc đồng bộ có thể vẫn đang tiếp tục trong hệ thống.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    resultArea.html(`<div class="alert alert-danger mt-3">${errorMessage}</div>`);
                },
                complete: function() {
                    btn.attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ đơn hàng từ API');

                    // Hide progress bar after longer period
                    setTimeout(function() {
                        progressBar.addClass('d-none');
                    }, 10000); // Tăng thời gian lên 10 giây
                }
            });
        });

        // Sync All Orders function
        $('#syncAllOrdersForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const btn = $('#syncAllOrdersBtn');
            const progressBar = $('#allOrdersSyncProgress');
            const resultArea = $('#allOrdersSyncResult');

            Swal.fire({
                title: 'Xác nhận đồng bộ tất cả đơn hàng',
                text: 'Quá trình này có thể mất NHIỀU thời gian và tài nguyên hệ thống. Bạn có chắc chắn muốn tiếp tục?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Đồng ý, tiếp tục!',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
                    progressBar.removeClass('d-none');
                    progressBar.find('.progress-bar').css('width', '10%');

                    // Hiển thị thông báo cho người dùng về thời gian chờ đợi
                    resultArea.html(`
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> Quá trình đồng bộ tất cả đơn hàng có thể mất <strong>RẤT NHIỀU THỜI GIAN</strong> (từ vài phút đến vài giờ).
                            Vui lòng kiên nhẫn chờ đợi và không đóng trình duyệt.
                        </div>
                    `);

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        timeout: 300000, // 5 phút
                        success: function(response) {
                            if (response.success) {
                                resultArea.html(`
                                    <div class="alert alert-success mt-3">
                                        <strong>${response.message}</strong>
                                    </div>
                                `);
                                
                                // Store the sync_info
                                if (response.sync_info) {
                                    window.currentSyncInfo = response.sync_info;
                                }
                                
                                // Check if we need to process more pages
                                if (response.continue && response.next_page) {
                                    resultArea.append(`
                                        <div class="sync-status-info alert alert-info mt-3">
                                            <i class="fas fa-spinner fa-spin"></i> Đang đồng bộ dữ liệu...
                                            <p class="mb-0 mt-1">Đang xử lý trang ${response.next_page}/${response.total_pages}</p>
                                        </div>
                                    `);
                                    
                                    // Process next page
                                    processNextPage(response.next_page, response.sync_info);
                                } else {
                                    // No more pages, start tracking progress
                                    trackAllOrdersSyncProgress();
                                }
                            } else {
                                progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-danger').addClass('bg-danger');
                                resultArea.html(`<div class="alert alert-danger mt-3">${response.message}</div>`);
                            }
                        },
                        error: function(xhr, status, error) {
                            progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-danger').addClass('bg-danger');

                            let errorMessage = 'Đã xảy ra lỗi khi đồng bộ';
                            if (status === 'timeout') {
                                errorMessage = 'Quá trình đồng bộ đã hết thời gian chờ. Việc đồng bộ có thể vẫn đang tiếp tục trong hệ thống.';
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            resultArea.html(`<div class="alert alert-danger mt-3">${errorMessage}</div>`);
                        },
                        complete: function() {
                            btn.attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ tất cả đơn hàng');
                            loadSyncStatus();
                        }
                    });
                }
            });
        });

        // Theo dõi tiến trình đồng bộ tất cả đơn hàng
        function trackAllOrdersSyncProgress() {
            const progressBar = $('#allOrdersSyncProgress');
            const resultArea = $('#allOrdersSyncResult');
            const progress = progressBar.find('.progress-bar');

            // Hiện thanh tiến trình
            progressBar.removeClass('d-none');

            // Biến đếm thời gian
            let elapsedTime = 0;
            const maxTime = 7200; // 2 giờ (tối đa)
            let completed = false;

            // Thêm thông tin đang chạy
            resultArea.append(`
                <div class="sync-status-info alert alert-info mt-3">
                    <i class="fas fa-spinner fa-spin"></i> Đang đồng bộ dữ liệu...
                    <span class="elapsed-time">00:00:00</span>
                </div>
            `);

            // Hiển thị thời gian đã trôi qua
            const timeDisplay = $('.elapsed-time');

            // Cập nhật tiến trình mỗi 5 giây
            const progressInterval = setInterval(function() {
                if (completed) {
                    clearInterval(progressInterval);
                    return;
                }

                elapsedTime += 5;

                // Hiển thị thời gian (format HH:MM:SS)
                const hours = Math.floor(elapsedTime / 3600);
                const minutes = Math.floor((elapsedTime % 3600) / 60);
                const seconds = elapsedTime % 60;
                timeDisplay.text(
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
                );

                // Gọi API để kiểm tra tiến trình
                $.ajax({
                    url: '{{ route("pancake.orders.sync-all-progress") }}',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Cập nhật thanh tiến trình
                            const progressPercent = response.progress || (elapsedTime / maxTime * 100);
                            progress.css('width', progressPercent + '%');
                            progress.text(Math.round(progressPercent) + '%');

                            // Cập nhật thông tin trạng thái
                            const stats = response.stats || {};

                            // Store sync_info if available to pass to next requests
                            if (response.sync_info) {
                                window.currentSyncInfo = response.sync_info;
                            }

                            // Nếu đã hoàn thành
                            if (response.status === 'completed' || progressPercent >= 100) {
                                completed = true;
                                progress.css('width', '100%');

                                // Cập nhật kết quả cuối cùng
                                $('.sync-status-info').remove();
                                resultArea.html(`
                                    <div class="alert alert-success mt-3">
                                        <strong>Đồng bộ hoàn tất!</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Tổng số đơn: ${stats.total || 0}</li>
                                            <li>Đơn hàng mới: ${stats.created || 0}</li>
                                            <li>Đơn hàng cập nhật: ${stats.updated || 0}</li>
                                            <li>Đơn hàng bỏ qua/lỗi: ${stats.skipped || 0}</li>
                                        </ul>
                                    </div>
                                `);

                                clearInterval(progressInterval);
                                
                                // Clear the sync info
                                window.currentSyncInfo = null;
                            }
                            // Nếu đang chạy, cập nhật thông tin
                            else if (response.in_progress && stats.total > 0) {
                                $('.sync-status-info').html(`
                                    <i class="fas fa-spinner fa-spin"></i> Đang đồng bộ dữ liệu...
                                    <span class="elapsed-time">${timeDisplay.text()}</span>
                                    <p class="mb-0 mt-1">Đã xử lý: ${stats.created + stats.updated || 0} đơn (${stats.created || 0} mới, ${stats.updated || 0} cập nhật)</p>
                                `);
                            }
                        }
                    },
                    error: function() {
                        // Tiếp tục theo dõi ngay cả khi có lỗi kết nối
                        console.log('Không thể kết nối đến máy chủ để kiểm tra tiến trình');
                    }
                });

                // Nếu quá lâu mà chưa hoàn thành
                if (elapsedTime > maxTime && !completed) {
                    $('.sync-status-info').html(`
                        <i class="fas fa-clock"></i> Đồng bộ đang được xử lý trong nền...
                        <span class="elapsed-time">${timeDisplay.text()}</span>
                        <p class="mb-0 mt-1">Quá trình đồng bộ mất nhiều thời gian hơn dự kiến nhưng vẫn đang chạy.</p>
                    `);
                }
            }, 5000); // 5 giây cập nhật một lần
        }

        // Function to process the next page of synchronization
        function processNextPage(pageNumber, syncInfo) {
            // Prepare data
            let postData = {
                _token: '{{ csrf_token() }}',
                page_number: pageNumber
            };
            
            // Add sync_info if available
            if (syncInfo) {
                postData.sync_info = syncInfo;
            } else if (window.currentSyncInfo) {
                postData.sync_info = window.currentSyncInfo;
            }
            
            // Call API to process next page
            $.ajax({
                url: '{{ route("pancake.process-next-page") }}',
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update progress
                        const progressBar = $('#allOrdersSyncProgress');
                        const resultArea = $('#allOrdersSyncResult');
                        const progressPercent = response.progress || Math.round((pageNumber / response.total_pages) * 100);
                        
                        progressBar.find('.progress-bar').css('width', progressPercent + '%');
                        progressBar.find('.progress-bar').text(Math.round(progressPercent) + '%');
                        
                        // Save sync_info for future requests
                        if (response.sync_info) {
                            window.currentSyncInfo = response.sync_info;
                        }
                        
                        // Update status info
                        $('.sync-status-info').html(`
                            <i class="fas fa-spinner fa-spin"></i> Đang đồng bộ dữ liệu...
                            <p class="mb-0 mt-1">Đang xử lý trang ${pageNumber}/${response.total_pages}</p>
                            <p class="mb-0">Đã tạo: ${response.stats.created || 0}, Cập nhật: ${response.stats.updated || 0}</p>
                        `);
                        
                        // Process next page if needed
                        if (response.continue && response.next_page) {
                            processNextPage(response.next_page, response.sync_info);
                        } else {
                            // Completed all pages
                            progressBar.find('.progress-bar').css('width', '100%');
                            
                            $('.sync-status-info').remove();
                            resultArea.html(`
                                <div class="alert alert-success mt-3">
                                    <strong>Đồng bộ hoàn tất!</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Tổng số đơn: ${response.total_entries || 0}</li>
                                        <li>Đơn hàng mới: ${response.stats.total_created || 0}</li>
                                        <li>Đơn hàng cập nhật: ${response.stats.total_updated || 0}</li>
                                        <li>Đơn hàng bỏ qua/lỗi: ${response.stats.errors_count || 0}</li>
                                    </ul>
                                </div>
                            `);
                            
                            // Clear the sync info
                            window.currentSyncInfo = null;
                        }
                    } else {
                        // Error - show error message
                        $('.sync-status-info').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> Lỗi: ${response.message}
                                <p class="mb-0 mt-1">Đồng bộ đã dừng tại trang ${pageNumber}.</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    // Connection error
                    let errorMsg = 'Lỗi kết nối đến máy chủ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    $('.sync-status-info').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> ${errorMsg}
                            <p class="mb-0 mt-1">Đồng bộ đã dừng tại trang ${pageNumber}.</p>
                        </div>
                    `);
                }
            });
        });

        // Sync Categories
        $('#syncCategoriesForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const button = $('#syncCategoriesBtn');
            const progress = $('#categorySyncProgress');
            const progressBar = progress.find('.progress-bar');
            const resultDiv = $('#categorySyncResult');

            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
            progress.removeClass('d-none');
            progressBar.css('width', '0%').attr('aria-valuenow', 0).text('0%');
            resultDiv.html('<p class="text-info">Đang xử lý yêu cầu đồng bộ danh mục...</p>');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).text('100%');
                    if (response.success) {
                        resultDiv.html('<p class="alert alert-success">' + response.message + '</p>');
                        if (response.stats) {
                            let statsHtml = '<ul class="list-group mt-2">';
                            statsHtml += '<li class="list-group-item">Tổng danh mục lấy về: ' + response.stats.total_fetched + '</li>';
                            statsHtml += '<li class="list-group-item text-success">Tạo mới: ' + response.stats.created + '</li>';
                            statsHtml += '<li class="list-group-item text-info">Cập nhật: ' + response.stats.updated + '</li>';
                            statsHtml += '<li class="list-group-item text-danger">Lỗi: ' + response.stats.errors + '</li>';
                            if(response.stats.error_messages && response.stats.error_messages.length > 0){
                                statsHtml += '<li class="list-group-item text-danger">Chi tiết lỗi: <ul>';
                                response.stats.error_messages.forEach(function(msg) {
                                    statsHtml += '<li>' + msg + '</li>';
                                });
                                statsHtml += '</ul></li>';
                            }
                            statsHtml += '</ul>';
                            resultDiv.append(statsHtml);
                        }
                    } else {
                        resultDiv.html('<p class="alert alert-danger">' + response.message + '</p>');
                        progressBar.addClass('bg-danger');
                    }
                },
                error: function(xhr, status, error) {
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).addClass('bg-danger').text('Lỗi');
                    let errorMsg = 'Lỗi AJAX: ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    resultDiv.html('<p class="alert alert-danger">' + errorMsg + '</p>');
                },
                complete: function() {
                    button.prop('disabled', false).html('<i class="fas fa-tags"></i> Đồng bộ Danh mục');
                    // setTimeout(function(){ progress.addClass('d-none'); }, 3000);
                }
            });
        });
    });
</script>
@endsection
