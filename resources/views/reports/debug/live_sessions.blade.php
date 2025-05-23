@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-bug mr-2 text-danger"></i>
                        Debug Live Session Patterns
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Patterns đang kiểm tra:</h6>
                    <div class="mb-4">
                        <ul class="list-group">
                            @foreach ($patterns as $name => $pattern)
                                <li class="list-group-item">
                                    <strong>{{ $name }}:</strong> 
                                    <code>{{ $pattern }}</code>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <h6>Kết quả từ đơn hàng mẫu:</h6>
                    @foreach ($results as $result)
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Order #{{ $result['id'] }}</strong>
                                <span class="ml-3 text-muted">{{ $result['created_at'] }}</span>
                            </div>
                            <div class="card-body">
                                <h6>Notes:</h6>
                                <pre class="p-3 bg-light">{{ $result['notes'] }}</pre>
                                
                                <h6>Kết quả match:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Pattern</th>
                                                <th>Kết quả</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($result['matches'] as $pattern => $match)
                                                <tr>
                                                    <td width="20%"><code>{{ $pattern }}</code></td>
                                                    <td>
                                                        @if ($match)
                                                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i> Khớp</span>
                                                            <pre class="mt-2 p-2 bg-light">{{ print_r($match, true) }}</pre>
                                                        @else
                                                            <span class="text-danger"><i class="fas fa-times-circle mr-1"></i> Không khớp</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 