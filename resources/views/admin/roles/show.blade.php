@extends('adminlte::page')

@section('title', 'Chi tiết Nhóm Quyền')

@section('content_header')
    <h1>Chi tiết Nhóm Quyền: {{ $role->name }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thông tin chi tiết</h3>
        <div class="card-tools">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">Quay lại danh sách</a>
            @can('roles.edit')
                <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-warning btn-sm">Sửa Role</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">ID:</label>
            <div class="col-sm-10">
                <input type="text" readonly class="form-control-plaintext" value="{{ $role->id }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Tên Role:</label>
            <div class="col-sm-10">
                <input type="text" readonly class="form-control-plaintext" value="{{ $role->name }}">
            </div>
        </div>
         <div class="form-group row">
            <label class="col-sm-2 col-form-label">Guard Name:</label>
            <div class="col-sm-10">
                <input type="text" readonly class="form-control-plaintext" value="{{ $role->guard_name }}">
            </div>
        </div>
         <div class="form-group row">
            <label class="col-sm-2 col-form-label">Permissions:</label>
            <div class="col-sm-10">
                @forelse($role->permissions as $permission)
                    <span class="badge badge-info mr-1 mb-1">{{ $permission->name }}</span>
                @empty
                    <span class="text-muted">Role này chưa có permission nào.</span>
                @endforelse
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Ngày tạo:</label>
            <div class="col-sm-10">
                <input type="text" readonly class="form-control-plaintext" value="{{ $role->created_at ? $role->created_at->format('d/m/Y H:i:s') : 'N/A' }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Ngày cập nhật:</label>
            <div class="col-sm-10">
                <input type="text" readonly class="form-control-plaintext" value="{{ $role->updated_at ? $role->updated_at->format('d/m/Y H:i:s') : 'N/A' }}">
            </div>
        </div>
    </div>
</div>
@stop
