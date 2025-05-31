@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $product->name }}</h5>
                    <div>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">Edit Product</a>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-secondary">Back to List</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <dl class="row">
                                <dt class="col-sm-4">SKU</dt>
                                <dd class="col-sm-8">{{ $product->sku }}</dd>

                                <dt class="col-sm-4">Description</dt>
                                <dd class="col-sm-8">{{ $product->description ?: 'N/A' }}</dd>

                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-danger' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Total Stock</dt>
                                <dd class="col-sm-8">{{ $product->total_stock }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6>Pancake Information</h6>
                            <dl class="row">
                                <dt class="col-sm-4">Pancake ID</dt>
                                <dd class="col-sm-8">{{ $product->pancake_id ?: 'N/A' }}</dd>

                                <dt class="col-sm-4">Last Sync</dt>
                                <dd class="col-sm-8">
                                    {{ $product->metadata['last_sync'] ?? 'Never' }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Variants ({{ $product->variants->count() }})</h5>
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addVariantModal">
                        Add Variant
                    </button>
                </div>
                <div class="card-body">
                    @if($product->variants->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Cost</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->variants as $variant)
                                        <tr>
                                            <td>{{ $variant->name }}</td>
                                            <td>{{ $variant->sku }}</td>
                                            <td>{{ number_format($variant->price, 2) }}</td>
                                            <td>{{ number_format($variant->cost, 2) }}</td>
                                            <td>{{ $variant->stock }}</td>
                                            <td>
                                                <a href="{{ route('products.variants.edit', [$product, $variant]) }}"
                                                   class="btn btn-sm btn-info">Edit</a>
                                                <form action="{{ route('products.variants.destroy', [$product, $variant]) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this variant?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="p-3">No variants found for this product.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Variant Modal -->
<div class="modal fade" id="addVariantModal" tabindex="-1" role="dialog" aria-labelledby="addVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVariantModalLabel">Add New Variant</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('products.variants.store', $product) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input type="text" class="form-control" id="sku" name="sku" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="form-group">
                        <label for="cost">Cost</label>
                        <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Variant</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
