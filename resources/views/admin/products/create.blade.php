@extends('adminlte::page')

@section('title', 'Thiết lập sản phẩm')

@section('content')
<div class="modal-content shadow-none">
    <div class="modal-header py-2">
        <h5 class="modal-title">Thiết lập sản phẩm</h5>
        <button type="button" class="close" onclick="window.location='{{ route('admin.products.index') }}'">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        <div class="modal-body py-2">
            <div class="row">
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-md-6 form-group mb-2">
                            <label for="sku" class="small mb-0">Mã sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('sku') is-invalid @enderror"
                                   id="sku" name="sku" value="{{ old('sku') }}" required>
                            @error('sku')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group mb-2">
                            <label for="name" class="small mb-0">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="description" class="small mb-0">Mô tả</label>
                        <textarea class="form-control form-control-sm" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group mb-2">
                        <label for="notes" class="small mb-0">Ghi chú nội bộ</label>
                        <textarea class="form-control form-control-sm" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="form-group mb-2">
                        <label for="category_id" class="small mb-0">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm select2 @error('category_id') is-invalid @enderror"
                                id="category_id" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-2">
                        <label for="warehouse_id" class="small mb-0">Kho thao tác</label>
                        <select class="form-control form-control-sm select2" id="warehouse_id" name="warehouse_id">
                            <option value="">Tất cả kho</option>
                            @foreach($warehouses as $id => $name)
                                <option value="{{ $id }}" {{ old('warehouse_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small mb-1">Thuộc tính sản phẩm</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="new_attribute_name" class="form-control" placeholder="Tên thuộc tính">
                            <input type="text" id="new_attribute_value" class="form-control" placeholder="Giá trị">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info btn-xs py-0 px-1" id="addAttribute" style="font-size: 0.7rem;">
                                    <i class="fas fa-plus"></i> Thêm
                                </button>
                            </div>
                        </div>
                        <div id="attributes-container" class="mt-1" style="max-height: 70px; overflow-y: auto;">
                            {{-- Attribute items will be appended here --}}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-1">
                <div class="col-md-4 form-group mb-1">
                    <div class="custom-control custom-switch custom-switch-sm">
                        <input type="checkbox" class="custom-control-input" id="track_inventory" name="track_inventory" value="1" {{ old('track_inventory', true) ? 'checked' : '' }}>
                        <label class="custom-control-label small" for="track_inventory">Tính tiền theo đơn vị</label>
                    </div>
                </div>
                <div class="col-md-4 form-group mb-1">
                    <div class="custom-control custom-switch custom-switch-sm">
                        <input type="checkbox" class="custom-control-input" id="print_name_on_order" name="print_name_on_order" value="1" {{ old('print_name_on_order', true) ? 'checked' : '' }}>
                        <label class="custom-control-label small" for="print_name_on_order">Che tên SP khi in</label>
                    </div>
                </div>
                <div class="col-md-4 form-group mb-1">
                    <div class="custom-control custom-switch custom-switch-sm">
                        <input type="checkbox" class="custom-control-input" id="sync_to_pancake" name="sync_to_pancake" value="1" {{ old('sync_to_pancake', true) ? 'checked' : '' }}>
                        <label class="custom-control-label small" for="sync_to_pancake">Đồng bộ lên Pancake</label>
                    </div>
                </div>
            </div>

            <hr class="my-2">

            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 font-weight-bold">Biến thể sản phẩm</h6>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-xs py-0 px-1" id="addVariation" style="font-size: 0.7rem;"><i class="fas fa-plus mr-1"></i>Thêm mẫu</button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-bordered table-hover table-sm" id="variationsTable">
                            <thead class="bg-light sticky-top" style="font-size: 0.8rem;">
                                <tr>
                                    <th style="width: 35px;">Ảnh</th>
                                    <th>Mã mẫu <span class="text-danger">*</span></th>
                                    <th>Mã vạch</th>
                                    <th>Giá nhập</th>
                                    <th>Giá bán <span class="text-danger">*</span></th>
                                    <th>Thuộc tính (VD:Đỏ,XL)</th>
                                    <th style="width: 70px;">Nặng(g)</th>
                                    <th style="width: 110px;">DxRxC(cm)</th>
                                    <th style="width: 30px;"></th>
                                </tr>
                            </thead>
                            <tbody id="variations-container" style="font-size: 0.8rem;">
                                {{-- Variation rows will be appended here --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer py-2 justify-content-between">
            <button type="button" class="btn btn-default btn-sm" onclick="window.location='{{ route('admin.products.index') }}'">
                <i class="fas fa-times mr-1"></i> Đóng
            </button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save mr-1"></i> Lưu sản phẩm
            </button>
        </div>
    </form>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
<style>
body {
    font-size: 0.875rem; /* Smaller base font for the page */
}
.modal-content {
    border-radius: 0.25rem; /* Slightly rounded corners */
    border: 1px solid #dee2e6;
}
.modal-title {
    font-size: 1.1rem;
}
.form-control-sm, .btn-sm, .custom-control-label.small, .btn-xs, .table-sm th, .table-sm td {
    font-size: 0.8rem; /* Smaller font for controls */
}
select.form-control-sm {
    height: calc(1.8125rem + 2px) !important;
}
.select2-container--bootstrap4 .select2-selection--single {
    height: calc(1.8125rem + 2px) !important;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
    line-height: 1.8125rem;
    font-size: 0.8rem;
}
.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
    height: 1.8125rem;
}
.table-sm th, .table-sm td {
    padding: 0.25rem; /* Compact table cells */
}
.custom-switch.custom-switch-sm .custom-control-label::before {
    height: 1.25rem;
    width: 2.25rem;
    border-radius: .625rem;
}
.custom-switch.custom-switch-sm .custom-control-label::after {
    width: calc(1.25rem - 4px);
    height: calc(1.25rem - 4px);
    border-radius: .625rem;
}
.sticky-top {
    position: -webkit-sticky; /* Safari */
    position: sticky;
    top: 0;
    z-index: 100; /* Ensure it's above other table content */
}
#attributes-container .badge { font-weight: normal; }
#attributes-container .attribute-item { margin-bottom: 0.25rem; }
#attributes-container .attribute-item .form-group { margin-bottom: 0.25rem; }
.table-responsive {
    border: 1px solid #dee2e6;
    border-top: none;
}
.table td .form-control-sm {
    padding: 0.1rem 0.25rem;
    height: calc(1.5em + .2rem + 2px);
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        minimumResultsForSearch: Infinity // Hide search box for simple selects
    });
    $('#category_id').select2({
        theme: 'bootstrap4',
        width: '100%',
    });

    // Attributes Management
    let attributeIndex = 0;
    function renderAttributes() {
        $('#attributes-container').empty();
        const attributesString = $('input[name="attributes_json"]').val();
        if (attributesString) {
            const attributes = JSON.parse(attributesString);
            attributes.forEach(attr => {
                const badge = `<span class="badge badge-secondary mr-1 mb-1 attribute-badge" data-name="${attr.name}" data-value="${attr.value}">${attr.name}: ${attr.value} <i class="fas fa-times ml-1 remove-badge-attr" style="cursor:pointer;"></i></span>`;
                $('#attributes-container').append(badge);
            });
        }
    }

    function updateAttributesInput() {
        const attributes = [];
        $('.attribute-badge').each(function() {
            attributes.push({ name: $(this).data('name'), value: $(this).data('value') });
        });
        // This hidden input will store the JSON string of attributes
        // We need to add this input to the form if it doesn't exist
        if ($('input[name="attributes_json"]').length === 0) {
            $('#productForm').append('<input type="hidden" name="attributes_json">');
        }
        $('input[name="attributes_json"]').val(JSON.stringify(attributes));
    }

    $('#addAttribute').click(function() {
        const name = $('#new_attribute_name').val().trim();
        const value = $('#new_attribute_value').val().trim();
        if (name && value) {
            const badge = `<span class="badge badge-secondary mr-1 mb-1 attribute-badge" data-name="${name}" data-value="${value}">${name}: ${value} <i class="fas fa-times ml-1 remove-badge-attr" style="cursor:pointer;"></i></span>`;
            $('#attributes-container').append(badge);
            $('#new_attribute_name').val('');
            $('#new_attribute_value').val('');
            updateAttributesInput();
        }
    });

    $(document).on('click', '.remove-badge-attr', function() {
        $(this).parent().remove();
        updateAttributesInput();
    });

    // Variations Management
    let variationCounter = $('#variations-container tr').length;

    function addVariationRow(variationData = {}) {
        const variationSku = variationData.sku || '';
        const variationBarcode = variationData.barcode || '';
        const variationCostPrice = variationData.cost_price || 0;
        const variationSellingPrice = variationData.selling_price || 0;
        const variationAttributesText = variationData.attributes_text || '';
        const variationWeight = variationData.weight || 0;
        const variationLength = variationData.length || 0;
        const variationWidth = variationData.width || 0;
        const variationHeight = variationData.height || 0;
        const imageUrl = variationData.image_url || '/images/no-image.png';

        const template = `
        <tr>
            <td>
                <div class="text-center">
                    <img src="${imageUrl}" class="img-thumbnail p-0 variation-img-preview" style="width: 30px; height: 30px; cursor:pointer;" onclick="$(this).next().click()">
                    <input type="file" name="variations[${variationCounter}][image]" class="d-none variation-image-input" accept="image/*">
                    <input type="hidden" name="variations[${variationCounter}][existing_image_url]" value="${variationData.image_url ? variationData.image_url : ''}">
                </div>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="variations[${variationCounter}][sku]" value="${variationSku}" required></td>
            <td><input type="text" class="form-control form-control-sm" name="variations[${variationCounter}][barcode]" value="${variationBarcode}"></td>
            <td><input type="number" class="form-control form-control-sm" name="variations[${variationCounter}][cost_price]" value="${variationCostPrice}" min="0" step="any"></td>
            <td><input type="number" class="form-control form-control-sm" name="variations[${variationCounter}][selling_price]" value="${variationSellingPrice}" min="0" step="any" required></td>
            <td><input type="text" class="form-control form-control-sm" name="variations[${variationCounter}][attributes_text]" value="${variationAttributesText}" placeholder="VD:Đỏ,XL"></td>
            <td><input type="number" class="form-control form-control-sm" name="variations[${variationCounter}][weight]" value="${variationWeight}" min="0"></td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control px-1" name="variations[${variationCounter}][length]" value="${variationLength}" min="0" step="0.1" placeholder="D">
                    <input type="number" class="form-control px-1" name="variations[${variationCounter}][width]" value="${variationWidth}" min="0" step="0.1" placeholder="R">
                    <input type="number" class="form-control px-1" name="variations[${variationCounter}][height]" value="${variationHeight}" min="0" step="0.1" placeholder="C">
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-xs py-0 px-1 remove-variation" style="font-size: 0.7rem;">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;
        $('#variations-container').append(template);
        variationCounter++;
    }

    $('#addVariation').click(function() { addVariationRow(); });

    $(document).on('change', '.variation-image-input', function(e) {
        const imgPreview = $(this).siblings('.variation-img-preview');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                imgPreview.attr('src', event.target.result);
            }
            reader.readAsDataURL(this.files[0]);
            $(this).next('input[name$="[existing_image_url]"]').val(''); // Clear existing image if new one is chosen
        } else {
            // If no file is selected, and there was an existing image, revert to it.
            // Otherwise, revert to no-image.png
            const existingImageUrl = $(this).next('input[name$="[existing_image_url]"]').val();
            imgPreview.attr('src', existingImageUrl || '/images/no-image.png');
        }
    });

    $(document).on('click', '.remove-variation', function() {
        $(this).closest('tr').remove();
        if ($('#variations-container tr').length === 0) {
            // Optionally, add a new empty row if all are removed
            // addVariationRow();
        }
    });

    // Add initial rows for old input or one default row
    const oldVariations = {!! json_encode(old('variations', [])) !!};
    if (oldVariations && oldVariations.length > 0) {
        oldVariations.forEach(function(variation) {
            addVariationRow(variation);
        });
    } else {
        addVariationRow(); // Add one default row if no old input
    }

    // Load old attributes if any
    const oldAttributesJson = '{{ old("attributes_json", "[]") }}';
    if (oldAttributesJson) {
        $('input[name="attributes_json"]').val(oldAttributesJson);
        renderAttributes();
    }

    // Auto-populate product SKU to the first variation SKU
    $('#sku').on('input', function() {
        const productSku = $(this).val();
        const firstVariationSkuInput = $('#variations-container tr:first-child input[name$="[sku]"]');
        if (firstVariationSkuInput.length && (firstVariationSkuInput.val() === '' || firstVariationSkuInput.data('is-default-sku'))) {
            firstVariationSkuInput.val(productSku);
            firstVariationSkuInput.data('is-default-sku', true); // Mark it as auto-filled
        }
    });
    $('#variations-container').on('input', 'tr:first-child input[name$="[sku]"]', function() {
        $(this).data('is-default-sku', false); // User manually changed it
    });

    // Form submission validation
    $('#productForm').submit(function(e) {
        let variationsValid = true;
        if ($('#variations-container tr').length === 0) {
             alert('Sản phẩm phải có ít nhất một biến thể.');
             e.preventDefault();
             return false;
        }
        $('#variations-container tr').each(function(index) {
            const skuInput = $(this).find('input[name="variations['+index+'][sku]"]');
            const priceInput = $(this).find('input[name="variations['+index+'][selling_price]"]');

            if (!skuInput.val()) {
                variationsValid = false;
                skuInput.addClass('is-invalid');
            } else {
                skuInput.removeClass('is-invalid');
            }

            if (!priceInput.val() || parseFloat(priceInput.val()) <= 0) {
                variationsValid = false;
                priceInput.addClass('is-invalid');
            } else {
                priceInput.removeClass('is-invalid');
            }
        });

        if (!variationsValid) {
            e.preventDefault();
            alert('Vui lòng nhập Mã mẫu và Giá bán (lớn hơn 0) hợp lệ cho tất cả các biến thể.');
            return false;
        }
        updateAttributesInput(); // Ensure attributes are up-to-date before submitting
    });

});
</script>
@stop
