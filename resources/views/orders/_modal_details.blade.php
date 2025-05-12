@if($order)
<style>
    .product-item-modal {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .product-item-modal:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .product-image-modal {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 15px;
    }
    .product-details-modal {
        flex-grow: 1;
    }
    .product-name-modal {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    .product-variation-modal {
        font-size: 0.9em;
        color: #555;
        margin-bottom: 5px;
    }
    .product-price-qty-modal {
        font-size: 0.9em;
        color: #333;
    }
</style>

<div class="container-fluid">
    {{-- Chi tiết đơn hàng cơ bản --}}
    <dl class="row mb-4">
        <dt class="col-sm-4">Mã đơn hàng:</dt>
        <dd class="col-sm-8">{{ $order->order_code }}</dd>

        <dt class="col-sm-4">Tên khách hàng:</dt>
        <dd class="col-sm-8">{{ $order->customer_name }}</dd>

        <dt class="col-sm-4">Số điện thoại:</dt>
        <dd class="col-sm-8">{{ $order->customer_phone }}</dd>

        <dt class="col-sm-4">Trạng thái:</dt>
        <dd class="col-sm-8"><span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span></dd>

        <dt class="col-sm-4">Tổng giá trị:</dt>
        <dd class="col-sm-8"><strong>{{ number_format($order->total_value, 0, '.', ',') }}đ</strong></dd>

        <dt class="col-sm-4">Người phụ trách:</dt>
        <dd class="col-sm-8">{{ $order->user->name ?? 'Chưa gán' }}</dd>

        <dt class="col-sm-4">Ngày tạo:</dt>
        <dd class="col-sm-8">{{ $order->created_at->format('d/m/Y H:i:s') }}</dd>

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
    <h5 class="mt-4">Sản phẩm trong đơn</h5>
    <div class="product-item-modal">
        @php
            $product = $order->productVariation->product ?? null;
            $variation = $order->productVariation ?? null;
            $imageUrl = $variation->image_url ?? $product->image_url ?? 'https://via.placeholder.com/80'; // Placeholder
        @endphp
        <img src="{{ $imageUrl }}" alt="{{ $product->name ?? 'Sản phẩm' }}" class="product-image-modal">
        <div class="product-details-modal">
            <span class="product-name-modal">{{ $order->product_name ?: ($product->name ?? 'N/A') }}</span>
            @if($variation)
                <div class="product-variation-modal">
                    SKU: {{ $variation->sku ?? $order->variation_id ?? 'N/A' }}
                    {{-- Ví dụ thêm nếu có attributes:
                    @if($variation->attributes)
                        @foreach($variation->attributes as $key => $value)
                            | {{ ucfirst($key) }}: {{ $value }}
                        @endforeach
                    @endif
                    --}}
                </div>
            @endif
            <div class="product-price-qty-modal">
                {{ number_format($order->price, 0, '.', ',') }}đ x {{ $order->quantity }}
            </div>
        </div>
    </div>
    {{-- Nếu đơn hàng có thể có nhiều sản phẩm, bạn sẽ cần lặp qua chúng ở đây.
         Hiện tại, cấu trúc order có vẻ như chỉ hỗ trợ một loại sản phẩm chính qua variation_id.
         Nếu có bảng order_items, logic sẽ khác. --}}


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
