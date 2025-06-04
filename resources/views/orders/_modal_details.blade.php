@if($order)
<style>
    .product-item-modal {
        background: #f8f9fa;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .product-item-modal:last-child {
        margin-bottom: 0;
    }
    .product-details-modal {
        width: 100%;
    }
    .product-name-modal {
        font-weight: bold;
        font-size: 1.1em;
        color: #2c3e50;
        display: block;
        margin-bottom: 8px;
    }
    .product-variation-modal {
        font-size: 0.9em;
        color: #6c757d;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #dee2e6;
    }
    .product-price-qty-modal {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.95em;
        color: #495057;
    }
    .product-subtotal {
        font-weight: bold;
        color: #28a745;
    }
</style>

<div class="container-fluid">
    {{-- Chi tiết đơn hàng cơ bản --}}
    <dl class="row mb-4">
        <dt class="col-sm-4">Mã đơn hàng:</dt>
        <dd class="col-sm-8">{{ $order->order_code }}</dd>

        <dt class="col-sm-4">Tên khách hàng:</dt>
        <dd class="col-sm-8">{{ $order->bill_full_name }}</dd>

        <dt class="col-sm-4">Số điện thoại:</dt>
        <dd class="col-sm-8">{{ $order->bill_phone_number }}</dd>

        <dt class="col-sm-4">Trạng thái:</dt>
        <dd class="col-sm-8"><span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span></dd>

        <dt class="col-sm-4">Tổng giá trị:</dt>
        <dd class="col-sm-8"><strong>{{ number_format($order->total_value, 0, '.', ',') }}đ</strong></dd>

        <dt class="col-sm-4">Người phụ trách:</dt>
        <dd class="col-sm-8">{{ $order->user->name ?? 'Chưa gán' }}</dd>

        <dt class="col-sm-4">Ngày tạo:</dt>
        <dd class="col-sm-8">{{ $order->inserted_at }}</dd>

        @if($order->address_full)
        <dt class="col-sm-4">Địa chỉ đầy đủ:</dt>
        <dd class="col-sm-8">{{ $order->address_full }}</dd>
        @endif

        @if($order->notes)
        <dt class="col-sm-4">Ghi chú đơn hàng:</dt>
        <dd class="col-sm-8">{!! nl2br(e($order->notes)) !!}</dd>
        @endif
    </dl>

    {{-- Thông tin sản phẩm --}}
    <h5 class="mt-4 mb-3">Sản phẩm trong đơn</h5>
    @if($order->products_data)
        @php
            $productsData = json_decode($order->products_data, true);
            $totalItems = 0;
            if(is_array($productsData)) {
                foreach($productsData as $product) {
                    $totalItems += $product['quantity'] ?? 0;
                }
            }
        @endphp
        @if(is_array($productsData) && count($productsData) > 0)
            <div class="text-muted mb-2">
                Tổng số sản phẩm: {{ count($productsData) }} ({{ $totalItems }} items)
            </div>
            @foreach($productsData as $product)
                <div class="product-item-modal">
                    <div class="product-details-modal">
                        <span class="product-name-modal">{{ $product['variation_info']['name'] ?? 'N/A' }}</span>
                        <div class="product-variation-modal">
                            <strong>Mã SP:</strong> {{ $product['variation_id'] ?? 'N/A' }}
                            @if(!empty($product['variation_info']['detail']))
                                <span class="mx-2">|</span> {{ $product['variation_info']['detail'] }}
                            @endif
                        </div>
                        <div class="product-price-qty-modal">
                            <div>
                                <span class="text-primary">{{ number_format($product['variation_info']['retail_price'] ?? 0, 0, '.', ',') }}đ</span>
                                <span class="mx-2">×</span>
                                <span>{{ $product['quantity'] ?? 1 }}</span>
                            </div>
                            <div class="product-subtotal">
                                {{ number_format(($product['variation_info']['retail_price'] ?? 0) * ($product['quantity'] ?? 1), 0, '.', ',') }}đ
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p>Không có thông tin sản phẩm.</p>
        @endif
    @else
        <p>Không có thông tin sản phẩm.</p>
    @endif

    {{-- Lịch sử cuộc gọi --}}
    <h5 class="mt-4">Lịch sử cuộc gọi ({{ $order->calls->count() }})</h5>
    @if($order->calls->count() > 0)
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-striped table-hover">
                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th>Người gọi</th>
                        <th>Thời gian</th>
                        <th>Thời lượng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->calls as $call)
                        <tr>
                            <td>{{ $call->user->name ?? 'N/A' }}</td>
                            <td>{{ $call->call_time ? $call->call_time->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td>{{ $call->call_duration ? $call->call_duration . 's' : 'N/A' }}</td>
                            <td>{!! nl2br(e($call->notes ?? '-')) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>Chưa có cuộc gọi nào cho đơn hàng này.</p>
    @endif

</div>
@else
<div class="alert alert-danger m-3">Không thể tải chi tiết đơn hàng.</div>
@endif
