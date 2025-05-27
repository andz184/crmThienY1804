@props(['initialFilters' => []])

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Bộ lọc</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="live-session-filters">
            <div class="row">
                <!-- Date Range Filter -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Khoảng thời gian</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="far fa-calendar-alt"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control float-right" id="date-range-filter">
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Trạng thái đơn hàng</label>
                        <select class="form-control select2" id="status-filter" multiple>
                            <option value="3">Thành công</option>
                            <option value="2">Đang giao</option>
                            <option value="6">Đã hủy</option>
                        </select>
                    </div>
                </div>

                <!-- Customer Type Filter -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loại khách hàng</label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="customer-type" value="all" checked> Tất cả
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="customer-type" value="new"> Mới
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="customer-type" value="returning"> Quay lại
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Revenue Range Filter -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Khoảng doanh thu</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" id="min-revenue" placeholder="Tối thiểu">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" id="max-revenue" placeholder="Tối đa">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Range Filter -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Khoảng doanh số</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control" id="min-sales" placeholder="Tối thiểu">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" id="max-sales" placeholder="Tối đa">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Province Filter -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tỉnh/Thành phố</label>
                        <select class="form-control select2" id="province-filter" multiple>
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                </div>

                <!-- Comparison Period -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>So sánh với</label>
                        <select class="form-control" id="comparison-period">
                            <option value="previous_period">Kỳ trước</option>
                            <option value="previous_year">Cùng kỳ năm trước</option>
                            <option value="custom">Tùy chỉnh</option>
                        </select>
                        <div id="custom-comparison-dates" class="mt-2" style="display: none;">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="far fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control float-right" id="custom-comparison-range">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i>Áp dụng
                    </button>
                    <button type="button" class="btn btn-secondary" id="reset-filters">
                        <i class="fas fa-undo mr-1"></i>Đặt lại
                    </button>
                    <div class="float-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" id="toggle-comparison">
                                <i class="fas fa-chart-line mr-1"></i>So sánh
                            </button>
                            <button type="button" class="btn btn-outline-success" id="export-excel">
                                <i class="fas fa-file-excel mr-1"></i>Xuất Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize date range picker
    $('#date-range-filter').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        },
        ranges: {
           'Hôm nay': [moment(), moment()],
           'Hôm qua': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           '7 ngày qua': [moment().subtract(6, 'days'), moment()],
           '30 ngày qua': [moment().subtract(29, 'days'), moment()],
           'Tháng này': [moment().startOf('month'), moment().endOf('month')],
           'Tháng trước': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Initialize select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Handle comparison period change
    $('#comparison-period').change(function() {
        if ($(this).val() === 'custom') {
            $('#custom-comparison-dates').show();
        } else {
            $('#custom-comparison-dates').hide();
        }
    });

    // Initialize custom comparison date range picker
    $('#custom-comparison-range').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    // Handle filter form submission
    $('#live-session-filters').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Handle filter reset
    $('#reset-filters').click(function() {
        $('#live-session-filters')[0].reset();
        $('.select2').val(null).trigger('change');
        applyFilters();
    });

    // Handle comparison toggle
    $('#toggle-comparison').click(function() {
        $(this).toggleClass('active');
        if ($(this).hasClass('active')) {
            $('#comparison-period').closest('.col-md-6').show();
        } else {
            $('#comparison-period').closest('.col-md-6').hide();
        }
        applyFilters();
    });

    // Function to apply filters
    function applyFilters() {
        const filters = {
            dateRange: $('#date-range-filter').val(),
            status: $('#status-filter').val(),
            customerType: $('input[name="customer-type"]:checked').val(),
            minRevenue: $('#min-revenue').val(),
            maxRevenue: $('#max-revenue').val(),
            minSales: $('#min-sales').val(),
            maxSales: $('#max-sales').val(),
            provinces: $('#province-filter').val(),
            comparison: $('#toggle-comparison').hasClass('active') ? {
                type: $('#comparison-period').val(),
                customRange: $('#custom-comparison-range').val()
            } : null
        };

        // Emit event for parent component to handle
        window.dispatchEvent(new CustomEvent('filtersChanged', { detail: filters }));
    }

    // Load provinces
    function loadProvinces() {
        $.get('/api/provinces', function(data) {
            const provinceSelect = $('#province-filter');
            provinceSelect.empty();

            data.forEach(function(province) {
                provinceSelect.append(new Option(province.name, province.code));
            });
        });
    }

    // Initial load
    loadProvinces();
});
</script>
@endpush

@push('styles')
<style>
.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
    background-color: #007bff;
    border-color: #006fe6;
    color: #fff;
    padding: 0 10px;
    margin-top: 0.31rem;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 5px;
}

.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fff;
}

.btn-group-toggle .btn {
    flex: 1;
}
</style>
@endpush
