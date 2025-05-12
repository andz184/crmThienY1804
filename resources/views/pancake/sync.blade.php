@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Đồng bộ dữ liệu Pancake</h5>
                    <div>
                        <button id="checkStatus" class="btn btn-info">
                            <i class="fas fa-sync"></i> Kiểm tra trạng thái
                        </button>
                        <button id="startSync" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Bắt đầu đồng bộ
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Trạng thái đồng bộ -->
                    <div id="syncStatus" class="alert d-none"></div>

                    <!-- Form điều khiển -->
                    <form id="syncForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="chunk">Số lượng mỗi lần xử lý:</label>
                                    <input type="number" class="form-control" id="chunk" name="chunk" value="100" min="1" max="1000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="force" name="force">
                                    <label class="form-check-label" for="force">Buộc cập nhật tất cả</label>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Log viewer -->
                    <div class="card">
                        <div class="card-header">
                            Log đồng bộ gần đây
                        </div>
                        <div class="card-body">
                            <pre id="logViewer" style="height: 300px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncStatus = document.getElementById('syncStatus');
    const logViewer = document.getElementById('logViewer');
    const startSyncBtn = document.getElementById('startSync');
    const checkStatusBtn = document.getElementById('checkStatus');
    const syncForm = document.getElementById('syncForm');

    // Kiểm tra trạng thái
    function checkSyncStatus() {
        fetch('{{ route("pancake.sync.status") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    syncStatus.classList.remove('d-none', 'alert-danger');
                    syncStatus.classList.add('alert-info');

                    let statusText = data.is_running ?
                        'Đang đồng bộ...' :
                        'Đồng bộ lần cuối: ' + new Date(data.last_sync * 1000).toLocaleString();

                    syncStatus.textContent = statusText;

                    // Hiển thị log
                    logViewer.textContent = data.last_logs.join('\n');
                    logViewer.scrollTop = logViewer.scrollHeight;
                }
            })
            .catch(error => {
                console.error('Lỗi kiểm tra trạng thái:', error);
                syncStatus.classList.remove('d-none');
                syncStatus.classList.add('alert-danger');
                syncStatus.textContent = 'Lỗi kiểm tra trạng thái';
            });
    }

    // Bắt đầu đồng bộ
    startSyncBtn.addEventListener('click', function() {
        const formData = new FormData(syncForm);

        startSyncBtn.disabled = true;
        syncStatus.classList.remove('d-none');
        syncStatus.classList.add('alert-info');
        syncStatus.textContent = 'Đang bắt đầu đồng bộ...';

        fetch('{{ route("pancake.sync") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chunk: formData.get('chunk'),
                force: formData.get('force') === 'on'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                syncStatus.classList.remove('alert-danger');
                syncStatus.classList.add('alert-success');
                syncStatus.textContent = 'Đồng bộ thành công!';

                // Hiển thị output
                if (data.output) {
                    logViewer.textContent = data.output.join('\n');
                    logViewer.scrollTop = logViewer.scrollHeight;
                }
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi đồng bộ:', error);
            syncStatus.classList.remove('alert-info');
            syncStatus.classList.add('alert-danger');
            syncStatus.textContent = 'Lỗi đồng bộ: ' + error.message;
        })
        .finally(() => {
            startSyncBtn.disabled = false;
        });
    });

    // Kiểm tra trạng thái định kỳ
    checkStatusBtn.addEventListener('click', checkSyncStatus);
    checkSyncStatus(); // Kiểm tra ngay khi tải trang
    setInterval(checkSyncStatus, 30000); // Kiểm tra mỗi 30 giây
});
</script>
@endpush
@endsection
