@forelse($customers as $customer)
    <tr>
        <td>
            <input type="checkbox" class="customer-checkbox" value="{{ $customer->id }}">
        </td>
        <td>{{ $customer->customer_code }}</td>
        <td>
            <a href="{{ route('customers.show', $customer->id) }}">{{ $customer->name }}</a>
        </td>
        <td>{{ optional($customer->primaryPhone)->phone_number }}</td>
        <td>
            @php
                $addressParts = [];
                if ($customer->street_address) $addressParts[] = $customer->street_address;
                if ($customer->ward) {
                    $wardName = \App\Models\Ward::where('code', $customer->ward)->value('name');
                    $addressParts[] = $wardName ?? $customer->ward;
                }
                if ($customer->district) {
                    $districtName = \App\Models\District::where('code', $customer->district)->value('name');
                    $addressParts[] = $districtName ?? $customer->district;
                }
                if ($customer->province) {
                    $provinceName = \App\Models\Province::where('code', $customer->province)->value('name');
                    $addressParts[] = $provinceName ?? $customer->province;
                }
                echo !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
            @endphp
        </td>
        <td class="text-center">{{ $customer->total_orders_count }}</td>
        <td class="text-right">{{ number_format($customer->total_spent, 0, ',', '.') }}đ</td>
        <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
        <td>
            <div class="btn-group">
                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-xs btn-info" title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                </a>
                @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}"
                   class="btn btn-xs btn-warning"
                   title="Sửa">
                    <i class="fas fa-edit"></i>
                </a>
                @endcan
                @can('customers.delete')
                <button type="button"
                        class="btn btn-xs btn-danger delete-customer"
                        data-id="{{ $customer->id }}"
                        title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
                @endcan
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center">Không có dữ liệu</td>
    </tr>
@endforelse
