{{-- This form is used for both Add and Edit (when loaded dynamically for edit) --}}
{{-- For edit, the form action and method will be set by JS or on the wrapping form tag --}}
{{-- If this is loaded dynamically for edit, ensure it has id="editVariationFormDynamic" --}}

<input type="hidden" name="_method" value="{{ $variation->exists ? 'PUT' : 'POST' }}"> {{-- This might be handled by the main form tag too --}}

<div class="mb-3">
    <label for="variation_name_{{ $variation->id ?? 'new' }}" class="form-label">Variation Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="variation_name_{{ $variation->id ?? 'new' }}" name="name" value="{{ old('name', $variation->name ?? '') }}" required>
    {{-- Add @error for specific modal errors if needed --}}
</div>

<div class="mb-3">
    <label for="variation_sku_{{ $variation->id ?? 'new' }}" class="form-label">SKU (Unique) <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="variation_sku_{{ $variation->id ?? 'new' }}" name="sku" value="{{ old('sku', $variation->sku ?? '') }}" required>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="variation_price_{{ $variation->id ?? 'new' }}" class="form-label">Price <span class="text-danger">*</span></label>
        <input type="number" step="0.01" class="form-control" id="variation_price_{{ $variation->id ?? 'new' }}" name="price" value="{{ old('price', $variation->price ?? '0.00') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="variation_stock_quantity_{{ $variation->id ?? 'new' }}" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="variation_stock_quantity_{{ $variation->id ?? 'new' }}" name="stock_quantity" value="{{ old('stock_quantity', $variation->stock_quantity ?? '0') }}" required>
    </div>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="variation_is_active_{{ $variation->id ?? 'new' }}" name="is_active" value="1" {{ old('is_active', $variation->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="variation_is_active_{{ $variation->id ?? 'new' }}">
        Active
    </label>
</div>

{{-- Note: The submit buttons are typically part of the modal footer, not this partial itself when used for AJAX Modals --}}
