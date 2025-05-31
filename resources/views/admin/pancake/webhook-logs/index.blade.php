@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pancake Webhook Logs</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                        <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Event Type</label>
                                    <input type="text" name="event_type" class="form-control" value="{{ request('event_type') }}" placeholder="e.g. order.created">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Search</label>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search in logs...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Type</th>
                                    <th>Status</th>
                                    <th>Order ID</th>
                                    <th>Customer ID</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->event_type }}</td>
                                        <td>
                                            <span class="badge badge-{{ $log->status === 'success' ? 'success' : ($log->status === 'error' ? 'danger' : 'warning') }}">
                                                {{ $log->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($log->order_id)
                                                <a href="{{ route('orders.show', $log->order_id) }}">{{ $log->order_id }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->customer_id)
                                                <a href="{{ route('customers.show', $log->customer_id) }}">{{ $log->customer_id }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <a href="{{ route('admin.pancake.webhook-logs.show', $log) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No webhook logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
