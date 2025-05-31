@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Synchronization with Pancake</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h5 class="card-title mb-0">Sync from Pancake</h5>
                                </div>
                                <div class="card-body">
                                    <p>Pull products and inventory data from Pancake to CRM.</p>
                                    <button id="syncFromPancakeBtn" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Start Sync
                                    </button>
                                    <div id="syncFromPancakeStatus" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h5 class="card-title mb-0">Push to Pancake</h5>
                                </div>
                                <div class="card-body">
                                    <p>Push selected products to Pancake.</p>
                                    <button id="pushToPancakeBtn" class="btn btn-warning" disabled>
                                        <i class="fas fa-upload"></i> Push Selected
                                    </button>
                                    <div id="pushToPancakeStatus" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="productsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Total Stock</th>
                                    <th>Status</th>
                                    <th>Last Sync</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <tr>
                                    <td><input type="checkbox" class="product-select" value="{{ $product->id }}"></td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->total_stock }}</td>
                                    <td>
                                        <span class="badge badge-{{ $product->is_active ? 'success' : 'danger' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ optional($product->metadata)['last_sync'] ? \Carbon\Carbon::parse($product->metadata['last_sync'])->format('Y-m-d H:i:s') : 'Never' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info sync-inventory" data-product-id="{{ $product->id }}">
                                            <i class="fas fa-sync"></i> Sync Inventory
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#productsTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print"]
    });

    // Handle select all checkbox
    $('#selectAll').change(function() {
        $('.product-select').prop('checked', $(this).prop('checked'));
        updatePushButton();
    });

    // Handle individual checkboxes
    $('.product-select').change(function() {
        updatePushButton();
    });

    // Update push button state
    function updatePushButton() {
        const selectedCount = $('.product-select:checked').length;
        $('#pushToPancakeBtn').prop('disabled', selectedCount === 0);
    }

    // Handle sync from Pancake
    $('#syncFromPancakeBtn').click(function() {
        const btn = $(this);
        const statusDiv = $('#syncFromPancakeStatus');

        btn.prop('disabled', true);
        statusDiv.html('<div class="alert alert-info">Syncing products from Pancake...</div>');

        $.ajax({
            url: '{{ route("pancake.products.sync") }}',
            method: 'POST',
            success: function(response) {
                statusDiv.html(`<div class="alert alert-success">${response.message}</div>`);
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                statusDiv.html(`<div class="alert alert-danger">${xhr.responseJSON?.message || 'Error syncing products'}</div>`);
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Handle push to Pancake
    $('#pushToPancakeBtn').click(function() {
        const btn = $(this);
        const statusDiv = $('#pushToPancakeStatus');
        const selectedProducts = $('.product-select:checked').map(function() {
            return $(this).val();
        }).get();

        btn.prop('disabled', true);
        statusDiv.html('<div class="alert alert-info">Pushing products to Pancake...</div>');

        const pushPromises = selectedProducts.map(productId =>
            $.ajax({
                url: `/pancake/products/${productId}/push`,
                method: 'POST'
            })
        );

        Promise.all(pushPromises)
            .then(() => {
                statusDiv.html('<div class="alert alert-success">All selected products pushed successfully</div>');
                setTimeout(() => location.reload(), 2000);
            })
            .catch(error => {
                statusDiv.html(`<div class="alert alert-danger">${error.responseJSON?.message || 'Error pushing products'}</div>`);
            })
            .finally(() => {
                btn.prop('disabled', false);
            });
    });

    // Handle inventory sync
    $('.sync-inventory').click(function() {
        const btn = $(this);
        const productId = btn.data('product-id');

        btn.prop('disabled', true);

        $.ajax({
            url: `/pancake/products/${productId}/inventory/sync`,
            method: 'POST',
            success: function(response) {
                toastr.success('Inventory synced successfully');
                setTimeout(() => location.reload(), 1000);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error syncing inventory');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endsection
