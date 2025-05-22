@extends('adminlte::page')

@section('title', 'Quản lý nhân viên Sale')

@section('content_header')
    <h1>Quản lý nhân viên Sale</h1>
@stop

@section('content')
    @include('layouts.partials.alert')

    <div class="card card-info card-outline mb-4">
        <div class="card-header">
            <h3 class="card-title">Hướng dẫn</h3>
        </div>
        <div class="card-body">
            <h5>Quản lý nhân viên Sale</h5>
            <ul>
                <li>Nhân viên Sale là những người dùng có vai trò 'staff' trong hệ thống.</li>
                <li>Bạn có thể tắt/bật trạng thái nhận đơn hàng của nhân viên bằng nút toggle.</li>
                <li>Khi nhân viên tắt trạng thái nhận đơn, các đơn hàng mới sẽ không được phân phối cho họ.</li>
                <li>Với những nhân viên ngừng nhận đơn có đơn hàng đang xử lý, bạn có thể phân phối lại đơn hàng bằng nút phân phối lại.</li>
            </ul>
            
            <h5>Phân phối đơn hàng</h5>
            <ul>
                <li>Hệ thống phân phối đơn hàng mới theo cài đặt ở trang <a href="{{ route('admin.settings.order-distribution') }}">Cài đặt phân phối đơn hàng</a>.</li>
                <li>Sử dụng nút "Phân phối đơn hàng mới" để phân phối các đơn hàng mới chưa được gán cho nhân viên nào.</li>
            </ul>
            
            <div class="mt-3">
                <form action="{{ route('admin.sales-staff.distribute-new-orders') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-random"></i> Phân phối đơn hàng mới
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Bộ lọc</h3>
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.sales-staff.index') }}" method="GET" id="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                placeholder="Tên, Email hoặc ID" value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="active_status">Trạng thái</label>
                            <select class="form-control" id="active_status" name="active_status">
                                <option value="">Tất cả</option>
                                <option value="active" {{ isset($filters['active_status']) && $filters['active_status'] == 'active' ? 'selected' : '' }}>
                                    Đang nhận đơn
                                </option>
                                <option value="inactive" {{ isset($filters['active_status']) && $filters['active_status'] == 'inactive' ? 'selected' : '' }}>
                                    Ngừng nhận đơn
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="has_pancake">Pancake ID</label>
                            <select class="form-control" id="has_pancake" name="has_pancake">
                                <option value="">Tất cả</option>
                                <option value="yes" {{ isset($filters['has_pancake']) && $filters['has_pancake'] == 'yes' ? 'selected' : '' }}>
                                    Đã liên kết
                                </option>
                                <option value="no" {{ isset($filters['has_pancake']) && $filters['has_pancake'] == 'no' ? 'selected' : '' }}>
                                    Chưa liên kết
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="order_count_min">Số đơn tối thiểu</label>
                            <input type="number" class="form-control" id="order_count_min" name="order_count_min" 
                                min="0" value="{{ $filters['order_count_min'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="order_count_max">Số đơn tối đa</label>
                            <input type="number" class="form-control" id="order_count_max" name="order_count_max" 
                                min="0" value="{{ $filters['order_count_max'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                    <a href="{{ route('admin.sales-staff.index') }}" class="btn btn-default">
                        <i class="fas fa-sync"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Danh sách nhân viên Sale</h3>
        </div>
        <div class="card-body">
            @if($salesStaff->isEmpty())
                <div class="alert alert-info">
                    <i class="icon fas fa-info"></i> Không có nhân viên Sale nào trong hệ thống.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Email</th>
                                <th>Pancake ID</th>
                                <th>Trạng thái</th>
                                <th>Đơn đang xử lý</th>
                                <th>Tổng đơn</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesStaff as $staff)
                                <tr>
                                    <td>{{ $staff->name }}</td>
                                    <td>{{ $staff->email }}</td>
                                    <td>
                                        @if($staff->pancake_uuid)
                                            <span class="badge badge-info">{{ $staff->pancake_uuid }}</span>
                                        @else
                                            <span class="badge badge-warning">Chưa liên kết</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="staff-status-badge badge {{ $staff->is_active ? 'badge-success' : 'badge-danger' }}" id="status-badge-{{ $staff->id }}">
                                            {{ $staff->is_active ? 'Đang nhận đơn' : 'Ngừng nhận đơn' }}
                                        </span>
                                    </td>
                                    <td>{{ $staff->processing_orders_count }}</td>
                                    <td>{{ $staff->total_orders_count }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="toggle-status-btn btn btn-sm {{ $staff->is_active ? 'btn-danger' : 'btn-success' }}" 
                                                    data-user-id="{{ $staff->id }}"
                                                    data-current-status="{{ $staff->is_active ? '1' : '0' }}" 
                                                    title="{{ $staff->is_active ? 'Tắt nhận đơn' : 'Bật nhận đơn' }}">
                                                <i class="fas {{ $staff->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </button>

                                            @if($staff->processing_orders_count > 0 && !$staff->is_active)
                                                <form action="{{ route('admin.sales-staff.reassign-orders', $staff) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary ml-1" title="Phân phối lại đơn hàng">
                                                        <i class="fas fa-random"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop

@push('css')
<style>
    .btn-group form {
        display: inline-block;
    }
    .toggle-status-btn.btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    .toggle-status-btn.btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .toggle-status-btn i {
        color: #fff !important;
        font-size: 1.2em;
    }
    .toggle-status-btn.btn-success i {
        color: #fff !important;
    }
    .toggle-status-btn.btn-danger i {
        color: #fff !important;
    }
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    $('.toggle-status-btn').on('click', function() {
        const button = $(this);
        const userId = button.data('user-id');
        const currentStatus = button.data('current-status') === '1';
        
        $.ajax({
            url: '{{ url("admin/sales-staff") }}/' + userId + '/toggle-active',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                button.attr('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin"></i>');
            },
            success: function(response) {
                if (currentStatus) {
                    button.removeClass('btn-danger').addClass('btn-success');
                    button.find('i').removeClass('fa-spinner fa-spin').removeClass('fa-toggle-on').addClass('fa-toggle-off');
                    button.attr('title', 'Bật nhận đơn');
                    $('#status-badge-' + userId).removeClass('badge-success').addClass('badge-danger');
                    $('#status-badge-' + userId).text('Ngừng nhận đơn');
                } else {
                    button.removeClass('btn-success').addClass('btn-danger');
                    button.find('i').removeClass('fa-spinner fa-spin').removeClass('fa-toggle-off').addClass('fa-toggle-on');
                    button.attr('title', 'Tắt nhận đơn');
                    $('#status-badge-' + userId).removeClass('badge-danger').addClass('badge-success');
                    $('#status-badge-' + userId).text('Đang nhận đơn');
                }
                
                button.data('current-status', currentStatus ? '0' : '1');
                
                const alertHtml = '<div class="alert alert-success alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
                    '<h5><i class="icon fas fa-check"></i> Thành công!</h5>' +
                    response.message +
                '</div>';
                
                const existingAlerts = $('.alert-dismissible');
                if (existingAlerts.length) {
                    existingAlerts.remove();
                }
                $('.content-header').after(alertHtml);
                
                setTimeout(function() {
                    $('.alert-dismissible').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 3000);
            },
            error: function(xhr) {
                console.error('Error updating status:', xhr);
                
                const errorHtml = '<div class="alert alert-danger alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' +
                    '<h5><i class="icon fas fa-ban"></i> Lỗi!</h5>' +
                    'Có lỗi xảy ra khi cập nhật trạng thái. Vui lòng thử lại.' +
                '</div>';
                
                const existingAlerts = $('.alert-dismissible');
                if (existingAlerts.length) {
                    existingAlerts.remove();
                }
                $('.content-header').after(errorHtml);
                
                // Reset button state
                if (currentStatus) {
                    button.html('<i class="fas fa-toggle-on"></i>');
                } else {
                    button.html('<i class="fas fa-toggle-off"></i>');
                }
            },
            complete: function() {
                button.attr('disabled', false);
            }
        });
    });

    // Apply filters when filter fields change
    $('#filter-form select').on('change', function() {
        $('#filter-form').submit();
    });
});
</script>
@endpush 