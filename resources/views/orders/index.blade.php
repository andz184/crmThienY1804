@extends('adminlte::page')

@section('title', 'Đơn hàng')

@section('meta_tags')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
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
            @endswitch
        </h1>
        <div>
            <a href="{{ route('orders.trashed') }}" class="btn btn-warning"><i class='bx bx-trash me-2'></i>Thùng rác</a>
            <a href="{{ route('orders.create') }}" class="btn btn-primary ms-2"><i class='bx bx-plus me-2'></i>Thêm mới</a>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class='bx bx-check-circle me-2'></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class='bx bx-x-circle me-2'></i>
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bộ lọc Đơn hàng</h5>
            <button class="btn btn-primary btn-sm btn-icon-toggle" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse" title="Hiển thị/Ẩn bộ lọc">
                <i class='bx bx-filter-alt'></i>
            </button>
        </div>
        <div class="collapse" id="filterCollapse">
            <div class="card-body">
                <form method="GET" action="{{ Request::url() }}">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="form-group">
                                <label for="search">Tìm kiếm</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="Mã đơn, Tên, SĐT..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="form-group">
                                <label for="status">Trạng thái đơn hàng</label>
                                <select name="status" id="status" class="form-control select2">
                                    <option value="">-- Tất cả trạng thái --</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="form-group">
                                <label for="system_status">Trạng thái hệ thống</label>
                                <select name="system_status" id="system_status" class="form-control select2">
                                     <option value="">-- Tất cả --</option>
                                     <option value="PANCAKE_PUSH_OK" {{ request('system_status') == 'PANCAKE_PUSH_OK' ? 'selected' : '' }}>Đã đẩy Pancake OK</option>
                                     <option value="PANCAKE_PUSH_FAILED_OUT_OF_STOCK" {{ request('system_status') == 'PANCAKE_PUSH_FAILED_OUT_OF_STOCK' ? 'selected' : '' }}>Lỗi Stock Pancake</option>
                                     <option value="PANCAKE_PUSH_FAILED_OTHER" {{ request('system_status') == 'PANCAKE_PUSH_FAILED_OTHER' ? 'selected' : '' }}>Lỗi Đẩy Pancake Khác</option>
                                     <option value="NOT_PUSHED_TO_PANCAKE" {{ request('system_status') == 'NOT_PUSHED_TO_PANCAKE' ? 'selected' : '' }}>Chưa đẩy Pancake</option>
                                </select>
                            </div>
                        </div>
                         @canany(['view orders', 'view team orders'])
                         <div class="col-lg-3 col-md-6 mb-3">
                             <div class="form-group">
                                 <label for="user_id">Nhân viên Sale</label>
                                 {{-- Để dropdown này hoạt động, bạn cần truyền biến $salesUsers (collection của User) từ Controller --}}
                                 @if(isset($salesUsers))
                                 <select name="user_id" id="user_id" class="form-control select2">
                                     <option value="">-- Tất cả Sale --</option>
                                     @foreach($salesUsers as $user)
                                         <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                             {{ $user->name }}
                                         </option>
                                     @endforeach
                                 </select>
                                 @else
                                  <input type="text" name="user_id" id="user_id" class="form-control" placeholder="Nhập ID nhân viên Sale" value="{{ request('user_id') }}">
                                  <small class="form-text text-muted">Controller chưa cung cấp danh sách sales.</small>
                                 @endif
                             </div>
                         </div>
                         @endcanany
                         <div class="col-lg-3 col-md-6 mb-3">
                             <div class="form-group">
                                 <label for="date_from">Từ ngày</label>
                                 <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                             </div>
                         </div>
                         <div class="col-lg-3 col-md-6 mb-3">
                             <div class="form-group">
                                 <label for="date_to">Đến ngày</label>
                                 <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                             </div>
                         </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class='bx bx-search me-1'></i> Tìm kiếm
                            </button>
                            <a href="{{ Request::url() }}" class="btn btn-outline-secondary">
                                <i class='bx bx-revision me-1'></i> Đặt lại
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Unified Sync Control Panel -->
    <div class="card card-control-panel mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class='bx bx-sync me-2'></i>Bảng điều khiển đồng bộ</h5>
            <button class="btn btn-light btn-sm btn-icon-toggle" type="button" data-toggle="collapse" data-target="#syncCollapse" aria-expanded="false" aria-controls="syncCollapse" title="Hiển thị/Ẩn bảng điều khiển">
                <i class='bx bxs-chevron-down'></i>
            </button>
        </div>
        <div class="collapse" id="syncCollapse">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-3">
                        <label for="date-range-picker" class="form-label control-panel-label">Chọn khoảng ngày tùy chỉnh</label>
                        <div class="input-group">
                            <input type="text" id="date-range-picker" class="form-control" placeholder="Chọn ngày bắt đầu và kết thúc"/>
                            <button id="sync-date-range-btn" class="btn btn-success"><i class='bx bx-play-circle me-1'></i>Đồng bộ</button>
                        </div>
                    </div>
                    <div class="col-lg-5 mb-3">
                         <label class="form-label control-panel-label">Hoặc chọn nhanh</label>
                         <div class="btn-group w-100" role="group">
                            <button id="sync-preset-today" class="btn btn-outline-secondary btn-preset">Hôm nay</button>
                            <button id="sync-preset-yesterday" class="btn btn-outline-secondary btn-preset">Hôm qua</button>
                            <button id="sync-preset-7days" class="btn btn-outline-secondary btn-preset">7 ngày qua</button>
                            <button id="sync-preset-this-month" class="btn btn-outline-secondary btn-preset">Tháng này</button>
                         </div>
                    </div>
                </div>
                 <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-between">
                        <button id="sync-this-week-btn" class="btn btn-info"><i class='bx bx-calendar-week me-1'></i>Đồng bộ tuần này</button>
                        <button id="cancel-any-sync-btn" class="btn btn-danger" disabled><i class='bx bx-block me-1'></i>Hủy tiến trình</button>
                    </div>
                 </div>
                <div id="sync-progress-container" class="mt-4 d-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <p id="sync-status" class="mb-1 text-muted">Đang chờ...</p>
                        <p class="mb-1 text-muted font-weight-bold">Thời gian: <span id="sync-elapsed-time">00:00</span></p>
                    </div>
                    <div class="progress" style="height: 10px; background-color: #495057;">
                        <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="sync-stats-details" class="mt-2 small d-flex justify-content-between">
                        <span>Trang: <span id="sync-pages">0/0</span></span>
                        <span>Mới: <span id="sync-created" class="badge badge-success">0</span></span>
                        <span>Cập nhật: <span id="sync-updated" class="badge badge-info">0</span></span>
                        <span>Lỗi: <span id="sync-errors" class="badge badge-danger">0</span></span>
                        <span>Tổng: <span id="sync-total" class="badge badge-primary">0</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


