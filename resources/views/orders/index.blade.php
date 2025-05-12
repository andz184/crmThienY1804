@extends('adminlte::page')

@section('title', 'Đơn hàng')

{{-- Add CSRF token for AJAX requests --}}
@section('meta_tags')
    <meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            Quản lý Đơn hàng
            @switch($filterType ?? null)
                @case('new_orders')
                    <small class="text-muted font-weight-normal">- Đơn hàng Mới</small>
                    @break
                @case('pushed_to_pancake')
                    <small class="text-muted font-weight-normal">- Đã đẩy Pancake OK</small>
                    @break
                @case('pancake_push_failed_stock')
                    <small class="text-muted font-weight-normal">- Đẩy Pancake: Lỗi Stock</small>
                    @break
                @case('pancake_not_successfully_pushed')
                    <small class="text-muted font-weight-normal">- Pancake: Chưa đẩy hoặc Lỗi</small>
                    @break
                @default
                    {{-- No specific filter title or for 'All Orders' --}}
            @endswitch
        </h1>
        <div>
            <a href="{{ route('orders.trashed') }}" class="btn btn-warning btn-sm">Thùng rác (Đã xóa)</a>
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">Thêm mới</a>
        </div>
    </div>
@stop

@section('content')

{{-- Thông báo thành công/lỗi --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if (session('error'))
     <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

{{-- Bộ lọc và Tìm kiếm --}}
<div class="card card-outline card-info mb-3">
    <div class="card-header">
        <h3 class="card-title">Bộ lọc Đơn hàng</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        {{-- Use current route for form action to preserve filter context --}}
        <form method="GET" action="{{ Request::url() }}" class="form-inline">
            <div class="row w-100">
                <!-- Cột 1 -->
                <div class="col-md-4">
                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('manager'))
                    <div class="form-group w-100 mb-3">
                        <label for="sale_id" class="d-block w-100 mb-2">Nhân viên Sale:</label>
                        <select name="sale_id" id="sale_id" class="form-control select2 w-100">
                            <option value="">-- Tất cả --</option>
                            @foreach($sales as $id => $name)
                                <option value="{{ $id }}" {{ request('sale_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Filter Trạng thái Đẩy Pancake (formerly CRM status) --}}
                    {{-- This dropdown now controls Pancake Push Status. Its name is 'status' --}}
                    <div class="form-group w-100 mb-3">
                        <label for="status" class="d-block w-100 mb-2">Trạng thái Đẩy Pancake:</label>
                        <select name="status" id="status" class="form-control select2 w-100"
                                @if(in_array($filterType, ['pushed_to_pancake', 'pancake_push_failed_stock', 'pancake_not_successfully_pushed'])) disabled @endif>
                            <option value="">-- Tất cả Pancake --</option>
                            <option value="success" {{ (request('status') == 'success' || $filterType === 'pushed_to_pancake') ? 'selected' : '' }}>Đã đẩy OK</option>
                            <option value="failed_stock" {{ (request('status') == 'failed_stock' || $filterType === 'pancake_push_failed_stock') ? 'selected' : '' }}>Lỗi Stock</option>
                            <option value="not_successfully_pushed" {{ (request('status') == 'not_successfully_pushed' || $filterType === 'pancake_not_successfully_pushed') ? 'selected' : '' }}>Chưa đẩy hoặc Lỗi</option>
                        </select>
                        @if(in_array($filterType, ['pushed_to_pancake', 'pancake_push_failed_stock', 'pancake_not_successfully_pushed']))
                            @php
                                $fixedPancakeStatusValue = match($filterType) {
                                    'pushed_to_pancake' => 'success',
                                    'pancake_push_failed_stock' => 'failed_stock',
                                    'pancake_not_successfully_pushed' => 'not_successfully_pushed',
                                    default => ''
                                };
                            @endphp
                            <input type="hidden" name="status" value="{{ $fixedPancakeStatusValue }}" />
                        @endif
                    </div>

                    {{-- Filter Trạng thái Đơn hàng (formerly Pancake Push Status) --}}
                    {{-- This dropdown now controls CRM Order Status. Its name is 'pancake_push_status_filter' --}}
                    <div class="form-group w-100 mb-3">
                        <label for="pancake_push_status_filter" class="d-block w-100 mb-2">Trạng thái Đơn hàng:</label>
                        <select name="pancake_push_status_filter" id="pancake_push_status_filter" class="form-control select2 w-100"
                                @if($filterType === 'new_orders') disabled @endif>
                            <option value="">-- Tất cả ĐH --</option>
                            <option value="{{ App\Models\Order::STATUS_MOI }}" {{ (request('pancake_push_status_filter') == App\Models\Order::STATUS_MOI || $filterType === 'new_orders') ? 'selected' : '' }}>Mới</option>
                            <option value="{{ App\Models\Order::STATUS_CAN_XU_LY }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_CAN_XU_LY ? 'selected' : '' }}>Cần xử lý</option>
                            <option value="{{ App\Models\Order::STATUS_CHO_HANG }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_CHO_HANG ? 'selected' : '' }}>Chờ hàng</option>
                            <option value="{{ App\Models\Order::STATUS_DA_DAT_HANG }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_DAT_HANG ? 'selected' : '' }}>Đã đặt hàng</option>
                            <option value="{{ App\Models\Order::STATUS_CHO_CHUYEN_HANG }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_CHO_CHUYEN_HANG ? 'selected' : '' }}>Chờ chuyển hàng</option>
                            <option value="{{ App\Models\Order::STATUS_DA_GUI_HANG }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_GUI_HANG ? 'selected' : '' }}>Đã gửi hàng</option>
                            <option value="{{ App\Models\Order::STATUS_DA_NHAN }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_NHAN ? 'selected' : '' }}>Đã nhận</option>
                            <option value="{{ App\Models\Order::STATUS_DA_NHAN_DOI }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_NHAN_DOI ? 'selected' : '' }}>Đã nhận (đổi)</option>
                            <option value="{{ App\Models\Order::STATUS_DA_THU_TIEN }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_THU_TIEN ? 'selected' : '' }}>Đã thu tiền</option>
                            <option value="{{ App\Models\Order::STATUS_DA_HOAN }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_HOAN ? 'selected' : '' }}>Đã hoàn</option>
                            <option value="{{ App\Models\Order::STATUS_DA_HUY }}" {{ request('pancake_push_status_filter') == App\Models\Order::STATUS_DA_HUY ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                        @if($filterType === 'new_orders')
                            <input type="hidden" name="pancake_push_status_filter" value="{{ App\Models\Order::STATUS_MOI }}" />
                        @endif
                    </div>
                </div>

                <!-- Cột 2 -->
                <div class="col-md-4">
                    <div class="form-group w-100 mb-3">
                        <label for="warehouse_id" class="d-block w-100 mb-2">Kho hàng:</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-control select2 w-100">
                            <option value="">-- Tất cả kho --</option>
                            @if(isset($warehouses) && $warehouses->count() > 0)
                                @foreach($warehouses as $id => $name)
                                    <option value="{{ $id }}" {{ request('warehouse_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group w-100 mb-3">
                        <label for="payment_method" class="d-block w-100 mb-2">Phương thức thanh toán:</label>
                        <select name="payment_method" id="payment_method" class="form-control select2 w-100">
                            <option value="">-- Tất cả --</option>
                            <option value="cod" {{ request('payment_method') == 'cod' ? 'selected' : '' }}>COD</option>
                            <option value="banking" {{ request('payment_method') == 'banking' ? 'selected' : '' }}>Chuyển khoản</option>
                            <option value="momo" {{ request('payment_method') == 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                            <option value="zalopay" {{ request('payment_method') == 'zalopay' ? 'selected' : '' }}>ZaloPay</option>
                            <option value="other" {{ request('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>

                    <div class="form-group w-100 mb-3">
                        <label for="shipping_provider" class="d-block w-100 mb-2">Đơn vị vận chuyển:</label>
                        <select name="shipping_provider_id" id="shipping_provider_id" class="form-control select2 w-100">
                            <option value="">-- Tất cả --</option>
                            @if(isset($shippingProviders) && $shippingProviders->count() > 0)
                                @foreach($shippingProviders as $id => $name)
                                    <option value="{{ $id }}" {{ request('shipping_provider_id') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group w-100 mb-3">
                        <label class="d-block w-100 mb-2">Phí vận chuyển:</label>
                        <div class="input-group">
                            <input type="number" name="min_shipping_fee" id="min_shipping_fee"
                                class="form-control" placeholder="Từ"
                                value="{{ request('min_shipping_fee') }}">
                            <div class="input-group-append input-group-prepend">
                                <span class="input-group-text">đến</span>
                            </div>
                            <input type="number" name="max_shipping_fee" id="max_shipping_fee"
                                class="form-control" placeholder="Đến"
                                value="{{ request('max_shipping_fee') }}">
                        </div>
                    </div>
                </div>

                <!-- Cột 3 -->
                <div class="col-md-4">
                    <div class="form-group w-100 mb-3">
                        <label for="date_range" class="d-block w-100 mb-2">Khoảng thời gian:</label>
                        <div class="input-group">
                            <input type="date" name="date_from" id="date_from" class="form-control"
                                value="{{ request('date_from') }}" placeholder="Từ ngày">
                            <div class="input-group-append input-group-prepend">
                                <span class="input-group-text">đến</span>
                            </div>
                            <input type="date" name="date_to" id="date_to" class="form-control"
                                value="{{ request('date_to') }}" placeholder="Đến ngày">
                        </div>
                    </div>

                    <div class="form-group w-100 mb-3">
                        <label for="search_term" class="d-block w-100 mb-2">Tìm kiếm:</label>
                        <input type="text" name="search_term" id="search_term" class="form-control w-100"
                            value="{{ request('search_term') }}"
                            placeholder="Tìm theo mã đơn, tên KH, SĐT...">
                    </div>
                </div>

                <!-- Nút tìm kiếm -->
                <div class="col-12 mt-3">
                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        {{-- Reset button should ideally go back to the current filtered view's base if applicable, or general index --}}
                        <a href="{{ Request::url() }}" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Đặt lại bộ lọc hiện tại
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- Bảng đơn hàng --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Danh sách Đơn hàng</h3>

    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th style="min-width: 120px;">Mã Đơn</th>
                        <th style="min-width: 150px;">Khách hàng</th>
                        <th style="min-width: 120px;">Điện thoại</th>
                        @canany(['view orders', 'view team orders'])
                            <th style="min-width: 120px;">Sale</th>
                        @endcanany
                        <th style="min-width: 150px;">Trạng thái CRM</th>
                        <th style="min-width: 150px;">Trạng thái Pancake</th>
                        <th style="min-width: 120px;">Ngày tạo</th>
                        <th style="min-width: 160px;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="order-table-body">
                    @include('orders._order_table_body', ['orders' => $orders])
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix" id="pagination-links">
        {{ $orders->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>

{{-- Modal cập nhật trạng thái --}}
@can('manage_calls')
<div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
  {{-- Nội dung modal giữ nguyên --}}
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="updateStatusForm" method="POST" action="">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="updateStatusModalLabel">Cập nhật trạng thái đơn hàng: <span id="modal_order_code"></span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="call_id" id="modal_call_id">
          <div class="form-group">
                <label for="modal_status">Trạng thái mới</label>
                <select name="status" id="modal_status" class="form-control" required>
                    <option value="">-- Chọn trạng thái --</option>
                     @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
             <div class="form-group">
                <label for="modal_notes">Ghi chú cuộc gọi</label>
                <textarea name="notes" id="modal_notes" class="form-control" rows="3"></textarea>
            </div>
             <div class="form-group">
                <label>File ghi âm (Simulation)</label>
                <p><a id="modal_recording_url" href="#" target="_blank"></a></p>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endcan

@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    /* CSS cho thông báo loading */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.7);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .loading-overlay .spinner-border {
        width: 3rem;
        height: 3rem;
    }
    .status-badge {
        cursor: pointer;
    }
    /* Đảm bảo các nút có kích thước đồng nhất */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .btn-sm i {
        font-size: 0.875rem;
        width: 1rem;
        text-align: center;
        vertical-align: middle;
    }
    /* Khoảng cách giữa các nút */
    .btn-sm + .btn-sm {
        margin-left: 0.25rem;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .card-header .btn {
            margin-top: 0.5rem;
        }
        .form-inline {
            flex-direction: column;
        }
        .form-inline .form-group {
            margin-right: 0;
            margin-bottom: 1rem;
            width: 100%;
        }
        .form-inline label {
            margin-bottom: 0.5rem;
        }
        .form-inline .form-control {
            width: 100%;
        }
        .form-inline .btn {
            width: 100%;
            margin: 0.25rem 0;
        }
        .table-responsive {
            border: 0;
            margin-bottom: 0;
        }
        .table-responsive > .table {
            margin-bottom: 0;
        }
        .table td, .table th {
            white-space: nowrap;
        }
    }

    /* Custom scrollbar styles */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endsection

@section('js')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const DEFAULT_CALL_NUMBER_FALLBACK = '0332360850'; // Fallback if data-phone is missing

    document.querySelectorAll('.btn-call').forEach(btn => {
        btn.addEventListener('click', function() {
            const phoneNumberToCall = this.dataset.phone || DEFAULT_CALL_NUMBER_FALLBACK;
            const callWindowUrl = `{{ route('calls.window') }}?phone_number=${encodeURIComponent(phoneNumberToCall)}`;

            // Kích thước cửa sổ có thể điều chỉnh
            const windowFeatures = 'width=380,height=600,resizable=yes,scrollbars=no,status=no';

            // Kiểm tra xem cửa sổ đã mở chưa để tránh mở nhiều lần (tùy chọn)
            // let callWin = window.open('', 'CallWindow'); // Cố gắng tham chiếu đến cửa sổ đã có tên
            // if (callWin.closed || !callWin.location || callWin.location.href === 'about:blank') {
            //     callWin = window.open(callWindowUrl, 'CallWindow', windowFeatures);
            // } else {
            //     callWin.focus(); // Nếu đã mở thì focus vào nó
            //      // Có thể gửi thông điệp để cửa sổ đó gọi số mới nếu cần
            //      // callWin.postMessage({ type: 'NEW_CALL', number: phoneNumberToCall }, '*');
            // }

            // Mở cửa sổ mới (phiên bản đơn giản, luôn mở mới hoặc ghi đè nếu cùng tên)
            window.open(callWindowUrl, 'CallWindow', windowFeatures);

            console.log(`Attempting to open call window for: ${phoneNumberToCall}`);
        });
    });

    // Xử lý modal cập nhật trạng thái (giữ nguyên logic này nếu cần)
    const updateStatusModal = $('#updateStatusModal');
    const updateStatusForm = $('#updateStatusForm');
    const modalOrderCode = $('#modal_order_code');
    const modalCallId = $('#modal_call_id');
    const modalStatus = $('#modal_status');
    const modalNotes = $('#modal_notes');
    const modalRecordingUrl = $('#modal_recording_url');

    $(document).on('click', '.status-badge', function() {
        const orderId = $(this).data('order-id');
        const orderCode = $(this).data('order-code');
        const callId = $(this).data('call-id'); // This might need to come from the popup now
        const currentStatus = $(this).data('current-status');
        const recordingUrl = $(this).data('recording-url');

        modalOrderCode.text(orderCode);
        modalCallId.val(callId || '');
        modalStatus.val(currentStatus || '');
        modalNotes.val('');
        if(recordingUrl && recordingUrl !== '#'){
            modalRecordingUrl.attr('href', recordingUrl).text(recordingUrl.split('/').pop() || 'Nghe file ghi âm');
            modalRecordingUrl.parent().show();
        } else {
            modalRecordingUrl.parent().hide();
        }
        updateStatusForm.attr('action', `/orders/${orderId}/update-status-after-call`);
        updateStatusModal.modal('show');
    });

    // TODO: Consider how to get call_id and recording_url from the popup window
    // back to the main page to populate the updateStatusModal. This usually involves
    // window.postMessage from the popup to this page (window.opener).
    // For now, clicking status badge will open modal without these if call made via popup.

    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Date range validation
        $('#date_from, #date_to').on('change', function() {
            var dateFrom = $('#date_from').val();
            var dateTo = $('#date_to').val();

            if (dateFrom && dateTo) {
                if (dateFrom > dateTo) {
                    // Original date validation might have an alert or action here
                    // For now, leaving the if block as is.
                } // Closes if (dateFrom > dateTo)
            } // Closes if (dateFrom && dateTo)
        }); // Closes $('#date_from, #date_to').on('change', ...)
    }); // THIS NOW CORRECTLY CLOSES: $(document).ready(function() { ... });

    // --- New JavaScript for Pushing to Pancake ---
    $(document).on('click', '.btn-push-pancake', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        const url = $(this).data('url');
        const button = $(this);

        Swal.fire({
            title: 'Xác nhận đẩy đơn?',
            text: `Bạn có chắc muốn đẩy đơn hàng #${orderId} lên Pancake không?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý, đẩy ngay!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state on button
                button.html('<i class="fas fa-spinner fa-spin fa-fw"></i> Đang đẩy...').prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content') // CSRF Token
                    },
                    success: function(response) {
                        button.html('<i class="fas fa-rocket fa-fw"></i>').prop('disabled', false); // Reset button
                        if (response.success) {
                            Swal.fire(
                                'Thành công!',
                                response.message || 'Đơn hàng đã được đẩy lên Pancake.',
                                'success'
                            ).then(() => {
                                // Optional: Refresh page or update table row
                                // location.reload();
                                // Or update a specific cell if you have internal_status displayed
                                // $(`#order-row-${orderId}-internal-status`).text('Pushed to Pancake');
                            });
                        } else {
                            Swal.fire(
                                'Lỗi!',
                                response.message || 'Không thể đẩy đơn hàng lên Pancake.',
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        button.html('<i class="fas fa-rocket fa-fw"></i>').prop('disabled', false); // Reset button
                        let errorMessage = 'Lỗi không xác định khi đẩy đơn hàng.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire(
                            'Lỗi Máy Chủ!',
                            errorMessage,
                            'error'
                        );
                        console.error("Pancake push error:", xhr.responseText);
                    }
                });
            }
        });
    });
    // --- End of New JavaScript ---

}); // THIS NOW CORRECTLY CLOSES: document.addEventListener('DOMContentLoaded', () => { ... });
</script>
@stop

