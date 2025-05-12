<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Đổi Mật khẩu') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Đảm bảo tài khoản của bạn đang sử dụng mật khẩu dài, ngẫu nhiên để duy trì bảo mật.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-group">
            <label for="update_password_current_password">{{ __('Mật khẩu hiện tại') }}</label>
            <input type="password" name="current_password" id="update_password_current_password"
                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                   autocomplete="current-password">
            @error('current_password', 'updatePassword')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <label for="update_password_password">{{ __('Mật khẩu mới') }}</label>
            <input type="password" name="password" id="update_password_password"
                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                   autocomplete="new-password">
            @error('password', 'updatePassword')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <label for="update_password_password_confirmation">{{ __('Xác nhận Mật khẩu mới') }}</label>
            <input type="password" name="password_confirmation" id="update_password_password_confirmation"
                   class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                   autocomplete="new-password">
             @error('password_confirmation', 'updatePassword')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group d-flex align-items-center gap-4 mt-4">
            <button type="submit" class="btn btn-warning">{{ __('Đổi Mật khẩu') }}</button>

            @if (session('status') === 'password-updated')
                 <span class="text-success ml-3">
                   <i class="fas fa-check"></i> {{ __('Đã lưu.') }}
               </span>
            @endif
        </div>
    </form>
</section>
