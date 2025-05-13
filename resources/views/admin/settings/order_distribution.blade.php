@extends('adminlte::page')

@section('title', 'Cài đặt phân phối đơn hàng')

@section('content_header')
    <h1>Cài đặt phân phối đơn hàng</h1>
@stop

@section('content')
    @include('layouts.partials.alert')

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Cài đặt phân phối đơn hàng</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update-order-distribution') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="order_distribution_type">Kiểu phân phối đơn hàng</label>
                            <select name="order_distribution_type" id="order_distribution_type" class="form-control @error('order_distribution_type') is-invalid @enderror">
                                <option value="sequential" {{ $settings['order_distribution_type'] === 'sequential' ? 'selected' : '' }}>
                                    Tuần tự (1,2,3) - Chia đều đơn hàng cho nhân viên
                                </option>
                                <option value="batch" {{ $settings['order_distribution_type'] === 'batch' ? 'selected' : '' }}>
                                    Theo lô (VD: 33,1,33,1) - Chia theo số lượng cụ thể
                                </option>
                            </select>
                            @error('order_distribution_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Chọn cách thức phân phối đơn hàng cho nhân viên
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="order_distribution_pattern">Mẫu phân phối</label>
                            <input type="text" name="order_distribution_pattern" id="order_distribution_pattern"
                                   class="form-control @error('order_distribution_pattern') is-invalid @enderror"
                                   value="{{ $settings['order_distribution_pattern'] }}"
                                   placeholder="VD: 1,2,3 hoặc 33,1,33,1">
                            @error('order_distribution_pattern')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                <strong>Hướng dẫn:</strong><br>
                                - Kiểu tuần tự (1,2,3): Mỗi số đại diện cho số đơn mỗi nhân viên nhận. VD: 1,1,1 = chia đều cho 3 người<br>
                                - Kiểu theo lô (33,1,33,1): Số đơn sẽ được chia theo thứ tự. VD: 33 đơn cho người 1, 1 đơn cho người 2, 33 đơn cho người 3, ...
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">Thống kê phân phối</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Đơn đang xử lý</th>
                                    <th>Tổng đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffStats as $staff)
                                    <tr>
                                        <td>{{ $staff->name }}</td>
                                        <td>{{ $staff->processing_orders_count }}</td>
                                        <td>{{ $staff->total_orders_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
<script>
    $(document).ready(function() {
        // Add any JavaScript functionality here
    });
</script>
@endpush
