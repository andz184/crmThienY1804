@forelse ($customers as $customer)
    <tr>
        <td><input type="checkbox" class="customer-checkbox" value="{{ $customer->id }}"></td>
        <td>
            <a href="{{ route('customers.show', $customer) }}"><strong>{{ $customer->name }}</strong></a>
        </td>
        <td>{{ $customer->phone }}</td>
        <td>{{ $customer->email ?? 'N/A' }}</td>
        <td>{{ $customer->total_orders_count }}</td>
        <td>{{ $customer->formatted_total_spent }}</td>
        <td>{{ $customer->formatted_last_order_date }}</td>
        <td>
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-info" title="Xem"><i class="fas fa-eye"></i></a>
            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning" title="Sửa"><i class="fas fa-edit"></i></a>
            @endcan
            @can('customers.delete')
                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline delete-customer-form" data-customer-name="{{ $customer->name }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng ' + this.dataset.customerName + ' không?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                </form>
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center">Không tìm thấy khách hàng nào.</td>
    </tr>
@endforelse
