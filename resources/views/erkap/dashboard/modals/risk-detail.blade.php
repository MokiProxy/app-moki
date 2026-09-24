@isset($assessments)
@php
$levelColor = ['VL' => '#4ade80', 'L' => '#a3e635', 'M' => '#facc15', 'H' => '#fb923c', 'VH' => '#ef4444'];
@endphp
<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Risk</th>
                <th>Sasaran</th>
                <th>Divisi</th>
                <th class="text-center">Prob</th>
                <th class="text-center">Impact</th>
                <th class="text-center">Skor</th>
                <th class="text-center">Level</th>
                <th>Mitigasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assessments as $assessment)
            @php
            $score = (int) ($assessment->inherent_score ?? 0);
            $level = match (true) {
                $score >= 21 => 'VH',
                $score >= 16 => 'H',
                $score >= 11 => 'M',
                $score >= 6 => 'L',
                default => 'VL',
            };
            $departmentTarget = $assessment->riskIdentification?->departmentTarget;
            @endphp
            <tr>
                <td class="fw-bold">{{ $assessment->riskIdentification?->risk ?? '-' }}</td>
                <td>{{ $departmentTarget->target ?? '-' }}</td>
                <td>{{ $departmentTarget->division->name ?? '-' }}</td>
                <td class="text-center">{{ $assessment->inherent_probability }}</td>
                <td class="text-center">{{ $assessment->inherent_impact }}</td>
                <td class="text-center fw-bold">{{ $score }}</td>
                <td class="text-center">
                    <span class="badge rounded-pill" style="background: {{ $levelColor[$level] }}; color: #1e293b;">{{ $level }}</span>
                </td>
                <td>{{ \Illuminate\Support\Str::limit($assessment->mitigation_plan, 60) ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted">Tidak ada risiko pada kombinasi ini.</td>
            </tr>
            @endforelse
        </tbody>
        @if($assessments->isNotEmpty())
        <tfoot class="bg-light">
            <tr>
                <th colspan="4">{{ $assessments->count() }} risiko</th>
                <th colspan="4" class="text-start">
                    @if(isset($summarized) && $summarized['averageScore'] > 0)
                    Rata-rata skor: {{ $summarized['averageScore'] }}
                    @endif
                </th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@else
<div class="text-center text-muted py-4">
    <i class="mdi mdi-cursor-default-click-outline mdi-36px"></i>
    <p class="mt-2 mb-0">Klik sel pada Risk Heat Map untuk melihat detail risiko.</p>
</div>
@endisset