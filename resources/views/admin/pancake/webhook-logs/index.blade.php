@extends('adminlte::page')

@section('title', 'Pancake Webhook Logs')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Pancake Webhook Logs</h1>
        <div class="alert alert-info mb-0 d-none d-md-block">
            <i class='bx bx-link me-2'></i>
            <strong>Webhook URL:</strong> <code>{{ env('APP_URL') . '/api/webhooks/pancake' }}</code>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Webhook Events</h3>
             <div class="card-tools">
                 <form method="GET" action="{{ route('admin.pancake.webhook-logs.index') }}" class="form-inline">
                     <div class="form-group mr-2">
                        <label for="event_type" class="mr-2">Event Type:</label>
                        <select name="event_type" id="event_type" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="order.created" {{ request('event_type') == 'order.created' ? 'selected' : '' }}>Order Created</option>
                            <option value="order.updated" {{ request('event_type') == 'order.updated' ? 'selected' : '' }}>Order Updated</option>
                            <option value="order.deleted" {{ request('event_type') == 'order.deleted' ? 'selected' : '' }}>Order Deleted</option>
                            <option value="other" {{ request('event_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                     </div>
                     <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                      <a href="{{ route('admin.pancake.webhook-logs.index') }}" class="btn btn-secondary btn-sm ml-2">Reset</a>
                 </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Type</th>
                            <th>Pancake Order ID</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Received At</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td>
                                    @if($log->event_type == 'order.created')
                                        <span class="badge badge-success">{{ $log->event_type }}</span>
                                    @elseif($log->event_type == 'order.updated')
                                        <span class="badge badge-info">{{ $log->event_type }}</span>
                                     @elseif($log->event_type == 'order.deleted')
                                        <span class="badge badge-danger">{{ $log->event_type }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $log->event_type }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->pancake_order_id ?? 'N/A' }}</td>
                                <td>
                                    @if($log->status == 'success')
                                        <span class="badge badge-success">{{ ucfirst($log->status) }}</span>
                                    @elseif($log->status == 'failed')
                                        <span class="badge badge-danger">{{ ucfirst($log->status) }}</span>
                                    @else
                                         <span class="badge badge-warning">{{ ucfirst($log->status) }}</span>
                                    @endif
                                </td>
                                <td style="max-width: 300px; word-wrap: break-word;">{{ Str::limit($log->message, 100) }}</td>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#log-{{ $log->id }}">
                                       <i class='bx bx-show'></i> View
                                    </button>
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
        </div>
        <div class="card-footer clearfix">
            {{ $logs->links() }}
        </div>
    </div>

    @foreach($logs as $log)
    <!-- Modal -->
    <div class="modal fade" id="log-{{ $log->id }}" tabindex="-1" role="dialog" aria-labelledby="log-{{ $log->id }}-label" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="log-{{ $log->id }}-label">Webhook Log #{{ $log->id }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <pre><code>{{ json_encode(json_decode($log->payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    @endforeach

@stop
