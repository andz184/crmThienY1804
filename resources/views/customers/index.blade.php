@extends('adminlte::page')

@section('title', 'Danh sách khách hàng')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
    <h1>Danh sách khách hàng</h1>
        <div>
            @can('customers.view_trashed')
                <a href="{{ route('customers.archive') }}" class="btn btn-warning btn-sm mr-1" title="Thùng rác"><i class="fas fa-trash-alt mr-1"></i>Thùng rác</a>
            @endcan
            @can('customers.sync')
                <button id="sync-customers-btn" class="btn btn-info btn-sm mr-1">
                    <i class="fas fa-sync-alt mr-1"></i>Đồng bộ từ Pancake
                </button>
            @endcan
            @can('customers.create')
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Thêm khách hàng</a>
            @endcan
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    {{-- Thông báo --}}
    @include('partials._alerts')

    {{-- Bộ lọc và Tìm kiếm --}}
    <div class="card card-outline card-primary collapsed-card mb-3" id="filter-card">
        <div class="card-header">
            <h3 class="card-title">Bộ lọc & Tìm kiếm</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body" style="display: none;">
            <form id="filter-form" method="GET" action="{{ route('customers.index') }}" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="search" class="mr-1">Tìm kiếm:</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Tên, SĐT, email..." value="{{ request('search') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_from" class="mr-1">Từ ngày tạo:</label>
                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_to" class="mr-1">Đến ngày tạo:</label>
                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="min_orders" class="mr-1">Min đơn:</label>
                    <input type="number" name="min_orders" id="min_orders" class="form-control form-control-sm" placeholder="Min" value="{{ request('min_orders') }}" style="width: 80px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_orders" class="mr-1">Max đơn:</label>
                    <input type="number" name="max_orders" id="max_orders" class="form-control form-control-sm" placeholder="Max" value="{{ request('max_orders') }}" style="width: 80px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="min_spent" class="mr-1">Min chi tiêu:</label>
                    <input type="number" name="min_spent" id="min_spent" class="form-control form-control-sm" placeholder="Min" value="{{ request('min_spent') }}" style="width: 100px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_spent" class="mr-1">Max chi tiêu:</label>
                    <input type="number" name="max_spent" id="max_spent" class="form-control form-control-sm" placeholder="Max" value="{{ request('max_spent') }}" style="width: 100px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="last_order_status" class="mr-1">Đơn cuối:</label>
                    <select name="last_order_status" id="last_order_status" class="form-control form-control-sm">
                        <option value="">-- Trạng thái --</option>
                        <option value="completed" {{ request('last_order_status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="pending" {{ request('last_order_status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="assigned" {{ request('last_order_status') == 'assigned' ? 'selected' : '' }}>Đã gán</option>
                        <option value="calling" {{ request('last_order_status') == 'calling' ? 'selected' : '' }}>Đang gọi</option>
                        <option value="failed" {{ request('last_order_status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                        <option value="canceled" {{ request('last_order_status') == 'canceled' ? 'selected' : '' }}>Đã hủy</option>
                        <option value="no_answer" {{ request('last_order_status') == 'no_answer' ? 'selected' : '' }}>Không nghe máy</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2">Lọc</button>
                <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm mb-2">Xóa lọc</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Danh sách Khách hàng</h3>
                <div>
                    @can('customers.delete')
                        <button id="bulk-delete-btn" class="btn btn-danger btn-sm mr-1" style="display: none;">Xóa mục đã chọn (<span id="selected-count">0</span>)</button>
                    @endcan
                    @can('customers.sync')
                        <button id="sync-customers-btn" class="btn btn-info btn-sm mr-1">
                            <i class="fas fa-sync-alt mr-1"></i>Đồng bộ từ Pancake
                        </button>
                    @endcan
                    @can('customers.create')
                        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Thêm khách hàng</a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-customers"></th>
                            <th>Mã KH</th>
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Tổng đơn</th>
                            <th>Tổng chi tiêu</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <input type="checkbox" class="customer-checkbox" value="{{ $customer->id }}">
                                </td>
                                <td>{{ $customer->pancake_id }}</td>
                                <td>
                                    <a href="{{ route('customers.show', $customer->id) }}">{{ $customer->name }}</a>
                                </td>
                                <td>{{ $customer->phone }}</td>
                                <td>
                                    @php
                                        $addressParts = [];
                                        if ($customer->street_address) $addressParts[] = $customer->street_address;
                                        if ($customer->ward) {
                                            $wardName = \App\Models\Ward::where('code', $customer->ward)->value('name');
                                            $addressParts[] = $wardName ?? $customer->ward;
                                        }
                                        if ($customer->district) {
                                            $districtName = \App\Models\District::where('code', $customer->district)->value('name');
                                            $addressParts[] = $districtName ?? $customer->district;
                                        }
                                        if ($customer->province) {
                                            $provinceName = \App\Models\Province::where('code', $customer->province)->value('name');
                                            $addressParts[] = $provinceName ?? $customer->province;
                                        }
                                        echo !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
                                    @endphp
                                </td>
                                <td class="text-center">{{ $customer->total_orders_count }}</td>
                                <td class="text-right">{{ number_format($customer->total_spent, 0, ',', '.') }}đ</td>
                                <td>{{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-xs btn-info" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('customers.edit')
                                        <a href="{{ route('customers.edit', $customer) }}"
                                           class="btn btn-xs btn-warning"
                                           title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('customers.delete')
                                        <button type="button"
                                                class="btn btn-xs btn-danger delete-customer"
                                                data-id="{{ $customer->id }}"
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $customers->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmSyncModal" tabindex="-1" role="dialog" aria-labelledby="confirmSyncModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmSyncModalLabel">Xác nhận Đồng bộ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn đồng bộ khách hàng từ Pancake không?
                <br><small class="text-muted">Quá trình này có thể mất từ vài phút đến hơn 10 phút nếu là lần đồng bộ đầu tiên.</small>
                <br><small class="text-muted">Các lần đồng bộ sau sẽ nhanh hơn vì chỉ đồng bộ dữ liệu mới.</small>
                @if($lastSync = Cache::get('pancake_last_sync'))
                <div class="mt-2">
                    <small class="text-info">Lần đồng bộ cuối: {{ \Carbon\Carbon::parse($lastSync)->format('d/m/Y H:i:s') }}</small>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="execute-sync-btn">Đồng bộ</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div class="modal fade" id="confirmBulkDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmBulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmBulkDeleteModalLabel">Xác nhận Xóa hàng loạt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa <strong id="bulk-delete-modal-count">0</strong> khách hàng đã chọn không?
                <br><small class="text-danger">Hành động này sẽ xóa mềm các khách hàng.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="execute-bulk-delete-btn">Xóa</button>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
$(document).ready(function() {
    // Bulk delete functionality
    function updateBulkDeleteButtonState() {
        const selectedIds = $('input.customer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        $('#selected-count').text(selectedIds.length);
        $('#bulk-delete-modal-count').text(selectedIds.length);

        if (selectedIds.length > 0) {
            $('#bulk-delete-btn').show();
        } else {
            $('#bulk-delete-btn').hide();
        }
    }

    // Select all checkbox
    $('#select-all-customers').on('change', function() {
        $('.customer-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkDeleteButtonState();
    });

    // Individual checkboxes
    $(document).on('change', '.customer-checkbox', function() {
        updateBulkDeleteButtonState();
        if (!$(this).prop('checked')) {
            $('#select-all-customers').prop('checked', false);
        }
    });

    // Show bulk delete confirmation modal
    $('#bulk-delete-btn').on('click', function() {
        $('#confirmBulkDeleteModal').modal('show');
    });

    // Execute bulk delete
    $('#execute-bulk-delete-btn').on('click', function() {
        const selectedIds = $('.customer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một khách hàng để xóa.');
            return;
        }

        $.ajax({
            url: "{{ route('customers.bulkDelete') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: selectedIds
            },
            beforeSend: function() {
                $('#execute-bulk-delete-btn').prop('disabled', true).text('Đang xóa...');
            },
            success: function(response) {
                $('#confirmBulkDeleteModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                let errorMsg = 'Lỗi xóa khách hàng.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert('Lỗi: ' + errorMsg);
            },
            complete: function() {
                $('#execute-bulk-delete-btn').prop('disabled', false).text('Xóa');
            }
        });
    });

    // Sync customers functionality
    $('#sync-customers-btn').on('click', function() {
        $('#confirmSyncModal').modal('show');
    });

    $('#execute-sync-btn').on('click', function() {
        let $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang đồng bộ...');

        $.ajax({
            url: '{{ route("customers.sync") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Đồng bộ thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (response.message || 'Có lỗi xảy ra khi đồng bộ'));
                }
            },
            error: function(xhr) {
                let errorMsg = 'Lỗi kết nối với máy chủ.';
                if (xhr.responseJSON) {
                    errorMsg = xhr.responseJSON.message || xhr.responseJSON.error || errorMsg;
                }
                alert('Lỗi: ' + errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('Đồng bộ');
                $('#confirmSyncModal').modal('hide');
            }
        });
    });

    // Delete individual customer
    $(document).on('click', '.delete-customer', function() {
        if (confirm('Bạn có chắc chắn muốn xóa khách hàng này không?')) {
            const customerId = $(this).data('id');
            const deleteUrl = "{{ route('customers.destroy', ':id') }}".replace(':id', customerId);

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    let errorMsg = 'Lỗi xóa khách hàng.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert('Lỗi: ' + errorMsg);
                }
            });
        }
    });
});
</script>
@stop

@section('plugins.Sweetalert2', true)
