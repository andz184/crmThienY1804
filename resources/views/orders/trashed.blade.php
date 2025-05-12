@extends('adminlte::page')

@section('title', 'Đơn hàng đã xóa')

@section('content_header')
    <h1>Thùng rác Đơn hàng</h1>
@stop

@section('content')
<div class="container-fluid">
    @include('partials._alerts')

    <div class="mb-3">
        <a href="{{ route('orders.index') }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Quay lại Danh sách</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Đơn hàng đã xóa</h3>
        </div>
        <div class="card-body p-0">
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Mã Đơn</th>
                                <th>Khách hàng</th>
                                <th>Điện thoại</th>
                                <th>Sản phẩm</th>
                                <th>Trạng thái</th>
                                <th>Ngày xóa</th>
                                <th style="width: 120px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td>{{ $order->order_code }}</td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td>{{ $order->customer_phone }}</td>
                                    <td>{{ $order->product_info }}</td>
                                    <td><span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span></td>
                                    <td>{{ $order->deleted_at ? $order->deleted_at->format('d/m/Y H:i') : '' }}</td>
                                    <td>
                                        @can('orders.restore')
                                            <form action="{{ route('orders.restore', $order->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Bạn có chắc muốn khôi phục đơn hàng này?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-success" title="Khôi phục"><i class="fas fa-undo"></i></button>
                                            </form>
                                        @endcan
                                        @can('orders.force_delete')
                                            <form action="{{ route('orders.forceDelete', $order->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('CẢNH BÁO: Hành động này sẽ XÓA VĨNH VIỄN đơn hàng và không thể hoàn tác. Bạn có chắc chắn?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" title="Xóa vĩnh viễn"><i class="fas fa-skull-crossbones"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Không có đơn hàng nào trong thùng rác.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-3 text-center">Thùng rác đơn hàng trống.</p>
            @endif
        </div>
        @if ($orders->hasPages())
            <div class="card-footer clearfix">
                {{ $orders->links('vendor.pagination.bootstrap-4') }}
            </div>
        @endif
    </div>
</div>
@stop

@section('js')
<script>
    // Basic JS for confirmation popups are handled by onsubmit, more advanced could be added here if needed.
</script>
@stop
