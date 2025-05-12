@forelse ($roles as $role)
    <tr>
        <td>{{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
        <td>{{ $role->name }}</td>
        <td>
            @foreach($role->permissions->pluck('name')->sort() as $permission)
                <span class="badge badge-info mr-1 mb-1">{{ $permission }}</span>
            @endforeach
        </td>
        <td>
            @can('roles.edit')
                <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-warning btn-xs">
                    <i class="fas fa-edit"></i> Sửa
                </a>
            @endcan
            @can('roles.delete')
                @if(!in_array($role->name, ['super-admin', 'admin', 'manager', 'staff'])) {{-- Prevent deleting default roles --}}
                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa role này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </form>
                @endif
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center">Không có role nào.</td>
    </tr>
@endforelse 