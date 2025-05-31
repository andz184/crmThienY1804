@extends('adminlte::page')

@section('title', 'Danh sách danh mục')

@section('content_header')
    <h1>Danh sách danh mục</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="d-flex gap-2 mb-0">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm danh mục..." value="{{ request('search') }}" style="width: 220px;">
            <button class="btn btn-outline-secondary" type="submit">Tìm kiếm</button>
        </form>
        <div>
            @can('settings.manage') {{-- Hoặc quyền phù hợp: categories.sync --}}
                <button type="button" class="btn btn-info mr-2" id="syncPancakeCategories">
                    <i class="fas fa-sync-alt"></i> Đồng bộ từ Pancake
                </button>
            @endcan
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm danh mục</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tên</th>
                        <th>Slug</th>
                        <th>Danh mục cha</th>
                        <th>Số sản phẩm</th>
                        <th>Kích hoạt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pancakeCategories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->parent->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ $category->products_count }}</span></td>
                            <td>
                                @if ($category->status == 'active')
                                    <span class="badge bg-success">kích hoạt </span>
                                @else
                                    <span class="badge bg-danger">Không</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa danh mục này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Không tìm thấy danh mục nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-center">
        {{ $pancakeCategories->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>



@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Sync Pancake Categories
            $('#syncPancakeCategories').on('click', function() {
                var syncButton = $(this);
                syncButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');

                Swal.fire({
                    title: 'Đang đồng bộ danh mục...',
                    text: 'Vui lòng đợi trong giây lát.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route("admin.pancake.categories.sync") }}', // Đảm bảo route này tồn tại và là POST
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        syncButton.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Đồng bộ từ Pancake');
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: response.message || 'Đồng bộ danh mục thành công!',
                                timer: 3000
                            }).then(() => {
                                location.reload(); // Reload trang để cập nhật danh sách
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi!',
                                text: response.message || 'Có lỗi xảy ra trong quá trình đồng bộ.'
                            });
                        }
                    },
                    error: function(xhr) {
                        syncButton.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Đồng bộ từ Pancake');
                        var errorMsg = 'Lỗi không xác định.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi Máy Chủ!',
                            text: errorMsg + ' (Code: ' + xhr.status + ')'
                        });
                        console.error("AJAX Error:", xhr.status, xhr.responseText);
                    }
                });
            });
        });
    </script>
@stop
