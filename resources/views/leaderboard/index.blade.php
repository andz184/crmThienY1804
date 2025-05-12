@extends('adminlte::page')

@section('title', 'Bảng Xếp Hạng Doanh Thu')

@section('content_header')
    <h1 class="m-0 text-dark">Bảng Xếp Hạng Doanh Thu <small class="text-muted">({{ $currentPeriodLabel }})</small></h1>
@stop

@section('content')

{{-- Filter Form --}}
<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Lọc theo thời gian</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('leaderboard.index') }}" id="leaderboard-filter-form">
            <div class="row d-flex align-items-end">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="mb-1">Xem theo:</label>
                    <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                        <label class="btn btn-outline-primary {{ $period == 'month' ? 'active' : '' }}">
                            <input type="radio" name="period" value="month" {{ $period == 'month' ? 'checked' : '' }} autocomplete="off"> Tháng
                        </label>
                        <label class="btn btn-outline-primary {{ $period == 'year' ? 'active' : '' }}">
                            <input type="radio" name="period" value="year" {{ $period == 'year' ? 'checked' : '' }} autocomplete="off"> Năm
                        </label>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2" id="month-filter" style="{{ $period == 'year' ? 'display: none;' : '' }}">
                    <label for="month" class="mb-1">Chọn tháng:</label>
                    <select name="month" id="month" class="form-control form-control-sm">
                        @foreach($availableMonths as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <label for="year" class="mb-1">Chọn năm:</label>
                    <select name="year" id="year" class="form-control form-control-sm">
                         @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                        @if(!$availableYears->contains(Carbon\Carbon::now()->year))
                             <option value="{{ Carbon\Carbon::now()->year }}" {{ $year == Carbon\Carbon::now()->year ? 'selected' : '' }}>{{ Carbon\Carbon::now()->year }}</option>
                        @endif
                    </select>
                </div>
                 <div class="col-lg-3 col-md-6 mb-2">
                     <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fas fa-search mr-1"></i>Xem Xếp Hạng</button>
                     <a href="{{ route('leaderboard.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-sync-alt mr-1"></i>Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Top 3 Section --}}
<h4 class="mb-3">Top 3 Vinh Danh</h4>
<div class="row mb-4">
    @php
        $topColors = ['success', 'warning', 'info'];
        $topIcons = ['fas fa-crown text-warning', 'fas fa-medal text-secondary', 'fas fa-award text-info']; // Gold, Silver, Bronze-ish
    @endphp
    @forelse($top3 as $index => $staff)
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="info-box shadow-sm bg-gradient-{{ $topColors[$index % 3] ?? 'light' }}">
                 <span class="info-box-icon elevation-1" style="background-color: rgba(0,0,0,0.1);"><i class="{{ $topIcons[$index] ?? 'fas fa-trophy' }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text font-weight-bold">Hạng {{ $index + 1 }} - {{ $staff->user_name }}</span>
                    <span class="info-box-number">{{ number_format($staff->total_revenue, 0, ',', '.') }} đ</span>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center shadow-sm"><i class="fas fa-info-circle mr-2"></i>Chưa có dữ liệu xếp hạng cho kỳ này.</div>
        </div>
    @endforelse
</div>

{{-- Full List Table --}}
<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list-ol mr-1"></i> Danh sách Xếp hạng Đầy đủ</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;" class="text-center">Hạng</th>
                        <th>Nhân viên</th>
                        <th class="text-right">Tổng Doanh thu (Completed)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fullList as $index => $staff)
                        <tr>
                            <td class="text-center font-weight-bold">{{ $index + 1 }}</td>
                            <td>{{ $staff->user_name }}</td>
                            <td class="text-right">{{ number_format($staff->total_revenue, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Không có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{-- Add pagination if needed --}}
    {{-- <div class="card-footer"> ... </div> --}}
</div>

@stop

@section('css')
<style>
    .info-box .info-box-icon {
        font-size: 1.8rem; /* Make icon slightly smaller */
    }
    .info-box-content {
        line-height: 1.4; /* Adjust line height */
    }
    .info-box-number {
         font-size: 1.3rem; /* Slightly larger number */
         font-weight: 600;
    }
</style>
@stop

@section('js')
<script>
$(function() {
    // Toggle month dropdown based on period selection
    $('input[name="period"]').on('change', function() {
        if ($(this).val() === 'month') {
            $('#month-filter').show();
        } else {
            $('#month-filter').hide();
        }
    });

    // Initialize Bootstrap Toggle
    $('.btn-group-toggle').button();
});
</script>
@stop
