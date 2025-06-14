@extends('adminlte::page')

@section('title', 'Chi tiết khách hàng - ' . $customer->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
    <h1>Khách hàng: {{ $customer->name }}</h1>
            <p class="text-muted mb-0">SĐT: {{ $customer->phone }} @if($customer->email) | Email: {{ $customer->email }} @endif</p>
        </div>
        <div>
            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning"><i class="fas fa-edit mr-1"></i>Sửa</a>
            @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        </div>
    </div>
@stop

@push('css')
<style>
.stat-box {
    background-color: #fff;
    border-radius: .5rem;
    box-shadow: 0 0 1px rgba(0,0,0,.125),0 1px 3px rgba(0,0,0,.2);
    display: flex;
    align-items: center;
    padding: 1.25rem;
    margin-bottom: 1rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease-in-out;
}
.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0,0,0,.15);
}
.stat-box .icon {
    font-size: 3.5rem;
    opacity: 0.2;
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    transition: all 0.3s linear;
}
.stat-box:hover .icon {
    transform: translateY(-50%) scale(1.1);
    opacity: 0.3;
}
.stat-box-content h3 {
    font-size: 1rem;
    font-weight: bold;
    color: #6c757d;
    margin: 0 0 5px;
    padding: 0;
    white-space: nowrap;
}
.stat-box-content p {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    padding: 0;
}
.stat-box.box-success .stat-box-content p { color: #28a745; }
.stat-box.box-info .stat-box-content p { color: #17a2b8; }
.stat-box.box-primary .stat-box-content p { color: #007bff; }
.stat-box.box-warning .stat-box-content p { color: #ffc107; }

/* Dark Mode Overrides */
.dark-mode .stat-box {
    background-color: #343a40;
    box-shadow: 0 0 1px rgba(0,0,0,.125),0 1px 3px rgba(0,0,0,.2);
    border: 1px solid #4b545c;
}
.dark-mode .stat-box:hover {
    background-color: #3f474e;
    box-shadow: 0 4px 10px rgba(0,0,0,.25);
}
.dark-mode .stat-box-content h3 {
    color: #c2c7d0;
}
.dark-mode .stat-box .icon {
    opacity: 0.15;
}
.dark-mode .stat-box:hover .icon {
    opacity: 0.25;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('partials._alerts')

    <!-- Customer Stats -->
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-box box-success">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-box-content">
                    <h3>Tổng chi tiêu</h3>
                    <p>{{ $customer->formatted_total_spent }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-box box-info">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-box-content">
                    <h3>Tổng số đơn</h3>
                    <p>{{ $customer->total_orders_count }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-box box-primary">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-box-content">
                    <h3>Tỉ lệ chốt</h3>
                    <p>
                        @if($customer->total_orders_count > 0)
                            {{ number_format(($customer->orders()->where('pancake_status', 3)->count() / $customer->total_orders_count) * 100, 2) }}%
                        @else
                            0%
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-box box-warning">
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-box-content">
                    <h3>Lần mua cuối</h3>
                    <p>
                        @php
                            $lastOrder = $customer->orders()->orderBy('pancake_inserted_at', 'desc')->first();
                            echo $lastOrder->pancake_inserted_at ?? 'N/A';
                        @endphp
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-1"></i>Lịch sử đơn hàng ({{ $customer->orders->count() }})</h3>
                </div>
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
                                            <td>{{ $order->pancake_inserted_at ?? $order->created_at }}</td>
                                            <td>
                                                @if($order->products_data)
                                                    @php
                                                        $productsData = json_decode($order->products_data, true);
                                                        if(is_array($productsData) && count($productsData) > 0) {
                                                            $firstProduct = $productsData[0];
                                                            echo e(Str::limit($firstProduct['variation_info']['name'] ?? 'N/A', 30));
                                                            if (count($productsData) > 1) {
                                                                echo '...';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                    @endphp
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ number_format($order->total_value, 0, '.', ',') }}đ</td>
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
                                                    Xem
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
        <div class="col-md-5">
            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-user-circle mr-1"></i>Thông tin liên hệ & Giao hàng</h3>
                </div>
                <div class="card-body py-2">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Họ tên</dt>
                        <dd class="col-sm-8">{{ $customer->name }}</dd>

                        <dt class="col-sm-4">Số điện thoại</dt>
                        <dd class="col-sm-8">{{ $customer->phone }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $customer->email ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Địa chỉ</dt>
                        <dd class="col-sm-8">
                            @php
                                $addressParts = [];
                                if ($customer->street_address) $addressParts[] = $customer->street_address;
                                if ($customer->ward) $addressParts[] = \App\Models\Ward::where('code', $customer->ward)->value('name') ?? $customer->ward;
                                if ($customer->district) $addressParts[] = \App\Models\District::where('code', $customer->district)->value('name') ?? $customer->district;
                                if ($customer->province) $addressParts[] = \App\Models\Province::where('code', 'LIKE', $customer->province . '%')->value('name') ?? $customer->province;
                                echo !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
                            @endphp
                        </dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-tags mr-1"></i>Phân tích mua hàng</h3>
                </div>
                <div class="card-body py-2">
                    <strong>Nhóm hàng đã mua:</strong>
                    @forelse($purchasedCategories as $catId => $category)
                        <div class="mt-2">
                            <p class="mb-0"><strong>{{ $category['name'] }}:</strong></p>
                            <ul class="list-unstyled pl-3 mb-0">
                                @foreach(array_slice($category['products'], 0, 3) as $productName)
                                    <li>- {{ e($productName) }}</li>
                                @endforeach
                                @if(count($category['products']) > 3)
                                    <li>- và {{ count($category['products']) - 3 }} sản phẩm khác...</li>
                                @endif
                            </ul>
                        </div>
                    @empty
                        <p class="mb-0">Chưa có thông tin.</p>
                    @endforelse
                    <hr class="my-2">
                    <strong>Size số đã mua:</strong>
                    <p class="mb-0">
                        @forelse($purchasedSizes as $size)
                            <span class="badge badge-secondary mr-1">{{ $size }}</span>
                        @empty
                            Chưa có thông tin.
                        @endforelse
                    </p>
                </div>
            </div>

            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-sticky-note mr-1"></i>Ghi chú & Lịch sử hệ thống</h3>
                </div>
                <div class="card-body py-2">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Ghi chú</dt>
                        <dd class="col-sm-8">{!! nl2br(e($customer->notes ?? 'Không có ghi chú')) !!}</dd>
                        
                        <dt class="col-sm-4">Ngày tạo KH</dt>
                        <dd class="col-sm-8">{{ $customer->formatted_created_at }}</dd>

                        <dt class="col-sm-4">Cập nhật cuối</dt>
                        <dd class="col-sm-8">{{ $customer->formatted_updated_at }}</dd>
                    </dl>
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
