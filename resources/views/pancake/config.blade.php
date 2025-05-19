@extends('layouts.app')

@section('title', 'Cấu hình Pancake')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cấu hình Pancake API</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('pancake.config.update') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="api_key">API Key <span class="text-danger">*</span></label>
                            <input type="text" name="api_key" id="api_key" class="form-control @error('api_key') is-invalid @enderror" value="{{ old('api_key', $settings['api_key']) }}" required>
                            @error('api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Lấy từ Pancake: Cấu hình &rarr; Cấu hình ứng dụng &rarr; API KEY</small>
                        </div>

                        <div class="form-group">
                            <label for="shop_id">ID Cửa hàng mặc định</label>
                            <input type="text" name="shop_id" id="shop_id" class="form-control @error('shop_id') is-invalid @enderror" value="{{ old('shop_id', $settings['shop_id']) }}">
                            @error('shop_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="page_id">ID Trang mặc định</label>
                            <input type="text" name="page_id" id="page_id" class="form-control @error('page_id') is-invalid @enderror" value="{{ old('page_id', $settings['page_id']) }}">
                            @error('page_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="webhook_secret">Webhook Secret</label>
                            <input type="text" name="webhook_secret" id="webhook_secret" class="form-control @error('webhook_secret') is-invalid @enderror" value="{{ old('webhook_secret', $settings['webhook_secret']) }}">
                            @error('webhook_secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Để kích hoạt webhook từ Pancake, cấu hình URL: <code>{{ url('/api/webhooks/pancake/order') }}</code></small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                            <button type="submit" name="test_connection" value="1" class="btn btn-info">Kiểm tra kết nối</button>
                        </div>
                    </form>

                    @if(session('shop_info'))
                        <hr>
                        <h5>Thông tin cửa hàng trên Pancake:</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID Cửa hàng</th>
                                        <th>Tên cửa hàng</th>
                                        <th>Các trang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(session('shop_info') as $shop)
                                        <tr>
                                            <td>{{ $shop['id'] }}</td>
                                            <td>{{ $shop['name'] }}</td>
                                            <td>
                                                @if(count($shop['pages']) > 0)
                                                    <ul class="list-unstyled">
                                                        @foreach($shop['pages'] as $page)
                                                            <li><strong>ID:</strong> {{ $page['id'] }} - <strong>Tên:</strong> {{ $page['name'] }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <em>Không có trang</em>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Webhook URLs</h3>
                </div>
                <div class="card-body">
                    <p>Cấu hình các webhook sau trên Pancake:</p>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Loại</th>
                                <th>URL</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Đơn hàng</td>
                                <td><code>{{ url('/api/webhooks/pancake/order') }}</code></td>
                                <td>Nhận thông tin khi có đơn hàng mới hoặc cập nhật trạng thái đơn</td>
                            </tr>
                            <tr>
                                <td>Tồn kho</td>
                                <td><code>{{ url('/api/webhooks/pancake/stock') }}</code></td>
                                <td>Nhận thông tin khi có thay đổi tồn kho</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
