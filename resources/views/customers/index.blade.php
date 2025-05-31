@extends('adminlte::page')

@section('title', 'Danh sách khách hàng')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
    <h1>Danh sách khách hàng</h1>
        <div>
            @can('customers.view_trashed')
                <a href="{{ route('customers.archive') }}" class="btn btn-warning btn-sm mr-1" title="Thùng rác"><i class="fas fa-trash-alt mr-1"></i>Thùng rác</a>
            @endcan
            {{-- @can('customers.sync')
                <button id="sync-customers-btn" class="btn btn-info btn-sm mr-1">Đồng bộ từ Đơn hàng</button>
            @endcan
            @can('customers.create')
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Thêm khách hàng</a>
            @endcan --}}
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
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Tên, SĐT, email..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_from" class="mr-1">Từ ngày tạo:</label>
                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_to" class="mr-1">Đến ngày tạo:</label>
                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="min_orders" class="mr-1">Min đơn:</label>
                    <input type="number" name="min_orders" id="min_orders" class="form-control form-control-sm" placeholder="Min" value="{{ $filters['min_orders'] ?? '' }}" style="width: 80px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_orders" class="mr-1">Max đơn:</label>
                    <input type="number" name="max_orders" id="max_orders" class="form-control form-control-sm" placeholder="Max" value="{{ $filters['max_orders'] ?? '' }}" style="width: 80px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="min_spent" class="mr-1">Min chi tiêu:</label>
                    <input type="number" name="min_spent" id="min_spent" class="form-control form-control-sm" placeholder="Min" value="{{ $filters['min_spent'] ?? '' }}" style="width: 100px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="max_spent" class="mr-1">Max chi tiêu:</label>
                    <input type="number" name="max_spent" id="max_spent" class="form-control form-control-sm" placeholder="Max" value="{{ $filters['max_spent'] ?? '' }}" style="width: 100px;">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="last_order_status" class="mr-1">Đơn cuối:</label>
                    <select name="last_order_status" id="last_order_status" class="form-control form-control-sm">
                        <option value="">-- Trạng thái --</option>
                        <option value="completed" {{ ($filters['last_order_status'] ?? '') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="pending" {{ ($filters['last_order_status'] ?? '') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="assigned" {{ ($filters['last_order_status'] ?? '') == 'assigned' ? 'selected' : '' }}>Đã gán</option>
                        <option value="calling" {{ ($filters['last_order_status'] ?? '') == 'calling' ? 'selected' : '' }}>Đang gọi</option>
                        <option value="failed" {{ ($filters['last_order_status'] ?? '') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                        <option value="canceled" {{ ($filters['last_order_status'] ?? '') == 'canceled' ? 'selected' : '' }}>Đã hủy</option>
                        <option value="no_answer" {{ ($filters['last_order_status'] ?? '') == 'no_answer' ? 'selected' : '' }}>Không nghe máy</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2">Lọc</button>
                <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm mb-2" id="reset-filter">Xóa lọc</a>
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
                            <th>Tên</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Tổng đơn</th>
                            <th>Tổng chi tiêu</th>
                            <th>Đơn cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="customer-table-body">
                        @include('customers._customers_table_body', ['customers' => $customers])
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix" id="pagination-links">
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

<!-- Progress Modal -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" role="dialog" aria-labelledby="syncProgressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false"> {{-- Prevent closing during progress --}}
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncProgressModalLabel">Đang đồng bộ Khách hàng...</h5>
                {{-- No close button while in progress initially --}}
            </div>
            <div class="modal-body">
                <p id="sync-message">Đang xử lý, vui lòng đợi...</p>
                <div class="progress mt-2" style="height: 25px;" id="sync-progress-bar-wrapper-modal">
                    <div id="sync-progress-bar-modal" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
            </div>
            <div class="modal-footer" id="sync-progress-modal-footer" style="display: none;">
                 <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close-sync-modal-btn">Đóng</button>
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
    function fetch_customers(url) {
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.table_html) {
                    $('#customer-table-body').html(response.table_html);
                    $('#pagination-links').html(response.pagination_html);
                    updateBulkDeleteButtonState();
                } else {
                    // If not AJAX response, reload the page
                    window.location.href = url;
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                // On error, reload the page
                window.location.href = url;
            }
        });
    }

    // Handle pagination clicks
    $(document).on('click', '#pagination-links .pagination a', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        fetch_customers(url);
        // Update URL without reloading
        window.history.pushState({}, '', url);
    });

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        var url = $(this).attr('action') + '?' + $(this).serialize();
        history.pushState(null, '', url); // Update URL
        fetch_customers(url);
    });

    // Reset filter button
    $('#reset-filter').on('click', function(e) {
        e.preventDefault();
        $('#filter-form').find('input[type="text"], input[type="date"], input[type="number"], select').val('');
        var url = $('#filter-form').attr('action');
        history.pushState(null, '', url);
        fetch_customers(url);
    });

    // Đồng bộ khách hàng AJAX - New flow with modals
    $('#sync-customers-btn').on('click', function(e) {
        e.preventDefault();
        $('#confirmSyncModal').modal('show');
    });

    $('#execute-sync-btn').on('click', function() {
        $('#confirmSyncModal').modal('hide');
        $('#syncProgressModal').modal('show');

        let $btn = $('#sync-customers-btn');
        let $barWrapper = $('#sync-progress-bar-wrapper-modal');
        let $bar = $('#sync-progress-bar-modal');
        let $syncMessage = $('#sync-message');
        let $progressModalFooter = $('#sync-progress-modal-footer');

        $btn.prop('disabled', true);
        $barWrapper.show();
        $bar.css('width', '0%').text('0%').removeClass('bg-success bg-danger').addClass('progress-bar-animated');
        $('#syncProgressModalLabel').text('Đang đồng bộ với Pancake...');
        $syncMessage.html(`
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="sr-only">Đang tải...</span>
                </div>
                <div>Đang kết nối với Pancake API...</div>
                <small class="text-muted">Vui lòng không đóng cửa sổ này trong quá trình đồng bộ.</small>
            </div>
        `);
        $progressModalFooter.hide();

        $.ajax({
            url: "{{ route('customers.sync') }}",
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            timeout: 7200000, // Tăng lên 2 giờ (2 * 60 * 60 * 1000 ms)
            success: function(res) {
                $bar.css('width', '100%');

                // Hiển thị chi tiết kết quả từ Pancake
                let resultHtml = '<div class="sync-results mt-3">';

                if (res.pancake_data) {
                    resultHtml += `
                        <div class="alert alert-info">
                            <strong>Dữ liệu từ Pancake:</strong><br>
                            - Tổng số khách hàng: ${res.pancake_data.total || 0}<br>
                            - Đã đồng bộ: ${res.pancake_data.synced || 0}<br>
                            - Bỏ qua: ${res.pancake_data.skipped || 0}<br>
                            ${res.is_initial_sync ? '<small class="text-muted">Đây là lần đồng bộ đầu tiên. Các lần sau sẽ nhanh hơn.</small>' : ''}
                        </div>
                    `;
                }

                if (res.errors && res.errors.length > 0) {
                    resultHtml += `
                        <div class="alert alert-warning">
                            <strong>Lỗi trong quá trình đồng bộ:</strong><br>
                            ${res.errors.map(err => `- ${err}`).join('<br>')}
                        </div>
                    `;
                }

                resultHtml += '</div>';

                if (res.success) {
                    $bar.addClass('bg-success').removeClass('progress-bar-animated').text('Hoàn tất!');
                    $('#syncProgressModalLabel').text('Đồng bộ Hoàn tất');
                    $syncMessage.html(`
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle"></i> ${res.message || 'Đồng bộ thành công!'}
                        </div>
                        ${resultHtml}
                    `);
                } else {
                    $bar.addClass('bg-warning').removeClass('progress-bar-animated').text('Hoàn thành với cảnh báo');
                    $('#syncProgressModalLabel').text('Đồng bộ Hoàn thành (Có Cảnh Báo)');
                    $syncMessage.html(`
                        <div class="text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i> ${res.message || 'Đồng bộ hoàn tất nhưng có một số vấn đề.'}
                        </div>
                        ${resultHtml}
                    `);
                }

                // Thêm nút làm mới
                $progressModalFooter.html(`
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Làm mới trang
                    </button>
                `).show();
            },
            error: function(xhr) {
                let errorMsg = 'Lỗi kết nối với máy chủ.';
                let detailError = '';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.error) {
                        detailError = xhr.responseJSON.error;
                    }
                    if (xhr.responseJSON.pancake_error) {
                        detailError = `Lỗi từ Pancake: ${xhr.responseJSON.pancake_error}`;
                    }
                }

                $bar.addClass('bg-danger').removeClass('progress-bar-animated').text('Lỗi!');
                $('#syncProgressModalLabel').text('Đồng bộ Thất bại');
                $syncMessage.html(`
                    <div class="alert alert-danger">
                        <strong>Lỗi:</strong> ${errorMsg}<br>
                        ${detailError ? `<small class="text-danger">${detailError}</small>` : ''}
                    </div>
                `);
                $progressModalFooter.html(`
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-danger" onclick="$('#execute-sync-btn').click()">
                        <i class="fas fa-redo"></i> Thử lại
                    </button>
                `).show();
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Optional: Reset progress modal when it's fully hidden if user closes it manually
    $('#syncProgressModal').on('hidden.bs.modal', function () {
        let $bar = $('#sync-progress-bar-modal');
        let $syncMessage = $('#sync-message');
        $bar.css('width', '0%').text('0%').removeClass('bg-success bg-danger bg-warning').addClass('progress-bar-animated');
        $('#syncProgressModalLabel').text('Đang đồng bộ Khách hàng...');
        $syncMessage.text('Đang xử lý, vui lòng đợi...');
        $('#sync-progress-modal-footer').hide();
        // If you want to reload after closing a successful sync modal:
        // if ($bar.hasClass('bg-success')) { location.reload(); }
    });

    // --- Bulk Delete Logic ---
    const $bulkDeleteBtn = $('#bulk-delete-btn');
    const $selectedCountSpan = $('#selected-count');
    const $selectAllCheckbox = $('#select-all-customers');
    const $customerTableBody = $('#customer-table-body');
    const $confirmBulkDeleteModal = $('#confirmBulkDeleteModal');
    const $bulkDeleteModalCount = $('#bulk-delete-modal-count');
    const $executeBulkDeleteBtn = $('#execute-bulk-delete-btn');

    function updateBulkDeleteButtonState() {
        const selectedIds = $customerTableBody.find('.customer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        $selectedCountSpan.text(selectedIds.length);
        if (selectedIds.length > 0) {
            $bulkDeleteBtn.show();
        } else {
            $bulkDeleteBtn.hide();
        }
        $bulkDeleteModalCount.text(selectedIds.length);
    }

    $selectAllCheckbox.on('change', function() {
        $customerTableBody.find('.customer-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkDeleteButtonState();
    });

    $customerTableBody.on('change', '.customer-checkbox', function() {
        updateBulkDeleteButtonState();
        if (!$(this).prop('checked')) {
            $selectAllCheckbox.prop('checked', false);
        }
    });

    // Initial state update in case table is loaded via AJAX later and has checked items
    // For AJAX loaded content, this needs to be called in success callback of fetch_customers
    updateBulkDeleteButtonState();

    // Trigger bulk delete confirmation
    $bulkDeleteBtn.on('click', function() {
        $confirmBulkDeleteModal.modal('show');
    });

    // Execute bulk delete
    $executeBulkDeleteBtn.on('click', function() {
        const selectedIds = $customerTableBody.find('.customer-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Vui lòng chọn ít nhất một khách hàng để xóa.');
            return;
        }

        $.ajax({
            url: "{{ route('customers.bulkDelete') }}", // Route to be defined
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: selectedIds
            },
            beforeSend: function() {
                $executeBulkDeleteBtn.prop('disabled', true).text('Đang xóa...');
            },
            success: function(response) {
                $confirmBulkDeleteModal.modal('hide');
                alert(response.message || 'Xóa khách hàng thành công!');
                // Refresh customer list or remove rows - for simplicity, reload page
                location.reload();
            },
            error: function(xhr) {
                let errorMsg = 'Lỗi xóa khách hàng.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                alert('Lỗi: ' + errorMsg);
            },
            complete: function() {
                 $executeBulkDeleteBtn.prop('disabled', false).text('Xóa');
            }
        });
    });

    // --- SweetAlert2 for Session Success/Error Messages on Page Load (for non-AJAX fallbacks if any) ---
    @if(session('success'))
        console.log('Session success message detected:', '{{ session("success") }}');
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '{{ session("success") }}',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    @endif

    @if(session('error'))
        console.log('Session error message detected:', '{{ session("error") }}');
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '{{ session("error") }}',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    @endif

    // Xử lý đồng bộ khách hàng
    $('#sync-customers-btn').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Đang đồng bộ...').prop('disabled', true);

        $.ajax({
            url: '{{ route("customers.sync") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success(response.message);
                // Reload trang sau khi đồng bộ thành công
                window.location.reload();
            },
            error: function(xhr) {
                var message = xhr.responseJSON ? xhr.responseJSON.message : 'Có lỗi xảy ra khi đồng bộ';
                toastr.error(message);
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

});
</script>
@stop

@section('plugins.Sweetalert2', true)