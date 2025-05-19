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

                <!-- Test Webhook Section -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Webhook đã nhận
                                </h3>
                            </div>
                            <div class="card-body">
                                <p>Webhook đã nhận được gần đây nhất:</p>
                                <div id="webhook-log">
                                    <div class="alert alert-secondary">
                                        Chưa nhận được webhook nào.
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
@stop

@section('css')
<style>
    .copy-btn.success {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
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
});
</script>
@stop
