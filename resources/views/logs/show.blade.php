@extends('adminlte::page')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Back to Logs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>ID</dt>
                                <dd>{{ $log->id }}</dd>

                                <dt>User</dt>
                                <dd>{{ $log->user->name }}</dd>

                                <dt>Action</dt>
                                <dd>{{ $log->action }}</dd>

                                <dt>Model</dt>
                                <dd>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</dd>

                                <dt>Date</dt>
                                <dd>{{ $log->created_at->format('Y-m-d H:i:s') }}</dd>

                                <dt>IP Address</dt>
                                <dd>{{ $log->ip_address }}</dd>

                                <dt>User Agent</dt>
                                <dd>{{ $log->user_agent }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            @if($log->old_data)
                            <h4>Old Data</h4>
                            <pre>{{ json_encode($log->old_data, JSON_PRETTY_PRINT) }}</pre>
                            @endif

                            @if($log->new_data)
                            <h4>New Data</h4>
                            <pre>{{ json_encode($log->new_data, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
