@extends('adminlte::page')

@section('title', 'Cài đặt Website')

@section('content_header')
    <h1>Cài đặt Website</h1>
@stop

@section('content')

    @include('layouts.partials.alert') {{-- Include your alert partial --}}

    @php
        $canFavicon = auth()->user()->can('settings.manage_favicon');
        $canSeo = auth()->user()->can('settings.manage_seo');
        $canCache = auth()->user()->can('settings.clear_cache');
        $canUpdateSomething = $canFavicon || $canSeo || $canCache;
    @endphp

    @if(!$canUpdateSomething)
        <div class="alert alert-warning">Bạn không có quyền thay đổi bất kỳ cài đặt nào.</div>
    @else
        <div class="row">
            <div class="col-md-8">

                {{-- General & Favicon Settings Card --}}
                @if($canFavicon)
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card card-primary card-outline mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Cài đặt chung & Favicon</h3>
                            </div>
                            <div class="card-body">
                                {{-- App Name --}}
                                <div class="form-group">
                                    <label for="app_name">Tên Website</label>
                                    <input type="text" name="app_name" id="app_name"
                                           class="form-control @error('app_name') is-invalid @enderror"
                                           value="{{ old('app_name', $settings['app_name'] ?? config('app.name')) }}" required>
                                    @error('app_name')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                {{-- Favicon Upload --}}
                                <div class="form-group">
                                    <label for="favicon">Favicon</label>
                                    @if(!empty($settings['favicon_url']))
                                        <div class="mb-2">
                                            <img src="{{ $settings['favicon_url'] }}" alt="Current Favicon" class="img-thumbnail" style="max-height: 32px;">
                                            <small class="text-muted d-block">Favicon hiện tại</small>
                                        </div>
                                    @endif
                                    <div class="custom-file">
                                        <input type="file" name="favicon" id="favicon" class="custom-file-input @error('favicon') is-invalid @enderror" accept="image/png, image/vnd.microsoft.icon, image/x-icon, image/jpeg, image/gif">
                                        <label class="custom-file-label" for="favicon">Chọn file mới (.ico, .png, .jpg, ...)</label>
                                    </div>
                                    @error('favicon')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Lưu cài đặt Chung</button>
                            </div>
                        </div>
                    </form>
                @endif

                {{-- SEO Settings Card --}}
                @if($canSeo)
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card card-info card-outline mb-4">
                            <div class="card-header">
                                <h3 class="card-title">Cài đặt SEO</h3>
                            </div>
                            <div class="card-body">
                                {{-- SEO Title --}}
                                <div class="form-group">
                                    <label for="seo_meta_title">Meta Title mặc định</label>
                                    <input type="text" name="seo_meta_title" id="seo_meta_title"
                                           class="form-control @error('seo_meta_title') is-invalid @enderror"
                                           value="{{ old('seo_meta_title', $settings['seo_meta_title'] ?? '') }}">
                                    @error('seo_meta_title')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                {{-- SEO Description --}}
                                <div class="form-group">
                                    <label for="seo_meta_description">Meta Description mặc định</label>
                                    <textarea name="seo_meta_description" id="seo_meta_description"
                                              class="form-control @error('seo_meta_description') is-invalid @enderror"
                                              rows="3">{{ old('seo_meta_description', $settings['seo_meta_description'] ?? '') }}</textarea>
                                    @error('seo_meta_description')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-info">Lưu cài đặt SEO</button>
                            </div>
                        </div>
                    </form>
                @endif

            </div> {{-- /.col-md-8 --}}

            <div class="col-md-4">
                {{-- Cache Management Card --}}
                @if($canCache)
                    <div class="card card-warning card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Quản lý Cache</h3>
                        </div>
                        <div class="card-body">
                            <p>Xóa bộ nhớ đệm có thể giải quyết một số vấn đề hiển thị hoặc dữ liệu cũ.</p>
                            <form action="{{ route('admin.settings.clearCache') }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa tất cả cache hệ thống?');">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block">Xóa tất cả Cache</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div> {{-- /.col-md-4 --}}
        </div> {{-- /.row --}}
    @endif
@stop

@push('js')
<script>
    // Script to update custom file input label
    $(document).ready(function(){
        $('.custom-file-input').on("change", function() {
            var fileName = $(this).val().split("\\").pop();
             if (!fileName) {
                 fileName = "Chọn file mới (.ico, .png, .jpg, ...)"; // Reset if no file selected
            }
            $(this).next('.custom-file-label').html(fileName);
        });
    });
</script>
@endpush
