@extends('adminlte::page')

@section('title', 'Sửa đơn hàng: ' . $order->order_code)

@section('content_header')
    <h1>Sửa đơn hàng: <small>{{ $order->order_code }}</small></h1>
@stop

@section('content')
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Có lỗi xảy ra!</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('orders.update', $order->id) }}" method="POST" id="orderEditForm">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Left Column --}}
                <div class="col-md-6">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin khách hàng & Địa chỉ</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="customer_name">Tên khách hàng <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror" required value="{{ old('customer_name', $order->customer_name) }}">
                                @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" id="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" required value="{{ old('customer_phone', $order->customer_phone) }}">
                                @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="customer_email">Email khách hàng</label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email', $order->customer_email) }}">
                                @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <hr>
                            <h5>Địa chỉ giao hàng</h5>
                            <div class="form-group">
                                <label for="province_code">Tỉnh/Thành phố</label>
                                <select name="province_code" id="province_code" class="form-control select2 @error('province_code') is-invalid @enderror" data-placeholder="-- Chọn Tỉnh/Thành phố --">
                                    <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    @foreach($provinces as $code => $name)
                                        <option value="{{ $code }}" {{ old('province_code', $order->province_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('province_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="district_code">Quận/Huyện</label>
                                <select name="district_code" id="district_code" class="form-control select2 @error('district_code') is-invalid @enderror" data-placeholder="-- Chọn Quận/Huyện --">
                                    <option value="">-- Chọn Quận/Huyện --</option>
                                    @if(old('district_code', $order->district_code) && $districts->isNotEmpty())
                                        @foreach($districts as $code => $name)
                                            <option value="{{ $code }}" {{ old('district_code', $order->district_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('district_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="ward_code">Phường/Xã</label>
                                <select name="ward_code" id="ward_code" class="form-control select2 @error('ward_code') is-invalid @enderror" data-placeholder="-- Chọn Phường/Xã --">
                                    <option value="">-- Chọn Phường/Xã --</option>
                                     @if(old('ward_code', $order->ward_code) && $wards->isNotEmpty())
                                        @foreach($wards as $code => $name)
                                            <option value="{{ $code }}" {{ old('ward_code', $order->ward_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('ward_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="street_address">Địa chỉ cụ thể</label>
                                <input type="text" name="street_address" id="street_address" class="form-control @error('street_address') is-invalid @enderror" value="{{ old('street_address', $order->street_address) }}">
                                @error('street_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                     <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin Pancake</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="pancake_shop_id">Pancake Shop</label>
                                <select name="pancake_shop_id" id="pancake_shop_id" class="form-control select2 @error('pancake_shop_id') is-invalid @enderror">
                                    <option value="">-- Chọn Pancake Shop --</option>
                                    @foreach($pancakeShops as $id => $name)
                                        <option value="{{ $id }}" {{ old('pancake_shop_id', $order->pancake_shop_id) == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pancake_shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="pancake_page_id">Pancake Page</label>
                                <select name="pancake_page_id" id="pancake_page_id" class="form-control select2 @error('pancake_page_id') is-invalid @enderror">
                                    <option value="">-- Chọn Pancake Page --</option>
                                    @if(old('pancake_page_id', $order->pancake_page_id) && $pancakePages->isNotEmpty())
                                        @foreach($pancakePages as $id => $name)
                                            <option value="{{ $id }}" {{ old('pancake_page_id', $order->pancake_page_id) == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('pancake_page_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="pancake_push_status_display">Trạng thái đẩy Pancake</label>
                                <input type="text" id="pancake_push_status_display" class="form-control" value="{{ $order->pancake_push_status ? ucfirst(str_replace('_', ' ', $order->pancake_push_status)) : 'Chưa đẩy' }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin đơn hàng & Sản phẩm</h3>
                        </div>
                        <div class="card-body">
                             <div class="form-group">
                                <label for="order_code_display">Mã đơn hàng</label>
                                <input type="text" id="order_code_display" class="form-control" value="{{ $order->order_code }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="user_id">Nhân viên Sale <span class="text-danger">*</span></label>
                                <select name="user_id" id="user_id" class="form-control select2 @error('user_id') is-invalid @enderror" required>
                                    <option value="">-- Chọn nhân viên --</option>
                                    @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('user_id', $order->user_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="status">Trạng thái đơn hàng <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control select2 @error('status') is-invalid @enderror" required>
                                    <option value="">-- Chọn trạng thái --</option>
                                    @foreach($statuses as $statusCode => $statusName)
                                        <option value="{{ $statusCode }}" {{ old('status', $order->status) == $statusCode ? 'selected' : '' }}>{{ $statusName }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <hr>
                            <h5>Sản phẩm <span class="text-danger">*</span></h5>
                            <div id="items_container">
                                @php
                                    $currentItems = old('items', $order->items->map(function($item) {
                                        // Use pancake_variation_id if available, otherwise fallback to code
                                        return ['code' => $item->pancake_variation_id ?? $item->code, 'quantity' => $item->quantity, 'price' => $item->price ?? 0];
                                    })->toArray());
                                @endphp
                                @foreach($currentItems as $index => $item)
                                <div class="row item-row mb-2 align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" name="items[{{ $index }}][code]" class="form-control @error("items.{$index}.code") is-invalid @enderror"
                                               placeholder="Mã SP (Pancake Variation ID)" value="{{ $item['code'] ?? '' }}">
                                        @error("items.{$index}.code") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" step="any" name="items[{{ $index }}][price]" class="form-control @error("items.{$index}.price") is-invalid @enderror"
                                               placeholder="Đơn giá" value="{{ $item['price'] ?? 0 }}" min="0">
                                        @error("items.{$index}.price") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control @error("items.{$index}.quantity") is-invalid @enderror"
                                               placeholder="Số lượng" value="{{ $item['quantity'] ?? 1 }}" min="1">
                                        @error("items.{$index}.quantity") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    @if($index == 0 && count($currentItems) == 1)
                                        <div class="col-md-2"></div> {{-- No remove button for the first row if it's the only one --}}
                                    @else
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i> Xóa</button>
                                        </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <button type="button" id="add_item_row" class="btn btn-success btn-sm mb-3">
                                <i class="fas fa-plus"></i> Thêm sản phẩm
                            </button>
                             @error('items') <div class="text-danger mb-2">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Thanh toán & Vận chuyển</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_fee">Phí vận chuyển</label>
                                        <input type="number" name="shipping_fee" id="shipping_fee" class="form-control @error('shipping_fee') is-invalid @enderror" value="{{ old('shipping_fee', $order->shipping_fee ?? 0) }}" min="0">
                                        @error('shipping_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transfer_money">Số tiền chuyển khoản (Pancake)</label>
                                        <input type="text" name="transfer_money" id="transfer_money" class="form-control @error('transfer_money') is-invalid @enderror" value="{{ old('transfer_money', $order->transfer_money) }}">
                                        @error('transfer_money') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                             <div class="form-group">
                                <label for="payment_method">Phương thức thanh toán</label>
                                <select name="payment_method" id="payment_method" class="form-control select2 @error('payment_method') is-invalid @enderror">
                                    <option value="">-- Chọn phương thức --</option>
                                    <option value="cod" {{ old('payment_method', $order->payment_method) == 'cod' ? 'selected' : '' }}>Thanh toán khi nhận hàng (COD)</option>
                                    <option value="banking" {{ old('payment_method', $order->payment_method) == 'banking' ? 'selected' : '' }}>Chuyển khoản ngân hàng</option>
                                    <option value="momo" {{ old('payment_method', $order->payment_method) == 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                                    <option value="zalopay" {{ old('payment_method', $order->payment_method) == 'zalopay' ? 'selected' : '' }}>ZaloPay</option>
                                    <option value="other" {{ old('payment_method', $order->payment_method) == 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                                @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="warehouse_id">Kho hàng <span class="text-danger">*</span></label>
                                        <select name="warehouse_id" id="warehouse_id" class="form-control select2 @error('warehouse_id') is-invalid @enderror" required>
                                            <option value="">-- Chọn kho hàng --</option>
                                            @foreach($warehouses as $id => $name)
                                                <option value="{{ $id }}" {{ old('warehouse_id', $order->warehouse_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shipping_provider_id">Đơn vị vận chuyển</label>
                                        <select name="shipping_provider_id" id="shipping_provider_id" class="form-control select2 @error('shipping_provider_id') is-invalid @enderror">
                                            <option value="">-- Chọn đơn vị vận chuyển --</option>
                                            @if(isset($shippingProviders) && $shippingProviders->count() > 0)
                                                @foreach($shippingProviders as $id => $name)
                                                    <option value="{{ $id }}" {{ old('shipping_provider_id', $order->shipping_provider_id) == $id ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('shipping_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Ghi chú & Trạng thái nội bộ</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="internal_status">Trạng thái nội bộ CRM</label>
                                <input type="text" name="internal_status" id="internal_status" class="form-control @error('internal_status') is-invalid @enderror" value="{{ old('internal_status', $order->internal_status) }}">
                                @error('internal_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                             <div class="form-group">
                                <label for="notes">Ghi chú khách hàng</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $order->notes) }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="additional_notes">Ghi chú nội bộ (cho sale)</label>
                                <textarea name="additional_notes" id="additional_notes" class="form-control @error('additional_notes') is-invalid @enderror" rows="2">{{ old('additional_notes', $order->additional_notes) }}</textarea>
                                @error('additional_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Cập nhật Đơn hàng</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>
@stop

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap4 .select2-selection--single { height: calc(2.25rem + 2px) !important; }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { line-height: calc(2.25rem + 2px) !important; padding-left: 0.75rem !important; padding-right: 1.75rem !important; }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: calc(2.25rem + 0px) !important; }
        .select2-container--bootstrap4 .select2-selection { border: 1px solid #ced4da; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out; border-radius: 0.25rem; }
        .select2-container--bootstrap4.select2-container--focus .select2-selection { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        .select2-dropdown { border: 1px solid #ced4da; border-radius: 0.25rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); margin-top: 2px; }
        .select2-results__option { padding: 0.4rem 0.75rem; font-size: 0.9rem; }
        .select2-container--bootstrap4 .select2-results__option--highlighted { background-color: #007bff; color: white; }
        .select2-results__options { max-height: 250px; overflow-y: auto; }
        .product-item .form-control { margin-bottom: 0; }
        .col-md-6 > .card { margin-bottom: 1rem; }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

        const initialProvinceCode = '{{ old("province_code", $order->province_code) }}';
        const initialDistrictCode = '{{ old("district_code", $order->district_code) }}';
        const initialWardCode = '{{ old("ward_code", $order->ward_code) }}';

        function fetchDistricts(provinceCode, selectedDistrictCode = null, callback = null) {
            $('#district_code').empty().append('<option value="">-- Đang tải Quận/Huyện --</option>').prop('disabled', true);
            $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            if (provinceCode) {
                $.ajax({
                    url: '{{ route("ajax.districts") }}', type: 'GET', data: { province_code: provinceCode },
                    success: function(data) {
                        $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', false);
                        $.each(data, function(code, name) { $('#district_code').append(new Option(name, code)); });
                        if (selectedDistrictCode) {
                            $('#district_code').val(selectedDistrictCode);
                        }
                        if (callback) callback();
                        else $('#district_code').trigger('change');
                    },
                    error: function() {
                        $('#district_code').empty().append('<option value="">-- Lỗi tải Quận/Huyện --</option>').prop('disabled', false);
                        if (callback) callback(); else $('#district_code').trigger('change');
                    }
                });
            } else {
                $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
                $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                if (callback) callback();
            }
        }

        function fetchWards(districtCode, selectedWardCode = null) {
            $('#ward_code').empty().append('<option value="">-- Đang tải Phường/Xã --</option>').prop('disabled', true);
            if (districtCode) {
                $.ajax({
                    url: '{{ route("ajax.wards") }}', type: 'GET', data: { district_code: districtCode },
                    success: function(data) {
                        $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', false);
                        let wardFound = false;
                        $.each(data, function(code, name) {
                            var option = new Option(name, code);
                            if ((selectedWardCode && code == selectedWardCode) || (!selectedWardCode && initialWardCode && code == initialWardCode && districtCode == initialDistrictCode) ) {
                                option.selected = true;
                                wardFound = true;
                            }
                            $('#ward_code').append(option);
                        });
                        $('#ward_code').trigger('change');
                    },
                    error: function() {
                        $('#ward_code').empty().append('<option value="">-- Lỗi tải Phường/Xã --</option>').prop('disabled', false);
                        $('#ward_code').trigger('change');
                    }
                });
            } else {
                $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            }
        }

        $('#province_code').on('change', function() {
            fetchDistricts($(this).val(), null, function() {
                $('#district_code').trigger('change');
            });
        });

        $('#district_code').on('change', function() {
            fetchWards($(this).val());
        });

        // Initial load for edit form
        if (initialProvinceCode) {
            fetchDistricts(initialProvinceCode, initialDistrictCode, function() {
                if (initialDistrictCode && $('#district_code').val() == initialDistrictCode) {
                    fetchWards(initialDistrictCode, initialWardCode);
                } else {
                    $('#district_code').trigger('change');
                }
            });
        }

        let rowCount = $('.item-row').length > 0 ? $('.item-row').length : {{ count($currentItems) }}; // Start with existing items
        if(rowCount == 0) { rowCount = 1; } // Ensure at least one row count for new additions if starts empty

        $('#add_item_row').click(function() {
            const newRowHtml = `
                <div class="row item-row mb-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="items[${rowCount}][code]" class="form-control" placeholder="Mã SP (Pancake Variation ID)">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="any" name="items[${rowCount}][price]" class="form-control" placeholder="Đơn giá" value="0" min="0">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="items[${rowCount}][quantity]" class="form-control" placeholder="Số lượng" value="1" min="1">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i> Xóa</button>
                    </div>
                </div>`;
            $('#items_container').append(newRowHtml);
            rowCount++;
        });

        $('#items_container').on('click', '.remove-row', function() {
            $(this).closest('.row').remove();
        });

        // Form submission
        $('#orderEditForm').submit(function(e) {
            // Ensure at least one item exists
            if ($('.item-row').length === 0) {
                e.preventDefault();
                alert('Vui lòng thêm ít nhất một sản phẩm.');
                return false;
            }
            return true;
        });

        // --- Pancake Shop/Page Dynamics ---
        const pancakeShopSelect = $('#pancake_shop_id');
        const pancakePageSelect = $('#pancake_page_id');
        const pancakePagesForShopUrl = '{{ route("ajax.pancakePagesForShop") }}'; // Make sure this route exists

        function loadPancakePages(shopId, selectedPageId = null) {
            if (!shopId) {
                pancakePageSelect.html('<option value="">-- Chọn Pancake Page --</option>').prop('disabled', true).trigger('change');
                return;
            }

            pancakePageSelect.prop('disabled', true).html('<option value="">Đang tải Pages...</option>');

            $.ajax({
                url: pancakePagesForShopUrl,
                type: 'GET',
                data: { pancake_shop_id: shopId },
                success: function(pages) {
                    pancakePageSelect.html('<option value="">-- Chọn Pancake Page --</option>');
                    if (pages && pages.length > 0) {
                        pages.forEach(function(page) {
                            let option = new Option(page.name + ` (ID: ${page.pancake_page_id})`, page.id, false, false);
                            pancakePageSelect.append(option);
                        });
                    }
                    if (selectedPageId) {
                        pancakePageSelect.val(selectedPageId);
                    }
                    pancakePageSelect.prop('disabled', false).trigger('change');
                },
                error: function(xhr) {
                    console.error('Error loading Pancake pages:', xhr);
                    pancakePageSelect.html('<option value="">Lỗi tải Pages</option>').prop('disabled', false).trigger('change');
                }
            });
        }

        pancakeShopSelect.on('change', function() {
            const selectedShopId = $(this).val();
            loadPancakePages(selectedShopId);
        });

        const initialPancakeShopId = pancakeShopSelect.val();
        if (initialPancakeShopId) {
            const initiallySelectedPageId = '{{ old("pancake_page_id", $order->pancake_page_id ?? "") }}';
            loadPancakePages(initialPancakeShopId, initiallySelectedPageId);
        }
        // --- End of Pancake Shop/Page Dynamics ---
    });
    </script>
@endpush
