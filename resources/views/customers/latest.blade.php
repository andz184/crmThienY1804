@extends('adminlte::page')

@section('title', 'Khách hàng mới nhất')

@section('content_header')
    <h1>Khách hàng mới nhất</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Tổng đơn</th>
                            <th>Tổng chi tiêu</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}"><strong>{{ $customer->name }}</strong></a>
                                </td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->email ?? 'N/A' }}</td>
                                <td>{{ $customer->total_orders_count }}</td>
                                <td>{{ $customer->formatted_total_spent }}</td>
                                <td>{{ $customer->formatted_created_at }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-info">Xem</a>
                                    @can('customers.edit')
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">Sửa ghi chú</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Không tìm thấy khách hàng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $customers->links() }}
    </div>
</div>
@stop
