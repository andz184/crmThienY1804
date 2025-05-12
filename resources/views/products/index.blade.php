@extends('adminlte::page')

@section('title', 'Danh sách sản phẩm')

@section('content_header')
    <h1>Danh sách sản phẩm</h1>
@stop

@section('content')
<div class="container-fluid">
    {{-- Thông báo --}}
    @include('partials._alerts')

    {{-- Bộ lọc và Tìm kiếm --}}
    <div class="card card-outline card-info mb-3">
        <div class="card-header">
            <h3 class="card-title">Bộ lọc Sản phẩm</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.products.index') }}" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label for="search" class="mr-1">Tìm kiếm:</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Tên, slug, SKU..." value="{{ request('search') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="category_id" class="mr-1">Danh mục:</label>
                    <select name="category_id" id="category_id" class="form-control form-control-sm">
                        <option value="">-- Tất cả danh mục --</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="is_active" class="mr-1">Trạng thái:</label>
                    <select name="is_active" id="is_active" class="form-control form-control-sm">
                        <option value="">-- Tất cả --</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Kích hoạt</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Vô hiệu hóa</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_from" class="mr-1">Từ ngày tạo:</label>
                    <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label for="date_to" class="mr-1">Đến ngày tạo:</label>
                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2">Lọc</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm mb-2">Xóa lọc</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách Sản phẩm</h3>
            <div class="card-tools">
                @can('products.create') {{-- Hoặc dùng route name admin.products.create --}}
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Biến thể (SKU - Giá - Kho)</th>
                            <th>Giá cơ bản</th>
                            <th>Kích hoạt</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td style="width: 80px;">
                                    @if($product->image) {{-- Giả sử có trường image --}}
                                        <img src="{{ asset('storage/' . $product->image) }}" class="img-thumbnail" style="max-width: 70px; max-height: 70px; object-fit: cover;">
                                    @else
                                        <span class="text-muted small">Không có ảnh</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.show', $product) }}"><strong>{{ $product->name }}</strong></a><br>
                                    <small class="text-muted">{{ $product->slug }}</small>
                                </td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td style="min-width: 200px;">
                                    @if ($product->variations->count() > 0)
                                        <ul class="list-unstyled mb-0 small">
                                        @foreach ($product->variations->take(3) as $variation)
                                            <li>
                                                <span class="badge bg-info">{{ $variation->sku }}</span> -
                                                {{ Str::limit($variation->name, 20) }}:
                                                <span class="text-success">{{ number_format($variation->price,0) }}đ</span>
                                                (Kho: {{ $variation->stock_quantity }})
                                                {!! $variation->is_active ? '<span class="badge bg-success ms-1">ON</span>' : '<span class="badge bg-danger ms-1">OFF</span>' !!}
                                            </li>
                                        @endforeach
                                        @if ($product->variations->count() > 3)
                                            <li class="text-muted">... và {{ $product->variations->count() - 3 }} biến thể khác</li>
                                        @endif
                                        </ul>
                                    @else
                                        <span class="text-muted small">Không có biến thể</span>
                                    @endif
                                </td>
                                <td><span class="text-primary fw-bold">{{ number_format($product->base_price, 0, '.', ',') }}đ</span></td>
                                <td>
                                    @if ($product->is_active)
                                        <span class="badge bg-success">Có</span>
                                    @else
                                        <span class="badge bg-danger">Không</span>
                                    @endif
                                </td>
                                <td>{{ $product->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.products.show', $product) }}" class="btn btn-xs btn-info" title="Xem"><i class="fas fa-eye"></i></a>
                                    @can('products.edit') {{-- Hoặc dùng route name admin.products.edit --}}
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-xs btn-warning" title="Sửa"><i class="fas fa-edit"></i></a>
                                    @endcan
                                    @can('products.delete') {{-- Hoặc dùng route name admin.products.destroy --}}
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này và toàn bộ biến thể của nó?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Không tìm thấy sản phẩm nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $products->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@stop
