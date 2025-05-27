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

                    {{-- New Filter for Pancake Origin --}}
                    <div class="form-group w-100 mb-3">
                        <label for="pancake_origin" class="d-block w-100 mb-2">Nguồn gốc Pancake:</label>
                        <select name="pancake_origin" id="pancake_origin" class="form-control select2 w-100">
                            <option value="">-- Tất cả --</option>
                            <option value="from_pancake" {{ request('pancake_origin') == 'from_pancake' ? 'selected' : '' }}>Tạo từ Pancake</option>
                            <option value="to_pancake" {{ request('pancake_origin') == 'to_pancake' ? 'selected' : '' }}>Đã đẩy lên Pancake</option>
                            <option value="not_synced" {{ request('pancake_origin') == 'not_synced' ? 'selected' : '' }}>Chưa đồng bộ</option>
                        </select>
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
        @can('sync-pancake')
        <div class="sync-controls">
            <div class="btn-group btn-group-sm mr-2">
                <button type="button" class="btn btn-primary btn-sync" id="sync-today-btn" title="Đồng bộ đơn hàng hôm nay">
                    <i class="fas fa-sync-alt"></i> Đơn hôm nay
                </button>
                <button type="button" class="btn btn-secondary btn-sync" id="sync-yesterday-btn" title="Đồng bộ đơn hàng hôm qua">
                    <i class="fas fa-sync-alt"></i> Hôm qua
                </button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Đồng bộ theo ngày cụ thể">
                    <i class="fas fa-calendar-alt"></i> Ngày cụ thể
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item sync-specific-day" href="#" data-days-ago="3">3 ngày trước</a>
                    <a class="dropdown-item sync-specific-day" href="#" data-days-ago="7">7 ngày trước</a>
                    <a class="dropdown-item sync-specific-day" href="#" data-days-ago="14">14 ngày trước</a>
                    <a class="dropdown-item sync-specific-day" href="#" data-days-ago="30">30 ngày trước</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="sync-custom-day">Chọn ngày cụ thể...</a>
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-danger btn-sync" id="sync-all-orders-btn" title="Đồng bộ tất cả đơn hàng từ Pancake">
                    <i class="fas fa-sync-alt"></i> Đồng bộ TẤT CẢ
                </button>
                <button type="button" class="btn btn-warning" id="cancel-stuck-sync-btn" title="Hủy tiến trình đồng bộ đang treo">
                    <i class="fas fa-ban"></i> Hủy đồng bộ
                </button>
            </div>
        </div>
        @endcan
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
</style>
@endsection

