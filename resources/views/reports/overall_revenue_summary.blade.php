@extends('adminlte::page')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card bg-light-blue">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Hướng dẫn các chỉ số</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>Doanh thu dự kiến:</strong> Tổng giá trị các đơn hàng không bao gồm các đơn ở trạng thái hủy (6, 7).</li>
                        <li><strong>Doanh thu thực tế:</strong> Tổng giá trị các đơn hàng đã được giao thành công (trạng thái 3).</li>
                        <li><strong>Đơn chốt:</strong> Tổng số đơn hàng được giao thành công (trạng thái 3).</li>
                        <li><strong>Đơn hủy:</strong> Tổng số đơn hàng bị hủy (trạng thái 6, 7).</li>
                        <li><strong>Tỷ lệ chốt:</strong> (Đơn chốt / (Tổng đơn - Đơn đang giao)) * 100.</li>
                        <li><strong>Tỷ lệ hủy:</strong> (Đơn hủy / Tổng đơn) * 100.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<div class="row">
        <div class="col-12">
        <!-- Live Dashboard -->
        <div class="live-dashboard mb-4">
            <div class="dashboard-header p-4">
                <div class="text-center mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-white-50 mb-2">Doanh thu dự kiến (đ)</p>
                            <h1 class="revenue-display mb-4">{{ number_format($totals['expected_total'] ?? 0, 0, ',', '.') }}</h1>
                        </div>
                        <div class="col-md-6">
                            <p class="text-white-50 mb-2">Doanh thu thực tế (đ)</p>
                            <h1 class="revenue-display mb-4">{{ number_format($totals['actual_total'] ?? 0, 0, ',', '.') }}</h1>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-7 mb-4">
                        <div class="stat-item">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Tổng đơn</span>
                            </div>
                            <h3>{{ number_format($totals['total_orders'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                        <div class="stat-item">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-users"></i>
                                <span>Tổng khách</span>
                            </div>
                            <h3>{{ number_format($totals['total_customers'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>

                <div class="dashboard-stats">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Đơn chốt</div>
                                <div class="stat-value">{{ number_format($totals['successful_orders'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Đơn hủy</div>
                                <div class="stat-value">{{ number_format($totals['canceled_orders'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Tỷ lệ chốt</div>
                                <div class="stat-value">{{ number_format($totals['success_rate'] ?? 0, 1, ',', '.') }}%</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-label">Khách hàng</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="customer-stat">
                                            <span class="customer-label">Mới:</span>
                                            <span class="customer-value">{{ number_format($totals['new_customers'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="customer-stat">
                                            <span class="customer-label">Cũ:</span>
                                            <span class="customer-value">{{ number_format($totals['returning_customers'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="customer-total">
                                        Tổng: {{ number_format($totals['total_customers'] ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

<style>
.live-dashboard {
    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    border-radius: 16px;
    color: white;
}

.dashboard-header {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 16px;
}

.revenue-display {
    font-size: 4rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -1px;
}

.stat-item {
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem 2rem;
    border-radius: 12px;
    margin: 0 10px;
}

.stat-item span {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
}

.stat-item h3 {
    font-size: 1.75rem;
    margin: 0.5rem 0 0;
    font-weight: 600;
}

.dashboard-stats {
    margin-top: 1rem;
}

.stat-box {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;
}

.stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1rem;
    margin-bottom: 0.75rem;
    font-weight: 500;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.customer-stat {
    margin-bottom: 0.5rem;
}

.customer-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-right: 0.5rem;
}

.customer-value {
    font-size: 1.1rem;
    font-weight: 600;
}

.customer-total {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    padding-left: 1rem;
    border-left: 1px solid rgba(255, 255, 255, 0.2);
}

@media (max-width: 768px) {
    .revenue-display {
        font-size: 3rem;
    }

    .stat-item {
        padding: 0.75rem 1.5rem;
    }

    .dashboard-stats .row {
        margin: 0 -0.5rem;
    }

    .dashboard-stats .col-md-3 {
        padding: 0 0.5rem;
        margin-bottom: 1rem;
    }
}
</style>
@endsection
