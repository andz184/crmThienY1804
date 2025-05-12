@extends('adminlte::page')

@section('title', 'Chỉnh sửa Hồ sơ')

@section('content_header')
    <h1>Chỉnh sửa Hồ sơ cá nhân</h1>
@stop

@section('content')
    {{-- Display session status messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            @php
                $statusMessage = '';
                switch (session('status')) {
                    case 'profile-updated':
                        $statusMessage = 'Hồ sơ đã được cập nhật.';
                        break;
                    case 'password-updated':
                        $statusMessage = 'Mật khẩu đã được cập nhật.';
                        break;
                    default:
                        $statusMessage = session('status');
                }
            @endphp
            {{ $statusMessage }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        {{-- Update Profile Information Card (Wider) --}}
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Thông tin Hồ sơ</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>
    </div>

     <div class="row">
        {{-- Update Password Card (Wider) --}}
        <div class="col-md-8">
            <div class="card card-warning">
                 <div class="card-header">
                    <h3 class="card-title">Đổi Mật khẩu</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>

@stop

{{-- Add CSS/JS if needed --}}
@section('css')
<style>
    .card-body > section > header { margin-bottom: 1.5rem; }
    /* Add other styles if needed */
</style>
@stop
