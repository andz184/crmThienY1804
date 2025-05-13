@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activity Log Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-default">
                            Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th style="width: 200px;">Date</th>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>{{ $log->user->name }}</td>
                                </tr>
                                <tr>
                                    <th>Action</th>
                                    <td>{{ ucfirst($log->action) }}</td>
                                </tr>
                                <tr>
                                    <th>Module</th>
                                    <td>{{ $log->module }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $log->description }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $log->ip_address }}</td>
                                </tr>
                                <tr>
                                    <th>User Agent</th>
                                    <td>{{ $log->user_agent }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($log->old_data || $log->new_data)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h4>Changes</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Old Value</th>
                                                <th>New Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if($log->action === 'updated')
                                                @foreach($log->new_data as $key => $value)
                                                    @if($key !== 'updated_at' && $key !== 'created_at')
                                                        <tr>
                                                            <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                            <td>{{ is_array($log->old_data[$key] ?? null) ? json_encode($log->old_data[$key]) : ($log->old_data[$key] ?? '') }}</td>
                                                            <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @else
                                                @foreach($log->new_data as $key => $value)
                                                    @if($key !== 'updated_at' && $key !== 'created_at')
                                                        <tr>
                                                            <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                            <td>-</td>
                                                            <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
