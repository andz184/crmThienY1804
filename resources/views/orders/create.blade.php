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
                            <h5>Thông tin hóa đơn</h5>
                            <div class="form-group">
                                <label for="bill_full_name">Tên trên hóa đơn (Pancake)</label>
                                <input type="text" name="bill_full_name" id="bill_full_name" class="form-control @error('bill_full_name') is-invalid @enderror" value="{{ old('bill_full_name') }}">
                                @error('bill_full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="bill_phone_number">SĐT trên hóa đơn (Pancake)</label>
                                <input type="text" name="bill_phone_number" id="bill_phone_number" class="form-control @error('bill_phone_number') is-invalid @enderror" value="{{ old('bill_phone_number') }}">
                                @error('bill_phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="bill_email">Email trên hóa đơn</label>
                                <input type="email" name="bill_email" id="bill_email" class="form-control @error('bill_email') is-invalid @enderror" value="{{ old('bill_email') }}">
                                @error('bill_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                            <div class="form-check">
                                <input type="checkbox" name="is_livestream" id="is_livestream" class="form-check-input" value="1" {{ old('is_livestream') ? 'checked' : '' }}>
                                <label for="is_livestream" class="form-check-label">Đơn hàng Livestream</label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="is_live_shopping" id="is_live_shopping" class="form-check-input" value="1" {{ old('is_live_shopping') ? 'checked' : '' }}>
                                <label for="is_live_shopping" class="form-check-label">Đơn hàng Live Shopping</label>
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
                                    <div class="col-md-3">
                                        <label class="small">Mã SP (Pancake Variation ID)</label>
                                        <input type="text" name="items[0][code]" class="form-control @error('items.0.code') is-invalid @enderror" placeholder="Mã SP (Pancake Variation ID)" value="{{ old('items.0.code') }}">
                                        @error('items.0.code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small">Tên sản phẩm</label>
                                        <input type="text" name="items[0][name]" class="form-control @error('items.0.name') is-invalid @enderror" placeholder="Tên sản phẩm" value="{{ old('items.0.name') }}">
                                        @error('items.0.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small">Đơn giá</label>
                                        <input type="number" step="any" name="items[0][price]" class="form-control @error('items.0.price') is-invalid @enderror" placeholder="Đơn giá" value="{{ old('items.0.price', 0) }}" min="0">
                                        @error('items.0.price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small">Số lượng</label>
                                        <input type="number" name="items[0][quantity]" class="form-control @error('items.0.quantity') is-invalid @enderror" placeholder="Số lượng" value="{{ old('items.0.quantity', 1) }}" min="1">
                                        @error('items.0.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <input type="hidden" name="items[0][product_code]" value="{{ old('items.0.product_code') }}">
                                        <input type="hidden" name="items[0][pancake_variant_id]" value="{{ old('items.0.pancake_variant_id') }}">
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add_item_row" class="btn btn-success btn-sm mb-3">
                                <i class="fas fa-plus"></i> Thêm sản phẩm
                            </button>
                            @error('items') <div class="text-danger mb-2">{{ $message }}</div> @enderror
                            <div class="row mt-3">
                                <div class="col text-right">
                                    <div class="font-weight-bold">Tổng giá trị: <span id="total_value_display">0</span> đ</div>
                                    <input type="hidden" name="total_value" id="total_value" value="0">
                                </div>
                            </div>
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
                                        <label for="shipping_provider_id">Đơn vị vận chuyển</label>
                                        <select name="shipping_provider_id" id="shipping_provider_id" class="form-control select2 @error('shipping_provider_id') is-invalid @enderror">
                                            <option value="">-- Chọn đơn vị vận chuyển --</option>
                                            @foreach($shippingProviders as $id => $name)
                                                @php
                                                    $provider = \App\Models\ShippingProvider::find($id);
                                                    $pancakeId = $provider ? $provider->pancake_partner_id : null;
                                                @endphp
                                                <option value="{{ $id }}" {{ old('shipping_provider_id') == $id ? 'selected' : '' }} data-pancake-id="{{ $pancakeId }}">
                                                    {{ $name }} {{ $pancakeId ? '(Pancake ID: '.$pancakeId.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('shipping_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_fee">Phí vận chuyển</label>
                                        <input type="number" name="shipping_fee" id="shipping_fee" class="form-control @error('shipping_fee') is-invalid @enderror" value="{{ old('shipping_fee', 0) }}" min="0">
                                        @error('shipping_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="is_free_shipping" id="is_free_shipping" class="form-check-input" value="1" {{ old('is_free_shipping') ? 'checked' : '' }}>
                                        <label for="is_free_shipping" class="form-check-label">Free shipping</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Phương thức thanh toán</label>
                                        <select name="payment_method" id="payment_method" class="form-control select2 @error('payment_method') is-invalid @enderror">
                                            <option value="cod" {{ old('payment_method') == 'cod' ? 'selected' : '' }}>COD (Thu tiền khi nhận hàng)</option>
                                            <option value="banking" {{ old('payment_method') == 'banking' ? 'selected' : '' }}>Chuyển khoản</option>
                                            <option value="momo" {{ old('payment_method') == 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                                            <option value="zalopay" {{ old('payment_method') == 'zalopay' ? 'selected' : '' }}>ZaloPay</option>
                                            <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="partner_fee">Phí đối tác</label>
                                        <input type="number" name="partner_fee" id="partner_fee" class="form-control @error('partner_fee') is-invalid @enderror" value="{{ old('partner_fee', 0) }}" min="0">
                                        @error('partner_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-check mb-3">
                                        <input type="checkbox" name="customer_pay_fee" id="customer_pay_fee" class="form-check-input" value="1" {{ old('customer_pay_fee') ? 'checked' : '' }}>
                                        <label for="customer_pay_fee" class="form-check-label">Khách hàng trả phí</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="returned_reason">Lý do hoàn/hủy đơn</label>
                                        <select name="returned_reason" id="returned_reason" class="form-control select2 @error('returned_reason') is-invalid @enderror">
                                            <option value="">Không áp dụng</option>
                                            <option value="1" {{ old('returned_reason') == '1' ? 'selected' : '' }}>Đổi ý</option>
                                            <option value="2" {{ old('returned_reason') == '2' ? 'selected' : '' }}>Lỗi sản phẩm</option>
                                            <option value="3" {{ old('returned_reason') == '3' ? 'selected' : '' }}>Giao hàng sai</option>
                                            <option value="4" {{ old('returned_reason') == '4' ? 'selected' : '' }}>Giao hàng chậm</option>
                                            <option value="5" {{ old('returned_reason') == '5' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                        @error('returned_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

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

                            <div class="form-group">
                                <label for="transfer_money">Số tiền chuyển khoản (Pancake)</label>
                                <input type="text" name="transfer_money" id="transfer_money" class="form-control @error('transfer_money') is-invalid @enderror" value="{{ old('transfer_money') }}">
                                @error('transfer_money') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                <div class="form-check mb-3">
                    <input type="checkbox" name="push_to_pancake" id="push_to_pancake" class="form-check-input" value="1" {{ old('push_to_pancake', true) ? 'checked' : '' }}>
                    <label for="push_to_pancake" class="form-check-label">Đẩy đơn hàng đến Pancake sau khi tạo</label>
                </div>
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
        // Initialize select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
        
        // Auto-populate billing information if empty
        $('#customer_name, #customer_phone, #customer_email').on('change', function() {
            const fieldMap = {
                'customer_name': 'bill_full_name',
                'customer_phone': 'bill_phone_number',
                'customer_email': 'bill_email'
            };
            
            const targetField = fieldMap[this.id];
            if (targetField && $('#' + targetField).val() === '') {
                $('#' + targetField).val($(this).val());
            }
        });

        // Hiển thị thông tin Pancake ID khi chọn đơn vị vận chuyển
        $('#shipping_provider_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const pancakeId = selectedOption.data('pancake-id');
            if (pancakeId) {
                console.log('Đã chọn đơn vị vận chuyển với Pancake ID:', pancakeId);
            }
        });

        // Item row management
        let itemCount = 1; // Start with 1 since we already have one row in the form
        
        // Add new item row
        $('#add_item_row').click(function() {
            let newRow = `
                <div class="row item-row mb-2 align-items-center">
                    <div class="col-md-3">
                        <label class="small">Mã SP (Pancake Variation ID)</label>
                        <input type="text" name="items[${itemCount}][code]" class="form-control" placeholder="Mã sản phẩm">
                    </div>
                    <div class="col-md-3">
                        <label class="small">Tên sản phẩm</label>
                        <input type="text" name="items[${itemCount}][name]" class="form-control" placeholder="Tên sản phẩm">
                    </div>
                    <div class="col-md-2">
                        <label class="small">Đơn giá</label>
                        <input type="number" step="any" name="items[${itemCount}][price]" class="form-control item-price" placeholder="Đơn giá" value="0" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="small">Số lượng</label>
                        <input type="number" name="items[${itemCount}][quantity]" class="form-control item-quantity" placeholder="Số lượng" value="1" min="1">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <input type="hidden" name="items[${itemCount}][product_code]" value="">
                        <input type="hidden" name="items[${itemCount}][pancake_variant_id]" value="">
                        <input type="hidden" name="items[${itemCount}][variation_detail]" value="">
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i> Xóa</button>
                    </div>
                </div>
            `;
            
            $('#items_container').append(newRow);
            itemCount++;
            calculateTotal(); // Recalculate total after adding row
        });

        // Remove item row
        $('#items_container').on('click', '.remove-row', function() {
            $(this).closest('.item-row').remove();
            calculateTotal(); // Recalculate total after removing row
        });

        // Calculate total when price or quantity changes
        $('#items_container').on('change', '.item-price, .item-quantity', function() {
            calculateTotal();
        });

        // Update shipping fee in totals when changed
        $('#shipping_fee').on('change', function() {
            calculateTotal();
        });
        
        // Free shipping checkbox handling
        $('#is_free_shipping').on('change', function() {
            if ($(this).is(':checked')) {
                const currentShippingFee = $('#shipping_fee').val();
                $('#shipping_fee').data('previous-value', currentShippingFee);
                $('#shipping_fee').val(0);
            } else {
                const previousValue = $('#shipping_fee').data('previous-value') || 0;
                $('#shipping_fee').val(previousValue);
            }
            calculateTotal();
        });

        // Initialize total calculation
        calculateTotal();

        // Function to calculate total
        function calculateTotal() {
            let total = 0;
            
            // Sum all line items
            $('.item-row').each(function() {
                const price = parseFloat($(this).find('.item-price').val()) || 0;
                const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
                total += price * quantity;
            });
            
            // Add shipping fee
            const shippingFee = parseFloat($('#shipping_fee').val()) || 0;
            
            // Calculate and display total with shipping
            const grandTotal = total + shippingFee;
            $('#total_value').val(grandTotal);
            $('#total_value_display').text(formatCurrency(grandTotal));
        }

        // Format currency for display
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
        }

        // Location dependencies
        $('#province_code').change(function() {
            var province_code = $(this).val();
            if(province_code) {
                loadDistricts(province_code);
            } else {
                $('#district_code').html('<option value="">-- Chọn Quận/Huyện --</option>');
                $('#district_code').prop('disabled', true);
                $('#ward_code').html('<option value="">-- Chọn Phường/Xã --</option>');
                $('#ward_code').prop('disabled', true);
            }
        });

        $('#district_code').change(function() {
            var district_code = $(this).val();
            if(district_code) {
                loadWards(district_code);
            } else {
                $('#ward_code').html('<option value="">-- Chọn Phường/Xã --</option>');
                $('#ward_code').prop('disabled', true);
            }
        });

        function loadDistricts(province_code) {
            $.ajax({
                url: '{{ route("ajax.districts") }}',
                type: 'GET',
                data: {province_code: province_code},
                success: function(data) {
                    $('#district_code').html('<option value="">-- Chọn Quận/Huyện --</option>');
                    $.each(data, function(code, name) {
                        $('#district_code').append('<option value="'+ code +'">'+ name +'</option>');
                    });
                    $('#district_code').prop('disabled', false);
                    
                    // If we have a previously selected district, select it again
                    @if(old('district_code'))
                    $('#district_code').val('{{ old('district_code') }}');
                    loadWards('{{ old('district_code') }}');
                    @endif
                }
            });
        }

        function loadWards(district_code) {
            $.ajax({
                url: '{{ route("ajax.wards") }}',
                type: 'GET',
                data: {district_code: district_code},
                success: function(data) {
                    $('#ward_code').html('<option value="">-- Chọn Phường/Xã --</option>');
                    $.each(data, function(code, name) {
                        $('#ward_code').append('<option value="'+ code +'">'+ name +'</option>');
                    });
                    $('#ward_code').prop('disabled', false);
                    
                    // If we have a previously selected ward, select it again
                    @if(old('ward_code'))
                    $('#ward_code').val('{{ old('ward_code') }}');
                    @endif
                }
            });
        }

        // Pancake shop and page linkage
        $('#pancake_shop_id').change(function() {
            var shop_id = $(this).val();
            if(shop_id) {
                loadPancakePages(shop_id);
            } else {
                $('#pancake_page_id').html('<option value="">-- Chọn Pancake Page --</option>');
                $('#pancake_page_id').prop('disabled', true);
            }
        });

        function loadPancakePages(shop_id) {
            $.ajax({
                url: '{{ route("ajax.pancakePagesForShop") }}',
                type: 'GET',
                data: {shop_id: shop_id},
                success: function(data) {
                    $('#pancake_page_id').html('<option value="">-- Chọn Pancake Page --</option>');
                    $.each(data, function(id, name) {
                        $('#pancake_page_id').append('<option value="'+ id +'">'+ name +'</option>');
                    });
                    $('#pancake_page_id').prop('disabled', false);
                    
                    // If we have a previously selected page, select it again
                    @if(old('pancake_page_id'))
                    $('#pancake_page_id').val('{{ old('pancake_page_id') }}');
                    @endif
                }
            });
        }

        // Load districts if province is already selected
        @if(old('province_code'))
            loadDistricts('{{ old('province_code') }}');
        @endif

        // Load pages if shop is already selected
        @if(old('pancake_shop_id'))
            loadPancakePages('{{ old('pancake_shop_id') }}');
        @endif
    });
    </script>
@endpush
