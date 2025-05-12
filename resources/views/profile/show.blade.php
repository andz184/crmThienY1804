@extends('adminlte::page')

@section('title', 'Hồ sơ của bạn')

@section('content_header')
    <h1>Hồ sơ cá nhân</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{ $user->name }}</h3>

                <p class="text-muted text-center">{{ $user->getRoleNames()->implode(', ') }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Email</b> <a class="float-right">{{ $user->email }}</a>
                    </li>
                    @if($user->team_id)
                    <li class="list-group-item">
                        <b>Thuộc Team ID</b> <a class="float-right">{{ $user->team_id }}</a>
                    </li>
                    @endif
                    @if($user->manages_team_id)
                     <li class="list-group-item">
                        <b>Quản lý Team ID</b> <a class="float-right">{{ $user->manages_team_id }}</a>
                    </li>
                    @endif
                    <li class="list-group-item">
                        <b>Ngày tham gia</b> <a class="float-right">{{ $user->created_at->format('d/m/Y') }}</a>
                    </li>
                </ul>

                {{-- Link to Edit Page --}}
                <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-block"><b>Chỉnh sửa Hồ sơ</b></a>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
</div>
@stop

@section('css')
    {{-- Add custom CSS if needed --}}
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop 