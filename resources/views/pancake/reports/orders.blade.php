<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Sync Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $order->order_id }}</td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                <td>
                    <span class="badge bg-{{ $order->status_color }}">
                        {{ $order->status }}
                    </span>
                </td>
                <td>{{ number_format($order->total_amount, 2) }}</td>
                <td>
                    <span class="badge bg-{{ $order->sync_status_color }}">
                        {{ $order->sync_status }}
                    </span>
                </td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($order->sync_status === 'failed')
                        <button class="btn btn-sm btn-warning" onclick="retrySync({{ $order->id }})">
                            <i class="fas fa-sync"></i>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No orders found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $orders->links() }}
</div>
@endif

<script>
function retrySync(orderId) {
    if (confirm('Are you sure you want to retry syncing this order?')) {
        fetch(`/orders/${orderId}/push-to-pancake`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to retry sync: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error occurred while retrying sync');
        });
    }
}
</script>
