@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Edit Product: {{ $product->name }}</h1>

    @include('partials._alerts')

    <div class="card mb-3">
        <div class="card-header">Product Details</div>
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="POST">
                @method('PUT')
                @include('products._form', ['product' => $product, 'categories' => $categories])
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>Product Variants ({{ $product->variants?->count() ?? 0 }})</span>
                {{-- Button to trigger modal for adding new variant --}}
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVariantModal">
                    Add New Variant
                </button>
            </div>
        </div>
        <div class="card-body" id="product-variants-list">
            @include('products.variants._variants_list', ['variants' => $product->variants, 'product' => $product])
        </div>
    </div>
</div>

{{-- Modal for Adding Variant --}}
<div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVariantModalLabel">Add New Variant to {{ $product->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addVariantForm" action="{{ route('admin.products.variants.store', $product) }}" method="POST">
                    @csrf
                    {{-- We will create this partial next --}}
                    @include('products.variants._form_modal', ['variant' => new \App\Models\ProductVariant(), 'product' => $product])
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Variant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Placeholder for Edit Variant Modal (to be loaded via JS) --}}
<div class="modal fade" id="editVariantModal" tabindex="-1" aria-labelledby="editVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Content will be loaded here by JavaScript --}}
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Add Variant Form Submission via AJAX
        const addVariantForm = document.getElementById('addVariantForm');
        if (addVariantForm) {
            addVariantForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh variants list (simple reload of partial or more complex DOM update)
                        fetch("{{ route('admin.products.variants.index', $product) }}")
                           .then(response => response.text())
                           .then(html => {
                                document.getElementById('product-variants-list').innerHTML = html;
                                bootstrap.Modal.getInstance(document.getElementById('addVariantModal')).hide();
                                // Show success message (you'll need a toast/alert mechanism)
                                alert(data.message);
                            });
                    } else {
                        // Handle errors (display them in the modal)
                        alert(data.message || 'Error adding variant. Please check input.');
                        // You might want to parse and display validation errors more gracefully here
                        console.error(data.errors);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
            });
        }

        // Handle Edit Variant Modal Triggering & Form Submission (similar AJAX pattern)
        const productVariantsList = document.getElementById('product-variants-list');
        if (productVariantsList) {
            productVariantsList.addEventListener('click', function(e) {
                if (e.target.matches('.edit-variant-btn') || e.target.closest('.edit-variant-btn')) {
                    e.preventDefault();
                    const button = e.target.matches('.edit-variant-btn') ? e.target : e.target.closest('.edit-variant-btn');
                    const editUrl = button.dataset.editUrl.replace('products', 'admin.products');
                    const updateUrl = button.dataset.updateUrl.replace('products', 'admin.products');

                    fetch(editUrl) // URL to get the edit form (products.variants.edit route)
                        .then(response => response.text())
                        .then(html => {
                            const editModal = document.getElementById('editVariantModal');
                            editModal.querySelector('.modal-content').innerHTML = html;
                            const modalInstance = new bootstrap.Modal(editModal);
                            modalInstance.show();

                            // Attach submit handler to the dynamically loaded form
                            const editVariantForm = editModal.querySelector('#editVariantFormDynamic'); // Ensure your loaded form has this ID
                            if(editVariantForm) {
                                editVariantForm.addEventListener('submit', function(submitEvent) {
                                    submitEvent.preventDefault();
                                    const formData = new FormData(this);
                                    fetch(updateUrl, { // Use data-update-url from button
                                        method: 'POST', // Should be PUT, but forms need _method field
                                        body: formData,
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    })
                                    .then(resp => resp.json())
                                    .then(data => {
                                        if(data.success) {
                                            fetch("{{ route('admin.products.variants.index', $product) }}")
                                               .then(response => response.text())
                                               .then(html => {
                                                    document.getElementById('product-variants-list').innerHTML = html;
                                                    modalInstance.hide();
                                                    alert(data.message);
                                                });
                                        } else {
                                            alert(data.message || 'Error updating variant.');
                                            console.error(data.errors);
                                        }
                                    }).catch(err => {
                                        console.error('Error:', err);
                                        alert('An unexpected error occurred while updating.');
                                    });
                                });
                            }
                        }).catch(err => console.error('Failed to load edit form:', err));
                }
            });
        }
    });
</script>
@endpush
