@extends('adminlte::page')

@section('title', 'Chi tiết khách hàng - ' . $customer->name)

@section('content_header')
    <h1>Khách hàng: {{ $customer->name }}</h1>
    <p class="text-muted">SĐT: {{ $customer->phone }} @if($customer->email) | Email: {{ $customer->email }} @endif</p>
@stop

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            @can('customers.edit')
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">Sửa</a>
        @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
        </div>
    </div>

    @include('partials._alerts')

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-header">Thông tin chi tiết</div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Họ tên</dt>
                        <dd class="col-sm-7">{{ $customer->name }}</dd>

                        <dt class="col-sm-5">Số điện thoại</dt>
                        <dd class="col-sm-7">{{ $customer->phone }}</dd>

                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7">{{ $customer->email ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Địa chỉ đầy đủ</dt>
                        <dd class="col-sm-7">{{ $customer->full_address }}</dd>

                        <dt class="col-sm-5">Tỉnh/Thành</dt>
                        <dd class="col-sm-7">{{ $customer->province ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Quận/Huyện</dt>
                        <dd class="col-sm-7">{{ $customer->district ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Phường/Xã</dt>
                        <dd class="col-sm-7">{{ $customer->ward ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Địa chỉ cụ thể</dt>
                        <dd class="col-sm-7">{{ $customer->street_address ?? 'N/A' }}</dd>

                        <hr class="my-2">

                        <dt class="col-sm-5">Đơn hàng đầu tiên</dt>
                        <dd class="col-sm-7">{{ $customer->formatted_first_order_date }}</dd>

                        <dt class="col-sm-5">Đơn hàng cuối cùng</dt>
                        <dd class="col-sm-7">{{ $customer->formatted_last_order_date }}</dd>

                        <dt class="col-sm-5">Tổng số đơn hàng</dt>
                        <dd class="col-sm-7">{{ $customer->total_orders_count }}</dd>

                        <dt class="col-sm-5">Tổng chi tiêu</dt>
                        <dd class="col-sm-7">{{ $customer->formatted_total_spent }}</dd>

                        <hr class="my-2">

                        <dt class="col-sm-5">Ghi chú</dt>
                        <dd class="col-sm-7">{!! nl2br(e($customer->notes ?? 'Không có ghi chú')) !!}</dd>

                        <dt class="col-sm-5">Ngày tạo KH</dt>
                        <dd class="col-sm-7">{{ $customer->formatted_created_at }}</dd>

                        <dt class="col-sm-5">Cập nhật KH lần cuối</dt>
                        <dd class="col-sm-7">{{ $customer->formatted_updated_at }}</dd>
                    </dl>
                </div>
            </div>

            <div class="mb-3">
                <a href="{{ route('customers.orders', $customer) }}" class="btn btn-info">Xem lịch sử mua hàng</a>
            </div>

            <div class="mb-3">
                <strong>Tỉ lệ nhận đơn thành công:</strong>
                {{ $customer->success_rate }}%
            </div>


        </div>

        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">Lịch sử đơn hàng ({{ $customer->orders->count() }})</div>
                <div class="card-body p-0">
                    @if($customer->orders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Mã ĐH</th>
                                        <th>Ngày tạo</th>
                                        <th>Sản phẩm chính</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->orders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}">{{ $order->order_code }}</a>
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ Str::limit($order->product_name ?? ($order->productVariation->product->name ?? 'N/A'), 30) }}</td>
                                            <td>{{ number_format($order->total_value, 0, '.', ',') }}đ</td>
                                            <td><span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span></td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-info view-order-btn" data-order-url="{{ route('orders.show', $order) }}" data-toggle="modal" data-target="#orderModal">
                                                    Xem ĐH
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="p-3 text-center">Không có đơn hàng nào cho khách hàng này.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Chi tiết Đơn hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="orderModalContent">
                    <p>Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Handle clicking the 'View Order' button
    $('.view-order-btn').on('click', function() {
        var orderUrl = $(this).data('order-url');
        var modalContent = $('#orderModalContent');
        var modalTitle = $('#orderModalLabel');

        // Reset modal content and title
        modalTitle.text('Chi tiết Đơn hàng');
        modalContent.html('<p><i class="fas fa-spinner fa-spin"></i> Loading...</p>');

        $.ajax({
            url: orderUrl,
            type: 'GET',
            success: function(response) {
                modalContent.html(response);
                // Attempt to extract order code from response for modal title (optional)
                // This is a bit fragile; depends on the structure of _modal_details.blade.php
                var tempDiv = $('<div>').html(response);
                var orderCode = tempDiv.find('h4:contains("Order Details")').text().match(/\\(([^)]+)\\)/);
                if (orderCode && orderCode[1]) {
                    modalTitle.text('Chi tiết Đơn hàng (' + orderCode[1] + ')');
                }
            },
            error: function(xhr) {
                var errorMessage = '<p class="text-danger">Lỗi khi tải chi tiết đơn hàng.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage += '<br>' + xhr.responseJSON.error;
                }
                errorMessage += '</p>';
                modalContent.html(errorMessage);
                console.error("Error loading order details: ", xhr);
            }
        });
    });

    // Clear modal content when it's hidden
    $('#orderModal').on('hidden.bs.modal', function () {
        $('#orderModalContent').html('<p><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
        $('#orderModalLabel').text('Chi tiết Đơn hàng');
    });
});
</script>
@stop
