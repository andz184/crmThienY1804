@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Chỉnh sửa sản phẩm: {{ $product->name }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.products.update', $product) }}" method="POST">
                        @method('PUT')
                        @include('admin.products.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
