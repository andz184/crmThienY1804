@extends('adminlte::page')

@section('title', isset($product) ? 'Chỉnh sửa sản phẩm' : 'Thiết lập sản phẩm')

@section('content')
<div class="container-fluid">
    <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="productForm">
        @csrf
        @if(isset($product))
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ isset($product) ? 'Chỉnh sửa sản phẩm' : 'Thiết lập sản phẩm' }}</h5>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="sku">Mã sản phẩm <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('sku') is-invalid @enderror"
                                   id="sku"
                                   name="sku"
                                   value="{{ old('sku', $product->sku ?? '') }}"
                                   required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $product->name ?? '') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="keyword">Keyword</label>
                            <input type="text"
                                   class="form-control"
                                   id="keyword"
                                   name="keyword"
                                   value="{{ old('keyword', $product->keyword ?? '') }}">
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả</label>
                            <textarea class="form-control"
                                     id="description"
                                     name="description"
                                     rows="3">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="notes">Ghi chú nội bộ</label>
                            <textarea class="form-control"
                                     id="notes"
                                     name="notes"
                                     rows="3">{{ old('notes', $product->notes ?? '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="track_inventory"
                                       name="track_inventory"
                                       value="1"
                                       {{ old('track_inventory', $product->track_inventory ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="track_inventory">
                                    Tính tiền theo đơn vị
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="print_name_on_order"
                                       name="print_name_on_order"
                                       value="1"
                                       {{ old('print_name_on_order', $product->print_name_on_order ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="print_name_on_order">
                                    Che tên sản phẩm khi in đơn
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="hide_in_pos"
                                       name="hide_in_pos"
                                       value="1"
                                       {{ old('hide_in_pos', $product->hide_in_pos ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="hide_in_pos">
                                    Không in sản phẩm khi in đơn
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="category_id">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-control select2"
                                    id="category_id"
                                    name="category_id"
                                    required>
                                <option value="">Chọn danh mục sản phẩm</option>
                                @foreach($categories as $id => $name)
                                    <option value="{{ $id }}"
                                            {{ old('category_id', $product->category_id ?? '') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="tags">Thẻ</label>
                            <select class="form-control select2"
                                    id="tags"
                                    name="tags[]"
                                    multiple>
                                @foreach($tags as $id => $name)
                                    <option value="{{ $id }}"
                                            {{ in_array($id, old('tags', $product->tags->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="warehouse_id">Kho thao tác</label>
                            <select class="form-control select2"
                                    id="warehouse_id"
                                    name="warehouse_id">
                                <option value="">Tất cả kho</option>
                                @foreach($warehouses as $id => $name)
                                    <option value="{{ $id }}"
                                            {{ old('warehouse_id', $product->warehouse_id ?? '') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="supplier_id">Nhà cung cấp</label>
                            <select class="form-control select2"
                                    id="supplier_id"
                                    name="supplier_id">
                                <option value="">Chọn nhà cung cấp sản phẩm</option>
                                @foreach($suppliers as $id => $name)
                                    <option value="{{ $id }}"
                                            {{ old('supplier_id', $product->supplier_id ?? '') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="supplier_link">Link nhập hàng</label>
                            <input type="url"
                                   class="form-control"
                                   id="supplier_link"
                                   name="supplier_link"
                                   placeholder="Enter để thêm link sản phẩm"
                                   value="{{ old('supplier_link', $product->supplier_link ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3">Thuộc tính sản phẩm</h5>
                        <div class="text-right mb-3">
                            <button type="button" class="btn btn-primary" id="addAttribute">
                                <i class="fas fa-plus"></i> Thêm thuộc tính
                            </button>
                        </div>

                        <div id="attributes-container">
                            @if(isset($product) && $product->attributes->count() > 0)
                                @foreach($product->attributes as $index => $attribute)
                                    <div class="attribute-item card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-11">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <input type="text"
                                                                       class="form-control"
                                                                       name="attributes[{{ $index }}][name]"
                                                                       placeholder="Tên thuộc tính"
                                                                       value="{{ $attribute->name }}"
                                                                       required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <input type="text"
                                                                       class="form-control"
                                                                       name="attributes[{{ $index }}][value]"
                                                                       placeholder="Giá trị"
                                                                       value="{{ $attribute->value }}"
                                                                       required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button"
                                                            class="btn btn-danger remove-attribute">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3">Biến thể sản phẩm</h5>
                        <div class="text-right mb-3">
                            <button type="button" class="btn btn-primary" id="addVariation">
                                <i class="fas fa-plus"></i> Thêm mẫu mã
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">Hình ảnh</th>
                                        <th>Mã mẫu</th>
                                        <th>Mã vạch</th>
                                        <th>Keyword</th>
                                        <th>Giá nhập cuối</th>
                                        <th>Giá bán</th>
                                        <th>Thuộc tính</th>
                                        <th style="width: 100px">Trọng lượng (g)</th>
                                        <th style="width: 100px">Tổng nhập</th>
                                        <th style="width: 100px">Có thể bán</th>
                                        <th style="width: 100px">Tồn kho</th>
                                        <th style="width: 100px">Số lỗi</th>
                                        <th style="width: 100px">Đang hoàn</th>
                                        <th>D x R x C(cm)</th>
                                        <th style="width: 100px">SP đầu thành</th>
                                        <th style="width: 50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="variations-container">
                                    @if(isset($product) && $product->variations->count() > 0)
                                        @foreach($product->variations as $index => $variation)
                                            <tr class="variation-row">
                                                <td>
                                                    <div class="image-upload">
                                                        <label for="variation-image-{{ $index }}">
                                                            <img src="{{ $variation->image ?? asset('images/no-image.png') }}"
                                                                 class="img-thumbnail"
                                                                 style="width: 50px; height: 50px; cursor: pointer;">
                                                        </label>
                                                        <input type="file"
                                                               id="variation-image-{{ $index }}"
                                                               name="variations[{{ $index }}][image]"
                                                               accept="image/*"
                                                               class="d-none variation-image-input">
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][sku]"
                                                           value="{{ $variation->sku }}"
                                                           required>
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][barcode]"
                                                           value="{{ $variation->barcode }}">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][keyword]"
                                                           value="{{ $variation->keyword }}">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][cost_price]"
                                                           value="{{ $variation->cost_price }}"
                                                           min="0">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][selling_price]"
                                                           value="{{ $variation->selling_price }}"
                                                           required
                                                           min="0">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][attributes]"
                                                           value="{{ $variation->attributes }}">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][weight]"
                                                           value="{{ $variation->weight }}"
                                                           min="0">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][total_import]"
                                                           value="{{ $variation->total_import }}"
                                                           readonly>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][available_quantity]"
                                                           value="{{ $variation->available_quantity }}"
                                                           readonly>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][stock_quantity]"
                                                           value="{{ $variation->stock_quantity }}"
                                                           readonly>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][defective_quantity]"
                                                           value="{{ $variation->defective_quantity }}"
                                                           readonly>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control"
                                                           name="variations[{{ $index }}][returning_quantity]"
                                                           value="{{ $variation->returning_quantity }}"
                                                           readonly>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="number"
                                                               class="form-control"
                                                               name="variations[{{ $index }}][length]"
                                                               value="{{ $variation->length }}"
                                                               min="0"
                                                               step="0.1"
                                                               style="width: 60px">
                                                        <input type="number"
                                                               class="form-control"
                                                               name="variations[{{ $index }}][width]"
                                                               value="{{ $variation->width }}"
                                                               min="0"
                                                               step="0.1"
                                                               style="width: 60px">
                                                        <input type="number"
                                                               class="form-control"
                                                               name="variations[{{ $index }}][height]"
                                                               value="{{ $variation->height }}"
                                                               min="0"
                                                               step="0.1"
                                                               style="width: 60px">
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-info btn-sm"
                                                            data-toggle="modal"
                                                            data-target="#compositeModal-{{ $index }}">
                                                        <i class="fas fa-cubes"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-danger btn-sm remove-variation">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> {{ isset($product) ? 'Cập nhật' : 'Lưu' }}
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template for new variation row -->
<template id="variation-template">
    <tr class="variation-row">
        <td>
            <div class="image-upload">
                <label for="variation-image-{index}">
                    <img src="{{ asset('images/no-image.png') }}"
                         class="img-thumbnail"
                         style="width: 50px; height: 50px; cursor: pointer;">
                </label>
                <input type="file"
                       id="variation-image-{index}"
                       name="variations[{index}][image]"
                       accept="image/*"
                       class="d-none variation-image-input">
            </div>
        </td>
        <td>
            <input type="text"
                   class="form-control"
                   name="variations[{index}][sku]"
                   required>
        </td>
        <td>
            <input type="text"
                   class="form-control"
                   name="variations[{index}][barcode]">
        </td>
        <td>
            <input type="text"
                   class="form-control"
                   name="variations[{index}][keyword]">
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][cost_price]"
                   min="0">
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][selling_price]"
                   required
                   min="0">
        </td>
        <td>
            <input type="text"
                   class="form-control"
                   name="variations[{index}][attributes]">
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][weight]"
                   min="0">
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][total_import]"
                   value="0"
                   readonly>
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][available_quantity]"
                   value="0"
                   readonly>
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][stock_quantity]"
                   value="0"
                   readonly>
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][defective_quantity]"
                   value="0"
                   readonly>
        </td>
        <td>
            <input type="number"
                   class="form-control"
                   name="variations[{index}][returning_quantity]"
                   value="0"
                   readonly>
        </td>
        <td>
            <div class="input-group">
                <input type="number"
                       class="form-control"
                       name="variations[{index}][length]"
                       min="0"
                       step="0.1"
                       style="width: 60px">
                <input type="number"
                       class="form-control"
                       name="variations[{index}][width]"
                       min="0"
                       step="0.1"
                       style="width: 60px">
                <input type="number"
                       class="form-control"
                       name="variations[{index}][height]"
                       min="0"
                       step="0.1"
                       style="width: 60px">
            </div>
        </td>
        <td>
            <button type="button"
                    class="btn btn-info btn-sm"
                    data-toggle="modal"
                    data-target="#compositeModal-{index}">
                <i class="fas fa-cubes"></i>
            </button>
        </td>
        <td>
            <button type="button"
                    class="btn btn-danger btn-sm remove-variation">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
<style>
.image-upload img:hover {
    opacity: 0.7;
}
.input-group input {
    border-radius: 0;
}
.input-group input:first-child {
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}
.input-group input:last-child {
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Handle variation image preview
    $(document).on('change', '.variation-image-input', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            const img = $(this).siblings('label').find('img');

            reader.onload = function(e) {
                img.attr('src', e.target.result);
            }

            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // Add new variation
    let variationIndex = {{ isset($product) ? $product->variations->count() : 0 }};
    $('#addVariation').click(function() {
        const template = $('#variation-template').html();
        const newRow = template.replace(/{index}/g, variationIndex);
        $('#variations-container').append(newRow);
        variationIndex++;
    });

    // Remove variation
    $(document).on('click', '.remove-variation', function() {
        const container = $('#variations-container');
        if (container.find('.variation-row').length > 1) {
            $(this).closest('.variation-row').remove();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Không thể xóa',
                text: 'Sản phẩm phải có ít nhất một mẫu mã'
            });
        }
    });

    // Add new attribute
    let attributeIndex = {{ isset($product) ? $product->attributes->count() : 0 }};
    $('#addAttribute').click(function() {
        const template = `
            <div class="attribute-item card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-11">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text"
                                               class="form-control"
                                               name="attributes[${attributeIndex}][name]"
                                               placeholder="Tên thuộc tính"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text"
                                               class="form-control"
                                               name="attributes[${attributeIndex}][value]"
                                               placeholder="Giá trị"
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button"
                                    class="btn btn-danger remove-attribute">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#attributes-container').append(template);
        attributeIndex++;
    });

    // Remove attribute
    $(document).on('click', '.remove-attribute', function() {
        $(this).closest('.attribute-item').remove();
    });

    // Form validation
    $('#productForm').submit(function(e) {
        let isValid = true;
        const requiredFields = $(this).find('input[required], select[required]');

        requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Vui lòng điền đầy đủ thông tin bắt buộc'
            });
        }
    });
});
</script>
@stop
