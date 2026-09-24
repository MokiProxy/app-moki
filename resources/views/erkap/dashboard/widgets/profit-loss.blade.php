@php
$rupiahShort = fn ($number) => (float) $number >= 1000000000
    ? number_format((float) $number / 1000000000, 2, ',', '.') . ' M'
    : ((float) $number >= 1000000
        ? number_format((float) $number / 1000000, 1, ',', '.') . ' jt'
        : number_format((float) $number, 0, ',', '.'));
@endphp

<div class="card shadow-sm h-100">
    <div class="card-body border-bottom bg-light">
        <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-chart-line mdi-18px me-2"></i>Proyeksi Laba Rugi (P&L)</h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 mb-3">
            <span class="badge bg-success">Pendapatan: {{ $rupiahShort($profitLoss['totalRevenue']) }}</span>
            <span class="badge bg-danger">Beban: {{ $rupiahShort($profitLoss['totalExpense']) }}</span>
            <span class="badge bg-primary">Laba Rugi: {{ $rupiahShort($profitLoss['netProfit']) }}</span>
            <span class="badge bg-info">Margin: {{ $profitLoss['margin'] }}%</span>
        </div>
        <div id="chart-profit-loss"></div>
    </div>
</div>