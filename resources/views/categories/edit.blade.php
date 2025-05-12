@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Category: {{ $category->name }}</h1>

    @include('partials._alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @method('PUT')
                @include('categories._form', ['category' => $category])
            </form>
        </div>
    </div>
</div>
@endsection
