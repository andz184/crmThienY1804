@forelse ($users as $user)
    <tr>
        <td>{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td>
            @foreach($user->roles->pluck('name') as $roleName)
                <span class="badge {{ $roleName == 'super-admin' ? 'badge-danger' : ($roleName == 'admin' ? 'badge-warning' : ($roleName == 'manager' ? 'badge-primary' : 'badge-success')) }} mr-1">{{ $roleName }}</span>
            @endforeach
        </td>
        <td>
            {{-- Chỉ hiển thị quản lý nếu user là staff --}}
            @if($user->hasRole('staff'))
                {{-- Hiển thị tên manager nếu có, ngược lại báo chưa gán --}}
                {{ $user->manager->name ?? 'Chưa gán' }}
            @endif
        </td>
        <td>
             @can('users.edit')
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning btn-xs">
                    <i class="fas fa-edit"></i> Sửa
                </a>
            @endcan
            @can('users.delete')
                @if(Auth::id() !== $user->id && !$user->hasRole('super-admin'))
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa user này?');">
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
        <td colspan="6" class="text-center">Không có user nào phù hợp với bộ lọc.</td>
    </tr>
@endforelse
