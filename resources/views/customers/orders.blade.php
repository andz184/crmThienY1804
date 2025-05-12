@extends('adminlte::page')

@section('title', 'Lịch sử mua hàng của ' . $customer->name)

@section('content_header')
    <h1>Lịch sử mua hàng của {{ $customer->name }}</h1>
    <p class="text-muted">SĐT: {{ $customer->phone }} @if($customer->email) | Email: {{ $customer->email }} @endif</p>
@stop

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-secondary">Quay lại chi tiết khách hàng</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tổng số đơn hàng: {{ $customer->total_orders_count }}</h3>
            <div class="card-tools">
                <span class="badge badge-info">Tỉ lệ thành công: {{ $customer->success_rate }}%</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Ngày đặt</th>
                            <th>Sản phẩm</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}">{{ $order->order_code }}</a>
                                </td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ Str::limit($order->product_name ?? ($order->productVariation->product->name ?? 'N/A'), 30) }}</td>
                                <td>{{ number_format($order->total_value, 0, '.', ',') }}đ</td>
                                <td><span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span></td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Không có đơn hàng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
