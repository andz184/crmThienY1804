@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1>Category: {{ $category->name }}</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning me-2">Edit</a>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    @include('partials._alerts')

    <div class="card mb-3">
        <div class="card-header">Details</div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $category->id }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $category->name }}</dd>

                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $category->slug }}</dd>

                <dt class="col-sm-3">Parent Category</dt>
                <dd class="col-sm-9">{{ $category->parent->name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $category->description ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Active</dt>
                <dd class="col-sm-9">{{ $category->is_active ? 'Yes' : 'No' }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $category->created_at->format('Y-m-d H:i:s') }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $category->updated_at->format('Y-m-d H:i:s') }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Products in this Category ({{ $category->products->count() }})</div>
        <div class="card-body">
            @if($category->products->count() > 0)
                <ul>
                    @foreach($category->products as $product)
                        <li><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></li>
                    @endforeach
                </ul>
            @else
                <p>No products found in this category.</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">Sub-categories ({{ $category->children->count() }})</div>
        <div class="card-body">
            @if($category->children->count() > 0)
                <ul>
                    @foreach($category->children as $child)
                        <li><a href="{{ route('categories.show', $child) }}">{{ $child->name }}</a></li>
                         {{-- You might want to display grandchildren recursively if needed --}}
                    @endforeach
                </ul>
            @else
                <p>No sub-categories found.</p>
            @endif
        </div>
    </div>

</div>
@endsection
