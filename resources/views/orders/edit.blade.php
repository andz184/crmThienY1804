@extends('adminlte::page')

@section('title', 'Chỉnh sửa Đơn hàng #'. $order->id)

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

<div class="container-fluid px-4">
        <form action="{{ route('orders.update', $order->id) }}" method="POST" id="orderCreateForm">
            @csrf
            @method('PUT')
            {{-- @dd($order) --}}
            <div class="row">
            {{-- Left Column (Products and Payment) --}}
            <div class="col-lg-8">
                {{-- Products Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title m-0 font-weight-bold text-primary">
                            <i class="fas fa-shopping-cart mr-2"></i>Sản phẩm
                            @if($order->pancake_order_id)
                                <small class="text-muted">(Tự động cập nhật lên Pancake)</small>
                            @endif
                        </h3>
                    </div>
                    <div class="card-body">
                    {{-- Product Source Selection --}}
                    <div class="form-group">
                        <label class="small font-weight-bold">Nguồn đơn hàng</label>
                        <select name="source" class="form-control select2" data-placeholder="-- Chọn nguồn đơn --">
                            <option value="">-- Chọn nguồn đơn --</option>

                            @foreach($productSources as $source)
                                <option value="{{old('source', $order->source) ?? $source->pancake_id }}" {{ $order->source == $source->pancake_id ? 'selected' : '' }}>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="items_container">
                        @php
                            $productsDataSource = old('items');
                            if (is_null($productsDataSource) && $order->products_data) {
                                $productsDataSource = json_decode($order->products_data, false);
                            }
                            if (is_null($productsDataSource)) {
                                $productsDataSource = [];
                            }
                            $totalAmount = 0;
                        @endphp

                        @if(!empty($productsDataSource))
                            @foreach($productsDataSource as $index => $itemData)
                                @php
                                    $currentItem = is_object($itemData) ? (array)$itemData : ($itemData ?? []);
                                    $variationInfo = $currentItem['variation_info'] ?? [];
                                    if (is_object($variationInfo)) $variationInfo = (array)$variationInfo;
                                    $productName = $variationInfo['name'] ?? '';
                                    $price = $variationInfo['retail_price'] ?? 0;
                                    $quantity = $currentItem['quantity'] ?? 1;
                                    $variationId = $currentItem['variation_id'] ?? '';
                                    $code = $currentItem['code'] ?? $variationId;
                                    $productId = $currentItem['product_id'] ?? '';
                                    // Đảm bảo variation_info lưu lại đúng dạng string
                                    $variationInfoForInput = is_array($variationInfo) || is_object($variationInfo) ? json_encode($variationInfo) : strval($variationInfo);
                                    $subtotal = $price * $quantity;
                                    $totalAmount += $subtotal;
                                @endphp
                                <div class="item-row mb-3">
                                    <div class="row align-items-end">
                                        <div class="col-md-3">
                                            <label class="small font-weight-bold">Mã SP</label>
                                            <div class="input-group">
                                                <input type="text" name="items[{{ $index }}][code]" class="form-control product-code" placeholder="Nhập mã SP (code)" value="{{ $code }}">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary search-product">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small font-weight-bold">Tên sản phẩm</label>
                                            <input type="text" name="items[{{ $index }}][name]" class="form-control product-name" placeholder="Tên sản phẩm" value="{{ $productName }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small font-weight-bold">Đơn giá</label>
                                            <input type="number" step="any" name="items[{{ $index }}][price]" class="form-control item-price" placeholder="Đơn giá" value="{{ $price }}" min="0">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small font-weight-bold">Số lượng</label>
                                            <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" placeholder="Số lượng" value="{{ $quantity }}" min="1">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-center">
                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $productId }}">
                                            <input type="hidden" name="items[{{ $index }}][variation_info]" value="{{ $variationInfoForInput }}">
                                            <button type="button" class="btn btn-link text-danger remove-row" style="{{ count($productsDataSource) > 1 ? '' : 'display: none;' }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @php
                                $shippingFee = floatval(old('shipping_fee', $order->shipping_fee ?? 0));
                                $discount = floatval(old('discount_amount', $order->discount_amount ?? 0));
                                $transferMoney = floatval(old('transfer_money', $order->transfer_money ?? 0));
                                $partnerFee = floatval(old('partner_fee', $order->partner_fee ?? 0));
                                $total = $totalAmount + $shippingFee + $partnerFee;
                                $afterDiscount = $total - $discount;
                                $remaining = $afterDiscount - $transferMoney;
                            @endphp

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.getElementById('total_amount').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format({{ $total }});
                                    document.getElementById('discount_display').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format({{ $discount }});
                                    document.getElementById('after_discount').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format({{ $afterDiscount }});
                                    document.getElementById('paid_amount').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format({{ $transferMoney }});
                                    document.getElementById('remaining_amount').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format({{ $remaining }});
                                });
                            </script>
                        @else
                        {{-- Default first empty row if no items --}}
                        <div class="item-row mb-3">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label class="small font-weight-bold">Mã SP</label>
                                    <div class="input-group">
                                        <input type="text" name="items[0][code]" class="form-control product-code" placeholder="Nhập mã SP (code)">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary search-product">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="small font-weight-bold">Tên sản phẩm</label>
                                    <input type="text" name="items[0][name]" class="form-control product-name" placeholder="Tên sản phẩm" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="small font-weight-bold">Đơn giá</label>
                                    <input type="number" step="any" name="items[0][price]" class="form-control item-price" placeholder="Đơn giá" value="0" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="small font-weight-bold">Số lượng</label>
                                    <input type="number" name="items[0][quantity]" class="form-control item-quantity" placeholder="Số lượng" value="1" min="1">
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <input type="hidden" name="items[0][product_id]" value="">
                                    <input type="hidden" name="items[0][variation_info]" value="">
                                    <button type="button" class="btn btn-link text-danger remove-row" style="display: none;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="text-left mt-3">
                        <button type="button" id="add_item_row" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                        </div>
                    </div>
                </div>

                {{-- Payment Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-success">
                            <i class="fas fa-money-bill-wave mr-2"></i>Thanh toán
                        </h3>
                        </div>
                        <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_free_shipping" name="is_free_shipping" value="1" {{ old('is_free_shipping', $order->is_free_shipping) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_free_shipping">Miễn phí giao hàng</label>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="customer_pay_fee" name="customer_pay_fee" value="1" {{ old('customer_pay_fee', $order->customer_pay_fee) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="customer_pay_fee">Khách trả phí</label>
                            </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6" id="shipping_fee_group">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Phí vận chuyển</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-truck"></i></span>
                    </div>
                                        <input type="number" name="shipping_fee" class="form-control" value="{{ old('shipping_fee', $order->shipping_fee ?? 0) }}">
                </div>
                        </div>
                            </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                    <label class="small font-weight-bold">Giảm giá đơn hàng</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-percent"></i></span>
                                    </div>
                                        <input type="number" name="discount_amount" class="form-control" value="{{ old('discount_amount', $order->discount_amount ?? 0) }}">
                                </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                    <label class="small font-weight-bold">Tiền chuyển khoản</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-money-check"></i></span>
                                        </div>
                                        <input type="number" name="transfer_money" class="form-control" value="{{ old('transfer_money', $order->transfer_money ?? 0) }}">
                                    </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                    <label class="small font-weight-bold">Phí đối tác</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-handshake"></i></span>
                                    </div>
                                        <input type="number" name="partner_fee" class="form-control" value="{{ old('partner_fee', $order->partner_fee ?? 0) }}">
                                </div>
                            </div>
                            </div>
                                    </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-sm">
                                <tr class="bg-light">
                                    <th class="w-50">Tổng tiền:</th>
                                    <td class="text-right font-weight-bold" id="total_amount">0 đ</td>
                                </tr>
                                <tr>
                                    <th>Giảm giá:</th>
                                    <td class="text-right text-success font-weight-bold" id="discount_display">0 đ</td>
                                </tr>
                                <tr>
                                    <th>Sau giảm giá:</th>
                                    <td class="text-right font-weight-bold" id="after_discount">0 đ</td>
                                </tr>
                                <tr>
                                    <th>Đã thanh toán:</th>
                                    <td class="text-right text-primary font-weight-bold" id="paid_amount">0 đ</td>
                                </tr>
                                <tr class="bg-light">
                                    <th>Còn thiếu:</th>
                                    <td class="text-right text-danger font-weight-bold" id="remaining_amount">0 đ</td>
                                </tr>
                            </table>
                                    </div>
                                    </div>
                                    </div>

                {{-- Shipping & Packaging Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-primary">
                            <i class="fas fa-box-open mr-2"></i>Vận chuyển & Đóng gói
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="small font-weight-bold">Kích thước gói hàng (D x R x C) <small class="text-muted">(cm)</small></label>
                            <div class="row">
                                <div class="col-4">
                                    <input type="number" name="shipping_length" class="form-control" placeholder="Dài" value="{{ old('shipping_length', $order->shipping_length) }}">
                                </div>
                                <div class="col-4">
                                    <input type="number" name="shipping_width" class="form-control" placeholder="Rộng" value="{{ old('shipping_width', $order->shipping_width) }}">
                                </div>
                                <div class="col-4">
                                    <input type="number" name="shipping_height" class="form-control" placeholder="Cao" value="{{ old('shipping_height', $order->shipping_height) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Đơn vị vận chuyển</label>
                            <select name="shipping_provider_id" id="shipping_provider_id" class="form-control select2 @error('shipping_provider_id') is-invalid @enderror" data-placeholder="-- Chọn đơn vị vận chuyển --">
                                <option value="">-- Chọn đơn vị vận chuyển --</option>
                                @if(isset($shippingProviders))
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

                {{-- Notes Section --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-info">
                            <i class="fas fa-sticky-note mr-2"></i>Ghi chú
                        </h3>
                                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#internal_note" role="tab">Nội bộ</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#external_note" role="tab">Đối tác</a>
                            </li>
                        </ul>
                        <div class="tab-content pt-3">
                            <div class="tab-pane active" id="internal_note" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Ghi chú nội bộ</label>
                                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $order->notes ?? '') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="small font-weight-bold">Trạng thái Đơn hàng (CRM)</label>
                                            <input type="text" class="form-control" value="{{ $statuses[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status ?? 'N/A')) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="external_note" role="tabpanel">
                                <textarea name="additional_notes" class="form-control" rows="3" placeholder="Ghi chú cho đối tác">{{ old('additional_notes', $order->additional_notes ?? '') }}</textarea>
                            </div>
                                </div>
                            </div>
                        </div>
            </div>

            {{-- Right Column (Customer and Shipping Info) --}}
            <div class="col-lg-4">
                {{-- Staff Assignment --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-primary">
                            <i class="fas fa-user-tie mr-2"></i>Thông tin xử lý
                        </h3>
                        </div>
                        <div class="card-body">
                                    <div class="form-group">
                            <label class="small font-weight-bold">Thời gian tạo</label>
                            <input type="text" class="form-control" value="{{ $order->created_at->format('d/m/Y H:i') }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">NV xử lý</label>
                            <select name="assigning_seller_id" class="form-control select2" required>
                                <option value="">-- Chọn nhân viên sale --</option>
                                @foreach($users->whereNotNull('pancake_uuid') as $user)
                                    <option value="{{ $user->pancake_uuid }}" {{ old('assigning_seller_id', $order->assigning_seller_id) == $user->pancake_uuid ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">NV chăm sóc</label>
                            <select name="assigning_care_id" class="form-control select2">
                                <option value="">-- Chọn nhân viên CSKH --</option>
                                @foreach($users->whereNotNull('pancake_uuid') as $user)
                                    <option value="{{ $user->id }}" {{ old('assigning_care_id', $order->assigning_care_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Marketer</label>
                            <select name="marketer_id" class="form-control select2">
                                <option value="">-- Chọn Marketer --</option>
                                @foreach($users->whereNotNull('pancake_uuid') as $user)
                                    <option value="{{ $user->id }}" {{ old('marketer_id', $order->marketer_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                                    {{-- Pancake Shop --}}
                                    <div class="form-group">
                                        <label class="small font-weight-bold" for="pancake_shop_id">Pancake Shop</label>
                                        <select name="pancake_shop_id" id="pancake_shop_id" class="form-control select2" data-placeholder="-- Chọn Shop Pancake --">
                                            <option value="">-- Chọn Shop Pancake --</option>
                                            @if(isset($pancakeShops))
                                                @foreach($pancakeShops as $id => $name)
                                                    <option value="{{ $id }}" {{ old('pancake_shop_id', $order->pancake_shop_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Pancake Page --}}
                                    <div class="form-group">
                                        <label class="small font-weight-bold" for="pancake_page_id">Pancake Page</label>
                                        <select name="pancake_page_id" id="pancake_page_id" class="form-control select2" data-placeholder="-- Chọn Page (sau khi chọn Shop) --" {{ !$order->pancake_shop_id ? 'disabled' : '' }}>
                                            <option value="">-- Chọn Page (sau khi chọn Shop) --</option>
                                            @if(isset($pancakePages) && $order->pancake_shop_id)
                                                @foreach($pancakePages as $page)
                                                    <option value="{{ $page->id }}" {{ old('pancake_page_id', $order->pancake_page_id) == $page->id ? 'selected' : '' }}>{{ $page->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    {{-- Post ID --}}
                                    <div class="form-group mb-0">
                                        <label class="small font-weight-bold" for="pancake_post_id">Post ID (Pancake)</label>
                                        <input type="text" name="pancake_post_id" id="pancake_post_id" class="form-control" placeholder="Nhập Post ID từ Pancake (nếu có)" value="{{ old('pancake_post_id', $order->pancake_post_id) }}">
                                    </div>

                                </div>
                </div>

                {{-- Customer Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-info">
                            <i class="fas fa-user mr-2"></i>Thông tin khách hàng
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="customer_suggestions" class="position-absolute bg-white w-100" style="display:none; z-index: 1000; /* Ensure this is properly managed by JS if detached */">
                            <!-- Customer suggestions will be populated here -->
                        </div>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $order->customer_id) }}">
                                    <div class="form-group">
                            <label class="small font-weight-bold">Tên khách hàng</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Tên khách hàng" value="{{ old('customer_name', $order->customer_name) }}">
                                </div>
                            </div>
                                    <div class="form-group">
                            <label class="small font-weight-bold">Số điện thoại</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                <input type="text" name="billing_phone" id="customer_phone" class="form-control" placeholder="SĐT" value="{{ old('billing_phone', $order->bill_phone_number ?? ($order->customer_phone ?? '')) }}">
                                    </div>
                                </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Email</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="Email" value="{{ old('customer_email', $order->customer_email) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shipping Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title m-0 font-weight-bold text-success">
                            <i class="fas fa-shipping-fast mr-2"></i>Thông tin giao hàng
                        </h3>
                    </div>
                    <div class="card-body">
                                    <div class="form-group">
                            <label class="small font-weight-bold">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                            <select name="province_code" id="province_code" class="form-control select2 @error('province_code') is-invalid @enderror" required data-placeholder="-- Chọn Tỉnh/Thành phố --">
                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                @foreach($provinces as $code => $name)
                                    <option value="{{ $code }}" {{ old('province_code', $order->province_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                                        </select>
                            @error('province_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Quận/Huyện <span class="text-danger">*</span></label>
                            <select name="district_code" id="district_code" class="form-control select2 @error('district_code') is-invalid @enderror" required data-placeholder="-- Chọn Quận/Huyện --" {{ old('province_code', $order->province_code) ? '' : 'disabled' }}>
                                <option value="">-- Chọn Quận/Huyện --</option>
                                {{-- Districts will be loaded by JS, but if there's an old value or $order value, we might need to pre-populate or ensure JS selects it --}}
                                @if(old('district_code', $order->district_code) && isset($districts))
                                    @foreach($districts as $code => $name) {{-- Assuming $districts is passed for the selected province if available --}}
                                        <option value="{{ $code }}" {{ old('district_code', $order->district_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                @elseif($order->district_code && $order->district_name) {{-- Fallback to direct order data if $districts not available --}}
                                     <option value="{{ $order->district_code }}" selected>{{ $order->district_name }}</option>
                                @endif
                            </select>
                            @error('district_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Phường/Xã <span class="text-danger">*</span></label>
                            <select name="ward_code" id="ward_code" class="form-control select2 @error('ward_code') is-invalid @enderror" required data-placeholder="-- Chọn Phường/Xã --" {{ old('district_code', $order->district_code) ? '' : 'disabled' }}>
                                <option value="">-- Chọn Phường/Xã --</option>
                                {{-- Wards will be loaded by JS --}}
                                @if(old('ward_code', $order->ward_code) && isset($wards))
                                    @foreach($wards as $code => $name) {{-- Assuming $wards is passed for the selected district if available --}}
                                        <option value="{{ $code }}" {{ old('ward_code', $order->ward_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                @elseif($order->ward_code && $order->ward_name)
                                    <option value="{{ $order->ward_code }}" selected>{{ $order->ward_name }}</option>
                                @endif
                            </select>
                            @error('ward_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                            <label class="small font-weight-bold">Địa chỉ cụ thể <span class="text-danger">*</span></label>
                            <textarea name="street_address" id="street_address" class="form-control @error('street_address') is-invalid @enderror" rows="2" required>{{ old('street_address', $order->street_address) }}</textarea>
                            @error('street_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Kho xuất hàng <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="warehouse_id" class="form-control select2 @error('warehouse_id') is-invalid @enderror" required data-placeholder="-- Chọn kho hàng --">
                                    <option value="">-- Chọn kho hàng --</option>
                                @if(isset($warehouses))
                                    @foreach($warehouses as $id => $name)
                                        <option value="{{ $id }}" {{ old('warehouse_id', $order->warehouse_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                @endif
                                </select>
                                @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            </div>
                        </div>
                    </div>
                        </div>

        {{-- Form Actions --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                        <div class="card-body">
                        <div class="custom-control custom-switch float-left">
                            <input type="checkbox" name="push_to_pancake" id="push_to_pancake" class="custom-control-input" value="1" {{ old('push_to_pancake', $order->pushed_to_pancake_at ? true : false) ? 'checked' : '' }}>
                            <label for="push_to_pancake" class="custom-control-label">Đẩy đơn hàng đến Pancake sau khi cập nhật</label>
                            </div>
                        <div class="float-right">
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Quay lại
                            </a>
                            @can('orders.push_to_pancake')
                            <button type="button" class="btn btn-info mr-2 btn-push-pancake"
                                    data-order-id="{{ $order->id }}"
                                    data-url="{{ route('orders.pushToPancake', $order->id) }}"
                                    title="Đẩy đơn hàng này lên Pancake">
                                <i class="fas fa-rocket fa-fw"></i> Đẩy lên Pancake
                            </button>
                            @endcan
                            <button type="submit" class="btn btn-primary ml-2">
                                <i class="fas fa-save mr-1"></i>Cập nhật đơn hàng
                            </button>
                            </div>
                            </div>
                        </div>
                    </div>
            </div>
        </form>
</div>
@stop

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
/* General Styles */
body {
    background-color: #f8f9fc;
}

.container-fluid {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}

/* Card Styles */
.card {
    border: none;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid #e3e6f0;
    padding: 1rem 1.25rem;
}

.card-header .card-title {
    font-size: 1rem;
    font-weight: 700 !important;
    margin: 0;
    color: #4e73df;
}

.card-body {
    padding: 1.25rem;
}

/* Form Controls */
.form-control {
    font-size: 0.875rem;
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.375rem 0.75rem;
    height: calc(1.5em + 0.75rem + 2px);
}

.form-control:focus {
    border-color: #bac8f3;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

textarea.form-control {
    height: auto;
}

/* Select2 Customization */
        .select2-container--bootstrap4 .select2-selection {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    height: calc(1.5em + 0.75rem + 2px) !important;
}

        .select2-container--bootstrap4.select2-container--focus .select2-selection {
    border-color: #bac8f3;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: calc(1.5em + 0.75rem) !important;
    padding-left: 0.75rem !important;
}

.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.75rem) !important;
}

/* Input Groups */
.input-group-text {
    font-size: 0.875rem;
    border: 1px solid #d1d3e2;
    background-color: #f8f9fc;
}

/* Custom Switches */
.custom-switch .custom-control-label::before {
    border: none;
    background-color: #eaecf4;
}

.custom-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #4e73df;
}

/* Tables */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fc;
}

.table td {
    vertical-align: middle;
}

/* Buttons */
.btn {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.35rem;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-primary:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
}

/* Customer Suggestions */
#customer_suggestions {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    max-height: 280px;
            overflow-y: auto;
    position: absolute;
    background: white;
    z-index: 1050;
    width: 100%;
}

.suggestion-item {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e3e6f0;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f8f9fc;
}

.suggestion-item .customer-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: #4e73df;
    margin: 0;
}

.suggestion-item .customer-meta {
    font-size: 0.8125rem;
    color: #5a5c69;
    margin: 0;
}

.suggestion-item .purchase-info {
    font-size: 0.75rem;
    color: #858796;
    font-style: italic;
    margin: 0;
}

/* Custom Scrollbar for Customer Suggestions */
#customer_suggestions::-webkit-scrollbar {
    width: 6px;
}

#customer_suggestions::-webkit-scrollbar-track {
    background: #f8f9fc;
    border-radius: 0.35rem;
}

#customer_suggestions::-webkit-scrollbar-thumb {
    background: #d1d3e2;
    border-radius: 0.35rem;
}

#customer_suggestions::-webkit-scrollbar-thumb:hover {
    background: #858796;
}

/* Icons */
.fa, .fas {
    font-size: 0.875rem;
}

/* Validation */
.was-validated .form-control:valid, .form-control.is-valid {
    border-color: #1cc88a;
}

.was-validated .form-control:invalid, .form-control.is-invalid {
    border-color: #e74a3b;
}

/* Required Fields */
.text-danger {
    color: #e74a3b !important;
}

/* Responsive Adjustments */
@media (max-width: 991.98px) {
    .container-fluid {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .card {
        margin-bottom: 1rem;
    }
}
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Make allPancakePages data available to JavaScript
        const allPancakePagesData = @json($allPancakePages ?? []);
        // For edit, the initial page ID is from the order or old input
        const initialPancakePageId = '{{ old("pancake_page_id", $order->pancake_page_id ?? null) }}';
    </script>
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').each(function() {
            $(this).select2({
            theme: 'bootstrap4',
                width: '100%',
                placeholder: $(this).attr('data-placeholder') || '-- Chọn --',
                allowClear: $(this).prop('multiple') ? false : true
            });
        });

        let suggestionsInitialized = false;
        let suggestionBox;

        // Customer search with debounce
        let searchTimeout;
        $('#customer_search, #customer_name, #customer_phone').on('input', function() {
            const query = $(this).val().trim();
            const currentInput = $(this);

            if (!suggestionsInitialized) {
                suggestionBox = $('#customer_suggestions').detach().appendTo('body');
                suggestionsInitialized = true;
            } else {
                suggestionBox = $('#customer_suggestions'); // Ensure we have the latest jQuery object if needed
            }

            clearTimeout(searchTimeout);

            if (currentInput.is('#customer_name') || currentInput.is('#customer_phone')) {
                if (query.length === 0) {
                    $('#customer_search').val('');
                    suggestionBox.slideUp(150);
                    return;
                }
            }

            if (query.length >= 1) {
                // Position the suggestion box under the current input
                const offset = currentInput.offset();
                const inputHeight = currentInput.outerHeight();
                const inputWidth = currentInput.outerWidth();

                suggestionBox.css({
                    top: offset.top + inputHeight + 'px',
                    left: offset.left + 'px',
                    width: inputWidth + 'px',
                    marginTop: '0px' // Reset any previous margin-top
                });

                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: '/api/customers/search',
                        method: 'GET',
                        data: { query: query },
                        dataType: 'json',
                        success: function(response) {
                            let html = '';
                            if (response && response.success && Array.isArray(response.data)) {
                                if (response.data.length > 0) {
                                    response.data.forEach(customer => {
                                        try {
                                            const purchaseInfo = customer.purchase_count
                                                ? `Đã mua: ${customer.purchase_count} lần`
                                                : (customer.total_spent ? `Tổng chi: ${formatCurrency(customer.total_spent)}` : 'Khách mới');
                                            // const avatar = customer.avatar || '/images/default-avatar.png'; // Avatar logic removed as column is removed
                                            const customerJson = JSON.stringify(customer).replace(/'/g, "&apos;").replace(/\"/g, "&quot;");

                                            html += `
                                                <div class="suggestion-item" data-customer='${customerJson}'>
                                                    <div class="customer-info">
                                                        <div class="customer-name">${customer.name || 'N/A'}</div>
                                                        <div class="customer-meta">${customer.phone || 'N/A'}</div>
                                                        <div class="purchase-info">${purchaseInfo}</div>
                                                    </div>
                                                </div>`;
                                        } catch (e) {
                                            console.error("Error processing customer data for suggestion: ", customer, e);
                                        }
                                    });
                                    suggestionBox.html(html);
                                } else {
                                    suggestionBox.html('<div class="p-2 text-center text-muted">Không tìm thấy khách hàng nào.</div>');
                                }
                            } else {
                                console.warn("API call successful, but response indicates failure or malformed data: ", response);
                                suggestionBox.html('<div class="p-2 text-center text-warning">Phản hồi từ máy chủ không hợp lệ.</div>');
                            }
                            suggestionBox.slideDown(150);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Customer search AJAX error: ", textStatus, errorThrown, jqXHR.responseText);
                            suggestionBox.html('<div class="p-2 text-center text-danger">Lỗi kết nối hoặc tìm kiếm thất bại.</div>').slideDown(150);
                        }
                    });
                }, 250);
            } else {
                suggestionBox.slideUp(150);
            }
        });

        // Function to format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        // Function to parse currency string to number
        function parseCurrency(str) {
            return parseInt(str.replace(/[^\d]/g, '')) || 0;
        }

        // Function to calculate totals
        function calculateTotals() {
            let subtotal = 0;

            // Calculate subtotal from all products
            $('.item-row').each(function() {
                const price = parseFloat($(this).find('.item-price').val()) || 0;
                const quantity = parseInt($(this).find('.item-quantity').val()) || 0;
                subtotal += price * quantity;
            });

            // Get other fees and discounts
            const shippingFee = parseFloat($('input[name="shipping_fee"]').val()) || 0;
            const discount = parseFloat($('input[name="discount_amount"]').val()) || 0;
            const transferMoney = parseFloat($('input[name="transfer_money"]').val()) || 0;
            const partnerFee = parseFloat($('input[name="partner_fee"]').val()) || 0;

            // Calculate totals
            const total = subtotal + shippingFee + partnerFee;
            const afterDiscount = total - discount;
            const remaining = afterDiscount - transferMoney;

            // Update display
            $('#total_amount').text(formatCurrency(total));
            $('#discount_display').text(formatCurrency(discount));
            $('#after_discount').text(formatCurrency(afterDiscount));
            $('#paid_amount').text(formatCurrency(transferMoney));
            $('#remaining_amount').text(formatCurrency(remaining));
        }

        // Initial calculation
        calculateTotals();

        // Initialize itemIndexCounter based on the number of item rows already rendered by Blade
        // This ensures that new items added via JS have unique indices that don't conflict with existing items.
        let itemIndexCounter = $('#items_container .item-row').length;

        // Add new product row
        $('#add_item_row').click(function() {
            const newRow = `
                <div class="item-row mb-3">
                    <div class="row align-items-end">
                    <div class="col-md-3">
                            <label class="small font-weight-bold">Mã SP</label>
                            <div class="input-group">
                                <input type="text" name="items[${itemIndexCounter}][code]" class="form-control product-code" placeholder="Nhập mã SP (code)">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary search-product">
                                        <i class="fas fa-search"></i>
                                    </button>
                    </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Tên sản phẩm</label>
                            <input type="text" name="items[${itemIndexCounter}][name]" class="form-control product-name" placeholder="Tên sản phẩm">
                    </div>
                    <div class="col-md-2">
                            <label class="small font-weight-bold">Đơn giá</label>
                            <input type="number" step="any" name="items[${itemIndexCounter}][price]" class="form-control item-price" placeholder="Đơn giá" value="0" min="0">
                    </div>
                    <div class="col-md-2">
                            <label class="small font-weight-bold">Số lượng</label>
                            <input type="number" name="items[${itemIndexCounter}][quantity]" class="form-control item-quantity" placeholder="Số lượng" value="1" min="1">
                    </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <input type="hidden" name="items[${itemIndexCounter}][product_id]" value="">
                            <input type="hidden" name="items[${itemIndexCounter}][variation_info]" value="">
                            <button type="button" class="btn btn-link text-danger remove-row">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#items_container').append(newRow);
            itemIndexCounter++; // Increment for the next new row

            // Ensure remove buttons are shown if there's more than one item row
            if ($('.item-row').length > 1) {
                $('.remove-row').show();
            }
        });

        // Remove product row
        $(document).on('click', '.remove-row', function() {
            $(this).closest('.item-row').remove();
            if ($('.item-row').length === 1) {
                $('.remove-row').hide(); // Hide remove button if only one row remains
            }
            calculateTotals(); // Recalculate totals after removing row
        });

        // Listen for changes in price, quantity, and other amounts
        $(document).on('input', '.item-price, .item-quantity', function() {
            calculateTotals();
        });

        $(document).on('input', 'input[name="shipping_fee"], input[name="discount_amount"], input[name="transfer_money"], input[name="partner_fee"]', function() {
            calculateTotals();
        });

        // Handle customer selection
        $(document).on('click', '.suggestion-item', function() {
            let customer;
            try {
                const customerDataString = $(this).attr('data-customer')
                                             .replace(/&apos;/g, "'")
                                             .replace(/&quot;/g, '"');
                customer = JSON.parse(customerDataString);
            } catch (e) {
                console.error("Failed to parse customer data from attribute: ", e, $(this).attr('data-customer'));
                alert("Có lỗi khi chọn khách hàng này.");
                return;
            }

            if (customer && typeof customer === 'object') {
                fillCustomerInfo(customer);
                $('#customer_suggestions').slideUp(150);
                $('#customer_search').val(customer.name || '');
            } else {
                console.error("Invalid customer data after parsing: ", customer);
                alert("Dữ liệu khách hàng không hợp lệ.");
            }
        });

        function fillCustomerInfo(customer) {
            $('#customer_name').val(customer.name || '');
            $('#customer_phone').val(customer.phone || '');
            $('#customer_email').val(customer.email || '');
            $('#customer_id').val(customer.id || '');
            $('textarea[name="street_address"]').val(customer.street_address || '');

            // Reset dependent dropdowns first
            resetLocationSelects('province');

            // Use customer.province, customer.district, customer.ward
            const provinceCode = customer.province;
            const districtCode = customer.district;
            const wardCode = customer.ward;

            if (provinceCode) {
                $('#province_code').val(provinceCode).trigger('change.select2');
                // The 'change' event on #province_code should trigger loadDistricts.
                // We need to ensure that after districts are loaded, if districtCode is available, it's selected.
                // And similarly for wards. This requires careful handling of callbacks or event listeners.

                // Option 1: Chaining callbacks (current refined approach)
                loadDistricts(provinceCode, function() {
                    if (districtCode) {
                        // Check if the option exists after loading
                        if ($('#district_code option[value="' + districtCode + '"]').length > 0) {
                            $('#district_code').val(districtCode).trigger('change.select2');
                            // Now, the 'change' event on #district_code should trigger loadWards
                            loadWards(districtCode, function() {
                                if (wardCode) {
                                     if ($('#ward_code option[value="' + wardCode + '"]').length > 0) {
                                        $('#ward_code').val(wardCode).trigger('change.select2');
                                     } else {
                                         console.warn('Ward code ' + wardCode + ' (from customer data) not found in dropdown after loading.');
                                     }
                                }
                            });
            } else {
                             console.warn('District code ' + districtCode + ' (from customer data) not found in dropdown after loading.');
                             // If district is not found, we might not want to proceed to load wards based on an invalid district,
                             // or we might want to clear the ward dropdown.
                             resetLocationSelects('district'); // Clears ward too
                        }
                    }
                });
            } else {
                // No province_code from customer, ensure all location selects are reset and disabled
                $('#province_code').val(null).trigger('change.select2');
                // resetLocationSelects('province'); // Already called at the beginning
            }
        }

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#customer_search, #customer_name, #customer_phone, #customer_suggestions').length) {
                $('#customer_suggestions').slideUp(150);
            }
        });

        function populateSelect(selector, data, placeholder) {
            let options = `<option value="">${placeholder}</option>`;
            if (data && typeof data === 'object') {
                for (const [code, name] of Object.entries(data)) {
                    options += `<option value="${code}">${name}</option>`;
                }
            }
            $(selector).html(options).trigger('change.select2'); // Refresh Select2 after populating
        }

        // Sửa lại loadDistricts và loadWards để nhận callback
        // Ensure these functions populate the dropdowns correctly and then call the callback.
        function loadDistricts(province_code, callback) {
            if (!province_code) {
                resetLocationSelects('district'); // This will also reset and disable ward
                if (typeof callback === 'function') callback();
                return;
            }

            $.ajax({
                url: '/api/districts',
                type: 'GET',
                data: { province_code: province_code },
                success: function(data) {
                    let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                    $.each(data, function(code, name) {
                        options += '<option value="'+ code +'">'+ name +'</option>';
                    });
                    $('#district_code').html(options).prop('disabled', false).trigger('change.select2');
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    console.error("Failed to load districts for province: " + province_code);
                    resetLocationSelects('district');
                    if (typeof callback === 'function') callback();
                }
            });
        }

        function loadWards(district_code, callback) {
            if (!district_code) {
                resetLocationSelects('ward');
                if (typeof callback === 'function') callback();
                return;
            }
            $.ajax({
                url: '/api/wards',
                type: 'GET',
                data: { district_code: district_code },
                success: function(data) {
                    let options = '<option value="">-- Chọn Phường/Xã --</option>';
                    $.each(data, function(code, name) {
                        options += '<option value="'+ code +'">'+ name +'</option>';
                    });
                    $('#ward_code').html(options).prop('disabled', false).trigger('change.select2');
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    console.error("Failed to load wards for district: " + district_code);
                    resetLocationSelects('ward');
                    if (typeof callback === 'function') callback();
                }
            });
        }

        function resetLocationSelects(fromLevel) {
            if (fromLevel === 'province' || fromLevel === 'district') {
                $('#district_code')
                    .html('<option value="">-- Chọn Quận/Huyện --</option>')
                    .prop('disabled', true)
                    .trigger('change.select2');
            }
            // Always reset ward if district or province is reset
            if (fromLevel === 'province' || fromLevel === 'district' || fromLevel === 'ward') {
                $('#ward_code')
                    .html('<option value="">-- Chọn Phường/Xã --</option>')
                    .prop('disabled', true)
                    .trigger('change.select2');
            }
        }

        // Event handlers for manual selection of province/district
        // These should correctly trigger the loading of the next level.
        $('#province_code').on('change', function() {
            const provinceCode = $(this).val();
            if (provinceCode) {
                 loadDistricts(provinceCode, function() {
                    // Callback after districts are loaded for manual province change
                    // If we were auto-filling, the fillCustomerInfo handles setting the district/ward.
                    // For manual change, we just load districts and reset ward.
                    resetLocationSelects('ward'); // Ensure ward is reset
                 });
            } else {
                resetLocationSelects('province'); // Clear both district and ward
            }
        });

        $('#district_code').on('change', function() {
            const districtCode = $(this).val();
            if (districtCode) {
                loadWards(districtCode, function(){
                    // Callback after wards are loaded for manual district change
                    // If we were auto-filling, fillCustomerInfo handles setting the ward.
                    // For manual selection, we just ensure it's enabled and Select2 is updated.
                    $('#ward_code').prop('disabled', false).trigger('change.select2');
                });
            } else {
                // If district is cleared, reset and disable ward
                resetLocationSelects('ward');
            }
        });

        // Initial call to setup placeholders for location if not pre-selected
        if (!$('#province_code').val()) {
            resetLocationSelects('province');
        } else if (!$('#district_code').val()) {
             resetLocationSelects('district');
        } else if (!$('#ward_code').val()) {
            resetLocationSelects('ward');
        }

        // Load Pancake Pages when Pancake Shop changes
        $('#pancake_shop_id').on('change', function() {
            const shopId = $(this).val();
            const $pageSelect = $('#pancake_page_id');
            $pageSelect.empty().append('<option value="">-- Chọn Page --</option>').prop('disabled', true);

            if (shopId && allPancakePagesData[shopId]) {
                const pagesForShop = allPancakePagesData[shopId];
                if (pagesForShop.length > 0) {
                    $.each(pagesForShop, function(index, page) {
                        $pageSelect.append($('<option>', {
                            value: page.id, // Assuming 'id' is the page's primary key
                            text: page.name
                        }));
                    });
                    $pageSelect.prop('disabled', false);
                } else {
                    $pageSelect.append('<option value="">Không tìm thấy Page cho Shop này</option>');
                }
            } else {
                 $pageSelect.append('<option value="">Không tìm thấy Page hoặc Shop không hợp lệ</option>');
            }

            // Attempt to re-select the initial/old page ID if it exists for the current shop
            if (initialPancakePageId && pagesForShop && pagesForShop.find(p => p.id == initialPancakePageId)) {
                $pageSelect.val(initialPancakePageId);
            } else if (initialPancakePageId && shopId && allPancakePagesData[shopId] && allPancakePagesData[shopId].find(p => p.id == initialPancakePageId)) {
                 //This case handles if initialPancakePageId was set but the initial shop didn't match, then user selects correct shop
                $pageSelect.val(initialPancakePageId);
            }

            $pageSelect.trigger('change.select2');
        });

        // Initial trigger if a shop is already selected on page load (for edit form)
        if ($('#pancake_shop_id').val()) {
            $('#pancake_shop_id').trigger('change');
        }

        // Toggle Shipping Fee display based on Free Shipping checkbox
        function toggleShippingFeeField() {
            const isFreeShippingChecked = $('#is_free_shipping').is(':checked');
            const $shippingFeeGroup = $('#shipping_fee_group');
            const $shippingFeeInput = $shippingFeeGroup.find('input[name="shipping_fee"]');

            if (isFreeShippingChecked) {
                $shippingFeeGroup.hide();
                $shippingFeeInput.val(0).prop('disabled', true); // Set to 0 and disable
            } else {
                $shippingFeeGroup.show();
                $shippingFeeInput.prop('disabled', false); // Re-enable
                // Optionally, restore a previous value if needed, or leave as is
            }
        }

        // Initial check on page load
        toggleShippingFeeField();

        // Event listener for checkbox change
        $('#is_free_shipping').on('change', function() {
            toggleShippingFeeField();
            calculateTotals(); // Recalculate totals when shipping fee might change
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
                                    text: response.message || 'Đã đẩy đơn hàng lên Pancake thành công.',
                                    icon: 'success'
                                });
                                // Don't reload page on edit view, just update button state
                                button.prop('disabled', false).html('<i class="fas fa-rocket fa-fw"></i>');
                            } else {
                                Swal.fire({
                                    title: 'Lỗi!',
                                    text: response.message || 'Có lỗi xảy ra khi đẩy đơn hàng.',
                                    icon: 'error'
                                });
                                button.prop('disabled', false).html('<i class="fas fa-rocket fa-fw"></i>');
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = 'Có lỗi xảy ra khi đẩy đơn hàng.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                title: 'Lỗi!',
                                text: errorMsg,
                                icon: 'error'
                            });
                            button.prop('disabled', false).html('<i class="fas fa-rocket fa-fw"></i>');
                        }
                    });
                }
            });
        });

        // Handle update on Pancake button click
        $('.update-on-pancake').click(function() {
            const orderId = $(this).data('order-id');
            const button = $(this);

            // Disable button and show loading state
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');

            // Make AJAX call to update order on Pancake
            $.ajax({
                url: `/orders/${orderId}/update-on-pancake`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: response.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: response?.message || 'Đã có lỗi xảy ra khi cập nhật đơn hàng lên Pancake.'
                    });
                },
                complete: function() {
                    // Re-enable button and restore original text
                    button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Cập nhật lên Pancake');
                }
            });
        });

        // If order has pancake_order_id, set up auto-update
        @if($order->pancake_order_id)
        $('#orderCreateForm').on('submit', function(e) {
            // Don't prevent form submission, but trigger Pancake update after successful save
            $(this).one('ajaxSuccess', function(event, xhr, settings) {
                $.ajax({
                    url: "{{ route('orders.updateOnPancake', $order->id) }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Đơn hàng đã được cập nhật trên Pancake');
                        } else {
                            toastr.error('Không thể cập nhật đơn hàng trên Pancake: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Lỗi khi cập nhật đơn hàng trên Pancake');
                    }
                });
            });
        });
        @endif
    });
    </script>
@endpush
