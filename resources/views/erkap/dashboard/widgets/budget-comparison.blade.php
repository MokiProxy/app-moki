@php
$rupiahShort = fn ($number) => (float) $number >= 1000000000
    ? number_format((float) $number / 1000000000, 2, ',', '.') . ' M'
    : ((float) $number >= 1000000
        ? number_format((float) $number / 1000000, 1, ',', '.') . ' jt'
        : number_format((float) $number, 0, ',', '.'));
$opexUtilization = $budget['opexBudget'] > 0 ? round(($budget['opexRealized'] / $budget['opexBudget']) * 100, 2) : 0;
$capexUtilization = $budget['capexBudget'] > 0 ? round(($budget['capexRealized'] / $budget['capexBudget']) * 100, 2) : 0;
@endphp

<div class="card shadow-sm h-100">
    <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-chart-bar mdi-18px me-2"></i>Budget & Realisasi (BvA)</h5>
        <small class="text-muted">Klik bar untuk detail</small>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="badge bg-primary">OPEX Budget: {{ $rupiahShort($budget['opexBudget']) }}</span>
            <span class="badge bg-primary-subtle text-primary border">OPEX Realisasi: {{ $rupiahShort($budget['opexRealized']) }} ({{ $opexUtilization }}%)</span>
            <span class="badge bg-warning text-dark">CAPEX Budget: {{ $rupiahShort($budget['capexBudget']) }}</span>
            <span class="badge bg-warning-subtle text-dark border">CAPEX Realisasi: {{ $rupiahShort($budget['capexRealized']) }} ({{ $capexUtilization }}%)</span>
        </div>
        <div id="chart-budget"></div>
    </div>
</div>