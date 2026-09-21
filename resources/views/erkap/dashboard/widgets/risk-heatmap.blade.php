@php
$levelColor = [
    'VL' => '#4ade80',
    'L' => '#a3e635',
    'M' => '#facc15',
    'H' => '#fb923c',
    'VH' => '#ef4444',
];
$scoreLevel = function ($score) {
    return match (true) {
        $score >= 21 => 'VH',
        $score >= 16 => 'H',
        $score >= 11 => 'M',
        $score >= 6 => 'L',
        default => 'VL',
    };
};
@endphp

<div class="card shadow-sm h-100">
    <div class="card-body border-bottom bg-light">
        <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-grid mdi-18px me-2"></i>Risk Heat Map ({{ $year }})</h5>
    </div>
    <div class="card-body">
        @if($executiveSummary['totalRisks'] === 0)
        <div class="text-center text-muted py-5">
            <i class="mdi mdi-shield-outline mdi-48px"></i>
            <p class="mt-2 mb-0">Belum ada data risiko untuk periode ini.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th class="align-middle text-center text-muted fw-bold" style="width: 60px;">Prob \ Impact</th>
                        @for($impact = 1; $impact <= 5; $impact++)
                        <th class="text-center text-muted fw-bold" style="width: 60px;">{{ $impact }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @for($probability = 5; $probability >= 1; $probability--)
                    <tr>
                        <td class="text-center text-muted fw-bold">{{ $probability }}</td>
                        @for($impact = 1; $impact <= 5; $impact++)
                        @php
                        $cell = $heatMap[$probability][$impact] ?? null;
                        $total = $cell['total'] ?? 0;
                        $level = $scoreLevel($probability * $impact);
                        @endphp
                        <td>
                            <div class="heat-cell" data-probability="{{ $probability }}" data-impact="{{ $impact }}" data-total="{{ $total }}"
                                style="background: {{ $levelColor[$level] }}; opacity: {{ $total > 0 ? 0.95 : 0.35 }};">
                                <span>{{ $probability * $impact }}</span>
                                <span class="heat-count">{{ $total > 0 ? $total . ' risiko' : '' }}</span>
                            </div>
                        </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
            @foreach($levelColor as $level => $color)
            <span class="d-inline-flex align-items-center gap-1 small text-muted">
                <span style="width: 14px; height: 14px; background: {{ $color }}; border-radius: 3px; display: inline-block;"></span>
                {{ $level }}
            </span>
            @endforeach
            <span class="ms-auto small text-muted"><i class="mdi mdi-information-outline"></i> Klik sel untuk drill-down</span>
        </div>
        @endif
    </div>
</div>