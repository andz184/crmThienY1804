@extends('adminlte::page')

@section('title', 'Quản lý Nhóm Quyền')

@section('content_header')
    <h1>Danh sách Nhóm Quyền (Roles)</h1>
@stop

@section('content')

{{-- Search Form --}}
<div class="card card-outline card-secondary collapsed-card" id="filter-card">
    <div class="card-header">
        <h3 class="card-title">Tìm kiếm</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        <form method="GET" action="{{ route('admin.roles.index') }}" id="filter-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="search">Tìm theo Tên Role:</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Nhập tên role...">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group w-100">
                        <button type="submit" class="btn btn-primary w-100">Tìm</button>
                    </div>
                </div>
                 <div class="col-md-3 d-flex align-items-end">
                    <div class="form-group w-100">
                        <a href="{{ route('admin.roles.index') }}" id="reset-filter-btn" class="btn btn-secondary w-100">Xóa tìm kiếm</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Role List Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Roles</h3>
        @can('roles.create')
            <div class="card-tools">
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm mới Role
                </a>
            </div>
        @endcan
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
         <div id="role-table-container" style="position: relative;">
            {{-- Loading Overlay --}}
            <div class="overlay-wrapper" id="table-loading-overlay" style="display: none;">
                <div class="overlay"><i class="fas fa-3x fa-sync-alt fa-spin"></i><div class="text-bold pt-2">Loading...</div></div>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Tên Role</th>
                        <th>Permissions</th>
                        <th style="width: 150px">Hành động</th>
                    </tr>
                </thead>
                {{-- ID cho tbody --}}
                <tbody id="role-table-body">
                    {{-- Include partial view --}}
                    @include('admin.roles._role_table_body', ['roles' => $roles])
                </tbody>
            </table>
        </div>
    </div>
    <!-- /.card-body -->
     {{-- ID cho pagination --}}
    <div class="card-footer clearfix" id="role-pagination">
        {{ $roles->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>
@stop

@section('js')
{{-- Copy JS AJAX tương tự Users, đổi ID target --}}
<script>
$(document).ready(function() {
    const tableContainer = $('#role-table-container');
    const tableBody = $('#role-table-body');
    const paginationContainer = $('#role-pagination');
    const loadingOverlay = $('#table-loading-overlay');
    const filterForm = $('#filter-form');
    let currentRequest = null;

    function fetchRoles(url) {
        if (currentRequest) {
            currentRequest.abort();
        }
        loadingOverlay.show();

        currentRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                tableBody.html(response.table_html);
                paginationContainer.html(response.pagination_html);
                history.pushState(null, '', url);
                loadingOverlay.hide();
                currentRequest = null;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus !== 'abort') {
                     console.error("AJAX Error:", textStatus, errorThrown);
                     alert('Đã có lỗi xảy ra khi tải dữ liệu Roles.');
                    loadingOverlay.hide();
                }
                currentRequest = null;
            }
        });
    }

    filterForm.on('submit', function(e) {
        e.preventDefault();
        const url = $(this).attr('action') + '?' + $(this).serialize();
        fetchRoles(url);
    });

    $('#reset-filter-btn').on('click', function(e) {
        e.preventDefault();
        filterForm.find('input[type="text"]').val('');
        const url = $(this).attr('href');
        fetchRoles(url);
    });

    $(document).on('click', '#role-pagination .pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (url) {
            fetchRoles(url);
        }
    });
});
</script>
@stop
