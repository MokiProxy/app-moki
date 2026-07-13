@extends('layouts.App')

@section('title', 'Dashboard I-Control Asset')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
    body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
    .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: 0.3s; }
    .stat-card { color: white; position: relative; overflow: hidden; }
    .stat-card:hover { transform: translateY(-5px); }
    .bg-indigo-grad { background: var(--primary-grad); }
    .bg-amber-grad { background: var(--warning-grad); }
    .bg-emerald-grad { background: var(--success-grad); }
    .icon-overlay { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.15; transform: rotate(-15deg); }
    .chart-container { position: relative; height: 100px; width: 100px; margin: 0 auto; }
    .chart-label { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 1.1rem; }
    .table thead th { background-color: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; border: none; }
    .avatar-circle { width: 35px; height: 35px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold text-dark mb-0">System Overview</h4>
        <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>{{ date('d F Y') }}</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-indigo-grad p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75">TOTAL ASSETS</p>
                        <h2 class="fw-bold mb-0">{{ $totalAsset }}</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartTotal"></canvas>
                        <div class="chart-label">100%</div>
                    </div>
                </div>
                <i class="fa-solid fa-boxes-stacked icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-amber-grad p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75">ASSETS IN USE</p>
                        <h2 class="fw-bold mb-0">{{ $assetUser }}</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartUser"></canvas>
                        <div class="chart-label">{{ $percentUser }}%</div>
                    </div>
                </div>
                <i class="fa-solid fa-user-tag icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-emerald-grad p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75">STANDBY ASSETS</p>
                        <h2 class="fw-bold mb-0">{{ $assetReady }}</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartReady"></canvas>
                        <div class="chart-label">{{ $percentReady }}%</div>
                    </div>
                </div>
                <i class="fa-solid fa-circle-check icon-overlay"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i>Asset Movement Trend</h6>
                    <div class="badge bg-light text-dark p-2">Last 6 Months</div>
                </div>
                <div style="height: 320px;">
                    <canvas id="mainMovementChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-4">Top Asset Categories</h6>
                @foreach($categories as $cat)
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">{{ $cat->name }}</span>
                        <span class="fw-bold small">{{ $cat->assets_count }} Unit</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px;">
                        @php $w = $totalAsset > 0 ? ($cat->assets_count / $totalAsset) * 100 : 0; @endphp
                        <div class="progress-bar bg-primary" style="width: {{ $w }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="card p-4">
                <h6 class="fw-bold mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent System Activity</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Asset Detail</th>
                                <th class="text-center">Action</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestActivities as $log)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3">{{ substr($log->transaction->employee->name ?? 'U', 0, 1) }}</div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $log->transaction->employee->name ?? 'System' }}</div>
                                            <small class="text-muted">{{ $log->transaction->employee->employee_id ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $log->asset->brand ?? '-' }}</div>
                                    <small class="text-muted">UID: {{ $log->asset->uid ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $log->transaction->type == 'OUT' ? 'bg-danger' : 'bg-success' }} rounded-pill px-3">
                                        {{ $log->transaction->type }}
                                    </span>
                                </td>
                                <td>{{ $log->created_at->format('d M Y') }}</td>
                                <td><small class="text-muted italic">{{ Str::limit($log->transaction->note ?? '-', 30) }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Konfigurasi Chart Kecil (Doughnut)
    const donutConfig = (value, color) => ({
        type: 'doughnut',
        data: { datasets: [{ data: [value, 100-value], backgroundColor: ['rgba(255,255,255,0.9)', 'rgba(255,255,255,0.2)'], borderWidth: 0 }] },
        options: { cutout: '80%', plugins: { legend: false, tooltip: false } }
    });

    new Chart(document.getElementById('chartTotal'), donutConfig(100, '#fff'));
    new Chart(document.getElementById('chartUser'), donutConfig({{ $percentUser }}, '#fff'));
    new Chart(document.getElementById('chartReady'), donutConfig({{ $percentReady }}, '#fff'));

    // Grafik Tren Pergerakan
    const ctx = document.getElementById('mainMovementChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 400);
    grad.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    grad.addColorStop(1, 'rgba(59, 130, 246, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [{
                label: 'Asset Out',
                data: {!! json_encode($dataOut) !!},
                borderColor: '#3b82f6',
                borderWidth: 3,
                fill: true,
                backgroundColor: grad,
                tension: 0.4
            }, {
                label: 'Asset In',
                data: {!! json_encode($dataIn) !!},
                borderColor: '#10b981',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [2, 2] } }, x: { grid: { display: false } } }
        }
    });
</script>
@endsection