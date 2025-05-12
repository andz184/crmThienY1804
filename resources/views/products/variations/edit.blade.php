<div class="modal-header">
    <h5 class="modal-title" id="editVariationModalLabel">Edit Variation for {{ $product->name }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    {{-- The form ID needs to match what the JS in products/edit.blade.php expects for the edit form --}}
    <form id="editVariationFormDynamic" action="{{ route('products.variations.update', [$product, $variation]) }}" method="POST">
        @csrf
        @method('PUT') {{-- This is important for the update route --}}
        @include('products.variations._form_modal', ['variation' => $variation, 'product' => $product])
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
