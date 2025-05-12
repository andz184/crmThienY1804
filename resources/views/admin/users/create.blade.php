@extends('adminlte::page')

@section('title', 'Thêm Người Dùng')

@section('content_header')
    <h1>Thêm Người Dùng mới</h1>
@stop

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Thông tin User</h3>
    </div>
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="name">Tên User <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                 @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                 @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Xác nhận Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>

            <div class="form-group">
                <label>Roles <span class="text-danger">*</span></label>
                <div class="row">
                     @foreach($roles as $id => $name)
                        <div class="col-md-3">
                            <div class="form-check">
                                {{-- Sử dụng $name để kiểm tra role 'staff' --}}
                                <input class="form-check-input user-role-checkbox" type="checkbox" name="roles[]" value="{{ $id }}" id="role_{{ $id }}" data-role-name="{{ $name }}" {{ in_array($id, old('roles', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $id }}">
                                    {{ $name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                 @error('roles') <span class="text-danger d-block">{{ $message }}</span> @enderror
                 @error('roles.*') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            {{-- Trường chọn Team chỉ hiển thị nếu chọn role 'staff' --}}
            <div class="form-group" id="team-assignment-section" style="display: none;"> <label for="team_id">Gán vào Team của Manager</label>
                <select class="form-control @error('team_id') is-invalid @enderror" id="team_id" name="team_id">
                    <option value="">-- Chọn Manager --</option>
                    {{-- Lấy danh sách Manager từ $assignableLeaders (Cần truyền biến này từ create method) --}}
                     @php
                        // Tạm thời lấy tất cả manager ở đây, nên tối ưu ở controller
                        $assignableLeaders = \App\Models\User::whereNotNull('manages_team_id')
                                                    ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                                                    ->pluck('name', 'manages_team_id');
                    @endphp
                    @foreach($assignableLeaders as $leaderTeamId => $leaderName)
                        <option value="{{ $leaderTeamId }}" {{ old('team_id') == $leaderTeamId ? 'selected' : '' }}>
                            {{ $leaderName }} (Team {{ $leaderTeamId }})
                        </option>
                    @endforeach
                </select>
                 @error('team_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Lưu User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        function toggleTeamAssignment() {
            let isStaffSelected = false;
            $('.user-role-checkbox:checked').each(function() {
                if ($(this).data('role-name') === 'staff') {
                    isStaffSelected = true;
                    return false; // exit each loop
                }
            });

            if (isStaffSelected) {
                $('#team-assignment-section').slideDown();
            } else {
                $('#team-assignment-section').slideUp();
                $('#team_id').val(''); // Reset selection when hiding
            }
        }

        // Initial check on page load
        toggleTeamAssignment();

        // Check when a role checkbox changes
        $('.user-role-checkbox').on('change', toggleTeamAssignment);
    });
</script>
@stop
