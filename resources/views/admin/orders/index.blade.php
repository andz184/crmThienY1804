@extends('adminlte::page')

@section('title', 'Quản lý Đơn hàng')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Quản lý Đơn hàng</h1>
        @can('orders.delete')
        <a href="{{ route('admin.orders.trashed') }}" class="btn btn-warning">
            <i class="fas fa-trash"></i> Thùng rác
        </a>
        @endcan
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @include('layouts.partials.alert')

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td>{{ $order->status }}</td>
                            <td>{{ number_format($order->total_amount, 2) }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @can('orders.edit')
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-info btn-sm" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('orders.delete')
                                <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn chuyển đơn hàng này vào thùng rác?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
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
        @if ($orders->hasPages())
            <div class="card-footer clearfix">
                {{ $orders->links('vendor.pagination.bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
