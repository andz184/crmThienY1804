@extends('adminlte::page')

@section('title', 'Tạo Đơn hàng mới')

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
        <form action="{{ route('orders.store') }}" method="POST" id="orderCreateForm">
            @csrf
            <div class="row">
                {{-- Left Column --}}
                <div class="col-md-8">
                    {{-- Products Section --}}
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart mr-2"></i>Sản phẩm
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="items_container">
                                <div class="row item-row mb-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="small font-weight-bold">Mã SP (Pancake Variation ID)</label>
                                        <div class="input-group">
                                            <input type="text" name="items[0][variation_id]" class="form-control variation-id" placeholder="Nhập mã sản phẩm">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary search-product">
                                                    <i class="fas fa-search"></i>
                                                </button>
                            </div>
                            </div>
                            </div>
                                    <div class="col-md-3">
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
                                    <div class="col-md-2">
                                        <input type="hidden" name="items[0][product_id]" value="">
                                        <input type="hidden" name="items[0][variation_info]" value="">
                                        <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                            </div>
                            </div>
                            </div>
                            <div class="text-left mt-3">
                                <button type="button" id="add_item_row" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Thêm sản phẩm
                            </button>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Section --}}
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Thanh toán</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_free_shipping" name="is_free_shipping" value="1">
                                            <label class="custom-control-label" for="is_free_shipping">Miễn phí giao hàng</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="customer_pay_fee" name="customer_pay_fee" value="1">
                                            <label class="custom-control-label" for="customer_pay_fee">Khách trả phí</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phí vận chuyển</label>
                                        <input type="number" name="shipping_fee" class="form-control" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Giảm giá đơn hàng</label>
                                        <input type="number" name="discount_amount" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                            <div class="form-group">
                                        <label>Tiền chuyển khoản</label>
                                        <input type="number" name="transfer_money" class="form-control" value="0">
                                    </div>
                                    </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phí đối tác</label>
                                        <input type="number" name="partner_fee" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>Tổng tiền:</th>
                                                <td class="text-right" id="total_amount">0 đ</td>
                                            </tr>
                                            <tr>
                                                <th>Giảm giá:</th>
                                                <td class="text-right text-success" id="discount_display">0 đ</td>
                                            </tr>
                                            <tr>
                                                <th>Sau giảm giá:</th>
                                                <td class="text-right" id="after_discount">0 đ</td>
                                            </tr>
                                            <tr>
                                                <th>Đã thanh toán:</th>
                                                <td class="text-right text-primary" id="paid_amount">0 đ</td>
                                            </tr>
                                            <tr>
                                                <th>Còn thiếu:</th>
                                                <td class="text-right text-danger" id="remaining_amount">0 đ</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes Section --}}
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">Ghi chú</h3>
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
                                    <textarea name="additional_notes" class="form-control" rows="3" placeholder="Ghi chú nội bộ"></textarea>
                                </div>
                                <div class="tab-pane" id="external_note" role="tabpanel">
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Ghi chú cho đối tác"></textarea>
                                    </div>
                                    </div>
                                    </div>
                                </div>
                            </div>

                {{-- Right Column --}}
                <div class="col-md-4">
                    {{-- Staff Assignment --}}
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin xử lý</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Thời gian tạo</label>
                                <input type="text" class="form-control" value="{{ now()->format('d/m/Y H:i') }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>NV xử lý</label>
                                <select name="assigning_seller_id" class="form-control select2" required>
                                    <option value="">-- Chọn nhân viên sale --</option>
                                    @foreach($users->whereNotNull('pancake_uuid') as $user)
                                        <option value="{{ $user->pancake_uuid }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>NV chăm sóc</label>
                                <select name="assigning_care_id" class="form-control select2" required>
                                    <option value="">-- Chọn nhân viên CSKH --</option>
                                    @foreach($users->whereNotNull('pancake_care_uuid') as $user)
                                        <option value="{{ $user->pancake_care_uuid }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                                    <div class="form-group">
                                <label>Marketer</label>
                                <select name="marketer_id" class="form-control select2">
                                    <option value="">-- Chọn Marketer --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                    {{-- Customer Information --}}
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user mr-2"></i>Thông tin khách hàng
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group position-relative">
                                <input type="text" name="customer_search" id="customer_search" class="form-control"
                                       placeholder="Tìm kiếm khách hàng theo SĐT hoặc tên">
                                <div id="customer_suggestions" class="position-absolute bg-white w-100" style="display:none; z-index: 1000;">
                                    <!-- Customer suggestions will be populated here -->
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Tên khách hàng">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="SĐT">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="email" name="customer_email" id="customer_email" class="form-control" placeholder="Email">
                            </div>
                        </div>
                    </div>

                    {{-- Shipping Information --}}
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin giao hàng</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Tỉnh/Thành phố</label>
                                <select name="province_code" class="form-control select2" required>
                                    <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                    @foreach($provinces as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quận/Huyện</label>
                                <select name="district_code" class="form-control select2" required disabled>
                                    <option value="">-- Chọn Quận/Huyện --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Phường/Xã</label>
                                <select name="ward_code" class="form-control select2" required disabled>
                                    <option value="">-- Chọn Phường/Xã --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Địa chỉ cụ thể</label>
                                <textarea name="street_address" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Đơn vị vận chuyển</label>
                                <select name="shipping_provider_id" class="form-control select2">
                                    <option value="">-- Chọn đơn vị vận chuyển --</option>
                                    @foreach($shippingProviders as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kho xuất hàng</label>
                                <select name="warehouse_id" class="form-control select2" required>
                                    <option value="">-- Chọn kho hàng --</option>
                                    @foreach($warehouses as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                <div class="form-check mb-3">
                        <input type="checkbox" name="push_to_pancake" id="push_to_pancake" class="form-check-input" value="1" checked>
                    <label for="push_to_pancake" class="form-check-label">Đẩy đơn hàng đến Pancake sau khi tạo</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Tạo đơn hàng</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại</a>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
    /* General form control improvements */
    .form-control,
    .select2-container--default .select2-selection--single {
        border-radius: 6px !important; /* Slightly less rounded than 8px for a tighter look */
        border: 1px solid #c0c4cc !important; /* Darker border for better visibility */
        height: 38px !important;
        box-shadow: none !important; /* Remove default shadows */
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out !important;
    }
    .form-control:focus,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.15) !important; /* Softer focus shadow */
    }

    /* Select2 specific styling */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px !important;
        color: #495057 !important; /* Standard text color */
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 5px !important; /* Adjust arrow position */
    }
    .select2-dropdown {
        border-radius: 6px !important;
        border: 1px solid #c0c4cc !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important; /* Slightly more pronounced shadow for dropdown */
    }
    .select2-search__field {
        border-radius: 4px !important;
        padding: 6px 8px !important;
        border: 1px solid #ced4da !important;
    }
        .select2-results__option {
        padding: 8px 12px !important;
    }
    .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
        color: white !important;
    }
    .select2-container .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important; /* Placeholder text color */
    }

    /* Customer suggestions styling - ensuring it's above other elements */
    #customer_suggestions {
        border: 1px solid #c0c4cc !important; /* Consistent border color */
        border-top: none !important; /* Avoid double border with input */
        border-radius: 0 0 6px 6px !important; /* Match bottom corners of input */
        max-height: 250px; /* Slightly reduced height */
            overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
        margin-top: -1px; /* Overlap slightly with the input field */
        background: white;
        width: 100%;
        position: absolute;
        z-index: 1051 !important; /* Ensure it's on top */
    }
    .suggestion-item {
        padding: 10px 12px; /* Increased padding */
        cursor: pointer;
        border-bottom: 1px solid #e9ecef; /* Lighter border for items */
        display: flex;
        align-items: center;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-item:hover {
        background-color: #e9ecef; /* Slightly darker hover */
    }
    .suggestion-item img {
        width: 36px; /* Slightly larger avatar */
        height: 36px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }
    .suggestion-item .customer-info {
        flex: 1;
    }
    .suggestion-item .customer-name {
        font-weight: 500;
        margin-bottom: 2px;
        color: #343a40; /* Darker name */
    }
    .suggestion-item .customer-meta {
        font-size: 0.8rem; /* Slightly smaller meta text */
        color: #6c757d;
    }
    .suggestion-item .purchase-info {
        font-size: 0.8rem;
        color: #6c757d;
        font-style: italic;
    }
    label {
        font-weight: 500 !important; /* Bolder labels */
        margin-bottom: 0.3rem !important;
        font-size: 0.875rem;
    }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').each(function() {
            $(this).select2({
            theme: 'bootstrap4',
                width: '100%',
                placeholder: $(this).attr('data-placeholder') || '-- Chọn --', // More generic placeholder
                allowClear: $(this).prop('multiple') ? false : true // Allow clear only for single selects
            });
        });

        // Customer search with debounce
        let searchTimeout;
        $('#customer_search').on('input', function() {
            const query = $(this).val().trim();
            const suggestionBox = $('#customer_suggestions');
            clearTimeout(searchTimeout);

            if (query.length >= 1) {
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
                                            const avatar = customer.avatar || '/images/default-avatar.png';
                                            const customerJson = JSON.stringify(customer).replace(/'/g, "&apos;").replace(/\"/g, "&quot;");

                                            html += `
                                                <div class="suggestion-item" data-customer='${customerJson}'>
                                                    <img src="${avatar}" alt="Avatar" onerror="this.onerror=null;this.src='/images/default-avatar.png';">
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
                                    if (html) {
                                        suggestionBox.html(html);
                                    } else {
                                        suggestionBox.html('<div class="p-2 text-center text-warning">Lỗi hiển thị dữ liệu gợi ý.</div>');
                                    }
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

        // Function to format currency (ensure it's available)
        function formatCurrency(amount) {
            if (isNaN(parseFloat(amount))) return '0 đ';
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        }

        // Handle customer selection (ensure customer data is correctly parsed)
        $(document).on('click', '.suggestion-item', function() {
            let customer;
            try {
                // The data-customer attribute should contain an HTML-escaped JSON string.
                // First, unescape HTML entities, then parse JSON.
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

            // Clear existing address selections first
            $('#province_code').val(null).trigger('change');
            $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true).trigger('change');
            $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true).trigger('change');
            $('textarea[name="street_address"]').val(customer.street_address || '');

            if (customer.province_code) {
                $('#province_code').val(customer.province_code).trigger('change');
                // Use a flag to prevent multiple triggers if district/ward data comes too fast or is pre-filled
                let districtLoaded = false;
                setTimeout(() => {
                    if (customer.district_code) {
                        $('#district_code').val(customer.district_code).trigger('change');
                        districtLoaded = true;
                        setTimeout(() => {
                            if (customer.ward_code) {
                                $('#ward_code').val(customer.ward_code).trigger('change');
                            }
                        }, 350); // Slightly longer for ward after district
                    }
                }, 350); // Wait for province to potentially load districts
            }
        }

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#customer_search, #customer_suggestions').length) {
                $('#customer_suggestions').slideUp(150);
            }
        });

        // Add null checks for location data population
        function populateSelect(selector, data, placeholder) {
            let options = `<option value="">${placeholder}</option>`;
            if (data && typeof data === 'object') { // Check if data is a valid object
                for (const [code, name] of Object.entries(data)) {
                    options += `<option value="${code}">${name}</option>`;
                }
            }
            $(selector).html(options);
        }

        // Ensure ajax routes for locations are correct
        function loadDistricts(province_code) {
            if (!province_code) {
                resetLocationSelects('district');
                return;
            }
            $.ajax({
                url: '/api/districts', // Using direct path
                type: 'GET',
                data: { province_code: province_code },
                dataType: 'json',
                success: function(data) {
                    populateSelect('#district_code', data, '-- Chọn Quận/Huyện --');
                    $('#district_code').prop('disabled', false);
                },
                error: function() {
                    console.error("Failed to load districts");
                    resetLocationSelects('district');
                }
            });
        }

        function loadWards(district_code) {
             if (!district_code) {
                resetLocationSelects('ward');
                return;
            }
            $.ajax({
                url: '/api/wards', // Using direct path
                type: 'GET',
                data: { district_code: district_code },
                dataType: 'json',
                success: function(data) {
                    populateSelect('#ward_code', data, '-- Chọn Phường/Xã --');
                    $('#ward_code').prop('disabled', false);
                },
                error: function() {
                    console.error("Failed to load wards");
                    resetLocationSelects('ward');
                }
            });
        }
        // Reset function
        function resetLocationSelects(fromLevel) {
            if (fromLevel === 'province' || fromLevel === 'district') {
                $('#district_code').empty().append('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true).trigger('change.select2');
            }
            if (fromLevel === 'province' || fromLevel === 'district' || fromLevel === 'ward') {
                 $('#ward_code').empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true).trigger('change.select2');
            }
        }
        // Initial call to setup placeholders for location if not pre-selected
        if (!$('#province_code').val()) {
            resetLocationSelects('province');
        } else if (!$('#district_code').val()) {
             resetLocationSelects('district');
        } else if (!$('#ward_code').val()) {
            resetLocationSelects('ward');
        }
    });
    </script>
@endpush
