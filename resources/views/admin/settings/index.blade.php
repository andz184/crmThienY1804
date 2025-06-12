@extends('adminlte::page')

@section('title', 'Cài đặt Website')

@section('content_header')
    <h1>Cài đặt Website</h1>
@stop

@section('content')

    @include('layouts.partials.alert') {{-- Include your alert partial --}}

    @php
        $canFavicon = auth()->user()->can('settings.manage_favicon');
        $canCache = auth()->user()->can('settings.clear_cache');
        $canUpdateSomething = $canFavicon || $canCache;
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
                                <h3 class="card-title">Cài đặt chung, Logo & Favicon</h3>
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

                                {{-- Logo Upload --}}
                                <div class="form-group">
                                    <label for="app_logo">Logo Website</label>
                                    <div class="mb-2" style="padding: 10px; border-radius: 5px; display: inline-block; background-color: #343a40;">
                                        <img src="{{ $settings['app_logo_url'] ?? '' }}" id="logo-preview" alt="Logo preview" class="img-thumbnail" style="max-height: 40px; background: transparent; border: none; @if(empty($settings['app_logo_url'])) display: none; @endif">
                                    </div>
                                    <small class="text-muted d-block">
                                        @if(!empty($settings['app_logo_url']))
                                            Logo hiện tại (hiển thị tốt nhất trên nền tối)
                                        @else
                                            Chưa có logo, vui lòng tải lên.
                                        @endif
                                    </small>
                                    <div class="custom-file mt-2">
                                        <input type="file" name="app_logo" id="app_logo" class="custom-file-input @error('app_logo') is-invalid @enderror" accept="image/png, image/jpeg, image/gif, image/svg+xml">
                                        <label class="custom-file-label" for="app_logo">Chọn file logo mới</label>
                                    </div>
                                    @error('app_logo')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                {{-- Favicon Upload --}}
                                <div class="form-group">
                                    <label for="favicon">Favicon</label>
                                    <div class="mb-2">
                                        <img src="{{ $settings['favicon_url'] ?? '' }}" id="favicon-preview" alt="Favicon preview" class="img-thumbnail" style="max-height: 32px; @if(empty($settings['favicon_url'])) display: none; @endif">
                                        <small class="text-muted d-block">
                                            @if(!empty($settings['favicon_url']))
                                                Favicon hiện tại
                                            @else
                                                Chưa có favicon, vui lòng tải lên.
                                            @endif
                                        </small>
                                    </div>
                                    <div class="custom-file mt-2">
                                        <input type="file" name="favicon" id="favicon" class="custom-file-input @error('favicon') is-invalid @enderror" accept="image/png, image/vnd.microsoft.icon, image/x-icon, image/jpeg, image/gif">
                                        <label class="custom-file-label" for="favicon">Chọn file mới (.ico, .png, .jpg, ...)</label>
                                    </div>
                                    @error('favicon')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Lưu Cài đặt Chung & Hình ảnh</button>
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
    $(document).ready(function () {
        function setupImagePreview(inputId, previewId) {
            $('#' + inputId).on('change', function(event) {
                // Update the file input label with the chosen file name
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);

                // Show image preview
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#' + previewId).attr('src', e.target.result).show();
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        setupImagePreview('app_logo', 'logo-preview');
        setupImagePreview('favicon', 'favicon-preview');
    });
</script>
@endpush
