@php
$rupiah = fn ($number) => 'Rp ' . number_format((float) $number, 0, ',', '.');
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-primary">
            <div class="card-body">
                <small class="text-white-50 text-uppercase fw-bold">Total Anggaran</small>
                <h4 class="mb-0 mt-1">{{ $rupiah($executiveSummary['totalBudget']) }}</h4>
                <div class="mt-2 small text-white-50">
                    OPEX: {{ $rupiah($executiveSummary['opexBudget']) }} &nbsp;|&nbsp; CAPEX: {{ $rupiah($executiveSummary['capexBudget']) }}
                </div>
            </div>
            <i class="mdi mdi-cash-multiple icon-overlay"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-success">
            <div class="card-body">
                <small class="text-white-50 text-uppercase fw-bold">Realisasi Anggaran</small>
                <h4 class="mb-0 mt-1">{{ $rupiah($executiveSummary['totalRealized']) }}</h4>
                <div class="progress mt-2" style="height: 8px; background: rgba(255,255,255,0.25);">
                    <div class="progress-bar bg-white" style="width: {{ min(100, $executiveSummary['utilization']) }}%"></div>
                </div>
                <div class="mt-1 small text-white-50">Utilisasi {{ $executiveSummary['utilization'] }}%</div>
            </div>
            <i class="mdi mdi-chart-donut icon-overlay"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-warning text-dark">
            <div class="card-body">
                <small class="text-dark-50 text-uppercase fw-bold">Program Kerja</small>
                <h4 class="mb-0 mt-1">{{ $executiveSummary['totalWorkPrograms'] }}</h4>
                <div class="mt-2 small">
                    Disetujui: <strong>{{ $executiveSummary['approvedPrograms'] }}</strong>
                </div>
            </div>
            <i class="mdi mdi-clipboard-text-outline icon-overlay"></i>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card bg-danger">
            <div class="card-body">
                <small class="text-white-50 text-uppercase fw-bold">Identifikasi Risiko</small>
                <h4 class="mb-0 mt-1">{{ $executiveSummary['totalRisks'] }}</h4>
                <div class="mt-2 small text-white-50">
                    <i class="mdi mdi-arrow-up-bold"></i> Positif: {{ $executiveSummary['positiveRisks'] }}
                    &nbsp;|&nbsp; <i class="mdi mdi-arrow-down-bold"></i> Negatif: {{ $executiveSummary['negativeRisks'] }}
                </div>
            </div>
            <i class="mdi mdi-shield-alert-outline icon-overlay"></i>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-bold">Rata-rata Skor Risiko</small>
                    <h4 class="mb-0 mt-1">{{ $executiveSummary['averageScore'] }}</h4>
                </div>
                <h2 class="text-primary mb-0 fw-bold">{{ round($executiveSummary['averageScore']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-9 col-md-12">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <small class="text-muted text-uppercase fw-bold">Distribusi Level Risiko</small>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach(['VL' => '#4ade80', 'L' => '#a3e635', 'M' => '#facc15', 'H' => '#fb923c', 'VH' => '#ef4444'] as $level => $color)
                    <span class="badge rounded-pill px-3 py-2" style="background: {{ $color }}; color: #1e293b;">
                        {{ $level }}: {{ $riskSummary['levelCounts'][$level] ?? 0 }}
                    </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>