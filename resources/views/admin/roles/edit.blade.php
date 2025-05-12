@extends('adminlte::page')

@section('title', 'Sửa Nhóm Quyền')

@section('content_header')
    <h1>Sửa Nhóm Quyền: {{ $role->name }}</h1>
@stop

@section('content')
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Thông tin Role</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="name">Tên Role</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Nhập tên role" value="{{ old('name', $role->name) }}" required {{ in_array($role->name, ['super-admin', 'admin']) ? 'readonly' : '' }}>
                @if(in_array($role->name, ['super-admin', 'admin']))
                    <small class="form-text text-muted">Không thể đổi tên các role hệ thống.</small>
                @endif
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label>Permissions</label>
                 <div class="row">
                    @foreach($permissions as $id => $name)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $id }}" id="perm_{{ $id }}" {{ in_array($id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="perm_{{ $id }}">
                                    {{ $name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                 @error('permissions')
                    <span class="text-danger" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                 @error('permissions.*')
                    <span class="text-danger d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <!-- /.card-body -->

        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Cập nhật Role</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@stop

@section('css')
    {{-- Add CSS specific to this page if needed --}}
@stop

@section('js')
    {{-- Add JS specific to this page if needed --}}
@stop
