@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Phân phối đơn hàng</h3>
                    @can('configure_distribution_settings')
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingsModal">
                            <i class="fas fa-cog"></i> Cài đặt phân phối
                        </button>
                    </div>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Staff Statistics -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Thống kê nhân viên</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Nhân viên</th>
                                                    <th>Đang xử lý</th>
                                                    <th>Tổng đơn</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($staffStats as $stat)
                                                <tr>
                                                    <td>{{ $stat->name }}</td>
                                                    <td>{{ $stat->processing_orders }}</td>
                                                    <td>{{ $stat->total_orders }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Pattern -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Mẫu phân phối</h4>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Loại phân phối</label>
                                        <select class="form-control" id="distributionType">
                                            <option value="sequential">Tuần tự (1,2,3)</option>
                                            <option value="batch">Theo lô (33,1,33,1)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Mẫu phân phối</label>
                                        <input type="text" class="form-control" id="distributionPattern"
                                               placeholder="VD: 1,2,3 hoặc 33,1,33,1">
                                    </div>
                                    @can('manage_order_distribution')
                                    <button class="btn btn-primary" id="savePattern">Lưu mẫu</button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <!-- Active Staff -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Nhân viên hoạt động</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Tên</th>
                                                    <th>Trạng thái</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($activeStaff as $staff)
                                                <tr>
                                                    <td>{{ $staff->name }}</td>
                                                    <td>
                                                        <span class="badge {{ $staff->is_active ? 'badge-success' : 'badge-danger' }}">
                                                            {{ $staff->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @can('manage_order_distribution')
                                                        <button class="btn btn-sm {{ $staff->is_active ? 'btn-danger' : 'btn-success' }}"
                                                                onclick="toggleStaffStatus({{ $staff->id }})">
                                                            {{ $staff->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                                                        </button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
@can('configure_distribution_settings')
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cài đặt phân phối</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tự động phân phối</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="autoDistribute">
                        <label class="custom-control-label" for="autoDistribute">Bật/Tắt</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Thời gian chờ (phút)</label>
                    <input type="number" class="form-control" id="waitTime" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="saveSettings">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
$(document).ready(function() {
    // Save distribution pattern
    $('#savePattern').click(function() {
        $.ajax({
            url: '{{ route("distribution.savePattern") }}',
            method: 'POST',
            data: {
                type: $('#distributionType').val(),
                pattern: $('#distributionPattern').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('Đã lưu mẫu phân phối');
            },
            error: function(error) {
                toastr.error('Có lỗi xảy ra');
            }
        });
    });

    // Save settings
    $('#saveSettings').click(function() {
        $.ajax({
            url: '{{ route("distribution.saveSettings") }}',
            method: 'POST',
            data: {
                auto_distribute: $('#autoDistribute').is(':checked'),
                wait_time: $('#waitTime').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('Đã lưu cài đặt');
                $('#settingsModal').modal('hide');
            },
            error: function(error) {
                toastr.error('Có lỗi xảy ra');
            }
        });
    });
});

function toggleStaffStatus(staffId) {
    $.ajax({
        url: `/staff/${staffId}/toggle-status`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            location.reload();
        },
        error: function(error) {
            toastr.error('Có lỗi xảy ra');
        }
    });
}
</script>
@endpush
@endsection
