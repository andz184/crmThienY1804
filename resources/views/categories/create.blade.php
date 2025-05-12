@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Category</h1>

    @include('partials._alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @include('categories._form', ['category' => new \App\Models\Category()])
            </form>
        </div>
    </div>
</div>
@endsection
