<div class="card shadow-sm h-100">
    <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-chart-donut mdi-18px me-2"></i>Program Kerja per Status</h5>
        <small class="text-muted">Tahun {{ $selectedRkap->year ?? $year }}</small>
    </div>
    <div class="card-body">
        <div id="chart-program"></div>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-2">
            <span class="small text-muted">Total: <strong>{{ $program['totalPrograms'] }}</strong></span>
            <span class="small text-muted">Disetujui: <strong>{{ $program['approvedPrograms'] }}</strong></span>
            @foreach($programDonutLabels as $index => $label)
            <span class="small text-muted">{{ $label }}: <strong>{{ $programDonutValues[$index] }}</strong></span>
            @endforeach
        </div>
    </div>
</div>