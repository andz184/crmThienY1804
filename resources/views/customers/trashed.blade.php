@extends('adminlte::page')

@section('title', 'Thùng rác Khách hàng')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Thùng rác Khách hàng</h1>
        <div>
            <a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i> Quay lại Danh sách</a>
            {{-- Potential future global actions for trashed items --}}
            {{-- @can('customers.restore_all')
                <button id="bulk-restore-all-btn" class="btn btn-success btn-sm mr-1" title="Khôi phục tất cả"><i class="fas fa-undo-alt mr-1"></i>Khôi phục Tất cả</button>
            @endcan
            @can('customers.empty_trash')
                <button id="empty-trash-btn" class="btn btn-danger btn-sm" title="Dọn sạch thùng rác"><i class="fas fa-times-circle mr-1"></i>Dọn sạch Thùng rác</button>
            @endcan --}}
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    @include('partials._alerts')

    {{-- Bộ lọc và Tìm kiếm (Adapted from index.blade.php) --}}
    <div class="card card-outline card-primary collapsed-card mb-3" id="filter-card-trashed">
        <div class="card-header">
            <h3 class="card-title">Bộ lọc & Tìm kiếm trong thùng rác</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body" style="display: none;">
            {{-- The form will submit to the current route (customers.archive) --}}
            <form id="filter-form-trashed" method="GET" action="{{ route('customers.archive') }}" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="search_trashed" class="mr-1">Tìm kiếm:</label>
                    <input type="text" name="search" id="search_trashed" class="form-control form-control-sm" placeholder="Tên, SĐT, email..." value="{{ request('search') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="deleted_date_from" class="mr-1">Từ ngày xóa:</label>
                    <input type="date" name="deleted_date_from" id="deleted_date_from" class="form-control form-control-sm" value="{{ request('deleted_date_from') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="deleted_date_to" class="mr-1">Đến ngày xóa:</label>
                    <input type="date" name="deleted_date_to" id="deleted_date_to" class="form-control form-control-sm" value="{{ request('deleted_date_to') }}">
                </div>

                {{-- These filters are from index.blade.php and likely non-functional for trashed items without backend changes --}}
                {{--
                <div class="form-group mr-2 mb-2">
                    <label for="min_orders_trashed" class="mr-1">Min đơn (cũ):</label>
                    <input type="number" name="min_orders" id="min_orders_trashed" class="form-control form-control-sm" placeholder="Min" value="{{ request('min_orders') }}" style="width: 80px;" disabled title="Chưa hoạt động cho thùng rác">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_orders_trashed" class="mr-1">Max đơn (cũ):</label>
                    <input type="number" name="max_orders" id="max_orders_trashed" class="form-control form-control-sm" placeholder="Max" value="{{ request('max_orders') }}" style="width: 80px;" disabled title="Chưa hoạt động cho thùng rác">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="min_spent_trashed" class="mr-1">Min chi tiêu (cũ):</label>
                    <input type="number" name="min_spent" id="min_spent_trashed" class="form-control form-control-sm" placeholder="Min" value="{{ request('min_spent') }}" style="width: 100px;" disabled title="Chưa hoạt động cho thùng rác">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_spent_trashed" class="mr-1">Max chi tiêu (cũ):</label>
                    <input type="number" name="max_spent" id="max_spent_trashed" class="form-control form-control-sm" placeholder="Max" value="{{ request('max_spent') }}" style="width: 100px;" disabled title="Chưa hoạt động cho thùng rác">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="last_order_status_trashed" class="mr-1">Đơn cuối (cũ):</label>
                    <select name="last_order_status" id="last_order_status_trashed" class="form-control form-control-sm" disabled title="Chưa hoạt động cho thùng rác">
                        <option value="">-- Trạng thái --</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="assigned">Đã gán</option>
                        <option value="calling">Đang gọi</option>
                        <option value="failed">Thất bại</option>
                        <option value="canceled">Đã hủy</option>
                        <option value="no_answer">Không nghe máy</option>
                    </select>
                </div>
                --}}
                <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2">Lọc</button>
                <a href="{{ route('customers.archive') }}" class="btn btn-secondary btn-sm mb-2" id="reset-filter-trashed">Xóa lọc</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Danh sách khách hàng trong thùng rác</h3>
                <div>
                    {{-- Bulk actions for trashed items --}}
                    @can('customers.restore') {{-- Assuming a general restore permission for bulk --}}
                        <button id="bulk-restore-selected-btn" class="btn btn-success btn-sm mr-1" style="display: none;">Khôi phục mục đã chọn (<span id="selected-trashed-count-restore">0</span>)</button>
                    @endcan
                    @can('customers.force_delete') {{-- Assuming a general force_delete permission for bulk --}}
                        <button id="bulk-force-delete-selected-btn" class="btn btn-danger btn-sm mr-1" style="display: none;">Xóa vĩnh viễn mục đã chọn (<span id="selected-trashed-count-force-delete">0</span>)</button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($trashedCustomers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-trashed-customers"></th>
                                <th>Tên</th>
                                <th>Số điện thoại</th>
                                <th>Email</th>
                                <th>Ngày xóa</th>
                                <th style="width: 120px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="trashed-customer-table-body">
                            @foreach ($trashedCustomers as $customer)
                                <tr id="trashed-customer-row-{{ $customer->id }}">
                                    <td><input type="checkbox" class="trashed-customer-checkbox" value="{{ $customer->id }}"></td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>{{ $customer->email ?? 'N/A' }}</td>
                                    <td>{{ $customer->deleted_at->format('d/m/Y H:i:s') }}</td>
                                    <td>
                                        @can('customers.restore')
                                            <form action="{{ route('customers.restore', $customer->id) }}" method="POST" class="d-inline restore-trashed-customer-form" data-id="{{ $customer->id }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-success" title="Khôi phục"><i class="fas fa-undo"></i></button>
                                            </form>
                                        @endcan
                                        @can('customers.force_delete')
                                            <form action="{{ route('customers.forceDelete', $customer->id) }}" method="POST" class="d-inline force-delete-trashed-customer-form" data-id="{{ $customer->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" title="Xóa vĩnh viễn"><i class="fas fa-skull-crossbones"></i></button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-3 text-center">Thùng rác trống.</p>
            @endif
        </div>
        @if($trashedCustomers->hasPages())
            <div class="card-footer clearfix" id="pagination-links-trashed">
                {{ $trashedCustomers->appends(request()->query())->links() }} {{-- Ensure filters are appended to pagination --}}
            </div>
        @endif
    </div>
</div>

{{-- Modals for bulk actions --}}
<div class="modal fade" id="confirmBulkRestoreModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận Khôi phục hàng loạt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn khôi phục <strong id="bulk-restore-modal-count">0</strong> khách hàng đã chọn không?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="execute-bulk-restore-btn">Khôi phục</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmBulkForceDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận Xóa vĩnh viễn hàng loạt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn XÓA VĨNH VIỄN <strong id="bulk-force-delete-modal-count">0</strong> khách hàng đã chọn không?<br><small class="text-danger">Hành động này không thể hoàn tác!</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="execute-bulk-force-delete-btn">Xóa vĩnh viễn</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // --- Bulk Selection Logic for Trashed Customers ---
    const $selectAllTrashedCheckbox = $('#select-all-trashed-customers');
    const $trashedCustomerTableBody = $('#trashed-customer-table-body');
    const $bulkRestoreBtn = $('#bulk-restore-selected-btn');
    const $bulkForceDeleteBtn = $('#bulk-force-delete-selected-btn');
    const $selectedTrashedCountRestoreSpan = $('#selected-trashed-count-restore');
    const $selectedTrashedCountForceDeleteSpan = $('#selected-trashed-count-force-delete');

    function updateBulkTrashedButtonState() {
        const selectedIds = $trashedCustomerTableBody.find('.trashed-customer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        $selectedTrashedCountRestoreSpan.text(selectedIds.length);
        $selectedTrashedCountForceDeleteSpan.text(selectedIds.length);

        if (selectedIds.length > 0) {
            $bulkRestoreBtn.show();
            $bulkForceDeleteBtn.show();
        } else {
            $bulkRestoreBtn.hide();
            $bulkForceDeleteBtn.hide();
        }
        $('#bulk-restore-modal-count').text(selectedIds.length);
        $('#bulk-force-delete-modal-count').text(selectedIds.length);
    }

    $selectAllTrashedCheckbox.on('change', function() {
        $trashedCustomerTableBody.find('.trashed-customer-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkTrashedButtonState();
    });

    $trashedCustomerTableBody.on('change', '.trashed-customer-checkbox', function() {
        updateBulkTrashedButtonState();
        if (!$(this).prop('checked')) {
            $selectAllTrashedCheckbox.prop('checked', false);
        }
    });
    updateBulkTrashedButtonState(); // Initial check

    // --- Trigger Bulk Action Modals ---
    $bulkRestoreBtn.on('click', function() {
        $('#confirmBulkRestoreModal').modal('show');
    });
    $bulkForceDeleteBtn.on('click', function() {
        $('#confirmBulkForceDeleteModal').modal('show');
    });

    // --- Execute Bulk Actions (Placeholders - requires backend implementation) ---
    $('#execute-bulk-restore-btn').on('click', function() {
        const selectedIds = $trashedCustomerTableBody.find('.trashed-customer-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một khách hàng để khôi phục.'); return;
        }
        // AJAX call to backend for bulk restore
        // Example:
        // $.ajax({
        //     url: "{{-- route('customers.bulkRestore') --}}", // Define this route
        //     type: 'POST', // or PATCH
        //     data: { _token: '{{ csrf_token() }}', ids: selectedIds, _method: 'PATCH' },
        //     success: function(response) { alert(response.message); location.reload(); },
        //     error: function(xhr) { alert('Lỗi khôi phục hàng loạt.'); console.error(xhr); }
        // });
        alert('Chức năng Khôi phục hàng loạt đang được phát triển. Selected IDs: ' + selectedIds.join(', '));
        $('#confirmBulkRestoreModal').modal('hide');
    });

    $('#execute-bulk-force-delete-btn').on('click', function() {
        const selectedIds = $trashedCustomerTableBody.find('.trashed-customer-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một khách hàng để xóa vĩnh viễn.'); return;
        }
        // AJAX call to backend for bulk force delete
        // Example:
        // $.ajax({
        //     url: "{{-- route('customers.bulkForceDelete') --}}", // Define this route
        //     type: 'POST',
        //     data: { _token: '{{ csrf_token() }}', ids: selectedIds, _method: 'DELETE' },
        //     success: function(response) { alert(response.message); location.reload(); },
        //     error: function(xhr) { alert('Lỗi xóa vĩnh viễn hàng loạt.'); console.error(xhr); }
        // });
        alert('Chức năng Xóa vĩnh viễn hàng loạt đang được phát triển. Selected IDs: ' + selectedIds.join(', '));
        $('#confirmBulkForceDeleteModal').modal('hide');
    });

    // Standard onsubmit confirmation for individual restore/force delete (already in HTML)
    // If you want to use AJAX for individual actions as well, you'd add event listeners here
    // for '.restore-trashed-customer-form' and '.force-delete-trashed-customer-form'
    // and preventDefault, then make AJAX calls.
});
</script>
@stop
