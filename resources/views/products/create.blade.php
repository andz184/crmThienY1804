@extends('adminlte::page')

@section('title', 'Thêm sản phẩm mới')

@section('content_header')
    <h1>Thêm sản phẩm mới</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug (tự động tạo nếu để trống)</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Danh mục</label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="base_price" class="form-label">Giá cơ bản</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('base_price') is-invalid @enderror" id="base_price" name="base_price" value="{{ old('base_price', '0.00') }}">
                            <span class="input-group-text">VNĐ</span>
                        </div>
                        @error('base_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Kích hoạt sản phẩm</label>
                        </div>
                        @error('is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="product_image" class="form-label">Ảnh sản phẩm</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="product_image" name="image" accept="image/*">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="image-preview" class="mt-2"></div>
                    </div>
                    <hr>
                    <h5>Biến thể sản phẩm</h5>
                    <div id="variations-list">
                        <!-- Biến thể sẽ được render ở đây bằng JS -->
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-2" id="add-variation-btn"><i class="fas fa-plus"></i> Thêm biến thể</button>
                    <hr>
                    <button type="submit" class="btn btn-success">Tạo sản phẩm</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Hủy</a>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview image before upload
    const imageInput = document.getElementById('product_image');
    const imagePreview = document.getElementById('image-preview');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">`;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    if (nameInput && slugInput) {
        nameInput.addEventListener('blur', function() {
            if (!slugInput.value) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9-]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });
    }
    // Quản lý biến thể động
    let variationIndex = 0;
    const variationsList = document.getElementById('variations-list');
    const addVariationBtn = document.getElementById('add-variation-btn');
    function renderVariationRow(idx) {
        return `
        <div class="card mb-2 variation-row" data-index="${idx}">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Tên biến thể</label>
                    <input type="text" name="variations[${idx}][name]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>SKU</label>
                    <input type="text" name="variations[${idx}][sku]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Giá</label>
                    <input type="number" step="0.01" name="variations[${idx}][price]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Số lượng kho</label>
                    <input type="number" name="variations[${idx}][stock_quantity]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Ảnh biến thể</label>
                    <input type="file" name="variations[${idx}][image]" class="form-control variation-image-input" accept="image/*">
                    <div class="variation-image-preview mt-1"></div>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm remove-variation-btn"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>`;
    }
    function addVariationRow() {
        variationsList.insertAdjacentHTML('beforeend', renderVariationRow(variationIndex));
        variationIndex++;
    }
    addVariationBtn.addEventListener('click', addVariationRow);
    variationsList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-variation-btn') || e.target.closest('.remove-variation-btn')) {
            const card = e.target.closest('.variation-row');
            card.remove();
        }
    });
    // Preview ảnh biến thể
    variationsList.addEventListener('change', function(e) {
        if (e.target.classList.contains('variation-image-input')) {
            const input = e.target;
            const preview = input.closest('.col-md-2').querySelector('.variation-image-preview');
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    preview.innerHTML = `<img src="${ev.target.result}" class="img-thumbnail" style="max-height: 60px;">`;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }
    });
});
</script>
@endpush
