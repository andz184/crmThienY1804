<style>
    .stats-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }
    .stats-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    .chart-container {
        position: relative;
        padding: 15px;
        background-color: #fff;
        border-radius: 0.375rem;
        box-shadow: 0 0 1rem rgba(0,0,0,.1);
        margin-bottom: 1.5rem;
        height: 380px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .chart-container:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .chart-container canvas {
        max-width: 100%;
        max-height: calc(100% - 40px);
    }
    .date-range-container {
        position: relative;
    }
    .date-picker {
        cursor: pointer;
        background-color: #fff !important;
    }
    .refresh-btn {
        transition: all 0.3s ease;
    }
    .refresh-btn:hover {
        transform: rotate(180deg);
    }
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }
    .table thead th {
        position: sticky;
        top: 0;
        background-color: #343a40;
        color: #fff;
    }
    .session-name {
        font-weight: bold;
        color: #3490dc;
    }
    .detail-btn {
        border-radius: 20px;
        padding: 0.25rem 0.75rem;
        transition: all 0.2s ease;
    }
    .detail-btn:hover {
        transform: scale(1.05);
    }
    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }
    .modal-header {
        background: linear-gradient(135deg, #3490dc, #6574cd);
        color: #fff;
    }
    .modal-body h6 {
        font-weight: bold;
        color: #3490dc;
        margin-top: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    /* Gradient styles for colored cards */
    .bg-info {
        background: linear-gradient(135deg, #3498db, #2980b9) !important;
    }
    .bg-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60) !important;
    }
    .bg-warning {
        background: linear-gradient(135deg, #f39c12, #f1c40f) !important;
    }
    .bg-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    }
    .bg-primary {
        background: linear-gradient(135deg, #9b59b6, #8e44ad) !important;
    }
    .bg-secondary {
        background: linear-gradient(135deg, #34495e, #2c3e50) !important;
    }
    .bg-dark {
        background: linear-gradient(135deg, #7f8c8d, #95a5a6) !important;
    }
    .chart-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #495057;
        text-align: center;
    }
    .chart-title i {
        margin-right: 8px;
        opacity: 0.8;
    }
    .stats-card .card-body h3 {
        font-size: 1.75rem;
        margin-bottom: 0;
        font-weight: 700;
        color: #fff;
    }
    .stats-card.bg-info h3,
    .stats-card.bg-success h3,
    .stats-card.bg-warning h3,
    .stats-card.bg-danger h3,
    .stats-card.bg-primary h3,
    .stats-card.bg-secondary h3,
    .stats-card.bg-dark h3 {
        color: #fff;
    }
    .stats-card .card-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgba(255,255,255,0.8);
    }
    .stats-card.bg-info .card-title,
    .stats-card.bg-success .card-title,
    .stats-card.bg-warning .card-title,
    .stats-card.bg-danger .card-title,
    .stats-card.bg-primary .card-title,
    .stats-card.bg-secondary .card-title,
    .stats-card.bg-dark .card-title {
        color: rgba(255,255,255,0.8);
    }
    .stats-card .stats-icon {
        font-size: 2.5rem;
        opacity: 0.3;
    }
    .date-range-container .form-control-sm {
        height: calc(1.5em + .5rem + 2px);
        padding: .25rem .5rem;
        font-size: .875rem;
    }
    .date-range-container .input-group-text {
        padding: .25rem .5rem;
    }
    #calculate-revenue-btn i {
        margin-right: 0.25rem;
    }
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
        flex-direction: column;
    }
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 2s linear infinite;
        margin-bottom: 10px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    /* Styles for the statistics cards */
    .stats-card .card-body {
        padding: 1rem; /* Adjust padding as needed */
    }
    .stats-card .card-body > div:first-child { /* Targets the div containing h5 and h3 */
        flex-grow: 1;
    }
    .stats-card h5 {
        font-size: 0.9rem; /* Adjust as needed */
        margin-bottom: 0.25rem !important; /* Override AdminLTE's mb-1 if too large */
    }
    .stats-card h3 {
        font-size: 1.75rem; /* Adjust as needed */
        word-wrap: break-word; /* Back to this for wrapping, might need tweaking if VND breaks again */
        line-height: 1.2; /* Adjust line height */
        margin-bottom: 0 !important;
    }
</style>
