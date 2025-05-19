@extends('layouts.app')

@section('title', 'Báo cáo theo chiến dịch')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Báo cáo theo chiến dịch (ID bài post)</h3>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="get" action="{{ route('reports.campaigns') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Từ ngày</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Đến ngày</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate ?? now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pancake_shop_id">Cửa hàng</label>
                                    <select class="form-control" id="pancake_shop_id" name="pancake_shop_id">
                                        <option value="">Tất cả cửa hàng</option>
                                        @foreach($shops as $shop)
                                            <option value="{{ $shop->id }}" {{ request('pancake_shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pancake_page_id">Trang</label>
                                    <select class="form-control" id="pancake_page_id" name="pancake_page_id">
                                        <option value="">Tất cả trang</option>
                                        <!-- Pages will be loaded via AJAX -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Lọc</button>
                                <a href="{{ route('reports.campaigns') }}" class="btn btn-secondary">Xóa bộ lọc</a>
                            </div>
                        </div>
                    </form>

                    <!-- Campaigns Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID bài post</th>
                                    <th>Số đơn hàng</th>
                                    <th>Doanh thu</th>
                                    <th>Giá trị trung bình</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($campaigns->isEmpty())
                                    <tr>
                                        <td colspan="4" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                @else
                                    @foreach($campaigns as $campaign)
                                        <tr>
                                            <td>{{ $campaign->post_id }}</td>
                                            <td>{{ number_format($campaign->total_orders) }}</td>
                                            <td>{{ number_format($campaign->total_revenue) }}đ</td>
                                            <td>{{ number_format($campaign->average_order_value) }}đ</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Load pages when shop changes
        $('#pancake_shop_id').change(function() {
            const shopId = $(this).val();
            const pageSelect = $('#pancake_page_id');

            pageSelect.empty().append('<option value="">Tất cả trang</option>');

            if (!shopId) {
                return;
            }

            $.get('{{ route('ajax.pancakePagesForShop') }}', { shop_id: shopId }, function(data) {
                if (data && data.length > 0) {
                    data.forEach(function(page) {
                        pageSelect.append(`<option value="${page.id}">${page.name}</option>`);
                    });
                }
            });
        });

        // Load pages for selected shop on page load
        if ($('#pancake_shop_id').val()) {
            $('#pancake_shop_id').trigger('change');
        }
    });
</script>
@endsection
