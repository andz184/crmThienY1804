@extends('adminlte::page')

@section('title', 'Quản lý danh mục')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Quản lý danh mục</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">Danh mục</li>
        </ol>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ isset($category) ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}</h3>
            </div>
            <form action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
                  method="POST"
                  id="categoryForm">
                @csrf
                @if(isset($category))
                    @method('PUT')
                @endif

                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $category->name ?? '') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3">{{ old('description', $category->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="parent_id">Danh mục cha</label>
                        <select class="form-control select2 @error('parent_id') is-invalid @enderror"
                                id="parent_id"
                                name="parent_id">
                            <option value="">-- Không có --</option>
                            @foreach($categories as $id => $name)
                                @if(!isset($category) || $id != $category->id)
                                    <option value="{{ $id }}"
                                            {{ old('parent_id', $category->parent_id ?? '') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Kích hoạt danh mục</label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ isset($category) ? 'Cập nhật' : 'Thêm mới' }}
                    </button>
                    @if(isset($category))
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Danh sách danh mục</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 50px">ID</th>
                                <th>Tên danh mục</th>
                                <th>Danh mục cha</th>
                                <th>Số sản phẩm</th>
                                <th>Trạng thái</th>
                                <th style="width: 120px">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                        @if($category->description)
                                            <br>
                                            <small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $category->parent->name ?? 'Không có' }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $category->products_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">
                                            {{ $category->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            @can('categories.edit')
                                                <a href="{{ route('admin.categories.edit', $category) }}"
                                                   class="btn btn-sm btn-warning"
                                                   data-toggle="tooltip"
                                                   title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan

                                            @can('categories.delete')
                                                <button type="button"
                                                        class="btn btn-sm btn-danger delete-category"
                                                        data-id="{{ $category->id }}"
                                                        data-toggle="tooltip"
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $category->id }}"
                                                      action="{{ route('admin.categories.destroy', $category) }}"
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
                                    <td colspan="6" class="text-center">Không có danh mục nào</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
    $('.delete-category').click(function(e) {
        e.preventDefault();
        const categoryId = $(this).data('id');

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
                $(`#delete-form-${categoryId}`).submit();
            }
        });
    });

    // Form validation
    $('#categoryForm').submit(function(e) {
        let isValid = true;
        const requiredFields = $(this).find('input[required]');

        requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng điền đầy đủ thông tin bắt buộc'
            });
        }
    });
});
</script>
@stop
