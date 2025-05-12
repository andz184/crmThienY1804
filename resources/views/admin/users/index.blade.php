@extends('adminlte::page')

@section('title', 'Quản lý Người Dùng')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Danh sách Người dùng</h1>
        <div>
            <a href="{{ route('admin.users.trashed') }}" class="btn btn-warning btn-sm">Thùng rác (Đã xóa)</a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Thêm mới</a>
        </div>
    </div>
@stop

@section('content')

{{-- Filter and Search Form --}}
<div class="card card-outline card-primary collapsed-card" id="filter-card">
    <div class="card-header">
        <h3 class="card-title">Bộ lọc & Tìm kiếm</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body" style="display: none;">
        {{-- Đặt ID cho form --}}
        <form method="GET" action="{{ route('admin.users.index') }}" id="filter-form">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search">Tìm theo Tên/Email:</label>
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Nhập tên hoặc email...">
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="form-group">
                        <label for="role">Lọc theo Role:</label>
                        <select name="role" id="role" class="form-control">
                            <option value="">-- Tất cả Roles --</option>
                            @foreach($filterRoles as $roleValue => $roleName)
                                <option value="{{ $roleValue }}" {{ request('role') == $roleValue ? 'selected' : '' }}>{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                         <label for="team_id">Lọc theo Team (Staff):</label>
                         <select name="team_id" id="team_id" class="form-control">
                             <option value="">-- Tất cả Teams --</option>
                              @foreach($filterTeams as $teamId => $teamLeaderName)
                                 <option value="{{ $teamId }}" {{ request('team_id') == $teamId ? 'selected' : '' }}>{{ $teamLeaderName }} (Team {{ $teamId }})</option>
                              @endforeach
                         </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-group w-100">
                        {{-- Nút Lọc trigger AJAX --}}
                        <button type="submit" class="btn btn-primary w-100">Lọc</button>
                         {{-- Nút Xóa lọc cũng trigger AJAX --}}
                        <a href="{{ route('admin.users.index') }}" id="reset-filter-btn" class="btn btn-secondary w-100 mt-1">Xóa lọc</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- User List Table --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Users</h3>
        @can('users.create')
            <div class="card-tools">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus mr-1"></i> Thêm mới User
                </a>
            </div>
        @endcan
    </div>
    <!-- /.card-header -->
    <div class="card-body p-0">
        {{-- ID cho table để dễ dàng target loading overlay --}}
        <div id="user-table-container" style="position: relative;">
            {{-- Thêm overlay khi loading --}}
            <div class="overlay-wrapper" id="table-loading-overlay" style="display: none;">
                <div class="overlay"><i class="fas fa-3x fa-sync-alt fa-spin"></i><div class="text-bold pt-2">Loading...</div></div>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Tên User</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Quản lý bởi</th>
                        <th style="width: 150px">Hành động</th>
                    </tr>
                </thead>
                {{-- ID cho tbody để cập nhật nội dung --}}
                <tbody id="user-table-body">
                    {{-- Include partial view ban đầu --}}
                    @include('admin.users._user_table_body', ['users' => $users])
                </tbody>
            </table>
        </div>
    </div>
    <!-- /.card-body -->
     {{-- ID cho pagination để cập nhật --}}
    <div class="card-footer clearfix" id="user-pagination">
         {{ $users->appends(request()->query())->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    const tableContainer = $('#user-table-container');
    const tableBody = $('#user-table-body');
    const paginationContainer = $('#user-pagination');
    const loadingOverlay = $('#table-loading-overlay');
    const filterForm = $('#filter-form');
    let currentRequest = null; // To abort previous requests

    function fetchUsers(url) {
        // Abort previous request if it exists
        if (currentRequest) {
            currentRequest.abort();
        }

        loadingOverlay.show(); // Show loading indicator

        currentRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                tableBody.html(response.table_html);
                paginationContainer.html(response.pagination_html);
                // Update browser URL without reloading
                history.pushState(null, '', url);
                loadingOverlay.hide();
                currentRequest = null;
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus !== 'abort') { // Don't show error if request was aborted
                     console.error("AJAX Error:", textStatus, errorThrown);
                     alert('Đã có lỗi xảy ra khi tải dữ liệu.');
                    loadingOverlay.hide();
                }
                currentRequest = null;
            }
        });
    }

    // Handle filter form submission
    filterForm.on('submit', function(e) {
        e.preventDefault(); // Prevent normal form submission
        const url = $(this).attr('action') + '?' + $(this).serialize();
        fetchUsers(url);
    });

    // Handle reset button click
    $('#reset-filter-btn').on('click', function(e) {
        e.preventDefault();
        filterForm.find('input[type="text"], select').val(''); // Clear form fields
        const url = $(this).attr('href');
        fetchUsers(url);
    });

    // Handle pagination link clicks
    // Use event delegation as pagination links are replaced dynamically
    $(document).on('click', '#user-pagination .pagination a', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (url) {
            fetchUsers(url);
        }
    });

     // Optional: Auto-submit filter form on select change
     // filterForm.find('select').on('change', function() {
     //     filterForm.submit();
     // });

});
</script>

    {{-- Include SweetAlert2 if you use it --}}
    {{-- <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userTableBody = document.querySelector('#user-table-body'); // Target the tbody

            userTableBody.addEventListener('click', function (event) {
                // Delegate event listening to the table body
                const button = event.target.closest('.delete-user-button');

                if (button) {
                    event.preventDefault(); // Prevent any default action if it was somehow a link/submit

                    const userId = button.dataset.userId;
                    const userName = button.dataset.userName;
                    const url = button.dataset.deleteUrl;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    // Basic confirmation (replace with SweetAlert if preferred)
                    if (confirm(`Bạn có chắc muốn xóa người dùng "${userName}"? Họ sẽ được chuyển vào thùng rác.`)) {
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json' // Important: Expect JSON response
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                // Handle errors (e.g., cannot delete self, cannot delete super-admin)
                                return response.json().then(data => {
                                     throw new Error(data.error || `HTTP error! Status: ${response.status}`);
                                });
                            }
                            return response.json(); // Parse success response
                        })
                        .then(data => {
                            // Remove the table row
                            const row = button.closest('tr');
                            row.remove();

                            // Show success message (e.g., using an alert partial or a toast library)
                            alert(data.message || 'User deleted successfully!'); // Replace with better notification
                             // You might want to update a count somewhere or check if the table is now empty
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            alert('Lỗi khi xóa người dùng: ' + error.message);
                        });
                    }
                }
            });
        });
    </script>
@stop

@section('plugins.Sweetalert2', true)
