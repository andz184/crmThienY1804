@extends('adminlte::page')

@section('title', 'Cấu hình Webhook Pancake')

@section('content_header')
    <h1 class="m-0 text-dark">Cấu hình Webhook Pancake</h1>
@stop

@section('content')
<div class="row">
    <!-- Webhook Info Card -->
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-link"></i>
                    Thông tin Webhook
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info"></i> Hướng dẫn cài đặt Webhook</h5>
                    <p>Webhook giúp Pancake gửi thông tin đơn hàng mới tự động về hệ thống CRM của bạn ngay khi có đơn hàng được tạo trên Pancake.</p>
                    <ol>
                        <li>Đăng nhập vào tài khoản Pancake của bạn</li>
                        <li>Vào mục <strong>Cấu hình → Tích hợp → Webhook</strong></li>
                        <li>Thêm webhook mới với URL được cung cấp bên dưới</li>
                        <li>Kiểm tra loại dữ kiện <strong>"Đơn hàng mới"</strong> để nhận thông báo đơn hàng</li>
                    </ol>
                </div>

                <!-- Webhook URL -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">URL Webhook Pancake</h3>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="webhookUrl" value="{{ $webhookUrl }}" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#webhookUrl">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Thông tin:</strong> Webhook này sẽ tự động xử lý tất cả các loại dữ liệu từ Pancake, bao gồm:
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Đơn hàng</strong> - Tự động tạo/cập nhật đơn hàng khi có đơn mới hoặc thay đổi ở Pancake</li>
                                        <li><strong>Khách hàng</strong> - Tự động tạo/cập nhật thông tin khách hàng</li>
                                        <li><strong>Kho hàng</strong> - Ghi nhận các thay đổi về số lượng sản phẩm</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Info -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Thông tin API</h3>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">API Key:</dt>
                                    <dd class="col-sm-8">
                                        <code>{{ !empty($apiKey) ? substr($apiKey, 0, 10) . '...' : 'Chưa cấu hình' }}</code>
                                        @if(empty($apiKey))
                                            <a href="{{ route('pancake.config') }}" class="btn btn-sm btn-warning">Cấu hình ngay</a>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-4">Shop ID:</dt>
                                    <dd class="col-sm-8">
                                        <code>{{ !empty($shopId) ? $shopId : 'Chưa cấu hình' }}</code>
                                        @if(empty($shopId))
                                            <a href="{{ route('pancake.config') }}" class="btn btn-sm btn-warning">Cấu hình ngay</a>
                                        @endif
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-check-circle"></i>
                                    Kiểm tra Webhook
                                </h3>
                            </div>
                            <div class="card-body">
                                <p>Sau khi cấu hình webhook trên Pancake, bạn có thể kiểm tra kết nối bằng cách:</p>
                                <ol>
                                    <li>Nhấn nút <strong>"Kiểm tra Webhook"</strong> trong cấu hình webhook tại Pancake.</li>
                                    <li>Hoặc tạo một đơn hàng thử nghiệm trên Pancake.</li>
                                </ol>
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Lưu ý:</strong> Webhook không yêu cầu xác thực. Chỉ cần sao chép và dán URL webhook vào cấu hình Pancake là đủ.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Webhook Logs Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-history"></i>
                                    Webhook đã nhận
                                </h3>
                                <!-- Filter Form -->
                                <div class="card-tools">
                                    <form method="GET" class="form-inline">
                                        <select name="status" class="form-control form-control-sm mr-2">
                                            <option value="">Tất cả trạng thái</option>
                                            <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Thành công</option>
                                            <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Lỗi</option>
                                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                        </select>
                                        <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Tìm kiếm..." value="{{ request('search') }}">
                                        <button type="submit" class="btn btn-sm btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Thời gian</th>
                                                <th>Loại</th>
                                                <th>Trạng thái</th>
                                                <th>Đơn hàng</th>
                                                <th>Khách hàng</th>
                                                <th>Chi tiết</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($webhookLogs as $log)
                                                <tr>
                                                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                                    <td>{{ $log->event_type }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $log->status === 'success' ? 'success' : ($log->status === 'error' ? 'danger' : 'warning') }}">
                                                            {{ $log->status === 'success' ? 'Thành công' : ($log->status === 'error' ? 'Lỗi' : 'Đang xử lý') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($log->order_id)
                                                            <a href="{{ route('orders.show', $log->order_id) }}">{{ $log->order_id }}</a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($log->customer_id)
                                                            <a href="{{ route('customers.show', $log->customer_id) }}">{{ $log->customer_id }}</a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info view-details"
                                                                data-toggle="modal"
                                                                data-target="#logDetailsModal"
                                                                data-log="{{ json_encode($log) }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Chưa có webhook nào được ghi nhận.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="mt-3">
                                    {{ $webhookLogs->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Webhook</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin cơ bản</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Thời gian:</dt>
                            <dd class="col-sm-8" id="modal-created-at"></dd>

                            <dt class="col-sm-4">Loại:</dt>
                            <dd class="col-sm-8" id="modal-event-type"></dd>

                            <dt class="col-sm-4">Trạng thái:</dt>
                            <dd class="col-sm-8" id="modal-status"></dd>

                            <dt class="col-sm-4">IP nguồn:</dt>
                            <dd class="col-sm-8" id="modal-source-ip"></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6>Kết quả xử lý</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Đơn hàng:</dt>
                            <dd class="col-sm-8" id="modal-order-id"></dd>

                            <dt class="col-sm-4">Khách hàng:</dt>
                            <dd class="col-sm-8" id="modal-customer-id"></dd>
                        </dl>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Dữ liệu nhận được</h6>
                        <pre id="modal-request-data" class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"></pre>

                        <h6>Dữ liệu đã xử lý</h6>
                        <pre id="modal-processed-data" class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"></pre>

                        <div id="modal-error-message" class="alert alert-danger d-none"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .copy-btn.success {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }
    pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize clipboard.js
    var clipboard = new ClipboardJS('.copy-btn');

    clipboard.on('success', function(e) {
        var btn = $(e.trigger);
        btn.html('<i class="fas fa-check"></i> Đã sao chép');
        btn.addClass('success');

        setTimeout(function() {
            btn.html('<i class="fas fa-copy"></i> Sao chép');
            btn.removeClass('success');
        }, 2000);

        e.clearSelection();
    });

    // Handle log details modal
    $('.view-details').click(function() {
        var log = $(this).data('log');

        // Basic info
        $('#modal-created-at').text(moment(log.created_at).format('DD/MM/YYYY HH:mm:ss'));
        $('#modal-event-type').text(log.event_type);
        $('#modal-status').html(`<span class="badge badge-${log.status === 'success' ? 'success' : (log.status === 'error' ? 'danger' : 'warning')}">${log.status}</span>`);
        $('#modal-source-ip').text(log.source_ip);

        // IDs
        $('#modal-order-id').html(log.order_id ? `<a href="/orders/${log.order_id}">${log.order_id}</a>` : '-');
        $('#modal-customer-id').html(log.customer_id ? `<a href="/customers/${log.customer_id}">${log.customer_id}</a>` : '-');

        // Data
        $('#modal-request-data').text(JSON.stringify(log.request_data, null, 2));
        $('#modal-processed-data').text(JSON.stringify(log.processed_data, null, 2));

        // Error message if any
        if (log.error_message) {
            $('#modal-error-message').text(log.error_message).removeClass('d-none');
        } else {
            $('#modal-error-message').addClass('d-none');
        }
    });
});
</script>
@stop
