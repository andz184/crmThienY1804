@extends('adminlte::page')

@section('title', 'Gán nhân viên Sale')

@section('content_header')
    <h1>Gán nhân viên Sale cho đơn hàng: {{ $order->order_code }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('orders.updateAssignment', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="assigning_seller_id">Chọn nhân viên Sale</label>
                        <select name="assigning_seller_id" id="assigning_seller_id" class="form-control" required>
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach($assignableUsersList as $id => $name)
                                <option value="{{ $id }}" {{ (old('assigning_seller_id', $order->assigning_seller_id) == $id) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Gán nhân viên</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
@stop 