@extends('layouts.app')

@section('title', 'Đồng bộ Pancake')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Đồng bộ dữ liệu từ Pancake</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Đồng bộ đơn hàng</h5>
                                </div>
                                <div class="card-body">
                                    <form id="syncOrdersForm" action="{{ route('pancake.orders.sync') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="start_date">Từ ngày</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="end_date">Đến ngày</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="syncOrdersBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ đơn hàng
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="orderSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="orderSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Đồng bộ khách hàng</h5>
                                </div>
                                <div class="card-body">
                                    <form id="syncCustomersForm" action="{{ route('customers.sync') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="customer_limit">Số lượng khách hàng tối đa</label>
                                            <input type="number" name="limit" id="customer_limit" class="form-control" value="1000" min="1" max="5000">
                                        </div>
                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="force" id="force_sync" class="form-check-input" value="1">
                                            <label for="force_sync" class="form-check-label">Đồng bộ lại dữ liệu đã tồn tại</label>
                                        </div>
                                        <button type="submit" class="btn btn-success" id="syncCustomersBtn">
                                            <i class="fas fa-sync"></i> Đồng bộ khách hàng
                                        </button>
                                    </form>

                                    <div class="progress mt-3 d-none" id="customerSyncProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <div id="customerSyncResult" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">Lịch sử đồng bộ</h5>
                                </div>
                                <div class="card-body">
                                    <div id="syncLogs">
                                        <p>Đang tải dữ liệu...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Order synchronization
        $('#syncOrdersForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const btn = $('#syncOrdersBtn');
            const progressBar = $('#orderSyncProgress');
            const resultArea = $('#orderSyncResult');

            btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
            progressBar.removeClass('d-none');
            progressBar.find('.progress-bar').css('width', '10%');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    progressBar.find('.progress-bar').css('width', '100%');

                    if (response.success) {
                        resultArea.html(`<div class="alert alert-success mt-3">${response.message}</div>`);
                    } else {
                        resultArea.html(`<div class="alert alert-danger mt-3">${response.message}</div>`);
                    }

                    loadSyncStatus();
                },
                error: function(xhr) {
                    progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-primary').addClass('bg-danger');

                    let errorMessage = 'Đã xảy ra lỗi khi đồng bộ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    resultArea.html(`<div class="alert alert-danger mt-3">${errorMessage}</div>`);
                },
                complete: function() {
                    btn.attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ đơn hàng');

                    // Hide progress bar after 3 seconds
                    setTimeout(function() {
                        progressBar.addClass('d-none');
                    }, 3000);
                }
            });
        });

        // Customer synchronization
        $('#syncCustomersForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const btn = $('#syncCustomersBtn');
            const progressBar = $('#customerSyncProgress');
            const resultArea = $('#customerSyncResult');

            btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
            progressBar.removeClass('d-none');
            progressBar.find('.progress-bar').css('width', '10%');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    progressBar.find('.progress-bar').css('width', '100%');

                    if (response.success) {
                        resultArea.html(`<div class="alert alert-success mt-3">${response.message}</div>`);
                    } else {
                        resultArea.html(`<div class="alert alert-danger mt-3">${response.message}</div>`);
                    }

                    // Start checking sync status for customers
                    checkSyncStatus();
                },
                error: function(xhr) {
                    progressBar.find('.progress-bar').css('width', '100%').removeClass('bg-success').addClass('bg-danger');

                    let errorMessage = 'Đã xảy ra lỗi khi đồng bộ';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    resultArea.html(`<div class="alert alert-danger mt-3">${errorMessage}</div>`);
                },
                complete: function() {
                    btn.attr('disabled', false).html('<i class="fas fa-sync"></i> Đồng bộ khách hàng');

                    // Hide progress bar after 3 seconds
                    setTimeout(function() {
                        progressBar.addClass('d-none');
                    }, 3000);
                }
            });
        });

        // Load sync status on page load
        loadSyncStatus();

        function loadSyncStatus() {
            $.ajax({
                url: '{{ route("pancake.sync.status") }}',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateSyncLogsDisplay(response);
                    }
                },
                error: function() {
                    $('#syncLogs').html('<div class="alert alert-warning">Không thể tải thông tin đồng bộ</div>');
                }
            });
        }

        function updateSyncLogsDisplay(data) {
            if (!data.last_logs || data.last_logs.length === 0) {
                $('#syncLogs').html('<div class="alert alert-info">Chưa có thông tin đồng bộ nào</div>');
                return;
            }

            let html = '<div class="sync-status mb-3">';
            if (data.is_running) {
                html += '<div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...</div>';
            } else {
                const lastSyncDate = data.last_sync ? new Date(data.last_sync * 1000).toLocaleString() : 'Chưa có';
                html += `<div class="alert alert-info">Lần đồng bộ cuối: ${lastSyncDate}</div>`;
            }
            html += '</div>';

            html += '<div class="log-entries" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 10px; border-radius: 5px;">';

            data.last_logs.forEach(function(log) {
                html += `<div class="log-entry">${log}</div>`;
            });

            html += '</div>';

            $('#syncLogs').html(html);
        }

        // Customer sync status check
        function checkSyncStatus() {
            let statusCheckInterval = setInterval(function() {
                $.ajax({
                    url: '{{ route("customers.sync-progress") }}',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.running) {
                            const percent = Math.round((response.processed / response.total) * 100);
                            $('#customerSyncProgress').find('.progress-bar').css('width', percent + '%');
                            $('#customerSyncResult').html(`<div class="alert alert-info mt-3">Đã đồng bộ ${response.processed}/${response.total} khách hàng (${percent}%)</div>`);
                        } else {
                            clearInterval(statusCheckInterval);
                            loadSyncStatus();
                        }
                    },
                    error: function() {
                        clearInterval(statusCheckInterval);
                    }
                });
            }, 2000);
        }
    });
</script>
@endsection
