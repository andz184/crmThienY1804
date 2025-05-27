@forelse ($orders as $order)
    <tr>
        <td>
            {{ $order->order_code }}
            @if($order->pancake_order_id)
                <span class="badge badge-primary ml-1" title="ID Pancake: {{ $order->pancake_order_id }}">
                    <i class="fas fa-arrow-down"></i> P{{ $order->pancake_order_id }}
                </span>
            @elseif($order->pancake_push_status === 'success' || $order->internal_status === 'Pushed to Pancake successfully.')
                <span class="badge badge-success ml-1" title="Đơn hàng đã đẩy lên Pancake">
                    <i class="fas fa-arrow-up"></i> P
                </span>
            @endif
        </td>
        <td>
            <div>{{ $order->customer_name }}</div>
            @if($order->bill_full_name && $order->bill_full_name != $order->customer_name)
                <small class="text-muted">Hóa đơn: {{ $order->bill_full_name }}</small>
            @endif
            @if($order->customer_email)
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $order->customer_email }}">
                    {{ $order->customer_email }}
                </small>
            @endif
        </td>
        <td>
            @if(auth()->user()->hasRole('staff'))
                {{ substr($order->customer_phone, 0, 3) . str_repeat('*', strlen($order->customer_phone) - 3) }}
            @else
                <div>{{ $order->customer_phone }}</div>
                @if($order->bill_phone_number && $order->bill_phone_number != $order->customer_phone)
                    <small class="text-muted">Hóa đơn: {{ $order->bill_phone_number }}</small>
                @endif
            @endif
        </td>
        {{-- Assuming product_info was a temporary column from older structure, if OrderItems are primary, this might need change or removal --}}
        {{-- For now, I'll comment it out as it's not in the main index.blade.php header --}}
        {{-- <td>{{ Str::limit($order->product_info, 50) }}</td> --}}
        @canany(['view orders', 'view team orders'])
            <td>
                {{ $order->assignedStaff->name ?? $order->assigning_seller_name ?? 'Chưa gán' }}
            </td>
        @endcanany
        <td> {{-- Trạng thái Pancake --}}
            @if($order->pancake_status)
                @if(is_numeric($order->pancake_status) && App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->exists())
                    @php $pancakeStatus = App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->first(); @endphp
                    <span class="badge badge-{{ $pancakeStatus->color }}" title="Trạng thái trên Pancake">
                        {{ $pancakeStatus->name }}
                    </span>
                @else
                    <span class="badge {{ $order->getPancakeStatusClass() }}" title="Trạng thái trên Pancake">{{ $order->getPancakeStatusText() }}</span>
                @endif
            @else
                <span class="badge badge-light">Chưa có</span>
            @endif
        </td>
        <td> {{-- Pancake Info --}}
            <div class="pancake-info small">
                @if($order->pancake_order_id)
                    <div class="mb-1">
                        <span class="badge badge-info">ID: {{ $order->pancake_order_id }}</span>
                    </div>
                @endif

                <div>
                    @if($order->is_livestream)
                        <span class="badge badge-info">Livestream</span>
                    @endif
                    @if($order->is_live_shopping)
                        <span class="badge badge-primary">Live Shopping</span>
                    @endif
                </div>

                <div class="mt-1">
                    @if($order->is_free_shipping)
                        <span class="badge badge-success">Free Ship</span>
                    @endif
                    @if($order->customer_pay_fee)
                        <span class="badge badge-warning">KH trả phí</span>
                    @endif
                </div>

                @if($order->partner_fee > 0)
                    <div class="mt-1">
                        <span class="badge badge-secondary">Phí ĐT: {{ number_format($order->partner_fee, 0, ',', '.') }}đ</span>
                    </div>
                @endif

                @if($order->returned_reason)
                    <div class="mt-1">
                        <span class="badge badge-danger" title="Lý do hoàn/hủy đơn">
                            Lý do: {{ $order->returned_reason }}
                        </span>
                    </div>
                @endif

                @if($order->tracking_code)
                    <div class="mt-1 text-truncate" style="max-width: 120px;" title="Mã vận đơn: {{ $order->tracking_code }}">
                        <i class="fas fa-truck fa-fw"></i> {{ $order->tracking_code }}
                    </div>
                @endif
            </div>
        </td>
        <td>{{($order->pancake_inserted_at) }}</td>
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
            $colspan = 6; // Base columns: Mã đơn, KH, SĐT, Trạng thái Pancake, Pancake Info, Ngày tạo, Hành động = 7
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
