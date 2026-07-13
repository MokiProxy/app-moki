@extends('layouts.Helpdesk')

@section('title', 'Dashboard Helpdesk')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
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
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold text-dark mb-0">Help Desk Overview</h4>
        <span class="text-muted"><i class="fa-regular fa-calendar me-2"></i>{{ date('d F Y') }}</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-indigo-grad p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75">TOTAL TIKET</p>
                        <h2 class="fw-bold mb-0">0</h2>
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
                        <p class="mb-0 opacity-75">TIKET SELESAI</p>
                        <h2 class="fw-bold mb-0">0</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartUser"></canvas>
                        <div class="chart-label">0%</div>
                    </div>
                </div>
                <i class="fa-solid fa-user-tag icon-overlay"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-emerald-grad p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75">TIKET BELUM SELESAI</p>
                        <h2 class="fw-bold mb-0">0</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="chartReady"></canvas>
                        <div class="chart-label">0%</div>
                    </div>
                </div>
                <i class="fa-solid fa-circle-check icon-overlay"></i>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection
