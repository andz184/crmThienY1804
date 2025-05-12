<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="form-group">
            <label for="name">{{ __('Tên') }}</label>
            <input type="text" name="name" id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">{{ __('Email') }}</label>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-muted">
                        {{ __('Địa chỉ email của bạn chưa được xác minh.') }}
                        <button form="send-verification" class="btn btn-xs btn-link p-0 m-0 align-baseline">{{ __('Nhấn vào đây để gửi lại email xác minh.') }}</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium text-sm text-success">
                            {{ __('Một liên kết xác minh mới đã được gửi đến địa chỉ email của bạn.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Certificate Upload Field --}}
        <div class="form-group">
            <label for="certificate">{{ __('Bằng cấp/Chứng chỉ (Tùy chọn)') }}</label>
             {{-- Preview Placeholder --}}
            <img id="certificate-preview" src="{{ $user->certificate_path ? Storage::url($user->certificate_path) : '#' }}"
                 alt="Xem trước bằng cấp"
                 class="img-thumbnail mt-2 mb-2 {{ $user->certificate_path ? '' : 'd-none' }}"
                 style="max-height: 150px;" />
            <div class="custom-file">
                 <input type="file" name="certificate" id="certificate"
                        class="custom-file-input @error('certificate') is-invalid @enderror" accept="image/*,application/pdf">
                 <label class="custom-file-label" for="certificate">{{ $user->certificate_path ? basename($user->certificate_path) : __('Chọn file...') }}</label>
            </div>
             @error('certificate')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        {{-- ID Card Upload Field --}}
        <div class="form-group">
            <label for="id_card">{{ __('Căn cước công dân (Tùy chọn)') }}</label>
            {{-- Preview Placeholder --}}
            <img id="id_card-preview" src="{{ $user->id_card_path ? Storage::url($user->id_card_path) : '#' }}"
                 alt="Xem trước căn cước"
                 class="img-thumbnail mt-2 mb-2 {{ $user->id_card_path ? '' : 'd-none' }}"
                 style="max-height: 150px;"/>
            <div class="custom-file">
                 <input type="file" name="id_card" id="id_card"
                        class="custom-file-input @error('id_card') is-invalid @enderror" accept="image/*">
                 <label class="custom-file-label" for="id_card">{{ $user->id_card_path ? basename($user->id_card_path) : __('Chọn file...') }}</label>
            </div>
             @error('id_card')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        <div class="form-group d-flex align-items-center gap-4 mt-4">
            <button type="submit" class="btn btn-primary">{{ __('Lưu Thay đổi') }}</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success ml-3">
                   <i class="fas fa-check"></i> {{ __('Đã lưu.') }}
               </span>
            @endif
        </div>
    </form>
</section>

@push('js')
<script>
    function previewFile(input, previewId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);

        if (file) {
            // Only preview images
            if (file.type.startsWith('image/')) {
                 const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                // If not an image (e.g., PDF for certificate), hide preview
                 preview.src = '#';
                 preview.classList.add('d-none');
                 // Optionally display a placeholder or message for non-image files
                 console.log('File type not supported for preview: ' + file.type);
            }
        } else {
            // No file selected, hide preview
            preview.src = '#';
            preview.classList.add('d-none');
        }
    }

    $(document).ready(function(){
        $('.custom-file-input').on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            if (!fileName) {
                 fileName = "Chọn file..."; // Reset if no file selected
            }
            $(this).next('.custom-file-label').html(fileName);

             // Trigger preview update
             const inputId = $(this).attr('id');
             const previewId = inputId + '-preview';
             previewFile(this, previewId);
        });

         // Set initial label for existing files
        $('#certificate.custom-file-input').trigger('change');
        $('#id_card.custom-file-input').trigger('change');
    });
</script>
@endpush
