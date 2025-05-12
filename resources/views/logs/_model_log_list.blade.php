<div class="table-responsive">
    <table class="table table-sm table-striped table-hover">
        <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Date</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->user->name ?? 'N/A' }}</td>
                <td>{{ ucfirst($log->action) }}</td>
                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td>
                    <a href="{{ route('admin.logs.show', $log) }}" class="btn btn-xs btn-default" title="View Full Log">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted">No history found for this item.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($logs->hasPages())
<div class="mt-2">
    {{ $logs->links('vendor.pagination.simple-bootstrap-4') }} {{-- Sử dụng simple pagination --}}
</div>
@endif 