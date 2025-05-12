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
                <span>Product Variations ({{ $product->variations->count() }})</span>
                {{-- Button to trigger modal for adding new variation --}}
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                    Add New Variation
                </button>
            </div>
        </div>
        <div class="card-body" id="product-variations-list">
            @include('products.variations._variations_list', ['variations' => $product->variations, 'product' => $product])
        </div>
    </div>
</div>

{{-- Modal for Adding Variation --}}
<div class="modal fade" id="addVariationModal" tabindex="-1" aria-labelledby="addVariationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVariationModalLabel">Add New Variation to {{ $product->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addVariationForm" action="{{ route('admin.products.variations.store', $product) }}" method="POST">
                    @csrf
                    {{-- We will create this partial next --}}
                    @include('products.variations._form_modal', ['variation' => new \App\Models\ProductVariation(), 'product' => $product])
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Variation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Placeholder for Edit Variation Modal (to be loaded via JS) --}}
<div class="modal fade" id="editVariationModal" tabindex="-1" aria-labelledby="editVariationModalLabel" aria-hidden="true">
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
        // Handle Add Variation Form Submission via AJAX
        const addVariationForm = document.getElementById('addVariationForm');
        if (addVariationForm) {
            addVariationForm.addEventListener('submit', function (e) {
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
                        // Refresh variations list (simple reload of partial or more complex DOM update)
                        fetch("{{ route('admin.products.variations.index', $product) }}")
                           .then(response => response.text())
                           .then(html => {
                                document.getElementById('product-variations-list').innerHTML = html;
                                bootstrap.Modal.getInstance(document.getElementById('addVariationModal')).hide();
                                // Show success message (you'll need a toast/alert mechanism)
                                alert(data.message);
                            });
                    } else {
                        // Handle errors (display them in the modal)
                        alert(data.message || 'Error adding variation. Please check input.');
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

        // Handle Edit Variation Modal Triggering & Form Submission (similar AJAX pattern)
        const productVariationsList = document.getElementById('product-variations-list');
        if (productVariationsList) {
            productVariationsList.addEventListener('click', function(e) {
                if (e.target.matches('.edit-variation-btn') || e.target.closest('.edit-variation-btn')) {
                    e.preventDefault();
                    const button = e.target.matches('.edit-variation-btn') ? e.target : e.target.closest('.edit-variation-btn');
                    const editUrl = button.dataset.editUrl.replace('products', 'admin.products');
                    const updateUrl = button.dataset.updateUrl.replace('products', 'admin.products');

                    fetch(editUrl) // URL to get the edit form (products.variations.edit route)
                        .then(response => response.text())
                        .then(html => {
                            const editModal = document.getElementById('editVariationModal');
                            editModal.querySelector('.modal-content').innerHTML = html;
                            const modalInstance = new bootstrap.Modal(editModal);
                            modalInstance.show();

                            // Attach submit handler to the dynamically loaded form
                            const editVariationForm = editModal.querySelector('#editVariationFormDynamic'); // Ensure your loaded form has this ID
                            if(editVariationForm) {
                                editVariationForm.addEventListener('submit', function(submitEvent) {
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
                                            fetch("{{ route('admin.products.variations.index', $product) }}")
                                               .then(response => response.text())
                                               .then(html => {
                                                    document.getElementById('product-variations-list').innerHTML = html;
                                                    modalInstance.hide();
                                                    alert(data.message);
                                                });
                                        } else {
                                            alert(data.message || 'Error updating variation.');
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
