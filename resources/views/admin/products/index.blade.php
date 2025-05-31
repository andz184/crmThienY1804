@extends('adminlte::page')

@section('title', 'Quản lý sản phẩm')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Quản lý sản phẩm</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">Sản phẩm</li>
        </ol>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Danh sách sản phẩm</h3>
                    <div class="card-tools">
                        @can('products.create')
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </a>
                        @endcan
                        @can('products.sync')
                        <button id="syncProductsBtn" class="btn btn-info">
                            <i class="fas fa-sync"></i> Đồng bộ với Pancake
                        </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="syncStatus" class="alert d-none"></div>

                <!-- Search and Filter Form -->
                <form action="{{ route('admin.products.index') }}" method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                           placeholder="Tìm theo tên, mã SKU..."
                                           value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select name="category_id" class="form-control select2">
                                    <option value="">-- Tất cả danh mục --</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">-- Trạng thái --</option>
                                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Đang hoạt động</option>
                                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá bán</th>
                                <th>Tồn kho</th>
                                <th>Trạng thái</th>
                                <th style="width: 150px">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>{{ $product->id }}</td>
                                <td class="text-center">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                             class="img-thumbnail" style="max-width: 50px">
                                    @else
                                        <span class="text-muted"><i class="fas fa-image"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->sku)
                                        <br><small class="text-muted">SKU: {{ $product->sku }}</small>
                                    @endif
                                </td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td>{{ number_format($product->base_price, 0, ',', '.') }} đ</td>
                                <td>
                                    @php
                                        $totalStock = $product->variants?->sum('stock_quantity') ?? 0;
                                        $stockClass = $totalStock > 0 ? 'success' : 'danger';
                                    @endphp
                                    <span class="badge badge-{{ $stockClass }}">
                                        {{ $totalStock }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $product->is_active ? 'success' : 'danger' }}">
                                        {{ $product->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @can('products.edit')
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                           class="btn btn-sm btn-warning"
                                           data-toggle="tooltip"
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan

                                        @can('products.delete')
                                        <button type="button"
                                                class="btn btn-sm btn-danger delete-product"
                                                data-id="{{ $product->id }}"
                                                data-toggle="tooltip"
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $product->id }}"
                                              action="{{ route('admin.products.destroy', $product) }}"
                                              method="POST"
                                              class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">Không có sản phẩm nào</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle delete confirmation
    $('.delete-product').click(function(e) {
        e.preventDefault();
        const productId = $(this).data('id');

        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $(`#delete-form-${productId}`).submit();
            }
        });
    });

    // Handle product sync
    $('#syncProductsBtn').click(function() {
        const btn = $(this);
        const statusDiv = $('#syncStatus');

        btn.prop('disabled', true);
        statusDiv.removeClass('d-none alert-success alert-danger')
                .addClass('alert-info')
                .html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ sản phẩm...');

        $.ajax({
            url: '{{ route("admin.products.sync") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                statusDiv.removeClass('alert-info alert-danger')
                        .addClass('alert-success')
                        .html('<i class="fas fa-check"></i> ' + response.message);
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                statusDiv.removeClass('alert-info alert-success')
                        .addClass('alert-danger')
                        .html('<i class="fas fa-times"></i> ' + (xhr.responseJSON?.message || 'Có lỗi xảy ra khi đồng bộ'));
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@stop
