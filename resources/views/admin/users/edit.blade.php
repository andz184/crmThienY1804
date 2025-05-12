@extends('adminlte::page')

@section('title', 'Sửa Người Dùng')

@section('content_header')
    <h1>Sửa Người Dùng: {{ $user->name }}</h1>
@stop

@section('content')
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Thông tin User</h3>
    </div>
    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="name">Tên User <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                 @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                 @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu mới (Để trống nếu không muốn đổi)</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" aria-describedby="passwordHelp">
                <small id="passwordHelp" class="form-text text-muted">Để trống trường này nếu bạn không muốn thay đổi mật khẩu hiện tại.</small>
                 @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Xác nhận Mật khẩu mới</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>

            {{-- Chỉ Admin/Super Admin mới được sửa Role --}}
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                <div class="form-group">
                    <label>Roles <span class="text-danger">*</span></label>
                     <div class="row">
                         @foreach($roles as $id => $name)
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input user-role-checkbox" type="checkbox" name="roles[]" value="{{ $id }}" id="role_{{ $id }}" data-role-name="{{ $name }}" {{ in_array($id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
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
            @else
                {{-- Hiển thị role hiện tại nếu không phải admin --}}
                 <div class="form-group">
                    <label>Roles</label>
                    <p>
                        @foreach($user->roles->pluck('name') as $roleName)
                            <span class="badge badge-info mr-1">{{ $roleName }}</span>
                        @endforeach
                    </p>
                </div>
            @endif

             {{-- Trường chọn Team chỉ hiển thị nếu user là staff và người sửa có quyền --}}
             @if($canEditTargetTeam)
                <div class="form-group" id="team-assignment-section" {{ !$user->hasRole('staff') ? 'style=display:none' : '' }}>
                     <label for="team_id">Gán vào Team của Manager</label>
                    <select class="form-control @error('team_id') is-invalid @enderror" id="team_id" name="team_id">
                        <option value="">-- Bỏ gán Team / Chọn Manager --</option>
                        @foreach($assignableLeaders as $leaderTeamId => $leaderName)
                            <option value="{{ $leaderTeamId }}" {{ old('team_id', $user->team_id) == $leaderTeamId ? 'selected' : '' }}>
                                {{ $leaderName }} (Team {{ $leaderTeamId }})
                            </option>
                        @endforeach
                    </select>
                    @error('team_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            @else
                 {{-- Hiển thị team hiện tại nếu không được sửa --}}
                 @if($user->hasRole('staff'))
                     <div class="form-group">
                        <label>Team hiện tại</label>
                        <p>{{ $user->team_id ? (\App\Models\User::where('manages_team_id', $user->team_id)->first()->name ?? 'Team ' . $user->team_id) : 'Chưa gán' }}</p>
                     </div>
                 @endif
            @endif

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Cập nhật User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Function to toggle team assignment section based on selected roles
        function toggleTeamAssignment() {
             // Only run if the team assignment section exists (i.e., user has permission to edit team)
            if(!$('#team-assignment-section').length) return;

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
                 // No need to reset value here as it might be pre-filled
            }
        }

        // Initial check only if role checkboxes are present (i.e. admin is editing)
        if($('.user-role-checkbox').length) {
            toggleTeamAssignment();
        }

        // Check when a role checkbox changes
        $('.user-role-checkbox').on('change', toggleTeamAssignment);
    });
</script>
@stop
