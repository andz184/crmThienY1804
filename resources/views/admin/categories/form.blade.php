@csrf

<div class="form-group">
    <label for="name">Tên danh mục <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $category->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description">Mô tả</label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
              rows="3">{{ old('description', $category->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="parent_id">Danh mục cha</label>
    <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
        <option value="">-- Chọn danh mục cha --</option>
        @foreach($parentCategories as $id => $name)
            <option value="{{ $id }}" {{ old('parent_id', $category->parent_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
    @error('parent_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
               {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">Hoạt động</label>
    </div>
</div>

<div class="form-group">
    <button type="submit" class="btn btn-primary">
        {{ isset($category) ? 'Cập nhật' : 'Thêm mới' }}
    </button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Hủy</a>
</div>
