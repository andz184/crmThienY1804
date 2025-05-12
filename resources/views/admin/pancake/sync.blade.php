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

                fetch('{{ route("admin.pancake.sync.now") }}', {
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
    });
</script>
@stop
