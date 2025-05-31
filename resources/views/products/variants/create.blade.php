@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Variant - {{ $product->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('products.variants.store', $product) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="sku">SKU</label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                id="sku" name="sku" value="{{ old('sku') }}" required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                id="price" name="price" value="{{ old('price') }}" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="cost">Cost</label>
                            <input type="number" step="0.01" class="form-control @error('cost') is-invalid @enderror"
                                id="cost" name="cost" value="{{ old('cost') }}" required>
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" class="form-control @error('stock') is-invalid @enderror"
                                id="stock" name="stock" value="{{ old('stock') }}" required>
                            @error('stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Variant</button>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
