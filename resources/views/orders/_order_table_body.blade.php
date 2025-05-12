@forelse ($orders as $order)
    <tr>
        <td>{{ $order->order_code }}</td>
        <td>{{ $order->customer_name }}</td>
        <td>
            @if(auth()->user()->hasRole('staff'))
                {{ substr($order->customer_phone, 0, 3) . str_repeat('*', strlen($order->customer_phone) - 3) }}
            @else
                {{ $order->customer_phone }}
            @endif
        </td>
        {{-- Assuming product_info was a temporary column from older structure, if OrderItems are primary, this might need change or removal --}}
        {{-- For now, I'll comment it out as it's not in the main index.blade.php header --}}
        {{-- <td>{{ Str::limit($order->product_info, 50) }}</td> --}}
        @canany(['view orders', 'view team orders'])
            <td>{{ $order->user->name ?? 'Chưa gán' }}</td>
        @endcanany
        <td> {{-- Trạng thái CRM --}}
            <span class="badge {{ $order->getStatusClass() }}">{{ $order->getStatusText() }}</span>
        </td>
        <td> {{-- Trạng thái Pancake --}}
            @if($order->pancake_push_status === 'success')
                <span class="badge badge-success">Đã đẩy OK</span>
            @elseif($order->pancake_push_status === 'failed_stock')
                <span class="badge badge-danger">Lỗi Stock</span>
            @elseif($order->pancake_push_status === 'failed_other')
                <span class="badge badge-warning">Lỗi Khác</span>
            @elseif($order->pancake_push_status)
                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $order->pancake_push_status)) }}</span>
            @else
                <span class="badge badge-light">Chưa đẩy</span>
            @endif
        </td>
        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
        <td>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-info btn-xs" title="Xem chi tiết"><i class="fas fa-eye fa-fw"></i></a>
            @can('calls.manage')
                <button class="btn btn-success btn-xs btn-call"
                        data-order-id="{{ $order->id }}"
                        data-phone="{{ $order->customer_phone }}"
                        {{-- data-action removed as it seems unused in current JS, keeping other attributes --}}
                        title="Gọi điện">
                    <i class="fas fa-phone-alt fa-fw"></i>
                </button>
            @endcan
            @can('orders.edit')
                 <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning btn-xs" title="Sửa"><i class="fas fa-edit fa-fw"></i></a>
            @endcan
            @can('teams.assign')
                 <a href="{{ route('orders.assign', $order) }}" class="btn btn-secondary btn-xs" title="Gán đơn"><i class="fas fa-user-plus fa-fw"></i></a>
            @endcan
            @can('orders.push_to_pancake')
                <button class="btn btn-info btn-xs btn-push-pancake"
                        data-order-id="{{ $order->id }}"
                        data-url="{{ route('orders.pushToPancake', $order->id) }}"
                        title="Đẩy đơn lên Pancake">
                    <i class="fas fa-rocket fa-fw"></i>
                </button>
            @endcan
            {{-- Actions from original index.blade.php are preferred here --}}
        </td>
    </tr>
@empty
    <tr>
        @php
            $colspan = 6; // Base columns: Mã đơn, KH, SĐT, Trạng thái CRM, Trạng thái Pancake, Ngày tạo, Hành động = 7
            if (auth()->user()->canany(['view orders', 'view team orders'])) {
                $colspan = 7; // Add Sale column
            }
        @endphp
        <td colspan="{{ $colspan }}" class="text-center">Không tìm thấy đơn hàng nào.</td>
    </tr>
@endforelse

{{-- This pagination part might be handled by the main index.blade.php if that's where full page loads are --}}
{{-- If AJAX updates the body AND pagination, it should stay. For now, assume main view handles initial pagination. --}}
@if(isset($orders) && $orders instanceof \Illuminate\Pagination\LengthAwarePaginator && $orders->hasPages())
<tr id="pagination-row" class="d-none">
     @php
        $colspan = 6;
        if (auth()->user()->canany(['view orders', 'view team orders'])) {
            $colspan = 7;
        }
    @endphp
    <td colspan="{{ $colspan }}">
        <div class="d-flex justify-content-center">
            {{ $orders->links('vendor.pagination.bootstrap-4') }}
        </div>
    </td>
</tr>
@endif

@push('js')
<script>
    // Keep existing JS, but the call button logic from index.blade.php is more complete.
    // The $(document).on('click', '.btn-call', ...) from index.blade.php handles the SweetAlert part.
    // If this partial is reloaded via AJAX, ensure event delegation is used for new buttons.
</script>
@endpush
