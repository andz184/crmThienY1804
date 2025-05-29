@extends('adminlte::page')

@section('content')
<div class="container-fluid">
<div class="row">
        <div class="col-12">
        <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Báo cáo doanh thu theo ngày</h3>
                    <div class="card-tools">
                        <form class="form-inline">
                            <div class="form-group mx-2">
                                <label class="mr-2">Từ ngày:</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                            <div class="form-group mx-2">
                                <label class="mr-2">Đến ngày:</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                            <div class="form-group mx-2">
                                <label class="mr-2">Số dòng:</label>
                                <select name="per_page" class="form-control">
                                    <option value="10" {{ $pagination['per_page'] == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $pagination['per_page'] == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $pagination['per_page'] == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $pagination['per_page'] == 100 ? 'selected' : '' }}>100</option>
                                </select>
                    </div>
                            <button type="submit" class="btn btn-primary">Xem báo cáo</button>
                        </form>
                    </div>
                </div>
            <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                                    <th>Ngày</th>
                                    <th>Doanh số dự kiến</th>
                                    <th>Doanh số thực tế</th>
                                    <th>Doanh thu dự kiến</th>
                                    <th>Doanh thu thực tế</th>
                                    <th>Tổng đơn</th>
                    <th>Đơn chốt</th>
                    <th>Đơn hủy</th>
                    <th>Đơn đang giao</th>
                                    <th>Tỷ lệ chốt (%)</th>
                                    <th>Tỷ lệ hủy (%)</th>
                    <th>Khách mới</th>
                    <th>Khách cũ</th>
                    <th>Tổng khách</th>
                </tr>
            </thead>
            <tbody>
                                @foreach($revenueData as $data)
                <tr>
                                    <td>{{ $data['date'] }}</td>
                                    <td class="text-right">{{ number_format($data['expected_total']) }}</td>
                                    <td class="text-right">{{ number_format($data['actual_total']) }}</td>
                                    <td class="text-right">{{ number_format($data['expected_revenue']) }}</td>
                                    <td class="text-right">{{ number_format($data['actual_revenue']) }}</td>
                                    <td class="text-center">{{ $data['total_orders'] }}</td>
                                    <td class="text-center">{{ $data['successful_orders'] }}</td>
                                    <td class="text-center">{{ $data['canceled_orders'] }}</td>
                                    <td class="text-center">{{ $data['delivering_orders'] }}</td>
                                    <td class="text-center">{{ number_format($data['success_rate'], 1) }}%</td>
                                    <td class="text-center">{{ number_format($data['cancellation_rate'], 1) }}%</td>
                                    <td class="text-center">{{ $data['new_customers'] }}</td>
                                    <td class="text-center">{{ $data['returning_customers'] }}</td>
                                    <td class="text-center">{{ $data['total_customers'] }}</td>
                </tr>
                                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
                    <td>Tổng cộng</td>
                    <td class="text-right">{{ number_format($totals['expected_total']) }}</td>
                    <td class="text-right">{{ number_format($totals['actual_total']) }}</td>
                    <td class="text-right">{{ number_format($totals['expected_revenue']) }}</td>
                    <td class="text-right">{{ number_format($totals['actual_revenue']) }}</td>
                    <td class="text-center">{{ $totals['total_orders'] }}</td>
                    <td class="text-center">{{ $totals['successful_orders'] }}</td>
                    <td class="text-center">{{ $totals['canceled_orders'] }}</td>
                    <td class="text-center">{{ $totals['delivering_orders'] }}</td>
                    <td class="text-center">{{ number_format($totals['success_rate'], 1) }}%</td>
                    <td class="text-center">{{ number_format($totals['cancellation_rate'], 1) }}%</td>
                    <td class="text-center">{{ $totals['new_customers'] }}</td>
                    <td class="text-center">{{ $totals['returning_customers'] }}</td>
                    <td class="text-center">{{ $totals['total_customers'] }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="d-flex justify-content-center mt-4">
            {{ $pagination['links'] }}
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
