@extends('adminlte::page')

@section('title', 'Báo Cáo')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Báo Cáo</h1>
        <button id="sync-pancake-btn" class="btn btn-primary">
            <i class="fas fa-sync-alt"></i> Đồng bộ dữ liệu từ Pancake
        </button>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @can('reports.total_revenue')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Tổng Doanh Thu</h5>
                                    <i class="fas fa-chart-line fa-4x text-primary my-4"></i>
                                    <p class="card-text">Xem tổng doanh thu và biểu đồ theo thời gian</p>
                                    <a href="{{ route('reports.total_revenue') }}" class="btn btn-primary">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.detailed')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Báo Cáo Chi Tiết</h5>
                                    <i class="fas fa-file-alt fa-4x text-success my-4"></i>
                                    <p class="card-text">Phân tích chi tiết doanh thu theo ngày, tháng, trạng thái</p>
                                    <a href="{{ route('reports.detail') }}" class="btn btn-success">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.product_groups')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Theo Nhóm Hàng Hóa</h5>
                                    <i class="fas fa-boxes fa-4x text-info my-4"></i>
                                    <p class="card-text">Phân tích doanh thu và đơn hàng theo từng nhóm sản phẩm</p>
                                    <a href="{{ route('reports.product_groups') }}" class="btn btn-info">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.campaigns')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Theo Chiến Dịch (Bài Post)</h5>
                                    <i class="fas fa-bullhorn fa-4x text-warning my-4"></i>
                                    <p class="card-text">Hiệu quả của từng chiến dịch và bài đăng trên mạng xã hội</p>
                                    <a href="{{ route('reports.campaigns') }}" class="btn btn-warning">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.live_sessions')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Phiên Live</h5>
                                    <i class="fas fa-video fa-4x text-danger my-4"></i>
                                    <p class="card-text">Phân tích hiệu quả các phiên bán hàng trực tiếp</p>
                                    <a href="{{ route('reports.live_sessions') }}" class="btn btn-danger">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.conversion_rates')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Tỉ Lệ Chốt Đơn</h5>
                                    <i class="fas fa-percentage fa-4x text-secondary my-4"></i>
                                    <p class="card-text">Phân tích tỉ lệ chuyển đổi từ khách tiềm năng thành đơn hàng</p>
                                    <a href="{{ route('reports.conversion_rates') }}" class="btn btn-secondary">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.customer_new')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Khách Hàng Mới</h5>
                                    <i class="fas fa-user-plus fa-4x text-primary my-4"></i>
                                    <p class="card-text">Phân tích khách hàng mới (đơn hàng đầu tiên)</p>
                                    <a href="{{ route('reports.new_customers') }}" class="btn btn-primary">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('reports.customer_returning')
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Khách Hàng Cũ</h5>
                                    <i class="fas fa-user-clock fa-4x text-success my-4"></i>
                                    <p class="card-text">Phân tích khách hàng cũ (đơn hàng thứ 2 trở đi)</p>
                                    <a href="{{ route('reports.returning_customers') }}" class="btn btn-success">Xem Báo Cáo</a>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .card-body {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Xử lý khi nhấn nút đồng bộ từ Pancake
    $('#sync-pancake-btn').click(function() {
        // Hiển thị loading spinner
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...');
        $(this).prop('disabled', true);

        // Thêm overlay thông báo đang đồng bộ
        $('body').append('<div id="sync-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;"><div class="bg-white p-4 rounded"><h3>Đang đồng bộ dữ liệu từ Pancake</h3><p>Vui lòng đợi trong giây lát...</p><div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div></div></div>');

        // Gọi API đồng bộ
        $.ajax({
            url: '{{ route("reports.sync_from_pancake") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Ẩn overlay
                $('#sync-overlay').remove();

                // Hiển thị thông báo thành công
                Swal.fire({
                    title: 'Đồng bộ thành công!',
                    html: `Đã đồng bộ ${response.data.orders_synced} đơn hàng<br>Tổng doanh thu: ${response.data.revenue_synced.toLocaleString('vi-VN')} đ`,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    // Tải lại trang để cập nhật dữ liệu
                    location.reload();
                });
            },
            error: function(error) {
                // Ẩn overlay
                $('#sync-overlay').remove();

                // Hiển thị thông báo lỗi
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi đồng bộ dữ liệu: ' + (error.responseJSON?.message || 'Không thể kết nối đến server'),
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Khôi phục nút
                $('#sync-pancake-btn').html('<i class="fas fa-sync-alt"></i> Đồng bộ dữ liệu từ Pancake');
                $('#sync-pancake-btn').prop('disabled', false);
            }
        });
    });
});
</script>
@stop
