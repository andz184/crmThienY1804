@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quản lý danh mục sản phẩm</h3>
                    <div class="card-tools">
                        @can('categories.create')
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm danh mục
                        </a>
                        @endcan
                        @can('categories.sync')
                        <button id="syncCategoriesBtn" class="btn btn-info">
                            <i class="fas fa-sync"></i> Đồng bộ với Pancake
                        </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div id="syncStatus" class="alert d-none"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Mô tả</th>
                                    <th>Danh mục cha</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ Str::limit($category->description, 50) }}</td>
                                    <td>{{ $category->parent->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $category->is_active ? 'success' : 'danger' }}">
                                            {{ $category->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            @can('categories.edit')
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            @can('categories.delete')
                                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#syncCategoriesBtn').click(function() {
        const btn = $(this);
        const statusDiv = $('#syncStatus');

        btn.prop('disabled', true);
        statusDiv.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('Đang đồng bộ danh mục...');

        $.ajax({
            url: '{{ route("admin.categories.sync") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                statusDiv.removeClass('alert-info alert-danger').addClass('alert-success').html(response.message);
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                statusDiv.removeClass('alert-info alert-success').addClass('alert-danger')
                    .html(xhr.responseJSON?.message || 'Có lỗi xảy ra khi đồng bộ');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
@endsection
