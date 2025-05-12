@extends('adminlte::page')

@section('title', 'Gán đơn hàng')

@section('content_header')
    <h1>Gán đơn hàng: {{ $order->order_code }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('orders.update', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="user_id">Chọn nhân viên</label>
                        <select name="user_id" class="form-control" required>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}" {{ $order->user_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Gán đơn</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
@stop 