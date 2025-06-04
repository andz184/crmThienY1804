@extends('adminlte::page')

@section('title', 'Chi tiết Đơn hàng - ' . $order->order_code)

@section('content_header')
    <h1 class="m-0 text-dark">Chi tiết Đơn hàng: {{ $order->order_code }}</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Chi tiết đơn hàng #{{ $order->order_code }}</h3>
                        <div class="card-tools">
                            @can('orders.push_to_pancake')
                                @if(!$order->pancake_order_id)
                                    <button type="button" class="btn btn-info mr-2 btn-push-pancake"
                                            data-order-id="{{ $order->id }}"
                                            data-url="{{ route('orders.pushToPancake', $order->id) }}"
                                            title="Đẩy đơn hàng này lên Pancake">
                                        <i class="fas fa-rocket fa-fw"></i> Đẩy lên Pancake
                                    </button>
                                @endif
                            @endcan
                            <a href="{{ route('orders.index') }}" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="orderTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab">
                                    Thông tin đơn hàng
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="logs-tab" data-toggle="tab" href="#logs" role="tab">
                                    Lịch sử thay đổi
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="orderTabContent">
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">Mã đơn hàng</dt>
                                            <dd class="col-sm-8">{{ $order->order_code }}</dd>

                                            <dt class="col-sm-4">Khách hàng</dt>
                                            <dd class="col-sm-8">{{ $order->customer_name }}</dd>

                                            <dt class="col-sm-4">Số điện thoại</dt>
                                            <dd class="col-sm-8">{{ $order->bill_phone_number }}</dd>

                                            <dt class="col-sm-4">Email</dt>
                                            <dd class="col-sm-8">{{ $order->customer_email ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Địa chỉ</dt>
                                            <dd class="col-sm-8">{{ $order->full_address ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Nhân viên phụ trách</dt>
                                            <dd class="col-sm-8">{{ $order->user->name ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Trạng thái</dt>
                                            <dd class="col-sm-8">{{ $order->status }}</dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">Kho hàng</dt>
                                            <dd class="col-sm-8">{{ $order->warehouse->name ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Phí vận chuyển</dt>
                                            <dd class="col-sm-8">{{ number_format($order->shipping_fee ?? 0) }} VNĐ</dd>

                                            <dt class="col-sm-4">Phương thức thanh toán</dt>
                                            <dd class="col-sm-8">{{ $order->payment_method ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Đơn vị vận chuyển</dt>
                                            <dd class="col-sm-8">{{ $order->shippingProvider->name ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Ghi chú</dt>
                                            <dd class="col-sm-8">{{ $order->notes ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Ghi chú bổ sung</dt>
                                            <dd class="col-sm-8">{{ $order->additional_notes ?? 'N/A' }}</dd>

                                            <dt class="col-sm-4">Ngày tạo</dt>
                                            <dd class="col-sm-8">{{ $order->created_at->format('d/m/Y H:i:s') }}</dd>
                                        </dl>
                                    </div>
                                </div>

                                <h5 class="mt-4">Sản phẩm</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Mã SP (Pancake)</th>
                                                <th>Tên sản phẩm</th>
                                                <th>Đơn giá</th>
                                                <th>Số lượng</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($order->products_data)
                                                @php
                                                    $productsData = json_decode($order->products_data, true);
                                                    $totalAmount = 0;
                                                @endphp
                                                @if(is_array($productsData) && count($productsData) > 0)
                                                    @foreach($productsData as $product)
                                                        @php
                                                            $price = $product['variation_info']['retail_price'] ?? 0;
                                                            $quantity = $product['quantity'] ?? 1;
                                                            $subtotal = $price * $quantity;
                                                            $totalAmount += $subtotal;
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $product['variation_id'] ?? 'N/A' }}</td>
                                                            <td>
                                                                {{ $product['variation_info']['name'] ?? 'N/A' }}
                                                                @if(!empty($product['variation_info']['detail']))
                                                                    <br>
                                                                    <small class="text-muted">{{ $product['variation_info']['detail'] }}</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-right">{{ number_format($price, 0, '.', ',') }}đ</td>
                                                            <td class="text-center">{{ $quantity }}</td>
                                                            <td class="text-right">{{ number_format($subtotal, 0, '.', ',') }}đ</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="5" class="text-center">Không có thông tin sản phẩm</td>
                                                    </tr>
                                                @endif
                                            @else
                                                <tr>
                                                    <td colspan="5" class="text-center">Không có thông tin sản phẩm</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-right">Tổng giá trị sản phẩm:</th>
                                                <td class="text-right">{{ number_format($totalAmount ?? 0, 0, '.', ',') }} VNĐ</td>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-right">Phí vận chuyển:</th>
                                                <td class="text-right">{{ number_format($order->shipping_fee ?? 0, 0, '.', ',') }} VNĐ</td>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-right">Tổng cộng:</th>
                                                <td class="text-right font-weight-bold">{{ number_format(($totalAmount ?? 0) + ($order->shipping_fee ?? 0), 0, '.', ',') }} VNĐ</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                {{-- Pancake Information Section --}}
                                <h5 class="mt-4">Thông tin Pancake</h5>
                                <div class="card card-outline card-primary">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <dl class="row">
                                                    <dt class="col-sm-5">Pancake Order ID</dt>
                                                    <dd class="col-sm-7">
                                                        @if($order->pancake_order_id)
                                                            <span class="badge badge-primary">{{ $order->pancake_order_id }}</span>
                                                            <small class="text-muted ml-2">(Đơn được tạo từ Pancake)</small>
                                                        @else
                                                            <span class="text-muted">Không có</span>
                                                        @endif
                                                    </dd>
                                                </dl>
                                            </div>
                                            <div class="col-md-6">
                                                <dl class="row">
                                                    <dt class="col-sm-5">Post ID (Campaign)</dt>
                                                    <dd class="col-sm-7">{{ $order->post_id ?? 'N/A' }}</dd>

                                                    <dt class="col-sm-5">Shop/Page</dt>
                                                    <dd class="col-sm-7">
                                                        @if($order->pancake_shop_id)
                                                            {{ \App\Models\PancakeShop::find($order->pancake_shop_id)->name ?? 'Shop ID: '.$order->pancake_shop_id }}
                                                            @if($order->pancake_page_id)
                                                                / {{ \App\Models\PancakePage::find($order->pancake_page_id)->name ?? 'Page ID: '.$order->pancake_page_id }}
                                                            @endif
                                                        @else
                                                            N/A
                                                        @endif
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <dl class="row">
                                                    <dt class="col-sm-5">Tên ghi hóa đơn</dt>
                                                    <dd class="col-sm-7">{{ $order->bill_full_name ?? 'N/A' }}</dd>

                                                    <dt class="col-sm-5">SĐT ghi hóa đơn</dt>
                                                    <dd class="col-sm-7">{{ $order->bill_phone_number ?? 'N/A' }}</dd>

                                                    <dt class="col-sm-5">Email ghi hóa đơn</dt>
                                                    <dd class="col-sm-7">{{ $order->bill_email ?? 'N/A' }}</dd>
                                                </dl>
                                            </div>
                                            <div class="col-md-6">
                                                <dl class="row">
                                                    <dt class="col-sm-5">Ghi Chú</dt>
                                                    <dd class="col-sm-7">
                                                        {{ $order->notes ?? 'N/A' }}
                                                    </dd>

                                                    <dt class="col-sm-5">Phí vận chuyển</dt>
                                                    <dd class="col-sm-7">
                                                        {{ number_format($order->shipping_fee ?? 0) }} VNĐ
                                                        @if($order->is_free_shipping)
                                                            <span class="badge badge-success">Free Ship</span>
                                                        @endif
                                                    </dd>

                                                    <dt class="col-sm-5">Phí đối tác</dt>
                                                    <dd class="col-sm-7">
                                                        {{ number_format($order->partner_fee ?? 0) }} VNĐ
                                                        @if($order->customer_pay_fee)
                                                            <span class="badge badge-warning">KH trả phí</span>
                                                        @endif
                                                    </dd>

                                                    <dt class="col-sm-5">Lý do hoàn/hủy</dt>
                                                    <dd class="col-sm-7">
                                                        @if($order->returned_reason)
                                                            <span class="badge badge-danger">
                                                                @switch($order->returned_reason)
                                                                    @case(1)
                                                                        Đổi ý
                                                                        @break
                                                                    @case(2)
                                                                        Lỗi sản phẩm
                                                                        @break
                                                                    @case(3)
                                                                        Giao hàng sai
                                                                        @break
                                                                    @case(4)
                                                                        Giao hàng chậm
                                                                        @break
                                                                    @default
                                                                        Lý do khác ({{ $order->returned_reason }})
                                                                @endswitch
                                                            </span>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>

                                        @if(!$order->pancake_order_id && (!$order->pancake_push_status || $order->pancake_push_status !== 'success') && $order->internal_status !== 'Pushed to Pancake successfully.')
                                            <div class="text-center mt-2">
                                                @can('orders.push_to_pancake')
                                                    <button class="btn btn-primary btn-push-pancake"
                                                            data-order-id="{{ $order->id }}"
                                                            data-url="{{ route('orders.pushToPancake', $order->id) }}">
                                                        <i class="fas fa-rocket mr-1"></i> Đẩy đơn lên Pancake
                                                    </button>
                                                @endcan
                                            </div>
                                        @endif

                                        @if($order->pancake_status)
                                        <tr>
                                            <th>Trạng thái Pancake:</th>
                                            <td>
                                                @if(is_numeric($order->pancake_status) && App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->exists())
                                                    @php $pancakeStatus = App\Models\PancakeOrderStatus::where('status_code', $order->pancake_status)->first(); @endphp
                                                    <span class="badge badge-{{ $pancakeStatus->color }}">{{ $pancakeStatus->name }}</span>
                                                @else
                                                    <span class="badge {{ $order->getPancakeStatusClass() }}">{{ $order->getPancakeStatusText() }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="logs" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Thời gian</th>
                                                <th>Người thực hiện</th>
                                                <th>Hành động</th>
                                                <th>Chi tiết</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($order->activities as $activity)
                                            <tr>
                                                <td>{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                                                <td>{{ $activity->user->name ?? 'N/A' }}</td>
                                                <td>{{ ucfirst($activity->action) }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info view-changes"
                                                            data-old="{{ json_encode($activity->old_data) }}"
                                                            data-new="{{ json_encode($activity->new_data) }}">
                                                        <i class="fas fa-eye"></i> Xem thay đổi
                                                    </button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center">Không có dữ liệu</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal hiển thị chi tiết thay đổi -->
    <div class="modal fade" id="changesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết thay đổi</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Dữ liệu cũ</h6>
                            <pre id="oldData"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Dữ liệu mới</h6>
                            <pre id="newData"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    </div>

    {{-- Other sections like Call History etc. --}}
    @if ($order->calls && $order->calls->count() > 0)
    @endif
@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            let callPopupWindow = null; // Store reference to the call window
            const currentShowPageOrderId = parseInt({{ $order->id }}, 10);
            let callPageStatusTimerInterval = null;
            let callPageStatusStartTime = null;

            // Load order history logs via AJAX
            function loadOrderLogs(url = "{{ route('admin.logs.model', ['modelType' => 'Order', 'modelId' => $order->id]) }}") {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        $('#order-log-history').html(response);
                    },
                    error: function(xhr) {
                        $('#order-log-history').html('<div class="p-3 text-center text-danger">Error loading history.</div>');
                        console.error('Error loading order logs:', xhr);
                    }
                });
            }

            // Initial load
            loadOrderLogs();

            // Handle pagination clicks within the log history section
            $(document).on('click', '#order-log-history .pagination a', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                loadOrderLogs(url);
            });

            // === START REFACTOR FOR VOIP HISTORY FETCH ===
            function fetchAndDisplayVoipHistory(orderId) {
                var $button = $('#btn-fetch-voip-history'); // Nút "Tải Lịch sử Voip24h" trên trang

                // Modal elements
                var $modal = $('#fetchVoipHistoryProgressModal');
                var $modalLabel = $('#fetchVoipHistoryProgressModalLabel');
                var $modalMessage = $('#fetch-voip-message');
                var $modalProgressBar = $('#fetch-voip-progress-bar');
                var $modalFooter = $('#fetch-voip-progress-modal-footer');

                // Reset và hiển thị Modal
                $modalLabel.text('Đang tải Lịch sử Voip24h...');
                $modalMessage.text('Đang xử lý, vui lòng đợi...');
                $modalProgressBar.removeClass('bg-success bg-danger bg-warning').addClass('progress-bar-animated').css('width', '0%').text('0%');
                $modalFooter.hide();
                $modal.modal('show');
                $button.prop('disabled', true); // Vô hiệu hóa nút trên trang khi modal mở

                // Mô phỏng tiến trình ban đầu trong modal
                // var progress = 0; // This var was unused
                $modalProgressBar.css('width', '10%').text('10%'); // Bắt đầu ở 10%
                $modalMessage.text('Đang khởi tạo yêu cầu...');

                var fetchUrl = `/orders/${orderId}/fetch-voip-history`;

                $.ajax({
                    url: fetchUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    },
                    success: function(response) {
                        $modalProgressBar.css('width', '100%').text('100%');

                        if (response.success) {
                            $modalProgressBar.addClass('bg-success').removeClass('progress-bar-animated');
                            $modalLabel.text('Hoàn tất');
                            $modalMessage.text(response.message || 'Đã xử lý yêu cầu đồng bộ lịch sử.');
                        } else {
                            $modalProgressBar.addClass('bg-warning').removeClass('progress-bar-animated'); // Hoặc bg-danger tùy mức độ lỗi
                            $modalLabel.text('Có lỗi xảy ra');
                            $modalMessage.text(response.message || 'Không thể hoàn tất yêu cầu.');
                        }
                        $modalFooter.show(); // Hiển thị nút Đóng
                    },
                    error: function(xhr) {
                        $modalProgressBar.addClass('bg-danger').removeClass('progress-bar-animated').css('width', '100%').text('Lỗi!');
                        $modalLabel.text('Lỗi Máy chủ');
                        $modalMessage.html('<strong>Lỗi:</strong> Đã xảy ra lỗi nghiêm trọng khi cố gắng tải lịch sử. Vui lòng thử lại.');
                        console.error('Error fetching Voip24h history:', xhr);
                        $modalFooter.show(); // Hiển thị nút Đóng
                    },
                    complete: function() {
                        $button.prop('disabled', false); // Kích hoạt lại nút trên trang khi AJAX hoàn tất
                    }
                });
            }

            // Fetch Voip24h Call History - Button Click
            $('#btn-fetch-voip-history').on('click', function() {
                var orderId = $(this).data('order-id');
                fetchAndDisplayVoipHistory(orderId);
            });

            // // Automatically fetch history on page load if no calls are currently displayed
            // var initialOrderId = {{ $order->id }};
            // // Check if the "empty" row exists
            // if ($('#call-history-table-body tr td[colspan="5"]').length > 0 &&
            //     $('#call-history-table-body tr td[colspan="5"]').text().includes('Chưa có cuộc gọi nào.')) {
            //     console.log('No call history found, attempting to fetch automatically.');
            //     fetchAndDisplayVoipHistory(initialOrderId);
            // }
            // === END REFACTOR FOR VOIP HISTORY FETCH ===

            // Xử lý khi modal được đóng (bằng nút "Đóng" hoặc cách khác)
            $('#fetchVoipHistoryProgressModal').on('hidden.bs.modal', function () {
                // Chỉ reload nếu lần trước đó đã thành công (progress bar màu xanh)
                if ($('#fetch-voip-progress-bar').hasClass('bg-success')) {
                    // Instead of location.reload(), we will now refresh the table content if the main goal is to see new calls.
                    // However, the existing VoIP history fetch is a full sync, so a reload might still be desired by the user
                    // if they manually triggered it. For automatic updates via postMessage, we won't reload the whole page.
                    // For now, let's keep the reload if the manual button was used and succeeded.
                    location.reload();
                }
                // Reset lại nút "Tải lịch sử Voip24h" trên trang chính nếu cần
                $('#btn-fetch-voip-history').prop('disabled', false);
            });

            function startInPageCallStatusTimer() {
                stopInPageCallStatusTimer(); // Clear existing timer
                callPageStatusStartTime = Date.now();
                const timerDisplay = $('#in-page-call-status-timer');
                timerDisplay.text('00:00');

                callPageStatusTimerInterval = setInterval(() => {
                    if (!callPageStatusStartTime) return;
                    const elapsed = Math.floor((Date.now() - callPageStatusStartTime) / 1000);
                    const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
                    const seconds = String(elapsed % 60).padStart(2, '0');
                    timerDisplay.text(`${minutes}:${seconds}`);
                }, 1000);
            }

            function stopInPageCallStatusTimer() {
                if (callPageStatusTimerInterval) {
                    clearInterval(callPageStatusTimerInterval);
                    callPageStatusTimerInterval = null;
                }
                callPageStatusStartTime = null;
                // $('#in-page-call-status-timer').text(''); // Optionally clear timer text or leave last value
            }

            // Listen for messages from the call popup window
            window.addEventListener('message', function(event) {
                // Basic security: Check the origin of the message
                const expectedOrigin = '{{ rtrim(url(''), '/') }}';
                if (event.origin !== expectedOrigin) {
                    console.warn('Message received from unexpected origin:', event.origin, 'Expected:', expectedOrigin);
                    return;
                }

                const eventData = event.data;
                if (!eventData || !eventData.event) {
                    console.log('Received message without event data:', eventData);
                    return;
                }

                const eventOrderId = eventData.orderId ? parseInt(eventData.orderId, 10) : null;

                // For call control events, ensure they match the current order
                if (['callAccepted', 'callMuteToggled', 'callHoldToggled', 'voipCallEnded'].includes(eventData.event)) {
                    if (eventOrderId !== currentShowPageOrderId) {
                        console.log(`Call event ${eventData.event} for different order (${eventOrderId}), ignoring on current order page (${currentShowPageOrderId}).`);
                        return;
                    }
                }

                switch(eventData.event) {
                    case 'voipCallEnded':
                        console.log('Call ended for current order (message from popup), refreshing call history table.');
                        $('#in-page-call-controls').hide();
                        $('.btn-call').prop('disabled', false); // Re-enable call button
                        $('#btn-mute-call, #btn-hold-call, #btn-hangup-call').prop('disabled', true);
                        stopInPageCallStatusTimer();
                        $('#in-page-call-status-timer').text('Đã kết thúc');

                        if (callPopupWindow && !callPopupWindow.closed) {
                            // Popup should ideally close itself, or parent can force close if necessary
                            // callPopupWindow.close();
                        }
                        callPopupWindow = null;
                        $('#in-page-callee-display').text(''); // Clear callee display

                        // Refresh call history (existing logic)
                        const $callHistoryTableBody = $('#call-history-table-body');
                        const originalContent = $callHistoryTableBody.html();
                        $callHistoryTableBody.html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang cập nhật lịch sử...</td></tr>');
                        $.ajax({
                            url: `{{ route('orders.callHistoryRows', ['order' => $order->id]) }}`, // Ensure order ID is correct here
                            type: 'GET',
                            success: function(responseHtml) {
                                $callHistoryTableBody.html(responseHtml);
                                console.log('Call history table refreshed successfully after voipCallEnded.');
                            },
                            error: function(xhr) {
                                console.error('Error refreshing call history table after voipCallEnded:', xhr);
                                $callHistoryTableBody.html(originalContent);
                                Swal.fire('Lỗi', 'Không thể cập nhật lịch sử cuộc gọi tự động.', 'error');
                            }
                        });
                        break;

                    case 'callStatusUpdate':
                        // console.log('Call status update from popup:', eventData);
                        if (eventOrderId !== currentShowPageOrderId) return;

                        const { statusText, calleeDisplay, callState } = eventData;

                        if ($('#in-page-call-controls').is(':visible')) { // Only update if controls are meant to be shown
                            $('#in-page-callee-display').text(calleeDisplay || '-');

                            if (callState.isActive && (statusText === 'Đã kết nối' || statusText.includes(':'))) {
                                // If call is active and status is "Đã kết nối" or already a timer string, ensure our timer runs
                                if (!callPageStatusTimerInterval) {
                                    startInPageCallStatusTimer();
                                }
                                // The timer itself will update the text, so we don't set statusText here if timer is active
                            } else {
                                // For other statuses (Ringing, Registering, etc.) or if call not active
                                stopInPageCallStatusTimer();
                                $('#in-page-call-status-timer').text(statusText || '-');
                            }
                            updateInPageControlsUI(callState);
                        } else if (callState.isActive) {
                            // If controls are not visible, but we get a status update that call is active (e.g. call accepted but main page missed initial 'callAccepted' message)
                            // This could happen if popup was opened and call started before show.blade.php's JS was fully ready.
                            // We should show controls and start timer here.
                            console.log('callStatusUpdate received for active call, but controls were hidden. Showing controls.');
                            $('#in-page-call-controls').show();
                            $('.btn-call').prop('disabled', true);
                            $('#in-page-callee-display').text(calleeDisplay || '-');
                            startInPageCallStatusTimer(); // Start timer as call is active
                            updateInPageControlsUI(callState);
                        }
                        break;

                    case 'callAccepted':
                        console.log('Call accepted for current order (message from popup). State:', eventData.callState);
                        $('#in-page-call-controls').show();
                        $('.btn-call').prop('disabled', true);
                        updateInPageControlsUI(eventData.callState);
                        startInPageCallStatusTimer();
                        break;

                    case 'callMuteToggled':
                    case 'callHoldToggled':
                        console.log(`Call state ${eventData.event} for current order. State:`, eventData.callState);
                        if ($('#in-page-call-controls').is(':visible')) {
                            updateInPageControlsUI(eventData.callState);
                        }
                        break;
                    default:
                         // console.log('Received unhandled message from popup:', eventData);
                        break;
                }
            }, false);

            function updateInPageControlsUI(callState) {
                if (!callState) {
                    console.warn('updateInPageControlsUI called without callState');
                    // Default to disabling if state is unknown
                    $('#btn-mute-call, #btn-hold-call, #btn-hangup-call').prop('disabled', true);
                    // $('#in-page-call-status-timer').text(''); // Don't clear status here, handled by callStatusUpdate or voipCallEnded
                    // stopInPageCallStatusTimer(); // Don't stop timer here, handled by callStatusUpdate or voipCallEnded
                    $('#in-page-callee-display').text('');
                    return;
                }

                const $muteBtn = $('#btn-mute-call');
                const $holdBtn = $('#btn-hold-call');

                if (callState.isMuted) {
                    $muteBtn.html('<i class="fas fa-microphone-slash"></i> Bỏ tắt tiếng').addClass('btn-warning').removeClass('btn-info');
                } else {
                    $muteBtn.html('<i class="fas fa-microphone"></i> Tắt tiếng').addClass('btn-info').removeClass('btn-warning');
                }

                if (callState.isOnHold) {
                    $holdBtn.html('<i class="fas fa-play"></i> Tiếp tục').addClass('btn-warning').removeClass('btn-info');
                } else {
                    $holdBtn.html('<i class="fas fa-pause"></i> Giữ máy').addClass('btn-info').removeClass('btn-warning');
                }

                if (callState.isActive) {
                    $('#btn-mute-call, #btn-hold-call, #btn-hangup-call').prop('disabled', false);
                    if (!callPageStatusTimerInterval && !$('#in-page-call-status-timer').text().includes('Đã kết thúc')) {
                        // If timer isn't running but call is active (e.g. page reloaded or popup opened before main page fully ready)
                        // We might need a way to get current call duration from popup or just show 'Đang hoạt động'
                        // For now, if callAccepted was missed, timer won't start here unless explicitly told.
                        // The startInPageCallStatusTimer is called on 'callAccepted'
                    }
                } else {
                    // This case is mainly handled by 'voipCallEnded' which hides controls
                    $('#btn-mute-call, #btn-hold-call, #btn-hangup-call').prop('disabled', true);
                    $('#in-page-call-controls').hide();
                    $('.btn-call').prop('disabled', false);
                    stopInPageCallStatusTimer();
                    $('#in-page-call-status-timer').text('Đã kết thúc');
                    $('#in-page-callee-display').text(''); // Clear callee display
                }
            }

            // Replace the existing .btn-call click handler to manage popup window and pass origin/order_id
            $(document).off('click', '.btn-call').on('click', '.btn-call', function(e) {
                e.preventDefault();
                const $button = $(this);
                const phoneNumberToCall = $button.data('phone');
                const orderId = $button.data('order-id'); // This is currentShowPageOrderId

                if (!phoneNumberToCall) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không tìm thấy số điện thoại khách hàng để thực hiện cuộc gọi.',
                    });
                    return;
                }

                // Construct URL with openerOrigin and order_id
                let callWindowUrl = `{{ route('calls.window') }}?phone_number=${encodeURIComponent(phoneNumberToCall)}&openerOrigin=${encodeURIComponent(window.location.origin)}`;
                if (orderId) {
                    callWindowUrl += `&order_id=${encodeURIComponent(orderId)}`;
                }

                const windowFeatures = 'width=380,height=600,resizable=yes,scrollbars=no,status=no';

                if (callPopupWindow && !callPopupWindow.closed) {
                    // If window is already open, maybe focus it or decide on behavior.
                    // For now, we'll close and reopen to ensure fresh state, though this might interrupt an ongoing call if not handled carefully.
                    // A better approach would be to check if it's for the same number/order and reuse.
                    // However, the popup itself manages call state, so reopening is simpler for now.
                    callPopupWindow.close();
                }

                callPopupWindow = window.open(callWindowUrl, 'CallWindow', windowFeatures);
                $button.prop('disabled', true); // Disable button after clicking
                $('#in-page-call-controls').hide(); // Hide controls until call is accepted
                $('#in-page-call-status-timer').text('Đang gọi...');

                if (callPopupWindow) {
                    // Monitor if the popup is closed by the user
                    const checkPopupClosedInterval = setInterval(() => {
                        if (callPopupWindow && callPopupWindow.closed) {
                            clearInterval(checkPopupClosedInterval);
                            if (!$('#in-page-call-controls').is(':visible') || $('#btn-hangup-call').is(':disabled') === false) {
                                // If controls were not shown (call not accepted) or hangup was not disabled (call ended properly)
                                // This means the user closed the popup before call ended or after it ended normally
                            }
                             // If controls are visible and hangup is enabled, it means call was active and user closed popup
                            if ($('#in-page-call-controls').is(':visible') && !$('#btn-hangup-call').is(':disabled')){
                                 // This means an active call was present and popup was closed, treat as call ended
                                 console.log('Call popup window closed by user during an active call.');
                                 // Manually trigger UI cleanup similar to 'voipCallEnded'
                                 $('#in-page-call-controls').hide();
                                 $('.btn-call').prop('disabled', false);
                                 stopInPageCallStatusTimer();
                                 $('#in-page-call-status-timer').text('Cửa sổ đóng');
                                 // Potentially send a hangup command if possible, though popup is gone.
                                 $('#in-page-callee-display').text(''); // Clear callee display
                            }
                            callPopupWindow = null;
                        }
                    }, 1000);
                } else {
                    Swal.fire('Lỗi', 'Không thể mở cửa sổ cuộc gọi. Vui lòng kiểm tra cài đặt chặn pop-up trình duyệt.', 'error');
                    $button.prop('disabled', false); // Re-enable if window fails to open
                }
            });

            // Handlers for new in-page call control buttons
            $('#btn-mute-call').on('click', function() {
                if (callPopupWindow && !callPopupWindow.closed && callPopupWindow.callControlAPI) {
                    callPopupWindow.callControlAPI.toggleMuteCall();
                } else {
                    console.warn('Call window not available for mute toggle.');
                    // Optionally, re-sync UI if popup seems gone
                    updateInPageControlsUI(null); // This will hide and disable
                }
            });

            $('#btn-hold-call').on('click', function() {
                if (callPopupWindow && !callPopupWindow.closed && callPopupWindow.callControlAPI) {
                    callPopupWindow.callControlAPI.toggleHoldCall();
                } else {
                    console.warn('Call window not available for hold toggle.');
                    updateInPageControlsUI(null);
                }
            });

            $('#btn-hangup-call').on('click', function() {
                if (callPopupWindow && !callPopupWindow.closed && callPopupWindow.callControlAPI) {
                    callPopupWindow.callControlAPI.hangUpCall();
                    // UI update will be handled by 'voipCallEnded' message from popup
                } else {
                    console.warn('Call window not available for hangup.');
                    updateInPageControlsUI(null);
                }
            });

            $('.view-changes').click(function() {
                var oldData = $(this).data('old');
                var newData = $(this).data('new');

                $('#oldData').text(JSON.stringify(oldData, null, 2));
                $('#newData').text(JSON.stringify(newData, null, 2));

                $('#changesModal').modal('show');
            });

            // AJAX for pushing individual order to Pancake
            $(document).on('click', '.btn-push-pancake', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                var url = $(this).data('url');
                var button = $(this);

                Swal.fire({
                    title: 'Xác nhận đẩy đơn?',
                    text: "Bạn có chắc chắn muốn đẩy đơn hàng #" + orderId + " lên Pancake không?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý, đẩy ngay!',
                    cancelButtonText: 'Hủy bỏ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang đẩy...');

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Thành công!',
                                        text: response.message,
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload page to show updated status
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Lỗi!',
                                        text: response.message || 'Có lỗi xảy ra khi đẩy đơn hàng.',
                                        icon: 'error'
                                    });
                                    button.prop('disabled', false).html('<i class="fas fa-rocket fa-fw"></i> Đẩy lên Pancake');
                                }
                            },
                            error: function(xhr) {
                                var errorMessage = 'Có lỗi xảy ra khi đẩy đơn hàng.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    title: 'Lỗi!',
                                    text: errorMessage,
                                    icon: 'error'
                                });
                                button.prop('disabled', false).html('<i class="fas fa-rocket fa-fw"></i> Đẩy lên Pancake');
                            }
                        });
                    }
                });
            });

        });
    </script>
@endpush
