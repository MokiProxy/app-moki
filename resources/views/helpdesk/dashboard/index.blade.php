@php
$role = session('user_role');
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.Helpdesk')

@section('title', 'Dashboard Helpdesk')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        --danger-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    }

    body {
        background-color: #f1f5f9;
        font-family: 'Inter', sans-serif;
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: 0.3s;
    }

    .stat-card {
        color: white;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .bg-indigo-grad {
        background: var(--primary-grad);
    }

    .bg-amber-grad {
        background: var(--warning-grad);
    }

    .bg-emerald-grad {
        background: var(--success-grad);
    }

    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .chart-container {
        position: relative;
        height: 100px;
        width: 100px;
        margin: 0 auto;
    }

    .chart-label {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        font-size: 1.1rem;
    }

    .table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #475569;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-0">
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h1 class="fw-bold text-dark mb-0">Halo, {{ explode(" ", $authUserName)[0] }}!</h1>
            <p>Selamat Datang di Help Desk!</p>
        </div>
        <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>{{ date('d F Y') }}</span>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card stat-card bg-indigo-grad p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 opacity-75">Total Tiket</p>
                        <h2 class="fw-bold mb-0 fs-1">{{ $totalTicket }}</h2>
                    </div>
                </div>
                <i class="fa-solid fa-boxes-stacked icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-amber-grad p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 opacity-75">Tiket Open</p>
                        <h2 class="fw-bold mb-0 fs-1">{{ $openTicket }}</h2>
                    </div>
                </div>
                <i class="fa-solid fa-boxes-stacked icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-indigo-grad p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 opacity-75">Tiket In Progress</p>
                        <h2 class="fw-bold mb-0 fs-1">{{ $inProgressTicket }}</h2>
                    </div>
                </div>
                <i class="fa-solid fa-boxes-stacked icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-emerald-grad p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-0 opacity-75">Tiket Closed</p>
                        <h2 class="fw-bold mb-0 fs-1">{{ $closedTicket }}</h2>
                    </div>
                </div>
                <i class="fa-solid fa-boxes-stacked icon-overlay"></i>
            </div>
        </div>

    </div>

    <div class="row g-4 mb-4">

        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-chart-line text-primary me-2"></i>Ticket Trend
                    </h6>
                    <div class="btn-group btn-group-sm" id="trendFilter">
                        <button class="btn btn-outline-primary active" data-filter="7d">7 Hari</button>
                        <button class="btn btn-outline-primary" data-filter="30d">30 Hari</button>
                        <button class="btn btn-outline-primary" data-filter="3m">3 Bulan</button>
                        <button class="btn btn-outline-primary" data-filter="1y">1 Tahun</button>
                    </div>
                </div>
                <div style="height: 320px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-3 text-center">Ticket by Status</h6>
                <div style="height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->role_id === App\Models\User::ROLE_SUPERADMIN)
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-chart-bar text-primary me-2"></i>Ticket by Division
                </h6>
                <div style="height: 300px;">
                    <canvas id="divisionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-3 text-center">Ticket by Category</h6>
                <div style="height: 250px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-4 mb-4">

        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Tickets
                </h6>
                @forelse($recentTickets as $ticket)
                <div class="d-flex align-items-start gap-3 pb-3 {{ !$loop->last ? 'border-bottom mb-3' : '' }}">
                    <div class="avatar-circle flex-shrink-0">
                        {{ strtoupper(substr($ticket->requester->employee->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="min-width-0">
                                <p class="fw-bold mb-0 text-truncate">{{ $ticket->title }}</p>
                                <small class="text-muted">
                                    {{ $ticket->ticket_number }} &middot;
                                    {{ $ticket->requester->employee->name ?? '-' }}
                                </small>
                            </div>
                            @php
                                $statusColor = match($ticket->status) {
                                    'OPEN' => 'primary',
                                    'ASSIGNED', 'PENDING', 'IN_PROGRESS' => 'warning',
                                    'RESOLVED', 'CLOSED' => 'success',
                                    'REJECTED' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusColor }} rounded-pill flex-shrink-0 ms-2" style="font-size:0.65rem;">
                                {{ $ticket->status }}
                            </span>
                        </div>
                        <small class="text-muted">{{ $ticket->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3 mb-0">Belum ada tiket</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-history text-primary me-2"></i>Recent Activities
                </h6>
                @forelse($recentActivities as $activity)
                <div class="d-flex align-items-start gap-3 pb-3 {{ !$loop->last ? 'border-bottom mb-3' : '' }}">
                    <div class="avatar-circle flex-shrink-0" style="background:#e0e7ff; color:#4f46e5;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size:0.8rem;"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <p class="fw-bold mb-0 text-truncate" style="font-size:0.85rem;">
                            {{ $activity->formatted_action }}
                        </p>
                        <small class="text-muted">
                            {{ $activity->ticket->ticket_number ?? '-' }} &middot;
                            {{ $activity->user->name ?? '-' }}
                        </small>
                        @if($activity->description)
                        <br><small class="text-muted fst-italic">{{ Str::limit($activity->description, 60) }}</small>
                        @endif
                        <br><small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3 mb-0">Belum ada aktivitas</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let trendChart;

    function loadTrendChart(filter = '7d') {
        fetch(`{{ route('helpdesk.dashboard.chart-data') }}?filter=${filter}`)
            .then(res => res.json())
            .then(json => {
                const ctx = document.getElementById('trendChart').getContext('2d');
                if (trendChart) trendChart.destroy();
                trendChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: json.labels,
                        datasets: [{
                            label: 'Jumlah Tiket',
                            data: json.data,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79,70,229,0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#4f46e5'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2, 2] } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            });
    }

    document.querySelectorAll('#trendFilter .btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#trendFilter .btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadTrendChart(this.dataset.filter);
        });
    });

    loadTrendChart('7d');

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($statusCounts->pluck('status')) !!},
            datasets: [{
                data: {!! json_encode($statusCounts->pluck('total')) !!},
                backgroundColor: [
                    '#3b82f6',
                    '#8b5cf6',
                    '#f59e0b',
                    '#6b7280',
                    '#10b981',
                    '#1e293b',
                    '#ef4444',
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } } },
            cutout: '60%'
        }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: {!! json_encode($categoryCounts->keys()) !!},
            datasets: [{
                data: {!! json_encode($categoryCounts->values()) !!},
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } } }
        }
    });

    @if(auth()->user()->role_id === App\Models\User::ROLE_SUPERADMIN)
    new Chart(document.getElementById('divisionChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($divisionCounts->keys()) !!},
            datasets: [{
                label: 'Jumlah Tiket',
                data: {!! json_encode($divisionCounts->values()) !!},
                backgroundColor: '#4f46e5',
                borderRadius: 6,
                barThickness: 40,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2, 2] } },
                x: { grid: { display: false } }
            }
        }
    });
    @endif
</script>
@endsection
