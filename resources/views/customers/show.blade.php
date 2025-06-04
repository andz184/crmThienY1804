@extends('adminlte::page')

@section('title', 'Chi tiết khách hàng - ' . $customer->name)

@section('content_header')
    <h1>Khách hàng: {{ $customer->name }}</h1>
    <p class="text-muted">SĐT: {{ $customer->primary_phone }} @if($customer->email) | Email: {{ $customer->email }} @endif</p>
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
                        <dd class="col-sm-7">
                            @php
                                $addressParts = [];
                                if ($customer->street_address) $addressParts[] = $customer->street_address;
                                if ($customer->ward) $addressParts[] = \App\Models\Ward::where('code', $customer->ward)->value('name') ?? $customer->ward;
                                if ($customer->district) $addressParts[] = \App\Models\District::where('code', $customer->district)->value('name') ?? $customer->district;
                                if ($customer->province) $addressParts[] = \App\Models\Province::where('code', $customer->province)->value('name') ?? $customer->province;
                                echo !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
                            @endphp
                        </dd>

                        <dt class="col-sm-5">Tỉnh/Thành</dt>
                        <dd class="col-sm-7">{{ \App\Models\Province::where('code', $customer->province)->value('name') ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Quận/Huyện</dt>
                        <dd class="col-sm-7">{{ \App\Models\District::where('code', $customer->district)->value('name') ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Phường/Xã</dt>
                        <dd class="col-sm-7">{{ \App\Models\Ward::where('code', $customer->ward)->value('name') ?? 'N/A' }}</dd>

                        <dt class="col-sm-5">Địa chỉ cụ thể</dt>
                        <dd class="col-sm-7">{{ $customer->street_address ?? 'N/A' }}</dd>

                        <hr class="my-2">

                        <dt class="col-sm-5">Đơn hàng đầu tiên</dt>
                        <dd class="col-sm-7">
                            @php
                                $firstOrder = $customer->orders()->orderBy('pancake_inserted_at', 'asc')->first();
                                echo isset($firstOrder->pancake_inserted_at) ? $firstOrder->pancake_inserted_at : 'N/A';

                            @endphp
                        </dd>

                        <dt class="col-sm-5">Đơn hàng cuối cùng</dt>
                        <dd class="col-sm-7">
                            @php
                                $lastOrder = $customer->orders()->orderBy('pancake_inserted_at', 'desc')->first();
                                echo isset($lastOrder->pancake_inserted_at) ? $lastOrder->pancake_inserted_at : 'N/A';
                            @endphp
                        </dd>

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
                                        <th>Sản phẩm</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->orders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}">{{ $order->pancake_order_id ?? $order->order_code }}</a>
                                            </td>
                                            <td>{{ $order->pancake_inserted_at ? $order->pancake_inserted_at : $order->created_at }}</td>
                                            <td>
                                                @if($order->products_data)
                                                    @php
                                                        $productsData = json_decode($order->products_data, true);
                                                        if(is_array($productsData) && count($productsData) > 0) {
                                                            $firstProduct = $productsData[0];
                                                            echo $firstProduct['variation_info']['name'] ?? 'N/A';
                                                            if (count($productsData) > 1) {
                                                                echo ' (+' . (count($productsData) - 1) . ' SP khác)';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    @endphp
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ number_format($order->total_value, 0, '.', ',') }}đ</td>
                                            <td>
                                                @if(is_numeric($order->pancake_status) && App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->exists())
                                                    @php $pancakeStatus = App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->first(); @endphp
                                                    <span class="badge badge-{{ $pancakeStatus->color }}">{{ $pancakeStatus->name }}</span>
                                                @else
                                                    <span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-info view-order-btn"
                                                        data-order-url="{{ route('orders.show', $order) }}"
                                                        data-toggle="modal"
                                                        data-target="#orderModal">
                                                    Xem ĐH
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Không có đơn hàng nào cho khách hàng này.</td>
                                        </tr>
                                    @endforelse
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
                <h5 class="modal-title" id="orderModalLabel">Chi tiết đơn hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('.view-order-btn').on('click', function() {
        var url = $(this).data('order-url');
        $('#orderModal .modal-body').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
        $.get(url, function(response) {
            $('#orderModal .modal-body').html(response);
        });
    });
});
</script>
@stop
