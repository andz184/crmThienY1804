@extends('adminlte::page')

@section('title', 'Báo Cáo Khách Hàng Mới')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Báo Cáo Khách Hàng Mới (Đơn Đầu Tiên)</h1>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @php
                        $isFirstOrder = true;
                    @endphp
                    @include('reports.partials.customer', ['isFirstOrder' => $isFirstOrder])
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css" />
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        // Đặt giá trị mặc định cho tham số is_first_order
        window.isFirstOrder = true;
    });
</script>
@stop
