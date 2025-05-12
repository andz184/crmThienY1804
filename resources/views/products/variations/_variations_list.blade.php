<table class="table table-sm table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>SKU</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($variations as $variation)
            <tr>
                <td>{{ $variation->name }}</td>
                <td>{{ $variation->sku }}</td>
                <td>{{ number_format($variation->price, 2) }}</td>
                <td>{{ $variation->stock_quantity }}</td>
                <td>
                    @if ($variation->is_active)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-danger">No</span>
                    @endif
                </td>
                <td>
                    <button type="button" class="btn btn-xs btn-warning edit-variation-btn"
                            data-bs-toggle="modal" data-bs-target="#editVariationModal"
                            data-edit-url="{{ route('products.variations.edit', [$product, $variation]) }}"
                            data-update-url="{{ route('products.variations.update', [$product, $variation]) }}">
                        Edit
                    </button>
                    <form action="{{ route('products.variations.destroy', [$product, $variation]) }}" method="POST" class="d-inline variation-delete-form"
                          onsubmit="return confirm('Are you sure you want to delete this variation? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No variations found for this product.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Script to handle delete form submission via AJAX for smoother UX --}}
<script>
    document.querySelectorAll('.variation-delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm(this.getAttribute('onsubmit').replace('return confirm(\'','').replace('\');',''))) return;

            const formData = new FormData(this);
            fetch(this.action, {
                method: 'POST', // Form method is POST, Laravel handles DELETE via _method
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetch("{{ route('products.variations.index', $product) }}")
                       .then(response => response.text())
                       .then(html => {
                            document.getElementById('product-variations-list').innerHTML = html;
                            alert(data.message);
                        });
                } else {
                    alert(data.message || 'Error deleting variation.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred while deleting variation.');
            });
        });
    });
</script>
