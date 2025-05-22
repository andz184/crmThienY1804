@extends('adminlte::page')

@section('title', 'Đồng bộ Pancake')

@section('meta_tags')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <h1>Đồng bộ dữ liệu Pancake</h1>
@stop

@section('content')
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title">Đồng bộ Shops & Pages</h3>
        </div>
        <div class="card-body">
            <p>Nhấn nút bên dưới để đồng bộ danh sách Shops và Pages từ Pancake API về hệ thống.</p>
            <p>Dữ liệu sẽ được cập nhật hoặc tạo mới dựa trên Pancake ID. Các shop/page không còn tồn tại trên Pancake sẽ không bị xóa khỏi đây.</p>

            @if(isset($lastSyncTime) && $lastSyncTime)
                <p class="text-muted">Lần đồng bộ cuối: {{ \Carbon\Carbon::parse($lastSyncTime)->format('d/m/Y H:i:s') }} (Dựa trên thời gian cập nhật bản ghi cuối)</p>
            @else
                <p class="text-muted">Chưa có thông tin đồng bộ.</p>
            @endif

            <button id="syncPancakeButton" class="btn btn-primary mb-3">
                <i class="fas fa-sync-alt"></i> Đồng bộ ngay
            </button>

            <div id="syncStatus" class="mt-3"></div>
        </div>
    </div>

    <div class="card card-outline card-success mb-3">
        <div class="card-header">
            <h3 class="card-title">Đồng bộ Nhân viên từ Pancake</h3>
        </div>
        <div class="card-body">
            <p>Tính năng này sẽ đồng bộ danh sách nhân viên từ Pancake và tạo tài khoản trong hệ thống CRM với vai trò <span class="badge badge-info">employee</span>.</p>
            <p>Mỗi nhân viên mới sẽ được tạo với email và mật khẩu trùng nhau để dễ đăng nhập lần đầu.</p>

            @if(isset($employeeCount) && $employeeCount > 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Hiện có <strong>{{ $employeeCount }}</strong> nhân viên đã được đồng bộ từ Pancake.
                </div>
            @endif

            @if(isset($lastEmployeeSyncTime))
                <p class="text-muted">Lần đồng bộ nhân viên cuối: {{ \Carbon\Carbon::parse($lastEmployeeSyncTime)->format('d/m/Y H:i:s') }}</p>
            @else
                <p class="text-muted">Chưa có thông tin đồng bộ nhân viên.</p>
            @endif

            <button id="syncEmployeesButton" class="btn btn-success mb-3">
                <i class="fas fa-users"></i> Đồng bộ nhân viên
            </button>

            <div id="syncEmployeesStatus" class="mt-3"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danh sách Shops đã đồng bộ ({{ $shops->count() }})</h3>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID nội bộ</th>
                                <th>Pancake ID</th>
                                <th>Tên Shop</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shops as $shop)
                                <tr>
                                    <td>{{ $shop->id }}</td>
                                    <td>{{ $shop->pancake_id }}</td>
                                    <td>
                                        @if($shop->avatar_url)<img src="{{ $shop->avatar_url }}" alt="Avatar" class="img-circle img-sm mr-2">@endif
                                        {{ $shop->name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Chưa có shop nào được đồng bộ.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danh sách Pages đã đồng bộ</h3>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Pancake Page ID</th>
                                <th>Tên Page</th>
                                <th>Platform</th>
                                <th>Thuộc Shop (Pancake ID)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $pageCount = 0; @endphp
                            @foreach($shops as $shop)
                                @foreach($shop->pages as $page)
                                @php $pageCount++; @endphp
                                <tr>
                                    <td>{{ $page->pancake_page_id }}</td>
                                    <td>{{ $page->name }}</td>
                                    <td>{{ $page->platform }}</td>
                                    <td>{{ $shop->name }} ({{ $shop->pancake_id }})</td>
                                </tr>
                                @endforeach
                            @endforeach
                            @if($pageCount === 0)
                                <tr>
                                    <td colspan="4" class="text-center">Chưa có page nào được đồng bộ.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const syncButton = document.getElementById('syncPancakeButton');
        const syncStatusDiv = document.getElementById('syncStatus');

        if (syncButton) {
            syncButton.addEventListener('click', function() {
                syncButton.disabled = true;
                syncButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...';
                syncStatusDiv.innerHTML = '<div class="alert alert-info">Đang tiến hành đồng bộ...</div>';

                fetch('{{ route("admin.sync.now") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    syncButton.disabled = false;
                    syncButton.innerHTML = '<i class="fas fa-sync-alt"></i> Đồng bộ ngay';
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: data.message || 'Đồng bộ dữ liệu Pancake thành công.',
                        }).then(() => {
                            location.reload(); // Reload page to see updated lists
                        });
                        syncStatusDiv.innerHTML = `<div class="alert alert-success">${data.message} (Vui lòng chờ trang tải lại...)</div>`;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi đồng bộ!',
                            text: data.message || 'Có lỗi xảy ra trong quá trình đồng bộ.',
                        });
                        syncStatusDiv.innerHTML = `<div class="alert alert-danger">Lỗi: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    syncButton.disabled = false;
                    syncButton.innerHTML = '<i class="fas fa-sync-alt"></i> Đồng bộ ngay';
                    console.error('Sync Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi kết nối!',
                        text: 'Không thể kết nối hoặc có lỗi xảy ra.',
                    });
                    syncStatusDiv.innerHTML = '<div class="alert alert-danger">Lỗi kết nối hoặc lỗi xử lý yêu cầu.</div>';
                });
            });
        }
        
        // Employee sync functionality
        const syncEmployeesButton = document.getElementById('syncEmployeesButton');
        const syncEmployeesStatusDiv = document.getElementById('syncEmployeesStatus');

        if (syncEmployeesButton) {
            syncEmployeesButton.addEventListener('click', function() {
                // Ask for confirmation
                Swal.fire({
                    title: 'Xác nhận đồng bộ nhân viên',
                    text: 'Hệ thống sẽ đồng bộ danh sách nhân viên từ Pancake và tạo tài khoản với mật khẩu trùng với email. Bạn muốn tiếp tục?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Đồng ý, đồng bộ ngay!',
                    cancelButtonText: 'Hủy bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Start sync
                        syncEmployeesButton.disabled = true;
                        syncEmployeesButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ nhân viên...';
                        syncEmployeesStatusDiv.innerHTML = '<div class="alert alert-info">Đang đồng bộ danh sách nhân viên từ Pancake...</div>';

                        fetch('{{ route("admin.sync.employees") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            syncEmployeesButton.disabled = false;
                            syncEmployeesButton.innerHTML = '<i class="fas fa-users"></i> Đồng bộ nhân viên';
                            
                            if (data.success) {
                                // Show success with stats
                                const stats = data.stats || {};
                                const statsHTML = `
                                    <div class="mt-2">
                                        <p class="mb-1"><i class="fas fa-plus-circle text-success"></i> Nhân viên mới: ${stats.created || 0}</p>
                                        <p class="mb-1"><i class="fas fa-sync-alt text-info"></i> Cập nhật: ${stats.updated || 0}</p>
                                        <p class="mb-0"><i class="fas fa-ban text-warning"></i> Bỏ qua: ${stats.skipped || 0}</p>
                                    </div>
                                `;
                                
                                // Show skipped reasons if all employees were skipped
                                let skippedReasonsHTML = '';
                                if (data.skipped_reasons && data.skipped_reasons.length > 0) {
                                    skippedReasonsHTML = `
                                    <div class="mt-3">
                                        <h5 class="text-warning">Lý do bỏ qua nhân viên</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Lý do</th>
                                                        <th>Dữ liệu nhận được</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                    `;
                                    
                                    data.skipped_reasons.forEach((item, index) => {
                                        if (index < 10) { // Limit to first 10 reasons to avoid huge dialogs
                                            skippedReasonsHTML += `
                                                <tr>
                                                    <td>${item.reason}</td>
                                                    <td><pre style="max-height:100px;overflow-y:auto">${JSON.stringify(item.data, null, 2)}</pre></td>
                                                </tr>
                                            `;
                                        }
                                    });
                                    
                                    if (data.skipped_reasons.length > 10) {
                                        skippedReasonsHTML += `
                                            <tr>
                                                <td colspan="2" class="text-center">... và ${data.skipped_reasons.length - 10} trường hợp khác</td>
                                            </tr>
                                        `;
                                    }
                                    
                                    skippedReasonsHTML += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    `;
                                }
                                
                                // Create HTML for employees table if we have synced employees
                                let employeesHTML = '';
                                if (data.employees && data.employees.length > 0) {
                                    employeesHTML = `
                                    <div class="mt-3">
                                        <h5>Danh sách nhân viên đã đồng bộ</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Tên</th>
                                                        <th>Email</th>
                                                        <th>Trạng thái</th>
                                                        <th>Mật khẩu</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                    `;
                                    
                                    data.employees.forEach(employee => {
                                        const statusClass = employee.status === 'created' ? 'success' : 'info';
                                        const statusText = employee.status === 'created' ? 'Mới' : 'Cập nhật';
                                        
                                        employeesHTML += `
                                            <tr>
                                                <td>${employee.name}</td>
                                                <td>${employee.email}</td>
                                                <td><span class="badge badge-${statusClass}">${statusText}</span></td>
                                                <td>${employee.password ? employee.password : '<i class="text-muted">Không thay đổi</i>'}</td>
                                            </tr>
                                        `;
                                    });
                                    
                                    employeesHTML += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    `;
                                }
                                
                                // Show the success message with stats and employees table
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công!',
                                    html: `${data.message || 'Đồng bộ nhân viên thành công.'}<br>${statsHTML}${skippedReasonsHTML}${employeesHTML}`,
                                    width: employeesHTML ? '800px' : '500px'
                                }).then(() => {
                                    // Only reload if there are no employees to display
                                    if (!data.employees || data.employees.length === 0) {
                                        location.reload();
                                    }
                                });
                                
                                // Update the status div with success message and employee table
                                syncEmployeesStatusDiv.innerHTML = `
                                    <div class="alert alert-success">
                                        ${data.message} 
                                    </div>
                                    ${skippedReasonsHTML}${employeesHTML}
                                `;
                                
                                // Update the employee count in the UI without reloading
                                const employeeCountContainer = document.querySelector('.alert.alert-info strong');
                                if (employeeCountContainer) {
                                    const currentCount = parseInt(employeeCountContainer.textContent) || 0;
                                    const newCount = currentCount + stats.created;
                                    employeeCountContainer.textContent = newCount;
                                }
                            } else {
                                // Show error
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi đồng bộ nhân viên!',
                                    text: data.message || 'Có lỗi xảy ra trong quá trình đồng bộ nhân viên.',
                                });
                                
                                syncEmployeesStatusDiv.innerHTML = `<div class="alert alert-danger">Lỗi: ${data.message}</div>`;
                                
                                // Show skipped reasons if available
                                if (data.skipped_reasons && data.skipped_reasons.length > 0) {
                                    let skippedReasonsHTML = `
                                    <div class="mt-3">
                                        <h5>Lý do bỏ qua nhân viên</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Lý do</th>
                                                        <th>Dữ liệu nhận được</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                    `;
                                    
                                    data.skipped_reasons.forEach((item, index) => {
                                        if (index < 10) {
                                            skippedReasonsHTML += `
                                                <tr>
                                                    <td>${item.reason}</td>
                                                    <td><pre style="max-height:100px;overflow-y:auto">${JSON.stringify(item.data, null, 2)}</pre></td>
                                                </tr>
                                            `;
                                        }
                                    });
                                    
                                    if (data.skipped_reasons.length > 10) {
                                        skippedReasonsHTML += `
                                            <tr>
                                                <td colspan="2" class="text-center">... và ${data.skipped_reasons.length - 10} trường hợp khác</td>
                                            </tr>
                                        `;
                                    }
                                    
                                    skippedReasonsHTML += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    `;
                                    
                                    syncEmployeesStatusDiv.innerHTML += skippedReasonsHTML;
                                    
                                    // Show a more detailed error in a modal
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Chi tiết lỗi đồng bộ nhân viên',
                                        html: `<div>${data.message}</div>${skippedReasonsHTML}`,
                                        width: '800px'
                                    });
                                }
                            }
                        })
                        .catch(error => {
                            syncEmployeesButton.disabled = false;
                            syncEmployeesButton.innerHTML = '<i class="fas fa-users"></i> Đồng bộ nhân viên';
                            
                            console.error('Employee Sync Error:', error);
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi kết nối!',
                                text: 'Không thể kết nối hoặc có lỗi xảy ra khi đồng bộ nhân viên.',
                            });
                            
                            syncEmployeesStatusDiv.innerHTML = '<div class="alert alert-danger">Lỗi kết nối hoặc lỗi xử lý yêu cầu đồng bộ nhân viên.</div>';
                        });
                    }
                });
            });
        }
    });
</script>
@stop
