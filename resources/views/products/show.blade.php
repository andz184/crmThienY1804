@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1>Product: {{ $product->name }}</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning me-2">Edit Product</a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    @include('partials._alerts')

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-header">Product Details</div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">ID</dt>
                        <dd class="col-sm-8">{{ $product->id }}</dd>

                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $product->name }}</dd>

                        <dt class="col-sm-4">Slug</dt>
                        <dd class="col-sm-8">{{ $product->slug }}</dd>

                        <dt class="col-sm-4">Category</dt>
                        <dd class="col-sm-8">{{ $product->category->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{!! nl2br(e($product->description)) !!}</dd>

                        <dt class="col-sm-4">Base Price</dt>
                        <dd class="col-sm-8">{{ number_format($product->base_price, 2) }}</dd>

                        <dt class="col-sm-4">Active</dt>
                        <dd class="col-sm-8">{{ $product->is_active ? 'Yes' : 'No' }}</dd>

                        <dt class="col-sm-4">Created At</dt>
                        <dd class="col-sm-8">{{ $product->created_at->format('Y-m-d H:i:s') }}</dd>

                        <dt class="col-sm-4">Updated At</dt>
                        <dd class="col-sm-8">{{ $product->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Variations ({{ $product->variations->count() }})</div>
                <div class="card-body p-0">
                    @if($product->variations->count() > 0)
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->variations as $variation)
                                    <tr>
                                        <td>{{ $variation->name }}</td>
                                        <td>{{ $variation->sku }}</td>
                                        <td>{{ number_format($variation->price, 2) }}</td>
                                        <td>{{ $variation->stock_quantity }}</td>
                                        <td>{{ $variation->is_active ? 'Yes' : 'No' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="p-3">No variations found for this product.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
