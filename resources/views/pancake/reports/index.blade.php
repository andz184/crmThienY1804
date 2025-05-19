@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Pancake Reports</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <button type="button" class="btn btn-light" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Report Type Selection -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card report-card" onclick="selectReport('orders')">
                                <div class="card-body text-center">
                                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                    <h5>Orders Report</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card" onclick="selectReport('customers')">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h5>Customers Report</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card" onclick="selectReport('products')">
                                <div class="card-body text-center">
                                    <i class="fas fa-box fa-2x mb-2"></i>
                                    <h5>Products Report</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card" onclick="selectReport('sync')">
                                <div class="card-body text-center">
                                    <i class="fas fa-sync fa-2x mb-2"></i>
                                    <h5>Sync Status Report</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Content Area -->
                    <div id="reportContent" class="mt-4">
                        <!-- Dynamic content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="applyFilter()">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.report-card i {
    color: #0d6efd;
}

.table-responsive {
    margin-top: 20px;
}

.badge {
    padding: 0.5em 0.75em;
}
</style>

<script>
function selectReport(type) {
    // Remove active class from all cards
    document.querySelectorAll('.report-card').forEach(card => {
        card.classList.remove('border-primary');
    });

    // Add active class to selected card
    event.currentTarget.classList.add('border-primary');

    // Load report content
    loadReportContent(type);
}

function loadReportContent(type) {
    const contentArea = document.getElementById('reportContent');
    contentArea.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

    // AJAX call to load report content
    fetch(`/pancake/reports/${type}`)
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Error loading report</div>';
        });
}

function applyFilter() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);

    // Reload current report with filters
    const activeReport = document.querySelector('.report-card.border-primary');
    if (activeReport) {
        const reportType = activeReport.getAttribute('onclick').match(/'([^']+)'/)[1];
        loadReportContent(reportType + '?' + params.toString());
    }

    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
}

function exportReport() {
    const activeReport = document.querySelector('.report-card.border-primary');
    if (activeReport) {
        const reportType = activeReport.getAttribute('onclick').match(/'([^']+)'/)[1];
        window.location.href = `/pancake/reports/${reportType}/export`;
    }
}
</script>
@endsection
