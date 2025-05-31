@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Thêm sản phẩm mới</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.products.store') }}" method="POST">
                        @csrf
                        @include('admin.products.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
