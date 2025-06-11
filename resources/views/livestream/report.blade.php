@extends('adminlte::page')

@section('title', 'Modern Live Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div class="tiktok-branding">
            <i class="fab fa-tiktok"></i>
            <span>Live Dashboard</span>
        </div>
        <div class="live-status">
            <button id="live-toggle" class="live-indicator">
                <span class="dot"></span>
                LIVE
            </button>
            <button class="help-button" data-toggle="modal" data-target="#helpModal" title="Hướng dẫn">
                <i class="fas fa-question-circle"></i>
            </button>
        </div>
    </div>
@stop

@section('content')
<div class="live-dashboard-container">
    <div class="row">
        <div class="col-12">
            <div class="main-stats-card">
                <p class="stat-label">Doanh Thu (đ)</p>
                <h1 id="realtime-revenue" class="stat-value-large jumpable">0</h1>
                <div class="sub-stat-container">
                    <i class="fas fa-box-open"></i>
                    <span>Tổng Đơn Hàng:</span>
                    <strong id="realtime-orders" class="jumpable">0</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="card-header">
                    <h6 class="section-title"><i class="fas fa-crown"></i> Top 5 Sản Phẩm</h6>
                </div>
                <div class="chart-container">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="card-header">
                    <h6 class="section-title"><i class="fas fa-map-marked-alt"></i> Top 5 Tỉnh Thành</h6>
                </div>
                <div class="chart-container">
                    <canvas id="topProvincesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="card-header">
                    <h6 class="section-title"><i class="fas fa-shopping-bag"></i> Đơn Hàng Mới Nhất</h6>
                </div>
                <div id="latest-order-info" class="latest-order-grid">
                    {{-- JS will populate this --}}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel"><i class="fas fa-info-circle"></i> Hướng Dẫn Sử Dụng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Nút <strong>LIVE</strong> dùng để điều khiển việc cập nhật dữ liệu real-time từ Google Sheets.</p>
                <ul>
                    <li><strong>Bật LIVE (nút có màu đỏ):</strong> Hệ thống sẽ tự động lấy dữ liệu mới mỗi 5 giây.</li>
                    <li><strong>Tắt LIVE (nút có màu xám):</strong> Hệ thống sẽ ngừng cập nhật tự động.</li>
                </ul>
                <p>Trang sẽ tự động bật chế độ LIVE khi được tải.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
{{-- CSRF Token for AJAX --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    :root {
        --bg-color: #0d0d1a;
        --card-bg: #1a1a38;
        --border-color: rgba(255, 255, 255, 0.1);
        --glow-color-pink: rgba(192, 38, 211, 0.5);
        --glow-color-purple: rgba(37, 117, 252, 0.5);
        --gradient-pink: linear-gradient(135deg, #c026d3, #7c3aed);
        --gradient-purple: linear-gradient(135deg, #2575fc, #6a11cb);
    }

    @keyframes background-pan {
        0% { background-position: 0% center; }
        100% { background-position: -200% center; }
    }

    body { 
        background-color: var(--bg-color);
        background-image: radial-gradient(circle at top right, rgba(124, 58, 237, 0.15), transparent 40%),
                          radial-gradient(circle at bottom left, rgba(192, 38, 211, 0.15), transparent 40%);
        color: #fff;
        font-family: 'Inter', sans-serif;
        overflow-y: hidden;
    }
    
    .content-wrapper, .main-header, .main-sidebar { 
        background-color: transparent !important;
        border: none;
    }
    .main-sidebar {
        background-color: rgba(13, 13, 26, 0.8) !important;
        backdrop-filter: blur(10px);
    }

    .live-dashboard-container {
        padding: 1.5rem;
        height: calc(100vh - 80px);
        overflow-y: auto;
    }
    
    .live-status {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .live-indicator, .help-button {
        background-color: rgba(255,255,255,0.05);
        border: 1px solid var(--border-color);
        backdrop-filter: blur(5px);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .help-button {
        padding: 0.5rem 0.75rem;
        font-size: 1.1rem;
    }

    .live-indicator .dot {
        width: 10px;
        height: 10px;
        background-color: #888;
        border-radius: 50%;
        display: inline-block;
        transition: background-color 0.3s ease;
    }

    .live-indicator.active .dot {
        background-color: #ef4444;
        animation: pulse 1.5s infinite;
    }
    .live-indicator.active {
        border-color: #ef4444;
    }

    .main-stats-card {
        border-radius: 20px;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        background: var(--gradient-pink);
        box-shadow: 0 0 20px var(--glow-color-pink), 0 0 40px var(--glow-color-pink);
        min-height: 220px;
    }
    
    .stat-label { 
        font-size: 1.5rem; /* Slightly larger label */
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        opacity: 0.8;
    }
    
    .stat-value-large { 
        font-size: 6rem; /* INCREASED SIZE */
        font-weight: 700;
        line-height: 1.1;
        margin: 0.5rem 0;
        text-shadow: 0 0 20px rgba(255,255,255,0.3);
    }

    .sub-stat-container {
        background-color: rgba(0,0,0,0.25);
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        margin-top: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.25rem;
        font-weight: 500;
        backdrop-filter: blur(5px);
    }
    .sub-stat-container strong {
        font-weight: 700;
        color: #fff;
    }

    .chart-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 1.5rem;
        height: 420px;
        backdrop-filter: blur(5px);
    }
    
    .chart-container, .latest-order-grid {
        height: 350px;
    }

    .latest-order-grid {
        height: 350px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .info-item { 
        background-color: rgba(0,0,0,0.2);
        padding: 1rem 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
        animation: fadeInUp 0.5s ease forwards;
    }
    .info-item:hover {
        transform: translateY(-5px);
        background-color: rgba(0,0,0,0.4);
    }
    
    .info-item i {
        font-size: 1.5rem;
        color: var(--glow-color-pink);
    }
    .info-item .info-content {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }
    .info-item .info-content label {
        font-size: 0.8rem;
        text-transform: uppercase;
        opacity: 0.6;
    }
    .info-item .info-content span {
        font-size: 1rem;
        font-weight: 500;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes jump {
        0% { transform: translateY(0) scale(1); }
        40% { transform: translateY(-10px) scale(1.1); }
        60% { transform: translateY(5px) scale(0.95); }
        100% { transform: translateY(0) scale(1); }
    }
    .jumpable {
        animation-duration: 0.5s;
    }

    /* Modal styles remain the same */
    .modal-content {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        backdrop-filter: blur(10px);
    }
    .modal-header {
        border-bottom: 1px solid var(--border-color);
    }
    .modal-footer {
        border-top: 1px solid var(--border-color);
    }
    .modal-title {
        color: var(--text-primary);
    }
    .close {
        color: var(--text-primary);
        text-shadow: none;
        opacity: 0.8;
    }
    .close:hover {
        opacity: 1;
    }
</style>
@stop

@section('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let updateInterval = null;
        let isLive = false;

        const chartOptions = {
            type: 'line',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.9)',
                    callbacks: {
                        label: (c) => ` Doanh thu: ${c.raw.toLocaleString('vi-VN')} VNĐ`
                    }
                }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    ticks: { 
                        color: 'rgba(255,255,255,0.7)',
                        maxRotation: 0,
                        minRotation: 0,
                        callback: function(value) {
                            const label = this.getLabelForValue(value);
                            if (label.length > 20) {
                                return label.substring(0, 20) + '...';
                            }
                            return label;
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: {
                        color: 'rgba(255,255,255,0.7)',
                        callback: (v) => {
                            if (v >= 1000000) return `${v / 1000000}M`;
                            if (v >= 1000) return `${v / 1000}K`;
                            return v;
                        }
                    }
                }
            }
        };
        
        const topProductsChart = new Chart(document.getElementById('topProductsChart'), { type: 'line', options: chartOptions });
        const topProvincesChart = new Chart(document.getElementById('topProvincesChart'), { type: 'line', options: chartOptions });

        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}'
        });

        pusher.subscribe('livestream-channel').bind('livestream-update', function(data) {
            updateStat('realtime-revenue', data.total_revenue, true);
            updateStat('realtime-orders', data.total_orders, false);
            
            const latestOrderContainer = document.getElementById('latest-order-info');
            if (data.latest_order) {
                latestOrderContainer.innerHTML = `
                    <div class="info-item">
                        <i class="fas fa-user-circle"></i>
                        <div class="info-content">
                            <label>Khách hàng</label>
                            <span>${data.latest_order.customer_name}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-box"></i>
                        <div class="info-content">
                            <label>Sản phẩm</label>
                            <span>${data.latest_order.product_name}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <label>Tỉnh</label>
                            <span>${data.latest_order.province}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-truck"></i>
                        <div class="info-content">
                            <label>Trạng thái</label>
                            <span>${data.latest_order.status}</span>
                        </div>
                    </div>
                `;
            } else {
                latestOrderContainer.innerHTML = '<p class="text-center opacity-50">Chưa có đơn hàng mới...</p>';
            }
            
            updateChart(topProductsChart, data.top_products, 'rgba(192, 38, 211, 0.85)');
            updateChart(topProvincesChart, data.top_provinces, 'rgba(37, 117, 252, 0.85)');
        });

        function updateChart(chart, newData, color) {
            if (!newData) return;
            const labels = Object.keys(newData);
            const dataPoints = Object.values(newData).map(item => item.revenue);

            const gradient = chart.ctx.createLinearGradient(0, 0, 0, chart.height);
            gradient.addColorStop(0, color.replace('0.85', '0.5'));
            gradient.addColorStop(1, color.replace('0.85', '0'));

            chart.data = {
                labels: labels,
                datasets: [{
                    data: dataPoints,
                    borderColor: color,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: color,
                    pointBorderColor: '#fff',
                    pointHoverRadius: 7,
                    pointRadius: 5,
                }]
            };
            chart.update('none');
        }

        function updateStat(elementId, value, isCurrency) {
            const el = document.getElementById(elementId);
            if (el) {
                const finalValue = value !== null && value !== undefined ? value : 0;
                el.innerText = isCurrency ? finalValue.toLocaleString('vi-VN') : finalValue.toLocaleString();
                el.classList.add('jump');
                setTimeout(() => el.classList.remove('jump'), 500);
            }
        }

        async function triggerUpdate() {
            if (!isLive) return;
            try {
                await fetch('{{ route("livestream.triggerUpdate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });
            } catch (error) {
                console.error('Error triggering update:', error);
            }
        }

        function toggleLivestream() {
            isLive = !isLive;
            document.getElementById('live-toggle').classList.toggle('active', isLive);
            if (isLive) {
                triggerUpdate();
                updateInterval = setInterval(triggerUpdate, 30000);
            } else {
                clearInterval(updateInterval);
            }
        }

        document.getElementById('live-toggle').addEventListener('click', toggleLivestream);

        toggleLivestream();
    });
</script>
@stop