{{-- Bảng đơn hàng --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="card-title mb-0">Danh sách Đơn hàng</h5>
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
                        <th style="min-width: 150px;">Trạng thái đơn hàng</th>
                        <th style="min-width: 150px;">Trạng thái hệ thống</th>
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
    <div class="card-footer clearfix">
        <div class="d-flex justify-content-between align-items-center">
            @if($orders->total() > 0)
            <div class="text-muted">
                Hiển thị {{ $orders->firstItem() }} - {{ $orders->lastItem() }} trên tổng số {{ $orders->total() }} đơn hàng
            </div>
            @endif
            {{ $orders->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}
        </div>
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

{{-- Modal đồng bộ theo ngày cụ thể --}}
<div class="modal fade" id="syncDateModal" tabindex="-1" role="dialog" aria-labelledby="syncDateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="syncDateModalLabel"><i class="fas fa-calendar-alt mr-2"></i>Đồng bộ đơn hàng theo ngày</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle mr-2"></i> Lưu ý: Chỉ đồng bộ 1 ngày duy nhất mỗi lần để tránh quá tải hệ thống.
        </div>
        <form id="syncDateForm">
          <div class="form-group">
            <label for="sync_date" class="font-weight-bold">Chọn ngày cần đồng bộ:</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
              </div>
              <input type="date" class="form-control" id="sync_date" name="sync_date" required max="{{ date('Y-m-d') }}">
            </div>
            <small class="form-text text-muted">Ngày đồng bộ không thể lớn hơn ngày hiện tại.</small>
          </div>
        </form>
        <div class="alert d-none mt-3" id="syncDateResult"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Đóng</button>
        <button type="button" class="btn btn-primary" id="syncDateBtn"><i class="fas fa-sync-alt mr-1"></i>Đồng bộ</button>
      </div>
    </div>
  </div>
</div>

{{-- Add Sync button that will be shown only to admin users --}}

@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css"/>
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

    /* Styles for sync buttons */
    .sync-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .sync-controls .btn-group {
        box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        transition: all 0.3s cubic-bezier(.25,.8,.25,1);
    }
    .sync-controls .btn-group:hover {
        box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .btn-sm i {
        font-size: 0.875rem;
        width: 1.2rem;
        text-align: center;
        vertical-align: middle;
    }

    /* Enhanced progress bar styling */
    .progress {
        height: 15px;
        background-color: #f5f5f5;
        border-radius: 10px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }
    .progress-bar {
        border-radius: 10px;
        transition: width 0.5s ease;
    }

    /* Sync stats in SweetAlert */
    #sync-stats {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
        border-left: 4px solid #17a2b8;
    }
    #sync-time-elapsed {
        padding-top: 5px;
        border-top: 1px dashed #dee2e6;
    }

    /* Enhanced modal styling */
    .modal-content {
        border: none;
        border-radius: 6px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .modal-header.bg-info {
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
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
        .sync-controls {
            margin-top: 1rem;
            justify-content: center;
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

    /* Style for SweetAlert2 popups */
    .swal2-popup {
        border-radius: 10px;
    }
    .swal2-title {
        font-size: 1.5rem;
    }
    .swal2-content {
        font-size: 1rem;
    }
    .swal2-styled.swal2-confirm,
    .swal2-styled.swal2-cancel {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 8px 25px;
    }

    /* Animated icon for sync buttons */
    .btn-sync .fa-sync-alt {
        transition: transform 0.5s ease;
    }
    .btn-sync:hover .fa-sync-alt {
        transform: rotate(180deg);
    }

    /* Control Panel Customizations */
    .card-control-panel {
        background-color: #2c2f33; /* Darker background */
        color: #f8f9fa; /* Lighter text */
        border: 1px solid #454d55;
        border-radius: .375rem;
    }
    .card-control-panel .card-header {
        background-color: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid #454d55;
        /* display, justify-content, and align-items are now handled by Bootstrap classes */
    }
    .control-panel-label {
        color: #ced4da;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    /* Icon Toggle Buttons */
    .btn-icon-toggle {
        background: transparent;
        border: 1px solid #6c757d;
        color: #ced4da;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 50%;
    }
    .btn-icon-toggle:hover {
        background: #495057;
        color: #f8f9fa;
    }
    .btn-icon-toggle > i {
        transition: transform 0.3s ease;
    }
    .btn-icon-toggle[aria-expanded="false"] > i {
        transform: rotate(0deg); /* Explicitly set rotation for collapsed state */
    }
    .btn-icon-toggle[aria-expanded="true"] > i {
        transform: rotate(180deg);
    }

    /* Input Group Styling */
    .card-control-panel .input-group .form-control {
        background-color: #23272b;
        border-color: #6c757d;
        color: #f8f9fa;
    }
    .card-control-panel .input-group .form-control::placeholder {
        color: #6c757d;
    }
    .card-control-panel .input-group .btn {
        z-index: 2; /* Ensure button is on top */
    }

    /* Preset Date Buttons */
    .btn-preset {
        background-color: transparent;
        border-color: #6c757d;
        color: #ced4da;
    }
    .btn-preset:hover, .btn-preset:focus, .btn-preset.active {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
        box-shadow: none;
    }

    /* Progress Bar Container */
    #sync-progress-container {
        margin-top: 1.5rem !important;
        padding-top: 1.5rem;
        border-top: 1px solid #454d55;
    }
    #sync-stats-details {
        color: #adb5bd;
    }

    /* Litepicker Dark Theme */
    .litepicker {
        background-color: #23272b !important;
        border-color: #6c757d !important;
    }
    .litepicker .container__months, .litepicker .container__tooltip {
        background-color: #23272b !important;
    }
    .litepicker .month-item-header, .litepicker .day-item, .litepicker .calendar-header-month, .litepicker .calendar-header-year, .litepicker .button-cancel {
        color: #f8f9fa !important;
    }
    .litepicker .day-item:hover {
        background-color: #007bff !important;
        color: #fff !important;
    }
    .litepicker .button-apply {
         background-color: #28a745 !important;
        color: #fff !important;
    }
    .litepicker .is-start-date, .litepicker .is-end-date {
        background-color: #007bff !important;
        color: #fff !important;
    }
    .litepicker .is-in-range {
        background-color: rgba(0, 123, 255, 0.3) !important;
    }

</style>
@endsection

@section('js')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Ensure CSRF token is set for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const DEFAULT_CALL_NUMBER_FALLBACK = '0332360850'; // Fallback if data-phone is missing

    // Initialize call buttons (use event delegation for dynamically loaded content)
    $(document).on('click', '.btn-call', function() {
        const phoneNumberToCall = this.dataset.phone || DEFAULT_CALL_NUMBER_FALLBACK;
        const callWindowUrl = `{{ route('calls.window') }}?phone_number=${encodeURIComponent(phoneNumberToCall)}`;
        const windowFeatures = 'width=380,height=600,resizable=yes,scrollbars=no,status=no';
        window.open(callWindowUrl, 'CallWindow', windowFeatures);
        console.log(`Attempting to open call window for: ${phoneNumberToCall}`);
    });

    // Initialize modal for status updates
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
        const callId = $(this).data('call-id');
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

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Date range validation
    $('#date_from, #date_to').on('change', function() {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();

        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Ngày bắt đầu không thể sau ngày kết thúc');
            $(this).val('');
        }
    });

    // Handle push to Pancake
    $(document).on('click', '.btn-push-pancake', function(e) {
        e.preventDefault();
        const btn = $(this);
        const url = btn.data('url');
        const orderId = btn.data('order-id');
        const isUpdate = btn.find('i').hasClass('fa-sync');

        Swal.fire({
            title: isUpdate ? 'Xác nhận cập nhật?' : 'Xác nhận đẩy đơn?',
            text: isUpdate
                ? "Bạn có chắc chắn muốn cập nhật đơn hàng #" + orderId + " trên Pancake không?"
                : "Bạn có chắc chắn muốn đẩy đơn hàng #" + orderId + " lên Pancake không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: isUpdate ? 'Đồng ý, cập nhật!' : 'Đồng ý, đẩy ngay!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button and show loading state
                btn.prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin"></i> ' + (isUpdate ? 'Đang cập nhật...' : 'Đang đẩy...'));

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Thành công!',
                                text: response.message || (isUpdate
                                    ? 'Đã cập nhật đơn hàng trên Pancake thành công.'
                                    : 'Đã đẩy đơn hàng lên Pancake thành công.'),
                                icon: 'success'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Lỗi!',
                                text: response.message || (isUpdate
                                    ? 'Có lỗi xảy ra khi cập nhật đơn hàng.'
                                    : 'Có lỗi xảy ra khi đẩy đơn hàng.'),
                                icon: 'error'
                            });
                            btn.prop('disabled', false)
                               .html('<i class="fas fa-' + (isUpdate ? 'sync' : 'rocket') + ' fa-fw"></i>');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = isUpdate
                            ? 'Có lỗi xảy ra khi cập nhật đơn hàng.'
                            : 'Có lỗi xảy ra khi đẩy đơn hàng.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Lỗi!',
                            text: errorMsg,
                            icon: 'error'
                        });
                        btn.prop('disabled', false)
                           .html('<i class="fas fa-' + (isUpdate ? 'sync' : 'rocket') + ' fa-fw"></i>');
                    }
                });
            }
        });
    });

    // Handle update on Pancake
    $(document).on('click', '.btn-update-pancake', function(e) {
        e.preventDefault();
        const btn = $(this);
        const url = btn.data('url');
        const orderId = btn.data('order-id');

        Swal.fire({
            title: 'Xác nhận cập nhật?',
            text: "Bạn có chắc chắn muốn cập nhật đơn hàng #" + orderId + " trên Pancake không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý, cập nhật!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button and show loading state
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Thành công!',
                                text: response.message || 'Đã cập nhật đơn hàng trên Pancake thành công.',
                                icon: 'success'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Lỗi!',
                                text: response.message || 'Có lỗi xảy ra khi cập nhật đơn hàng.',
                                icon: 'error'
                            });
                            btn.prop('disabled', false).html('<i class="fas fa-sync fa-fw"></i>');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Có lỗi xảy ra khi cập nhật đơn hàng.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Lỗi!',
                            text: errorMsg,
                            icon: 'error'
                        });
                        btn.prop('disabled', false).html('<i class="fas fa-sync fa-fw"></i>');
                    }
                });
            }
        });
    });

    // === UNIFIED PANCAKE SYNC IMPLEMENTATION ===
    const syncState = {
        inProgress: false,
        syncInfo: null,
        totalPages: 0,
        currentPage: 0,
        totalEntries: 0,
        stats: { created: 0, updated: 0, errors: 0, total: 0 },
        startTime: null,
        timerInterval: null
    };

    const picker = new Litepicker({
        element: document.getElementById('date-range-picker'),
        singleMode: false,
        format: 'YYYY-MM-DD',
        setup: (picker) => {
            picker.on('selected', (date1, date2) => {
                // on selection
            });
        }
    });

    // Preset buttons
    $('.btn-preset').on('click', function() {
        $('.btn-preset').removeClass('active');
        $(this).addClass('active');
    });

    $('#sync-preset-today').on('click', function() {
        const today = new Date();
        picker.setDateRange(today, today);
        $('#sync-date-range-btn').trigger('click');
    });

    $('#sync-preset-yesterday').on('click', function() {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        picker.setDateRange(yesterday, yesterday);
        $('#sync-date-range-btn').trigger('click');
    });

    $('#sync-preset-7days').on('click', function() {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 6);
        picker.setDateRange(startDate, endDate);
        $('#sync-date-range-btn').trigger('click');
    });

    $('#sync-preset-this-month').on('click', function() {
        const today = new Date();
        const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        picker.setDateRange(startDate, endDate);
         $('#sync-date-range-btn').trigger('click');
    });

    // Main sync buttons
    $('#sync-date-range-btn').on('click', function() {
        if (syncState.inProgress) {
            Swal.fire('Đang đồng bộ', 'Một quá trình đồng bộ đã chạy. Vui lòng chờ hoặc hủy.', 'warning');
            return;
        }

        const startDate = picker.getStartDate();
        const endDate = picker.getEndDate();

        if (!startDate || !endDate) {
            Swal.fire('Lỗi', 'Vui lòng chọn cả ngày bắt đầu và kết thúc.', 'error');
            return;
        }

        const startTimestamp = Math.floor(new Date(startDate.dateInstance.setHours(0, 0, 0, 0)).getTime() / 1000);
        const endTimestamp = Math.floor(new Date(endDate.dateInstance.setHours(23, 59, 59, 999)).getTime() / 1000);

        startSync(startTimestamp, endTimestamp);
    });

    $('#sync-this-week-btn').on('click', function() {
        if (syncState.inProgress) {
            Swal.fire('Đang đồng bộ', 'Một quá trình đồng bộ đã chạy. Vui lòng chờ hoặc hủy.', 'warning');
            return;
        }
        startSync(); // No params will trigger the 'sync-all' (this week) route
    });

    $('#cancel-any-sync-btn').on('click', function() {
        if (!syncState.inProgress) {
             Swal.fire('Không có gì để hủy', 'Hiện không có tiến trình đồng bộ nào đang chạy.', 'info');
            return;
        }

        Swal.fire({
            title: 'Hủy đồng bộ?',
            text: "Bạn có chắc muốn dừng quá trình đồng bộ đang chạy?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý, Hủy!',
            cancelButtonText: 'Không'
        }).then((result) => {
            if (result.isConfirmed) {
                cancelSync();
            }
        });
    });

    function startSync(startTimestamp, endTimestamp) {
        // Reset state and UI
        Object.assign(syncState, {
            inProgress: true,
            syncInfo: null,
            totalPages: 0,
            currentPage: 0,
            totalEntries: 0,
            stats: { created: 0, updated: 0, errors: 0, total: 0 },
            startTime: new Date().getTime(),
            timerInterval: null
        });
        $('#sync-progress-container').removeClass('d-none');
        $('#sync-progress-bar').removeClass('bg-success bg-danger').addClass('bg-info');
        $('.btn-group button, #sync-date-range-btn, #sync-this-week-btn').prop('disabled', true);
        $('#cancel-any-sync-btn').prop('disabled', false);
        updateProgressUI(0, 'Đang gửi yêu cầu đến Pancake...');

        // Start the timer
        syncState.timerInterval = setInterval(updateTimer, 1000);

        const url = '{{ route("pancake.orders.sync-all") }}';
        let data = {};
        if(startTimestamp && endTimestamp) {
            data = {
                startDateTime: startTimestamp,
                endDateTime: endTimestamp,
            };
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (!syncState.inProgress) return;

                // Force UI update upon receiving a response
                $('#sync-status').text('Đã nhận phản hồi, đang xử lý dữ liệu...');

                if (response.success) {
                        syncState.syncInfo = response.sync_info;
                        syncState.totalPages = response.total_pages;
                        syncState.totalEntries = response.total_entries;
                    updateStatsFromResponse(response);

                    if (response.continue && response.next_page) {
                        processNextPage(response.next_page);
                    } else {
                        finishSync();
                    }
                } else {
                    handleSyncError(response.message || 'Lỗi không xác định khi bắt đầu đồng bộ.');
                }
            },
            error: function(xhr) {
                if (!syncState.inProgress) return;
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối khi bắt đầu đồng bộ.';
                handleSyncError(errorMsg);
            }
        });
    }

    function processNextPage(pageNumber) {
        if (!syncState.inProgress) return;
        syncState.currentPage = pageNumber;
        updateProgressUI();

        const data = {
            page_number: pageNumber,
            sync_info: syncState.syncInfo
        };

        $.ajax({
            url: '{{ route("pancake.orders.sync-process-next") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (!syncState.inProgress) return;
                if (response.success) {
                    updateStatsFromResponse(response);
                    if (response.continue && response.next_page) {
                        setTimeout(() => processNextPage(response.next_page), 500); // Add a small delay
                    } else {
                        finishSync();
                    }
                } else {
                    handleSyncError(response.message || `Lỗi khi xử lý trang ${pageNumber}.`);
                }
            },
            error: function(xhr) {
                if (!syncState.inProgress) return;
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : `Lỗi kết nối khi xử lý trang ${pageNumber}.`;
                handleSyncError(errorMsg);
            }
        });
    }

    function cancelSync() {
        if (!syncState.inProgress) return;
        syncState.inProgress = false;

        if (syncState.syncInfo && syncState.syncInfo.token) {
            // If we have a token, it means a sync process is running on the server.
            // We need to send a request to cancel it.
            showLoading('Đang gửi yêu cầu hủy...');
            /*
            $.ajax({
                url: '{{ route("pancake.sync.cancel") }}', // Corrected route name
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    sync_token: syncState.syncInfo.token
                },
                success: function(response) {
                    hideLoading();
                    resetSyncUI('Đồng bộ đã được hủy thành công.', false);
                    toastr.success('Tiến trình đồng bộ đã được hủy.', 'Đã hủy');
                },
                error: function(xhr) {
                    hideLoading();
                    resetSyncUI('Không thể hủy đồng bộ. Vui lòng thử lại.', true);
                    toastr.error('Có lỗi xảy ra khi cố gắng hủy. ' + (xhr.responseJSON?.message || ''), 'Lỗi');
                },
                complete: function() {
                    syncState.inProgress = false; // Ensure progress is stopped
                    $('#sync-progress-container').addClass('d-none'); // Hide progress bar
                }
            });
            */
            // Mock cancellation for UI testing without lag
            setTimeout(() => {
                hideLoading();
                resetSyncUI('Đồng bộ đã được hủy thành công (DEMO).', false);
                toastr.success('Tiến trình đồng bộ đã được hủy (DEMO).', 'Đã hủy');
                syncState.inProgress = false;
                $('#sync-progress-container').addClass('d-none');
            }, 500);
        } else {
            // If there's no token, it means the process hasn't started on the server yet,
            // or it's just the initial client-side setup. We can just reset the UI.
            resetSyncUI('Đồng bộ đã được hủy.', false);
            $('#sync-progress-container').addClass('d-none'); // Hide progress bar
            toastr.info('Tiến trình đã được hủy.', 'Đã hủy');
            syncState.inProgress = false;
        }
    }

    function updateTimer() {
        if (!syncState.inProgress || !syncState.startTime) {
            clearInterval(syncState.timerInterval);
            return;
        }
        const now = new Date().getTime();
        const elapsedSeconds = Math.floor((now - syncState.startTime) / 1000);

        const hours = Math.floor(elapsedSeconds / 3600);
        const minutes = Math.floor((elapsedSeconds % 3600) / 60);
        const seconds = elapsedSeconds % 60;

        let timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        if (hours > 0) {
            timeString = `${hours.toString().padStart(2, '0')}:${timeString}`;
        }

        $('#sync-elapsed-time').text(timeString);
    }

    function updateStatsFromResponse(response) {
        if (!response.stats) return;
        syncState.stats.created += response.stats.created || 0;
        syncState.stats.updated += response.stats.updated || 0;
        syncState.stats.errors += response.stats.errors_count || (response.stats.errors ? response.stats.errors.length : 0);
        syncState.stats.total = syncState.stats.created + syncState.stats.updated;
        syncState.currentPage = response.stats.current_page || syncState.currentPage;
        updateProgressUI();
    }

    function updateProgressUI(progress, statusText) {
        if (progress === undefined) {
            progress = syncState.totalPages > 0 ? Math.round((syncState.currentPage / syncState.totalPages) * 100) : 0;
        }

        $('#sync-progress-bar').css('width', `${progress}%`).text(`${progress}%`);

        if (statusText) {
             $('#sync-status').text(statusText);
        } else {
             $('#sync-status').text(`Đang xử lý trang ${syncState.currentPage} / ${syncState.totalPages}...`);
        }

        $('#sync-pages').text(`${syncState.currentPage}/${syncState.totalPages}`);
        $('#sync-created').text(syncState.stats.created);
        $('#sync-updated').text(syncState.stats.updated);
        $('#sync-errors').text(syncState.stats.errors);
        $('#sync-total').text(syncState.stats.total);
    }

    function handleSyncError(errorMessage) {
        Swal.fire('Lỗi đồng bộ!', errorMessage, 'error');
        resetSyncUI(`Lỗi: ${errorMessage}`, true);
    }

    function finishSync() {
        const finalStatus = `Hoàn tất! Đồng bộ ${syncState.stats.total} đơn hàng. Mới: ${syncState.stats.created}, Cập nhật: ${syncState.stats.updated}, Lỗi: ${syncState.stats.errors}.`;
        updateProgressUI(100, finalStatus);
        $('#sync-progress-bar').addClass('bg-success').removeClass('bg-info');
        resetSyncUI(finalStatus, false);
        Swal.fire('Hoàn tất!', finalStatus, 'success').then(() => {
            if(syncState.stats.total > 0) window.location.reload();
        });
    }

    function resetSyncUI(statusText, isError = false) {
        if (syncState.timerInterval) clearInterval(syncState.timerInterval);
        syncState.inProgress = false;
        $('.btn-group button, #sync-date-range-btn, #sync-this-week-btn, .btn-preset').prop('disabled', false);
        $('#cancel-any-sync-btn').prop('disabled', true);
        $('#sync-status').text(statusText);

        if(isError) {
             $('#sync-progress-bar').addClass('bg-danger').removeClass('bg-info bg-success');
        } else {
            // On successful cancellation or completion, hide the progress container.
            // We delay it slightly to allow the user to read the final message.
            setTimeout(() => {
                $('#sync-progress-container').addClass('d-none');
            }, 3000);
        }
    }
});
</script>
@stop
