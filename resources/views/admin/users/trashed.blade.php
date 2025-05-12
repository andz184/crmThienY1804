@extends('adminlte::page')

@section('title', 'Người dùng đã xóa')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>Người dùng đã xóa</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @include('layouts.partials.alert')

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Ngày xóa</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedUsers as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->getRoleNames()->implode(', ') }}</td>
                            <td>{{ $user->deleted_at->format('d/m/Y H:i') }}</td>
                            <td>
                                {{-- Restore Button --}}
                                <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn khôi phục người dùng này?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm" title="Khôi phục">
                                        <i class="fas fa-trash-restore"></i>
                                    </button>
                                </form>

                                {{-- Force Delete Button --}}
                                <form action="{{ route('admin.users.forceDelete', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn XÓA VĨNH VIỄN người dùng này? Hành động này không thể hoàn tác!');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa vĩnh viễn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Không có người dùng nào trong thùng rác.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($trashedUsers->hasPages())
            <div class="card-footer clearfix">
                {{ $trashedUsers->links('vendor.pagination.bootstrap-4') }}
            </div>
        @endif
    </div>
@stop