@section('js')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

    // Initialize Push to Pancake buttons
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
                    success: function(response) {
                        button.html('<i class="fas fa-rocket fa-fw"></i>').prop('disabled', false);
                        if (response.success) {
                            Swal.fire(
                                'Thành công!',
                                response.message || 'Đơn hàng đã được đẩy lên Pancake.',
                                'success'
                            ).then(() => {
                                location.reload();
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
                        button.html('<i class="fas fa-rocket fa-fw"></i>').prop('disabled', false);
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

    // === PANCAKE SYNC IMPLEMENTATION ===
    @can('sync-pancake')
    // Global variables for sync state
    const syncState = {
        inProgress: false,
        currentPage: 0,
        totalPages: 0,
        startDate: null,
        endDate: null,
        stats: {
            created: 0,
            updated: 0,
            errors: [],
            total: 0
        },
        progressInterval: null,
        startTime: null,
        syncInfo: null,
        retryAttempts: 0
    };

    // Event listeners for sync buttons
    $('#sync-all-orders-btn').on('click', function() {
        confirmAndStartSync('all');
    });

    $('#sync-today-btn').on('click', function() {
        const today = new Date().toISOString().split('T')[0];
        confirmAndStartSync('date', {
            date: today,
            displayText: 'hôm nay'
        });
    });

    $('#sync-yesterday-btn').on('click', function() {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        confirmAndStartSync('date', {
            date: yesterdayStr,
            displayText: 'hôm qua'
        });
    });

    $('.sync-specific-day').on('click', function(e) {
        e.preventDefault();
        const daysAgo = parseInt($(this).data('days-ago'));
        const date = new Date();
        date.setDate(date.getDate() - daysAgo);
        const formattedDate = date.toISOString().split('T')[0];

        confirmAndStartSync('date', {
            date: formattedDate,
            displayText: `${daysAgo} ngày trước`,
            daysAgo: daysAgo
        });
    });

    $('#sync-custom-day').on('click', function(e) {
        e.preventDefault();
        $('#syncDateModal').modal('show');
    });

    $('#syncDateBtn').on('click', function() {
        const syncDate = $('#sync_date').val();
        if (!syncDate) {
            Swal.fire({
                title: 'Lỗi',
                text: 'Vui lòng chọn ngày cần đồng bộ',
                icon: 'error'
            });
            return;
        }

        $('#syncDateModal').modal('hide');

        confirmAndStartSync('date', {
            date: syncDate,
            displayText: formatDate(syncDate),
            fromModal: true
        });
    });

    $('#cancel-stuck-sync-btn').on('click', function() {
        cancelSync();
    });

    // Sync by date button in the card
    $('#sync_by_date_btn').on('click', function() {
        const syncDate = $('#sync_date').val();
        if (!syncDate) {
            Swal.fire({
                title: 'Lỗi',
                text: 'Vui lòng chọn ngày cần đồng bộ',
                icon: 'error'
            });
            return;
        }

        confirmAndStartSync('date', {
            date: syncDate,
            displayText: formatDate(syncDate)
        });
    });

    // Sync all button in the card
    $('#sync_all_btn').on('click', function() {
        confirmAndStartSync('all');
    });

    // Function to confirm and start sync
    function confirmAndStartSync(syncType, options = {}) {
        // Check if sync is already in progress
        if (syncState.inProgress) {
            Swal.fire({
                title: 'Đang có quá trình đồng bộ!',
                text: 'Một quá trình đồng bộ khác đang chạy. Bạn có muốn hủy và bắt đầu mới?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hủy và bắt đầu mới',
                cancelButtonText: 'Không, giữ nguyên'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Cancel current sync and start new one
                    cancelSync(true).then(() => {
                        setTimeout(() => {
                            startSync(syncType, options);
                        }, 1000);
                    });
                }
            });
            return;
        }

        // If this is a date sync, store the date in syncState
        if (syncType === 'date' && options.date) {
            syncState.date = options.date;
        }

        // Show confirmation dialog
        let title, text, icon = 'question';

        if (syncType === 'all') {
            title = 'Xác nhận đồng bộ TẤT CẢ đơn hàng';
            text = 'Quá trình này có thể mất nhiều thời gian và tài nguyên hệ thống. Bạn có chắc chắn muốn tiếp tục?';
            icon = 'warning';
        } else if (syncType === 'date') {
            title = `Xác nhận đồng bộ đơn hàng ${options.displayText}`;
            text = `Bạn có chắc muốn đồng bộ đơn hàng của ${options.displayText}?`;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý, bắt đầu đồng bộ',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                startSync(syncType, options);
            }
        });
    }

    // Function to start sync process
    function startSync(syncType, options = {}) {
        // Show loading dialog with immediate feedback
        Swal.fire({
            title: 'Đang khởi tạo...',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-2" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="mb-0">Hệ thống đang kết nối đến Pancake...</p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Reset sync state
        syncState.inProgress = true;
        syncState.currentPage = 0;
        syncState.totalPages = 0;
        syncState.startTime = new Date().getTime();
        syncState.stats = {
            created: 0,
            updated: 0,
            errors: [],
            total: 0
        };

        // Determine API endpoint and data
        let url, data = {};

        if (syncType === 'all') {
            url = '{{ route("pancake.orders.sync-all") }}';
        } else if (syncType === 'date') {
            url = '{{ route("pancake.orders.sync-all") }}';

            // Convert date to start and end timestamps
            const selectedDate = new Date(options.date);
            const startDateTime = Math.floor(new Date(selectedDate.setHours(0, 0, 0, 0)).getTime() / 1000);
            const endDateTime = Math.floor(new Date(selectedDate.setHours(23, 59, 59, 999)).getTime() / 1000);

            data = {
                date: options.date,
                startDateTime: startDateTime,
                endDateTime: endDateTime
            };

            console.log("Đồng bộ theo ngày:", {
                date: options.date,
                startDateTime: startDateTime,
                endDateTime: endDateTime
            });

            // For older dates, show additional warning
            const today = new Date();
            const diffDays = Math.floor((today - selectedDate) / (1000 * 60 * 60 * 24));

            if (diffDays > 7) {
                Swal.fire({
                    title: 'Lưu ý',
                    text: `Bạn đang đồng bộ dữ liệu từ ${diffDays} ngày trước. Quá trình này có thể mất nhiều thời gian hoặc không lấy được đầy đủ dữ liệu cũ.`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn tiếp tục',
                    cancelButtonText: 'Hủy bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeSync(url, data);
                    } else {
                        syncState.inProgress = false;
                        Swal.close();
                    }
                });
                return;
            }
        }

        // Execute sync
        executeSync(url, data);
    }

    // Function to execute sync API call
    function executeSync(url, data) {
        // Log the data being sent for debugging
        console.log('Sending sync request:', url, data);

        // Ensure we're using the correct parameter name 'date' instead of 'sync_date'
        if (data.sync_date) {
            data.date = data.sync_date;
            delete data.sync_date;
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                console.log('Sync response:', response);
                if (response.success) {
                    // Store sync info
                    if (response.sync_info) {
                        syncState.syncInfo = response.sync_info;
                    }

                    // Store total pages
                    if (response.total_pages) {
                        syncState.totalPages = response.total_pages;
                    }

                    // Store total entries
                    if (response.total_entries) {
                        syncState.totalEntries = response.total_entries;
                    }

                    console.log("Sync started: Page " + (response.current_page || 1) + "/" +
                               (response.total_pages || 1) + ", Continue: " +
                               (response.continue ? "Yes" : "No") + ", Next page: " +
                               (response.next_page || "None"));

                    // Update sync stats if available
                    if (response.stats) {
                        syncState.stats.created += response.stats.created || 0;
                        syncState.stats.updated += response.stats.updated || 0;
                        if (response.stats.errors && response.stats.errors.length) {
                            syncState.stats.errors = syncState.stats.errors.concat(response.stats.errors);
                        }
                    }

                    // Show progress dialog
                    showSyncProgress();

                    // If there are more pages to process
                    if (response.continue && response.next_page) {
                        processNextPage(response.next_page);
                    } else {
                        // Finish sync if only one page
                        finishSync();
                    }
                } else {
                    handleSyncError(response.message || 'Có lỗi xảy ra trong quá trình đồng bộ.');
                }
            },
            error: function(xhr) {
                console.error('Sync error:', xhr);
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Đã xảy ra lỗi khi đồng bộ';
                handleSyncError(errorMsg);
            }
        });
    }

    // Process next page of sync
    function processNextPage(pageNumber) {
        syncState.currentPage = pageNumber;

        const data = {
            page_number: pageNumber
        };

        // Add sync info if available
        if (syncState.syncInfo) {
            data.sync_info = syncState.syncInfo;
        }

        // For date sync, include the date parameter with the correct name
        if (syncState.date) {
            data.date = syncState.date;
        }

        console.log(`Processing page ${pageNumber}/${syncState.totalPages || '?'}`);

        $.ajax({
            url: '{{ route("pancake.orders.sync-process-next") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Update total pages if changed
                    if (response.total_pages) {
                        syncState.totalPages = response.total_pages;
                    }

                    // Update stats
                    if (response.stats) {
                        syncState.stats.created += response.stats.created || 0;
                        syncState.stats.updated += response.stats.updated || 0;
                        syncState.stats.total += response.stats.total || 0;

                        if (response.stats.errors && response.stats.errors.length) {
                            syncState.stats.errors = syncState.stats.errors.concat(response.stats.errors);
                        }

                        // Update stats in the progress dialog
                        updateProgressDialog();
                    }

                    console.log(`Completed page ${response.current_page}/${response.total_pages}, continue=${response.continue}, next_page=${response.next_page || 'none'}`);

                    // If there are more pages
                    if (response.continue && response.next_page) {
                        processNextPage(response.next_page);
                    } else {
                        // Finished all pages
                        console.log("Sync completed - last page or no more pages");
                        finishSync();
                    }
                } else {
                    handleSyncError(response.message || 'Có lỗi xảy ra khi xử lý trang tiếp theo.');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Đã xảy ra lỗi khi đồng bộ';
                handleSyncError(errorMsg);
            }
        });
    }

    // Show sync progress dialog
    function showSyncProgress() {
        Swal.fire({
            title: 'Đang đồng bộ dữ liệu',
            html: `
                <div class="text-left">
                    <p class="mb-2">Tiến trình đồng bộ đang được thực hiện. Vui lòng không đóng trang này.</p>
                    <div class="progress mb-3">
                        <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                             role="progressbar" style="width: 0%"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="sync-stats" class="p-2 border rounded bg-light">
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1"><strong>Tiến trình:</strong> <span id="sync-page-progress">0/0</span> trang</p>
                                <p class="mb-1">Đơn hàng mới: <span id="sync-created" class="badge badge-success">0</span></p>
                                <p class="mb-1">Đơn cập nhật: <span id="sync-updated" class="badge badge-info">0</span></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1">Tiến độ: <span id="sync-progress-percentage">0%</span></p>
                                <p class="mb-1">Lỗi: <span id="sync-errors" class="badge badge-danger">0</span></p>
                                <p class="mb-1">Tổng: <span id="sync-total-processed">0</span>/<span id="sync-total-expected">?</span></p>
                            </div>
                        </div>
                        <p class="mb-1" id="sync-current-operation">Đang khởi tạo dữ liệu...</p>
                        <p class="mb-0" id="sync-processing-details"></p>
                        <p class="mb-0 text-muted" id="sync-time-elapsed">Thời gian: 0 giây</p>
                    </div>
                    <div id="sync-error-details" class="mt-2 text-danger d-none">
                        <p class="mb-1"><i class="fas fa-exclamation-triangle"></i> Lỗi gần đây:</p>
                        <ul id="sync-error-list" class="pl-3 mb-0 small"></ul>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Hủy đồng bộ',
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                cancelSync();
            }
        });

        // Start checking progress periodically
        startProgressTracking();

        // Hiển thị ngay phản hồi ban đầu
        updateElapsedTime();
        $('#sync-current-operation').text('Đang kết nối đến máy chủ Pancake...');
    }

    // Update progress dialog with current stats
    function updateProgressDialog() {
        $('#sync-created').text(syncState.stats.created);
        $('#sync-updated').text(syncState.stats.updated);
        $('#sync-errors').text(syncState.stats.errors.length);

        // Update progress bar
        const progress = syncState.totalPages > 0
            ? Math.round((syncState.currentPage / syncState.totalPages) * 100)
            : 0;

        $('#sync-progress-bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');
    }

    // Track sync progress
    function startProgressTracking() {
        // Clear any existing interval
        if (syncState.progressInterval) {
            clearInterval(syncState.progressInterval);
        }

        // Variable for tracking timing
        let checkCount = 0;
        let interval = 500; // Start with more frequent checks (500ms)

        // Initial immediate check
        setTimeout(() => checkSyncProgress(), 100);

        // Check progress with adaptive timing
        function setNextCheck() {
            checkCount++;

            // Adjust interval based on check count
            if (checkCount > 3 && checkCount < 10) {
                interval = 1000; // 1 second after first few checks
            } else if (checkCount >= 10) {
                interval = 2000; // 2 seconds after more checks
            }

            syncState.progressInterval = setTimeout(() => {
                checkSyncProgress();
                setNextCheck();
            }, interval);
        }

        // Start the first timer
        setNextCheck();

        // Function to check sync progress
        function checkSyncProgress() {
            $.ajax({
                url: '{{ route("pancake.orders.sync-all-progress") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Store the sync info for cancel operations
                        if (response.sync_info) {
                            syncState.syncInfo = response.sync_info;
                        }

                        // Update progress bar
                        const progress = response.progress || 0;
                        $('#sync-progress-bar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');
                        $('#sync-progress-percentage').text(progress + '%');

                        // Update page progress
                        const currentPage = response.current_page || syncState.currentPage || 0;
                        const totalPages = response.total_pages || syncState.totalPages || 1;
                        $('#sync-page-progress').text(`${currentPage}/${totalPages}`);

                        // Update order counts
                        const created = response.stats?.created || 0;
                        const updated = response.stats?.updated || 0;
                        const errors = response.stats?.errors_count || 0;
                        $('#sync-created').text(created);
                        $('#sync-updated').text(updated);
                        $('#sync-errors').text(errors);

                        // Update total processed/expected
                        const totalProcessed = response.order_stats?.total_processed || (created + updated);
                        const totalExpected = response.order_stats?.total_expected || totalProcessed;
                        $('#sync-total-processed').text(totalProcessed);
                        $('#sync-total-expected').text(totalExpected > 0 ? totalExpected : '?');

                        // Update detailed progress information
                        const statusElement = $('#sync-current-operation');
                        if (statusElement.length) {
                            // Use server message if available, or create default message
                            if (response.message) {
                                statusElement.text(response.message);
                            } else {
                                let statusMsg = `Đang xử lý trang ${currentPage}/${totalPages}`;
                                if(totalExpected > 0) {
                                    statusMsg += ` - ${totalProcessed}/${totalExpected} đơn hàng`;
                                }
                                statusElement.text(statusMsg);
                            }
                        }

                        // Show elapsed time from server if available, otherwise calculate locally
                        const timeElement = $('#sync-time-elapsed');
                        if (timeElement.length) {
                            if (response.detailed_progress?.elapsed_time) {
                                timeElement.text(`Thời gian: ${response.detailed_progress.elapsed_time}`);
                            } else {
                                updateElapsedTime();
                            }
                        }

                        // Add estimated time remaining if available
                        const processingDetails = $('#sync-processing-details');
                        if (processingDetails.length) {
                            let detailsHtml = '';

                            // Add processing rate if available
                            if (response.detailed_progress?.processing_rate) {
                                detailsHtml += `<span class="badge badge-info">Tốc độ: ${response.detailed_progress.processing_rate} đơn/phút</span> `;
                            }

                            // Add estimated time remaining if available
                            if (response.detailed_progress?.estimated_time_remaining) {
                                detailsHtml += `<span class="badge badge-warning">Còn lại: ${response.detailed_progress.estimated_time_remaining}</span>`;
                            }

                            processingDetails.html(detailsHtml);
                        }

                        // Display recent errors if available
                        if (response.stats?.failed_orders && response.stats.failed_orders.length > 0) {
                            const errorDetails = $('#sync-error-details');
                            const errorList = $('#sync-error-list');

                            errorDetails.removeClass('d-none');
                            errorList.empty();

                            // Display up to 3 most recent errors
                            response.stats.failed_orders.slice(-3).forEach(error => {
                                const errorText = error.order_id ?
                                    `Đơn #${error.order_id}: ${error.error}` :
                                    error.error;
                                errorList.append(`<li>${errorText}</li>`);
                            });
                        }

                        // Update sync state from server
                        if (response.current_page) syncState.currentPage = response.current_page;
                        if (response.total_pages) syncState.totalPages = response.total_pages;
                        syncState.inProgress = response.in_progress;

                        // Check if sync is completed
                        const isCompleted = progress >= 100 || !response.in_progress;

                        // If sync is paused but not completed
                        if (!response.in_progress && response.current_page < response.total_pages) {
                            // Show resume confirmation
                            clearInterval(syncState.progressInterval);

                            Swal.fire({
                                title: 'Đồng bộ tạm dừng',
                                html: `Đồng bộ đã tạm dừng ở trang ${response.current_page}/${response.total_pages}. Bạn có muốn tiếp tục không?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Tiếp tục',
                                cancelButtonText: 'Đóng'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Resume sync with next page
                                    processNextPage(response.current_page + 1);
                                    return;
                                } else {
                                    syncState.inProgress = false;
                                }
                            });
                        }

                        // If completed
                        if (isCompleted) {
                            finishSync();
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error checking sync progress:', xhr);
                    $('#sync-current-operation').text('Không thể kết nối đến máy chủ. Đang thử lại...');
                }
            });
        }
    }

    // Update elapsed time display
    function updateElapsedTime() {
        if (!syncState.startTime) return;

        const now = new Date().getTime();
        const elapsed = Math.floor((now - syncState.startTime) / 1000); // Seconds

        // Format elapsed time
        let timeString = formatTime(elapsed);

        $('#sync-time-elapsed').text('Thời gian: ' + timeString);

        // Also update the progress bar in the card if it exists
        $('#sync_progress_bar').css('width', (syncState.totalPages > 0 ? Math.round((syncState.currentPage / syncState.totalPages) * 100) : 0) + '%');
        $('#sync_status_text').text('Đang đồng bộ trang ' + syncState.currentPage + '/' + syncState.totalPages);

        // Update stats in the card
        if (syncState.stats) {
            $('#sync_order_stats').removeClass('d-none');
            $('#sync_new_orders').text(syncState.stats.created);
            $('#sync_updated_orders').text(syncState.stats.updated);
            $('#sync_errors').text(syncState.stats.errors.length);
        }
    }

    // Finish sync process
    function finishSync() {
        // Stop progress tracking
        if (syncState.progressInterval) {
            clearInterval(syncState.progressInterval);
            syncState.progressInterval = null;
        }

        // Update state
        syncState.inProgress = false;

        // Check if sync was partial
        const isPartialSync = syncState.currentPage < syncState.totalPages;

        // Calculate stats
        const totalOrders = syncState.stats.created + syncState.stats.updated;
        const elapsedSeconds = Math.floor((new Date().getTime() - syncState.startTime) / 1000);
        const processingRate = elapsedSeconds > 0 ? Math.round((totalOrders / elapsedSeconds) * 60) : 0;

        // Show results dialog
        let resultTitle = isPartialSync ? 'Đồng bộ tạm dừng!' : 'Đồng bộ hoàn tất!';

        // Add badge labels
        const newBadge = '<span class="badge badge-success">Mới</span>';
        const updateBadge = '<span class="badge badge-info">Cập nhật</span>';
        const errorBadge = '<span class="badge badge-danger">Lỗi</span>';

        Swal.fire({
            title: resultTitle,
            html: `
                <div class="text-left">
                    <p class="mb-3">Quá trình đồng bộ ${isPartialSync ? 'đã tạm dừng' : 'đã hoàn tất'} sau ${formatTime(elapsedSeconds)}.</p>
                    ${isPartialSync ?
                      `<div class="alert alert-warning">
                        <p class="mb-2"><i class="fas fa-exclamation-triangle mr-2"></i> Đã xử lý ${syncState.currentPage}/${syncState.totalPages} trang.</p>
                        <p class="mb-0">Bạn có thể tiếp tục đồng bộ các trang còn lại sau.</p>
                       </div>` : ''}
                    <div class="alert ${totalOrders > 0 ? 'alert-success' : 'alert-info'}">
                        <h5 class="mb-2">Kết quả đồng bộ:</h5>
                        <ul class="mb-0 fa-ul">
                            <li><span class="fa-li"><i class="fas fa-plus-circle text-success"></i></span>
                                ${newBadge} Đơn hàng mới: ${syncState.stats.created}</li>
                            <li><span class="fa-li"><i class="fas fa-sync-alt text-info"></i></span>
                                ${updateBadge} Đơn hàng cập nhật: ${syncState.stats.updated}</li>
                            <li><span class="fa-li"><i class="fas fa-exclamation-triangle text-danger"></i></span>
                                ${errorBadge} Số lỗi: ${syncState.stats.errors.length}</li>
                            <li><span class="fa-li"><i class="fas fa-calculator"></i></span>
                                <strong>Tổng đơn hàng đã xử lý: ${totalOrders}</strong></li>
                        </ul>
                    </div>
                    <div class="alert alert-light border mt-3">
                        <h6 class="mb-2">Thống kê hiệu suất:</h6>
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1"><i class="fas fa-clock text-info"></i> Thời gian: ${formatTime(elapsedSeconds)}</p>
                            </div>
                            <div class="col-6">
                                <p class="mb-1"><i class="fas fa-tachometer-alt text-success"></i> Tốc độ: ${processingRate} đơn/phút</p>
                            </div>
                        </div>
                    </div>
                    ${syncState.stats.errors.length > 0 ?
                      '<div class="alert alert-warning mt-3"><strong>Chi tiết lỗi:</strong><ul class="mb-0">' +
                      syncState.stats.errors.slice(0, 5).map(err => `<li>${err}</li>`).join('') +
                      (syncState.stats.errors.length > 5 ? `<li>...và ${syncState.stats.errors.length - 5} lỗi khác</li>` : '') +
                      '</ul></div>' : ''}
                </div>
            `,
            icon: isPartialSync ? 'warning' : 'success',
            confirmButtonText: isPartialSync ? 'Tiếp tục đồng bộ' : 'OK',
            showCancelButton: isPartialSync,
            cancelButtonText: 'Đóng'
        }).then((result) => {
            // If user wants to continue sync
            if (isPartialSync && result.isConfirmed) {
                // Continue from next page
                syncState.inProgress = true;
                processNextPage(syncState.currentPage + 1);
                return;
            }

            // Reload page if orders were created or updated
            if (totalOrders > 0) {
                window.location.href = '{{ route("orders.index") }}';
            }
        });
    }

    // Handle sync error
    function handleSyncError(errorMessage) {
        // Stop progress tracking
        if (syncState.progressInterval) {
            clearInterval(syncState.progressInterval);
            syncState.progressInterval = null;
        }

        // Update state
        syncState.inProgress = false;

        // Track retry attempts
        if (!syncState.retryAttempts) {
            syncState.retryAttempts = 0;
        }

        // Allow retrying a few times for connection issues
        if (syncState.retryAttempts < 2 &&
           (errorMessage.includes('timeout') ||
            errorMessage.includes('connection') ||
            errorMessage.includes('network') ||
            errorMessage.includes('không kết nối'))) {

            syncState.retryAttempts++;

            Swal.fire({
                title: 'Đang thử lại...',
                html: `
                    <div class="text-center">
                        <div class="spinner-border text-warning" role="status">
                            <span class="sr-only">Đang tải...</span>
                        </div>
                        <p class="mt-2">Gặp sự cố kết nối. Đang thử lại lần ${syncState.retryAttempts}/2...</p>
                        <p class="text-danger small">${errorMessage}</p>
                    </div>
                `,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then(() => {
                // Try to resume from where we left off
                if (syncState.currentPage > 0) {
                    syncState.inProgress = true;
                    processNextPage(syncState.currentPage);
                } else {
                    // Restart completely if we didn't even get to the first page
                    startSync(syncState.date ? 'date' : 'all', syncState.date ? { date: syncState.date } : {});
                }
            });

            return;
        }

        // Reset retry counter
        syncState.retryAttempts = 0;

        // Show error message
        Swal.fire({
            title: 'Lỗi đồng bộ!',
            html: `
                <div class="text-left">
                    <p class="text-danger font-weight-bold">${errorMessage}</p>
                    <p class="mt-2">Bạn có thể:</p>
                    <ul>
                        <li>Thử lại quá trình đồng bộ</li>
                        <li>Kiểm tra kết nối mạng</li>
                        <li>Kiểm tra cài đặt Pancake của bạn</li>
                    </ul>
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'Thử lại',
            showCancelButton: true,
            cancelButtonText: 'Đóng'
        }).then((result) => {
            if (result.isConfirmed) {
                // Try again with the same settings
                if (syncState.date) {
                    startSync('date', { date: syncState.date });
                } else {
                    startSync('all');
                }
            }
        });
    }

    // Cancel sync process
    function cancelSync(silent = false) {
        return new Promise((resolve, reject) => {
            if (!syncState.inProgress && !silent) {
                Swal.fire({
                    title: 'Thông báo',
                    text: 'Không có quá trình đồng bộ nào đang chạy.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
                resolve();
                return;
            }

            if (!silent) {
                Swal.fire({
                    title: 'Đang hủy...',
                    html: `
                        <div class="text-center">
                            <div class="spinner-grow text-warning mb-2" role="status">
                                <span class="sr-only">Đang hủy...</span>
                            </div>
                            <p class="mb-0">Đang dừng quá trình đồng bộ...</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            $.ajax({
                url: '{{ route("pancake.sync.cancel") }}',
                type: 'POST',
                success: function(response) {
                    // Stop progress tracking
                    if (syncState.progressInterval) {
                        clearInterval(syncState.progressInterval);
                        syncState.progressInterval = null;
                    }

                    // Update state
                    syncState.inProgress = false;

                    if (!silent) {
                        Swal.fire({
                            title: 'Đã hủy!',
                            text: 'Quá trình đồng bộ đã được hủy thành công.',
                            icon: 'info',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.close();
                    }

                    resolve(response);
                },
                error: function(xhr) {
                    if (!silent) {
                        const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Không thể hủy quá trình đồng bộ.';
                        Swal.fire({
                            title: 'Lỗi!',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }

                    reject(xhr);
                }
            });
        });
    }

    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Helper function to format time duration
    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;

        let result = '';
        if (hours > 0) result += hours + ' giờ ';
        if (minutes > 0 || hours > 0) result += minutes + ' phút ';
        result += remainingSeconds + ' giây';

        return result;
    }
    @endcan
});
</script>
@stop
