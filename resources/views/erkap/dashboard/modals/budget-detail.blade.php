@isset($realizations)
<div class="p-3 bg-light border-bottom">
    <strong>{{ $type === 'opex' ? 'OPEX (Biaya Rutin)' : 'CAPEX (Investasi)' }}</strong>
    &nbsp;|&nbsp; Bulan: {{ \Illuminate\Support\Carbon::createFromDate(null, $month, 1)->translatedFormat('F') }} {{ $year }}
</div>
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Program Kerja</th>
                <th>Kebutuhan / Investasi</th>
                <th>Divisi</th>
                <th class="text-end">Anggaran</th>
                <th class="text-end">Realisasi</th>
                <th class="text-end">Variance</th>
                <th class="text-center">Variance %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($realizations as $realization)
            @php
            $cost = $realization->routineCost ?? $realization->investmentPlan;
            $program = $realization->routineCost?->workProgram ?? $realization->investmentPlan?->workProgram;
            $division = $program?->riskIdentification?->departmentTarget?->division;
            @endphp
            <tr>
                <td class="fw-bold">{{ $program->name ?? '-' }}</td>
                <td>{{ $cost->need ?? $cost->name ?? '-' }}</td>
                <td>{{ $division->name ?? '-' }}</td>
                <td class="text-end">{{ number_format($realization->budgeted, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($realization->realized, 0, ',', '.') }}</td>
                <td class="text-end {{ $realization->variance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($realization->variance, 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($realization->variance_percent, 2, ',', '.') }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted">Belum ada data realisasi untuk periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot class="bg-light">
            <tr>
                <th colspan="3" class="text-end">TOTAL</th>
                <th class="text-end">{{ number_format($totals['budgeted'], 0, ',', '.') }}</th>
                <th class="text-end">{{ number_format($totals['realized'], 0, ',', '.') }}</th>
                <th class="text-end {{ $totals['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totals['variance'], 0, ',', '.') }}</th>
                <th class="text-center"></th>
            </tr>
        </tfoot>
    </table>
</div>
@else
<div class="text-center text-muted py-4">
    <i class="mdi mdi-cursor-default-click-outline mdi-36px"></i>
    <p class="mt-2 mb-0">Klik bar pada grafik Budget untuk melihat detail realisasi.</p>
</div>
@endisset