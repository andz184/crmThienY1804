@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Cài đặt phân phối đơn hàng') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Thống kê phân phối đơn hàng -->
                    <div class="mb-4">
                        <h5>Thống kê phân phối đơn hàng</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nhân viên</th>
                                        <th>Số đơn đang xử lý</th>
                                        <th>Tổng đơn đã nhận</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($staffStats as $stat)
                                    <tr>
                                        <td>{{ $stat->name }}</td>
                                        <td>{{ $stat->processing_orders_count }}</td>
                                        <td>{{ $stat->total_orders_count }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.update-order-distribution') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="distribution_type">{{ __('Kiểu phân phối đơn hàng') }}</label>
                            <select name="distribution_type" id="distribution_type" class="form-control @error('distribution_type') is-invalid @enderror">
                                <option value="sequential" {{ WebsiteSetting::get('order_distribution_type') === 'sequential' ? 'selected' : '' }}>
                                    Tuần tự (1,2,3) - Chia đều đơn hàng cho nhân viên
                                </option>
                                <option value="batch" {{ WebsiteSetting::get('order_distribution_type') === 'batch' ? 'selected' : '' }}>
                                    Theo lô (VD: 33,1,33,1) - Chia theo số lượng cụ thể
                                </option>
                            </select>
                            @error('distribution_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="distribution_pattern">{{ __('Mẫu phân phối') }}</label>
                            <input type="text" name="distribution_pattern" id="distribution_pattern"
                                   class="form-control @error('distribution_pattern') is-invalid @enderror"
                                   value="{{ WebsiteSetting::get('order_distribution_pattern') }}"
                                   placeholder="VD: 1,2,3 hoặc 33,1,33,1">
                            <small class="form-text text-muted">
                                <strong>Hướng dẫn:</strong><br>
                                - Kiểu tuần tự (1,2,3): Mỗi số đại diện cho số đơn mỗi nhân viên nhận. VD: 1,1,1 = chia đều cho 3 người<br>
                                - Kiểu theo lô (33,1,33,1): Số đơn sẽ được chia theo thứ tự. VD: 33 đơn cho người 1, 1 đơn cho người 2, 33 đơn cho người 3, ...
                            </small>
                            @error('distribution_pattern')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- Danh sách nhân viên -->
                        <div class="form-group mb-3">
                            <label>{{ __('Nhân viên tham gia phân phối') }}</label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tên nhân viên</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($staffMembers as $staff)
                                        <tr>
                                            <td>{{ $staff->name }}</td>
                                            <td>
                                                <span class="badge bg-{{ $staff->status === 'active' ? 'success' : 'danger' }}">
                                                    {{ $staff->status === 'active' ? 'Đang hoạt động' : 'Không hoạt động' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Lưu cài đặt') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('distribution_type').addEventListener('change', function() {
    var patternInput = document.getElementById('distribution_pattern');
    var placeholder = this.value === 'sequential' ? 'VD: 1,1,1' : 'VD: 33,1,33,1';
    patternInput.placeholder = placeholder;
});
</script>
@endpush
@endsection
