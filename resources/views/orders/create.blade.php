@extends('adminlte::page')

@section('title', 'Tạo Đơn hàng mới')

@section('content_header')
    <h1>Tạo Đơn hàng mới</h1>
@stop

@section('content')
{{-- Add this block to display all validation errors at the top --}}
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
{{-- End Add --}}

<div class="card">
    <div class="card-body">
        <form action="{{ route('orders.store') }}" method="POST" id="orderCreateForm">
            @csrf
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
                                <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror" required value="{{ old('customer_name') }}">
                                @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" id="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" required value="{{ old('customer_phone') }}">
                                @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="customer_email">Email khách hàng</label>
                                <input type="email" name="customer_email" id="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}">
                                @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr>
                            <h5>Địa chỉ giao hàng</h5>
                            <div class="form-group">
                                <label for="province_code">Tỉnh/Thành phố</label>
                                <select name="province_code" id="province_code" class="form-control select2 @error('province_code') is-invalid @enderror" data-placeholder="-- Chọn Tỉnh/Thành phố --">
                                    <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    @foreach($provinces as $code => $name)
                                        <option value="{{ $code }}" {{ old('province_code') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('province_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="district_code">Quận/Huyện</label>
                                <select name="district_code" id="district_code" class="form-control select2 @error('district_code') is-invalid @enderror" data-placeholder="-- Chọn Quận/Huyện --" disabled>
                                    <option value="">-- Chọn Quận/Huyện --</option>
                                    {{-- Districts will be loaded by JS --}}
                                </select>
                                @error('district_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="ward_code">Phường/Xã</label>
                                <select name="ward_code" id="ward_code" class="form-control select2 @error('ward_code') is-invalid @enderror" data-placeholder="-- Chọn Phường/Xã --" disabled>
                                    <option value="">-- Chọn Phường/Xã --</option>
                                    {{-- Wards will be loaded by JS --}}
                                </select>
                                @error('ward_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="street_address">Địa chỉ cụ thể (Số nhà, tên đường, thôn/xóm)</label>
                                <input type="text" name="street_address" id="street_address" class="form-control @error('street_address') is-invalid @enderror" value="{{ old('street_address') }}" placeholder="Ví dụ: Số 10, ngõ 50, đường ABC">
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
                                        <option value="{{ $id }}" {{ old('pancake_shop_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pancake_shop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="pancake_page_id">Pancake Page</label>
                                <select name="pancake_page_id" id="pancake_page_id" class="form-control select2 @error('pancake_page_id') is-invalid @enderror" {{ old('pancake_shop_id') ? '' : 'disabled' }}>
                                    <option value="">-- Chọn Pancake Page --</option>
                                    {{-- Options will be populated by JavaScript --}}
                                    @if(old('pancake_shop_id') && isset($pancakePages) && $pancakePages->isNotEmpty())
                                        {{-- This block helps repopulate if validation fails and pages were loaded based on old shop_id --}}
                                        @foreach($pancakePages as $id => $name)
                                            <option value="{{ $id }}" {{ old('pancake_page_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('pancake_page_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="order_code">Mã đơn hàng <span class="text-danger">*</span></label>
                                        <input type="text" name="order_code" id="order_code" class="form-control @error('order_code') is-invalid @enderror" required value="{{ old('order_code', 'DH'.strtoupper(uniqid())) }}">
                                        @error('order_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="user_id">Nhân viên Sale <span class="text-danger">*</span></label>
                                        <select name="user_id" id="user_id" class="form-control select2 @error('user_id') is-invalid @enderror" required>
                                            <option value="">-- Chọn nhân viên --</option>
                                            @foreach($users as $id => $name)
                                                <option value="{{ $id }}" {{ old('user_id', Auth::id()) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="status">Trạng thái đơn hàng <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control select2 @error('status') is-invalid @enderror" required>
                                    <option value="">-- Chọn trạng thái --</option>
                                    @foreach($statuses as $statusCode => $statusName)
                                        <option value="{{ $statusCode }}" {{ old('status', \App\Models\Order::STATUS_MOI) == $statusCode ? 'selected' : '' }}>{{ $statusName }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <hr>
                            <h5>Sản phẩm <span class="text-danger">*</span></h5>
                            <div id="items_container">
                                <div class="row item-row mb-2 align-items-center">
                                    <div class="col-md-4">
                                        <input type="text" name="items[0][code]" class="form-control @error('items.0.code') is-invalid @enderror" placeholder="Mã SP (Pancake Variation ID)" value="{{ old('items.0.code') }}">
                                        @error('items.0.code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" step="any" name="items[0][price]" class="form-control @error('items.0.price') is-invalid @enderror" placeholder="Đơn giá" value="{{ old('items.0.price', 0) }}" min="0">
                                        @error('items.0.price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="items[0][quantity]" class="form-control @error('items.0.quantity') is-invalid @enderror" placeholder="Số lượng" value="{{ old('items.0.quantity', 1) }}" min="1">
                                        @error('items.0.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2"></div>
                                </div>
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
                                        <input type="number" name="shipping_fee" id="shipping_fee" class="form-control @error('shipping_fee') is-invalid @enderror" value="{{ old('shipping_fee', 0) }}" min="0">
                                        @error('shipping_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transfer_money">Số tiền chuyển khoản (Pancake)</label>
                                        <input type="text" name="transfer_money" id="transfer_money" class="form-control @error('transfer_money') is-invalid @enderror" value="{{ old('transfer_money') }}">
                                        @error('transfer_money') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Phương thức thanh toán</label>
                                <select name="payment_method" id="payment_method" class="form-control select2 @error('payment_method') is-invalid @enderror">
                                    <option value="">-- Chọn phương thức --</option>
                                    <option value="cod" {{ old('payment_method') == 'cod' ? 'selected' : '' }}>Thanh toán khi nhận hàng (COD)</option>
                                    <option value="banking" {{ old('payment_method') == 'banking' ? 'selected' : '' }}>Chuyển khoản ngân hàng</option>
                                    <option value="momo" {{ old('payment_method') == 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                                    <option value="zalopay" {{ old('payment_method') == 'zalopay' ? 'selected' : '' }}>ZaloPay</option>
                                    <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
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
                                                <option value="{{ $id }}" {{ old('warehouse_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
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
                                                    <option value="{{ $id }}" {{ old('shipping_provider_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
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
                                <label for="internal_status">Trạng thái nội bộ</label>
                                <input type="text" name="internal_status" id="internal_status" class="form-control @error('internal_status') is-invalid @enderror" value="{{ old('internal_status') }}">
                                @error('internal_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="notes">Ghi chú khách hàng</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="additional_notes">Ghi chú nội bộ (cho sale)</label>
                                <textarea name="additional_notes" id="additional_notes" class="form-control @error('additional_notes') is-invalid @enderror" rows="2">{{ old('additional_notes') }}</textarea>
                                @error('additional_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Tạo Đơn hàng</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>
@stop

@push('css')
    {{-- Add Select2 CSS if not already available globally --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* --- Start of new Select2 styles --- */
        /* Ensure Bootstrap 4 theme's select has the correct height and alignment. */
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important; /* Match form-control height */
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px) !important; /* Vertical centering */
            padding-left: 0.75rem !important; /* Default bs4 padding */
            padding-right: 1.75rem !important; /* Space for arrow */
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 0px) !important; /* Full height arrow */
        }

        /* Custom styling to make Select2 elements 'pop' more */
        .select2-container--bootstrap4 .select2-selection {
            border: 1px solid #ced4da; /* Standard Bootstrap border */
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Subtle lift effect */
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            border-radius: 0.25rem; /* Added for rounded corners */
        }

        /* Enhanced focus style for Select2, mimicking Bootstrap's focus */
        .select2-container--bootstrap4.select2-container--focus .select2-selection {
            border-color: #80bdff; /* Bootstrap focus border color */
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); /* Bootstrap focus shadow */
        }

        /* Styling for the dropdown panel to make it stand out */
        .select2-dropdown {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); /* More pronounced shadow for dropdown */
            margin-top: 2px; /* Slight separation from the select box */
        }

        /* Optional: Style for options in the dropdown for better clarity */
        .select2-results__option {
            padding: 0.4rem 0.75rem; /* Adjust padding for options */
            font-size: 0.9rem; /* Slightly smaller font for options if needed */
        }

        .select2-container--bootstrap4 .select2-results__option--highlighted {
            background-color: #007bff; /* Bootstrap primary color */
            color: white;
        }

        /* Ensure scrollbar for long Select2 dropdowns */
        .select2-results__options {
            max-height: 250px; /* Adjust as needed */
            overflow-y: auto;
        }
        /* --- End of new Select2 styles --- */

        /* Existing rule, keep it */
        .product-item .form-control {
            margin-bottom: 0; /* Remove default margin for inputs inside product item for better alignment */
        }

        /* Adjust card margin for better spacing in two-column layout */
        .col-md-6 > .card { margin-bottom: 1rem; }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Address dynamics (Province -> District -> Ward)
        function fetchDistricts(provinceCode, selectedDistrictCode = null) {
            $('#district_code').empty().append('<option value="">-- Đang tải Quận/Huyện --</option>').prop('disabled', true);
            $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);

            if (provinceCode) {
                $.ajax({
                    url: '{{ route("ajax.districts") }}', // Ensure this route is defined
                    type: 'GET',
                    data: { province_code: provinceCode },
                    success: function(data) {
                        $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', false);
                        $.each(data, function(code, name) {
                            const option = new Option(name, code);
                            $('#district_code').append(option);
                        });
                        if (selectedDistrictCode) {
                            $('#district_code').val(selectedDistrictCode);
                        }
                        $('#district_code').trigger('change'); // Trigger change to potentially load wards if a district is pre-selected
                    },
                    error: function() {
                        $('#district_code').empty().append('<option value="">-- Lỗi tải Quận/Huyện --</option>').prop('disabled', false);
                    }
                });
            } else {
                 $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
            }
        }

        function fetchWards(districtCode, selectedWardCode = null) {
            $('#ward_code').empty().append('<option value="">-- Đang tải Phường/Xã --</option>').prop('disabled', true);
            if (districtCode) {
                $.ajax({
                    url: '{{ route("ajax.wards") }}', // Ensure this route is defined
                    type: 'GET',
                    data: { district_code: districtCode },
                    success: function(data) {
                        $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', false);
                        $.each(data, function(code, name) {
                            const option = new Option(name, code);
                            $('#ward_code').append(option);
                        });
                        if (selectedWardCode) {
                            $('#ward_code').val(selectedWardCode);
                        }
                        $('#ward_code').trigger('change');
                    },
                     error: function() {
                        $('#ward_code').empty().append('<option value="">-- Lỗi tải Phường/Xã --</option>').prop('disabled', false);
                    }
                });
            } else {
                 $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            }
        }

        $('#province_code').on('change', function() {
            fetchDistricts($(this).val());
        });

        $('#district_code').on('change', function() {
            fetchWards($(this).val());
        });

        // Trigger initial load if old province is present (e.g., after validation error)
        const oldProvince = '{{ old("province_code") }}';
        const oldDistrict = '{{ old("district_code") }}';
        const oldWard = '{{ old("ward_code") }}';

        if(oldProvince) {
            fetchDistricts(oldProvince, oldDistrict);
            if(oldDistrict){
                 // Small delay to ensure districts are populated before trying to fetch wards
                setTimeout(function() { fetchWards(oldDistrict, oldWard); }, 500);
            }
        }

        // Dynamic Item Rows
        let rowCount = $('.item-row').length; // Start based on existing rows (should be 1)

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
                </div>
            `;
            $('#items_container').append(newRowHtml);
            rowCount++;
            // Ensure the first row also gets a remove button if it's no longer the only row
            if ($('.item-row').length > 1 && $('.item-row:first .remove-row').length === 0) {
                 $('.item-row:first .col-md-2').html('<button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i> Xóa</button>');
            }
        });

        $('#items_container').on('click', '.remove-row', function() {
            $(this).closest('.row.item-row').remove();
             // If only one row remains, remove its delete button
            if ($('.item-row').length === 1) {
                $('.item-row:first .remove-row').remove();
            }
        });

        // Form submission validation for items
        $('#orderCreateForm').submit(function(e) {
            if ($('.item-row').length === 0) {
                e.preventDefault();
                // Consider using a more user-friendly notification like SweetAlert
                alert('Vui lòng thêm ít nhất một sản phẩm.');
                // Add error message display logic if needed
                if (!$('#items_container').next('.text-danger').length) {
                     $('#items_container').after('<div class="text-danger mb-2">Vui lòng thêm ít nhất một sản phẩm.</div>');
                }
                return false;
            }
             $('#items_container').next('.text-danger').remove(); // Clear error message if items exist
            return true;
        });

        // --- Pancake Shop/Page Dynamics ---
        const pancakeShopSelect = $('#pancake_shop_id');
        const pancakePageSelect = $('#pancake_page_id');
        const pancakePagesForShopUrl = '{{ route("ajax.pancakePagesForShop") }}';

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

        const initialPancakeShopId = pancakeShopSelect.val(); // This will be old('pancake_shop_id') if present
        if (initialPancakeShopId) {
            const initiallySelectedPageId = '{{ old("pancake_page_id", "") }}';
            loadPancakePages(initialPancakeShopId, initiallySelectedPageId);
        } else {
            pancakePageSelect.prop('disabled', true); // Keep disabled if no shop initially
        }
        // --- End of Pancake Shop/Page Dynamics ---
    });
    </script>
@endpush
