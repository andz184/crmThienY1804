@extends('adminlte::page')

@section('title', 'Báo Cáo Tổng Doanh Thu')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Báo Cáo Tổng Doanh Thu</h1>
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
                    @include('reports.partials.revenue')
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .revenue-summary h2 {
        font-size: 2.5rem;
        font-weight: bold;
        color: #28a745;
    }
    .date-range-container {
        max-width: 300px;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    // Các script JS sẽ được sử dụng trong partial
</script>
@stop
