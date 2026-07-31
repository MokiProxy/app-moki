@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.ITAdmin')

@section('title', 'Dashboard IT Admin')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        --danger-grad: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        --info-grad: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
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

    .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    .bg-danger-grad {
        background: var(--danger-grad);
    }

    .bg-info-grad {
        background: var(--info-grad);
    }

    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
    }

    .role-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .progress-bar-custom {
        height: 8px;
        border-radius: 9999px;
        background-color: #e2e8f0;
        overflow: hidden;
    }

    .progress-bar-custom .fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.6s ease;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-0">
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <h1 class="fw-bold text-dark mb-0">Halo, {{ explode(" ", $authUserName)[0] }}!</h1>
            <p>Selamat datang di IT Admin Panel</p>
        </div>
        <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>{{ date('d F Y') }}</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card stat-card bg-indigo-grad p-4">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-1 fw-medium opacity-75">Total User</p>
                        <h2 class="fw-bold mb-0">{{ $totalUsers }}</h2>
                    </div>
                </div>
                <div class="icon-overlay">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card bg-emerald-grad p-4">
                <div class="d-flex align-items-center">
                    <div>
                        <p class="mb-1 fw-medium opacity-75">Total Role</p>
                        <h2 class="fw-bold mb-0">{{ $totalRoles }}</h2>
                    </div>
                </div>
                <div class="icon-overlay">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid fa-users-gear me-2 text-indigo"></i>
                        User per Role
                    </h5>
                    @if($roles->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px">No</th>
                                    <th>Role</th>
                                    <th style="width: 80px" class="text-center">Total</th>
                                    <th style="width: 120px">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $key => $role)
                                @php
                                    $percentage = $totalUsers > 0 ? round(($role->users_count / $totalUsers) * 100, 1) : 0;
                                    $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899', '#14b8a6'];
                                    $barColor = $colors[$key % count($colors)];
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $key + 1 }}</td>
                                    <td class="fw-semibold text-capitalize">{{ $role->name }}</td>
                                    <td class="text-center fw-bold">{{ $role->users_count }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress-bar-custom flex-grow-1">
                                                <div class="fill" style="width: {{ $percentage }}%; background: {{ $barColor }};"></div>
                                            </div>
                                            <small class="text-muted fw-semibold">{{ $percentage }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada role.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid fa-chart-pie me-2 text-indigo"></i>
                        Persebaran User per Role
                    </h5>
                    @if($roles->count() > 0)
                        @foreach($roles as $key => $role)
                        @php
                            $percentage = $totalUsers > 0 ? round(($role->users_count / $totalUsers) * 100, 1) : 0;
                            $colors = ['bg-indigo-grad', 'bg-emerald-grad', 'bg-amber-grad', 'bg-danger-grad', 'bg-info-grad'];
                            $gradClass = $colors[$key % count($colors)];
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold text-capitalize">{{ $role->name }}</span>
                                <span class="fw-bold">{{ $role->users_count }} user ({{ $percentage }}%)</span>
                            </div>
                            <div class="card stat-card {{ $gradClass }} p-3">
                                <div class="icon-overlay">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="opacity-75">Role</small>
                                    <small class="opacity-75">{{ $percentage }}%</small>
                                </div>
                                <div class="progress mt-1" style="height: 6px; background: rgba(255,255,255,0.3);">
                                    <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada data pengguna.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
