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
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">Thống kê phân phối đơn hàng cho nhân viên</h3>
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
                                @if(count($staffStats) == 0)
                                    <tr>
                                        <td colspan="3" class="text-center">Không có dữ liệu nhân viên</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="mt-3">
                            <a href="{{ route('admin.sales-staff.index') }}" class="btn btn-sm btn-info">
                                <i class="fas fa-users"></i> Quản lý nhân viên Sale
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(count($skippedStaffReasons) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">Nhân viên bị bỏ qua khi đồng bộ</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Có {{count($skippedStaffReasons)}} nhân viên bị bỏ qua trong quá trình đồng bộ!</h5>
                        <p>Dưới đây là lý do và dữ liệu nhận được từ Pancake</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Lý do bỏ qua</th>
                                    <th>Dữ liệu từ API</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($skippedStaffReasons as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><span class="badge badge-danger">{{ $item['reason'] }}</span></td>
                                        <td>
                                            <pre class="code-data">{{ json_encode($item['data'], JSON_PRETTY_PRINT) }}</pre>
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
    @endif

    <!-- Modal to show skipped employees -->
    <div class="modal fade" id="skippedEmployeesModal" tabindex="-1" role="dialog" aria-labelledby="skippedEmployeesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="skippedEmployeesModalLabel">Danh sách nhân viên bị bỏ qua</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="skippedEmployeesLoading" class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Đang tải dữ liệu...</p>
                    </div>
                    <div id="skippedEmployeesContent" style="display:none;">
                        <p>Lần đồng bộ cuối: <span id="lastSyncTime">N/A</span></p>
                        <div class="alert alert-info" id="noSkippedMessage" style="display:none;">
                            <i class="fas fa-info-circle"></i> Không có nhân viên nào bị bỏ qua trong lần đồng bộ gần nhất.
                        </div>
                        <div id="skippedEmployeesList" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Lý do bỏ qua</th>
                                            <th>Dữ liệu JSON từ Pancake</th>
                                        </tr>
                                    </thead>
                                    <tbody id="skippedEmployeesTable">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="skippedEmployeesError" class="alert alert-danger" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@stop

@push('css')
<style>
    .code-data {
        max-height: 200px;
        overflow-y: auto;
        font-size: 12px;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
    }

    .json-viewer {
        max-height: 200px;
        overflow-y: auto;
        background: #f5f5f5;
        padding: 10px;
        border-radius: 4px;
        font-size: 11px;
        font-family: monospace;
    }
</style>
@endpush

@push('js')
<script>
    $(document).ready(function() {
        // Add button handler for checking skipped employees
        $('#checkSkippedEmployeesBtn').on('click', function() {
            $('#skippedEmployeesModal').modal('show');
            loadSkippedEmployees();
        });

        function loadSkippedEmployees() {
            $('#skippedEmployeesLoading').show();
            $('#skippedEmployeesContent, #skippedEmployeesError').hide();
            
            $.ajax({
                url: "{{ route('admin.sync.skipped-employees') }}",
                type: "GET",
                dataType: "json",
                success: function(response) {
                    $('#skippedEmployeesLoading').hide();
                    $('#skippedEmployeesContent').show();
                    
                    if (response.success) {
                        // Show last sync time if available
                        if (response.last_sync) {
                            $('#lastSyncTime').text(response.last_sync);
                        } else {
                            $('#lastSyncTime').text('Không có dữ liệu');
                        }
                        
                        // Check if there are skipped employees
                        if (response.skipped_count > 0) {
                            $('#skippedEmployeesList').show();
                            $('#noSkippedMessage').hide();
                            
                            // Clear and populate the table
                            $('#skippedEmployeesTable').empty();
                            
                            $.each(response.skipped_reasons, function(index, reason) {
                                var row = '<tr>' +
                                    '<td>' + (index + 1) + '</td>' +
                                    '<td><span class="badge badge-danger">' + reason.reason + '</span></td>' +
                                    '<td><div class="json-viewer">' + JSON.stringify(reason.data, null, 2) + '</div></td>' +
                                    '</tr>';
                                $('#skippedEmployeesTable').append(row);
                            });
                            
                        } else {
                            $('#skippedEmployeesList').hide();
                            $('#noSkippedMessage').show();
                        }
                    } else {
                        showError(response.message || 'Có lỗi xảy ra khi tải dữ liệu');
                    }
                },
                error: function(xhr, status, error) {
                    $('#skippedEmployeesLoading').hide();
                    showError('Lỗi khi kết nối đến máy chủ: ' + error);
                }
            });
        }
        
        function showError(message) {
            $('#skippedEmployeesError').show();
            $('#errorMessage').text(message);
        }
    });
</script>
@endpush
