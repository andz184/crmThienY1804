@extends('adminlte::page')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Quản lý Đơn hàng</h2>
                    <div>
                        <a href="{{ route('orders.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tạo đơn hàng mới
                        </a>
                        <button id="syncWithPancake" class="btn btn-info ml-2">
                            <i class="fas fa-sync"></i> Đồng bộ từ Pancake
                        </button>
                        <button id="syncAllToPancake" class="btn btn-success ml-2">
                            <i class="fas fa-upload"></i> Đồng bộ lên Pancake
                        </button>
                    </div>
                </div>

                <!-- Webhook Info Section -->
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-link"></i> Webhook URL</h5>
                            <p class="text-muted mb-0 small">Cấu hình webhooks trong Pancake để nhận đơn hàng và khách hàng tự động</p>
                        </div>
                        <div class="input-group" style="max-width: 500px;">
                            <input type="text" id="webhookUrl" class="form-control" value="{{ url('/api/webhooks/pancake/order') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copyWebhook">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <a href="{{ route('admin.pancake.webhooks') }}" class="btn btn-info">
                                    <i class="fas fa-cog"></i> Cấu hình
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Cấu hình webhook để đồng bộ đơn hàng và khách hàng tự động. Truy cập
                            <a href="{{ route('admin.pancake.webhooks') }}">trang cấu hình webhook</a>
                            để xem tất cả các webhook có sẵn.
                        </small>
                    </div>
                </div>

                <!-- Webhook Configuration Help -->
                <div class="collapse" id="webhookInfo">
                    <div class="card-body bg-light border-top">
                        <h6><i class="fas fa-info-circle text-info"></i> Hướng dẫn cấu hình webhook trong Pancake</h6>
                        <ol class="pl-3 mb-0">
                            <li>Đăng nhập vào tài khoản quản trị Pancake</li>
                            <li>Vào phần <strong>Cài đặt</strong> &raquo; <strong>Webhooks</strong></li>
                            <li>Nhấn nút <strong>Thêm webhook mới</strong></li>
                            <li>Điền thông tin cấu hình:
                                <ul class="mt-2">
                                    <li><strong>URL:</strong> {{ url('/api/webhooks/pancake/order') }}</li>
                                    <li><strong>Sự kiện:</strong> Chọn <code>order.created</code> và <code>order.updated</code></li>
                                    <li><strong>Trạng thái:</strong> Kích hoạt</li>
                                    <li><strong>Format:</strong> JSON</li>
                                </ul>
                            </li>
                            <li>Lưu lại cấu hình</li>
                        </ol>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-lightbulb"></i> <strong>Lưu ý:</strong> Đảm bảo rằng website của bạn có thể truy cập từ internet để Pancake có thể gửi webhook đến.
                        </div>
                    </div>
                </div>
                <!-- End Webhook Info Section -->

                <div class="card-body">
                    <!-- Filter & Search -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form action="{{ route('orders.consolidated') }}" method="GET" class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="status">Trạng thái</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="all">Tất cả trạng thái</option>
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="pancake_status">Trạng thái Pancake</label>
                                    <select name="pancake_status" id="pancake_status" class="form-control">
                                        <option value="">Tất cả</option>
                                        <option value="pushed" {{ request('pancake_status') == 'pushed' ? 'selected' : '' }}>Đã đồng bộ</option>
                                        <option value="not_pushed" {{ request('pancake_status') == 'not_pushed' ? 'selected' : '' }}>Chưa đồng bộ</option>
                                        <option value="failed" {{ request('pancake_status') == 'failed' ? 'selected' : '' }}>Đồng bộ lỗi</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search">Tìm kiếm</label>
                                    <input type="text" name="search" id="search" class="form-control"
                                           placeholder="Mã đơn, Tên KH, SĐT, Email..."
                                           value="{{ request('search') }}">
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
                                    <a href="{{ route('orders.consolidated') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Sản phẩm</th>
                                    <th>Trạng thái</th>
                                    <th>Giá trị</th>
                                    <th>Pancake</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}" class="font-weight-bold">
                                            {{ $order->order_code ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer_name }}</div>
                                        <small>{{ $order->customer_phone }}</small>
                                    </td>
                                    <td>
                                        @foreach($order->items as $item)
                                            <div class="small">{{ $item->product_name }} ({{ $item->quantity }})</div>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge {{ $order->getStatusClass() }}">
                                            {{ $order->getStatusText() }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($order->total_value, 0, ',', '.') }} ₫</td>
                                    <td>
                                        @if($order->pancake_order_id)
                                            <span class="badge badge-success">Đã đồng bộ</span>
                                        @elseif($order->pancake_push_status == 'failed')
                                            <span class="badge badge-danger" data-toggle="tooltip"
                                                  title="{{ $order->internal_status }}">Lỗi</span>
                                        @else
                                            <span class="badge badge-secondary">Chưa đồng bộ</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(!$order->pancake_order_id)
                                                <button type="button" class="btn btn-sm btn-success push-to-pancake"
                                                        data-order-id="{{ $order->id }}">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-danger delete-order"
                                                    data-order-id="{{ $order->id }}"
                                                    data-order-code="{{ $order->order_code }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">Không có đơn hàng nào</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Sync Progress -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đang đồng bộ dữ liệu từ Pancake</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: 0%" aria-valuenow="0"
                         aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="syncStatus" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-danger" id="cancelSync">Hủy đồng bộ</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa đơn hàng <span id="orderCodeToDelete"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <form id="deleteOrderForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Copy webhook URL
    $('#copyWebhook').click(function() {
        var webhookUrl = document.getElementById('webhookUrl');
        webhookUrl.select();
        document.execCommand('copy');

        // Show success message
        $(this).html('<i class="fas fa-check"></i> Copied!');
        setTimeout(() => {
            $(this).html('<i class="fas fa-copy"></i> Copy');
        }, 2000);
    });

    // Sync from Pancake (existing code)
    $('#syncWithPancake').click(function() {
        // Show the progress modal
        $('#syncProgressModal').modal('show');
        $('#syncStatus').html('<p>Đang chuẩn bị đồng bộ...</p>');
        $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
        $('.modal-title').text('Đang đồng bộ dữ liệu từ Pancake');

        // Start the sync process
        $.ajax({
            url: '{{ route("pancake.orders.sync") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    checkSyncProgress();
                } else {
                    $('#syncStatus').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Đã xảy ra lỗi khi đồng bộ.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#syncStatus').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    });

    // Sync all orders to Pancake (new functionality)
    $('#syncAllToPancake').click(function() {
        if (!confirm('Bạn có chắc chắn muốn đồng bộ tất cả đơn hàng chưa đồng bộ lên Pancake?')) {
            return;
        }

        // Show the progress modal
        $('#syncProgressModal').modal('show');
        $('#syncStatus').html('<p>Đang chuẩn bị đồng bộ đơn hàng lên Pancake...</p>');
        $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
        $('.modal-title').text('Đang đồng bộ dữ liệu lên Pancake');

        // Start the sync process
        $.ajax({
            url: '{{ route("pancake.orders.push.bulk") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    checkSyncProgress();
                } else {
                    $('#syncStatus').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Đã xảy ra lỗi khi đồng bộ lên Pancake.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#syncStatus').html('<div class="alert alert-danger">' + errorMsg + '</div>');
            }
        });
    });

    // Check sync progress
    function checkSyncProgress() {
        $.ajax({
            url: '{{ route("pancake.sync.status") }}',
            type: 'GET',
            success: function(response) {
                // Update progress bar
                let progress = response.progress || 0;
                $('.progress-bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');

                // Update status message
                let statusHtml = '<p>' + (response.message || 'Đang đồng bộ...') + '</p>';

                if (response.stats) {
                    statusHtml += '<ul>';
                    statusHtml += '<li>Tổng số: ' + (response.stats.total || 0) + '</li>';
                    statusHtml += '<li>Đã đồng bộ: ' + (response.stats.synced || 0) + '</li>';
                    statusHtml += '<li>Lỗi: ' + (response.stats.failed || 0) + '</li>';
                    statusHtml += '</ul>';
                }

                $('#syncStatus').html(statusHtml);

                // Continue checking if not done
                if (!response.is_completed) {
                    setTimeout(checkSyncProgress, 2000);
                } else {
                    $('#syncStatus').append('<div class="alert alert-success">Đồng bộ hoàn tất! Trang sẽ tải lại sau 3 giây.</div>');
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                }
            },
            error: function() {
                $('#syncStatus').html('<div class="alert alert-danger">Không thể kiểm tra tiến trình đồng bộ.</div>');
            }
        });
    }

    // Cancel sync
    $('#cancelSync').click(function() {
        $.ajax({
            url: '{{ route("pancake.sync.cancel") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#syncStatus').html('<div class="alert alert-warning">Đã hủy đồng bộ.</div>');
                $('.progress-bar').removeClass('progress-bar-animated');
                setTimeout(function() {
                    $('#syncProgressModal').modal('hide');
                }, 1500);
            }
        });
    });

    // Push individual order to Pancake
    $('.push-to-pancake').click(function() {
        const orderId = $(this).data('order-id');
        const btn = $(this);

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/orders/' + orderId + '/push-to-pancake',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Đẩy đơn hàng lên Pancake thành công!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    toastr.error('Lỗi: ' + response.message);
                    btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i>');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Đã xảy ra lỗi khi đẩy đơn hàng.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i>');
            }
        });
    });

    // Delete order confirmation
    $('.delete-order').click(function() {
        const orderId = $(this).data('order-id');
        const orderCode = $(this).data('order-code');

        $('#orderCodeToDelete').text(orderCode);
        $('#deleteOrderForm').attr('action', '/orders/' + orderId);
        $('#deleteOrderModal').modal('show');
    });
});
</script>
@endsection
