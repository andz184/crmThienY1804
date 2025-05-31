@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Chỉnh sửa danh mục: {{ $category->name }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                        @method('PUT')
                        @include('admin.categories.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
