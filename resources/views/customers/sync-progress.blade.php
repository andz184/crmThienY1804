<!-- Modal Đồng bộ -->
<div class="modal fade" id="syncModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel">Đồng bộ dữ liệu từ Pancake</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="syncProgress"></div>
                </div>
                <div id="syncStatus" class="text-center mb-3">Đang chuẩn bị đồng bộ...</div>
                <div id="syncStats" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnHide">Chạy nền</button>
                <button type="button" class="btn btn-danger" id="btnCancel">Hủy</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast thông báo -->
<div class="position-fixed bottom-0 right-0 p-3" style="z-index: 9999; right: 0; bottom: 0;">
    <div id="syncToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000">
        <div class="toast-header">
            <strong class="mr-auto">Thông báo</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

@push('scripts')
<script>
let syncInterval;
let isSyncing = false;
let isPageVisible = true;

// Track page visibility
document.addEventListener('visibilitychange', function() {
    isPageVisible = !document.hidden;
    if (document.hidden) {
        clearInterval(syncInterval);
        isSyncing = false;
    }
});

function startSync() {
    if (!isPageVisible) return;

    isSyncing = true;
    $('#syncModal').modal('show');
    $('#syncProgress').css('width', '0%');
    $('#syncStatus').text('Đang chuẩn bị đồng bộ...');
    $('#syncStats').html('');

    $.ajax({
        url: '{{ route("customers.start-sync") }}',
        method: 'POST',
        success: function(response) {
            if (!isPageVisible) return;

            if (response.success) {
                checkProgress();
                syncInterval = setInterval(checkProgress, 2000);
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (!isPageVisible) return;
            showError('Không thể bắt đầu đồng bộ: ' + xhr.responseText);
        }
    });
}

function checkProgress() {
    if (!isPageVisible || !isSyncing) {
        clearInterval(syncInterval);
        return;
    }

    $.ajax({
        url: '{{ route("customers.sync-progress") }}',
        method: 'GET',
        success: function(response) {
            if (!isPageVisible || !isSyncing) {
                clearInterval(syncInterval);
                return;
            }

            const progress = response.progress || 0;
            $('#syncProgress').css('width', progress + '%');
            $('#syncStatus').text(response.message || 'Đang đồng bộ...');

            if (response.stats) {
                let statsHtml = `
                    <div class="row">
                        <div class="col-6">Tổng số: ${response.stats.total || 0}</div>
                        <div class="col-6">Đã xử lý: ${response.stats.synced || 0}</div>
                        <div class="col-6">Lỗi: ${response.stats.failed || 0}</div>
                        <div class="col-6">Không có SĐT: ${response.stats.no_phone || 0}</div>
                    </div>
                `;
                $('#syncStats').html(statsHtml);
            }

            if (response.is_completed) {
                clearInterval(syncInterval);
                isSyncing = false;
                showSuccess(response.message);
                setTimeout(() => {
                    $('#syncModal').modal('hide');
                    location.reload();
                }, 2000);
            }
        },
        error: function() {
            if (!isPageVisible) return;
            clearInterval(syncInterval);
            showError('Không thể kiểm tra tiến trình đồng bộ');
        }
    });
}

function showError(message) {
    $('#syncToast .toast-body').text(message);
    $('#syncToast').toast('show');
}

function showSuccess(message) {
    $('#syncToast .toast-body').text(message);
    $('#syncToast').toast('show');
}

// Xử lý nút chạy nền
$('#btnHide').click(function() {
    $('#syncModal').modal('hide');
});

// Xử lý nút hủy
$('#btnCancel').click(function() {
    if (confirm('Bạn có chắc muốn hủy quá trình đồng bộ?')) {
        $.ajax({
            url: '{{ route("customers.cancel-sync") }}',
            method: 'POST',
            success: function() {
                isSyncing = false;
                clearInterval(syncInterval);
                $('#syncModal').modal('hide');
                showSuccess('Đã hủy đồng bộ');
            }
        });
    }
});

// Khởi động đồng bộ khi click nút
$('#btnSync').click(startSync);

// Cleanup khi modal đóng
$('#syncModal').on('hidden.bs.modal', function () {
    if (!isSyncing) {
        clearInterval(syncInterval);
    }
});

// Cleanup khi rời trang
window.addEventListener('beforeunload', function() {
    clearInterval(syncInterval);
    isSyncing = false;
});
</script>
@endpush
