@php
    $departmentTarget = $model->departmentTarget;
    $rating = $departmentTarget?->ratingCriteria?->rating;
    $ratingBadge = $rating === 'A' ? 'success' : ($rating === 'B' ? 'warning' : ($rating === 'C' ? 'danger' : 'secondary'));
@endphp

<div class="card mb-3 approval-section">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="mdi mdi-file-document-outline me-1"></i> Ringkasan Risiko</h6>
    </div>
    <div class="card-body">
        @include('erkap.approvals.partials.fields', [
            'columns' => 3,
            'fields' => [
                [
                    'label' => 'Risiko',
                    'value' => e($model->risk),
                    'html' => true,
                ],
                [
                    'label' => 'Arah Risiko',
                    'value' => $model->risk_direction === 'positive' ? 'Positif' : 'Negatif',
                    'badge' => $model->risk_direction === 'positive' ? 'success' : 'danger',
                ],
                [
                    'label' => 'Status Dokumen',
                    'value' => $model->statusLabel(),
                    'badge' => 'soft-'.$model->statusClass(),
                ],
                [
                    'label' => 'Divisi',
                    'value' => $departmentTarget?->division?->name ?? '-',
                ],
                [
                    'label' => 'Sasaran Departemen',
                    'value' => $departmentTarget?->target ?? '-',
                ],
                [
                    'label' => 'Rating Sasaran',
                    'value' => $rating ?? '-',
                    'badge' => $ratingBadge,
                ],
                [
                    'label' => 'Risk Type',
                    'value' => $model->riskType?->name ?? '-',
                ],
                [
                    'label' => 'Risk Taxonomy',
                    'value' => $model->riskTaxonomy?->name ?? '-',
                ],
                [
                    'label' => 'Risk Appetite',
                    'value' => $model->riskTaxonomy?->riskAppetite?->name ?? '-',
                ],
            ],
        ])
    </div>
</div>

@include('erkap.approvals.partials.collection', [
    'title' => 'Sumber Risiko',
    'icon' => 'mdi-source-branch',
    'unit' => 'sumber',
    'empty' => 'Belum ada sumber risiko yang dicatat.',
    'columns' => [
        ['key' => 'reason', 'label' => 'Sumber / Penyebab Risiko'],
    ],
    'rows' => $model->reasons->map(fn ($reason) => ['reason' => e($reason->reason)])->all(),
])

@include('erkap.approvals.partials.collection', [
    'title' => 'Dampak',
    'icon' => 'mdi-alert-circle-outline',
    'unit' => 'dampak',
    'empty' => 'Belum ada dampak yang dicatat.',
    'columns' => [
        ['key' => 'impact', 'label' => 'Dampak Risiko'],
    ],
    'rows' => $model->impacts->map(fn ($impact) => ['impact' => e($impact->impact)])->all(),
])

@php
    $analysisRows = $model->analysis->map(function ($analysis) {
        $score = $analysis->riskScoreValue?->score;
        $level = $analysis->riskScoreValue?->level;
        $scoreBadge = $score === null
            ? 'secondary'
            : ($score >= 20 ? 'danger' : ($score >= 10 ? 'warning' : 'success'));

        return [
            'probability' => e(trim(($analysis->riskProbability?->name ?? '-').' ('.($analysis->riskProbability?->point ?? '-').')')),
            'impact' => e(trim(($analysis->riskImpact?->name ?? '-').' ('.($analysis->riskImpact?->point ?? '-').')')),
            'score' => $score === null ? '-' : '<span class="badge bg-'.$scoreBadge.'">'.$score.'</span>',
            'level' => $level === null ? '-' : '<span class="badge bg-'.$scoreBadge.'">'.e($level).'</span>',
        ];
    })->all();
@endphp

@include('erkap.approvals.partials.collection', [
    'title' => 'Analisis Risiko',
    'icon' => 'mdi-calculator-variant-outline',
    'unit' => 'analisis',
    'empty' => 'Belum ada analisis risiko yang dicatat.',
    'columns' => [
        ['key' => 'probability', 'label' => 'Probabilitas'],
        ['key' => 'impact', 'label' => 'Dampak'],
        ['key' => 'score', 'label' => 'Skor', 'class' => 'text-center'],
        ['key' => 'level', 'label' => 'Level', 'class' => 'text-center'],
    ],
    'rows' => $analysisRows,
])

@include('erkap.approvals.partials.collection', [
    'title' => 'Strategi Mitigasi',
    'icon' => 'mdi-shield-check-outline',
    'unit' => 'strategi',
    'empty' => 'Belum ada strategi mitigasi. Form 1 tidak bisa diajukan tanpa strategi.',
    'columns' => [
        ['key' => 'strategy', 'label' => 'Strategi Mitigasi'],
    ],
    'rows' => $model->departmentRiskStrategies->map(fn ($strategy) => ['strategy' => e($strategy->strategy)])->all(),
])

@php
    $workProgramRows = $model->workPrograms->map(fn ($workProgram) => [
        'code' => e($workProgram->code ?? '-'),
        'name' => e($workProgram->name),
        'units' => e($workProgram->units ?? '-'),
        'year_plan' => $workProgram->year_plan === null
            ? '-'
            : e(number_format((float) $workProgram->year_plan, 2, ',', '.')).'%',
        'status' => '<span class="badge bg-soft-'.$workProgram->statusClass().' text-'.$workProgram->statusClass().'">'.e($workProgram->statusLabel()).'</span>',
    ])->all();
@endphp

@include('erkap.approvals.partials.collection', [
    'title' => 'Program Kerja',
    'icon' => 'mdi-clipboard-list-outline',
    'unit' => 'program',
    'empty' => 'Belum ada program kerja. Form 1 tidak bisa diajukan tanpa program kerja.',
    'columns' => [
        ['key' => 'code', 'label' => 'Kode'],
        ['key' => 'name', 'label' => 'Program Kerja'],
        ['key' => 'units', 'label' => 'Satuan', 'class' => 'text-center'],
        ['key' => 'year_plan', 'label' => 'Rencana Tahunan', 'class' => 'text-center'],
        ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
    ],
    'rows' => $workProgramRows,
])
