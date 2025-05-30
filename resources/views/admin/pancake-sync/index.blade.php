<!-- Order Sources Section -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Nguồn đơn từ Pancake</h5>
        <button type="button" class="btn btn-primary" onclick="syncOrderSources()">
            <i class="fas fa-sync"></i> Đồng bộ nguồn đơn
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên nguồn đơn</th>
                        <th>Nền tảng</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật lần cuối</th>
                    </tr>
                </thead>
                <tbody id="orderSourcesTable">
                    @foreach($orderSources ?? [] as $source)
                    <tr>
                        <td>{{ $source->pancake_id }}</td>
                        <td>{{ $source->name }}</td>
                        <td>{{ $source->platform }}</td>
                        <td>
                            <span class="badge {{ $source->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $source->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </td>
                        <td>{{ $source->updated_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Nguồn hàng từ Pancake</h5>
        {{-- @can('product-sources.sync') --}}
        <button type="button" class="btn btn-primary" id="syncProductSourcesBtn">
            <i class="fas fa-sync-alt"></i> Đồng bộ nguồn hàng
        </button>
        {{-- @endcan --}}
    </div>
    <div class="card-body">
        @can('product-sources.view')
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên nguồn hàng</th>
                        <th>Loại</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật lúc</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productSources ?? [] as $source)
                    <tr>
                        <td>{{ $source->pancake_id }}</td>
                        <td>{{ $source->name }}</td>
                        <td>{{ $source->type }}</td>
                        <td>
                            @if($source->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-danger">Không hoạt động</span>
                            @endif
                        </td>
                        <td>{{ $source->updated_at }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="alert alert-warning">
            Bạn không có quyền xem danh sách nguồn hàng.
        </div>
        @endcan
    </div>
</div>

@push('scripts')
<script>
function syncOrderSources() {
    const button = event.target.closest('button');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...';

    fetch('{{ route("pancake.sync.order-sources") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('Có lỗi xảy ra khi đồng bộ nguồn đơn');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-sync"></i> Đồng bộ nguồn đơn';
    });
}

@can('product-sources.sync')
<script>
$(document).ready(function() {
    $('#syncProductSourcesBtn').click(function() {
        const btn = $(this);
        const originalHtml = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.pancake-sync.sync-product-sources") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi đồng bộ nguồn hàng';
                toastr.error(message);
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endcan
@endpush
