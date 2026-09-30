@php
    $monthColumns = [
        'jan_plan' => 'Januari', 'feb_plan' => 'Februari', 'mar_plan' => 'Maret',
        'apr_plan' => 'April', 'may_plan' => 'Mei', 'jun_plan' => 'Juni',
        'jul_plan' => 'Juli', 'aug_plan' => 'Agustus', 'sep_plan' => 'September',
        'oct_plan' => 'Oktober', 'nov_plan' => 'November', 'dec_plan' => 'Desember',
    ];

    $monthRows = collect($monthColumns)->map(fn ($label, $column) => [
        'month' => $label,
        'plan' => $model->{$column} === null
            ? '-'
            : e(number_format((float) $model->{$column}, 2, ',', '.')).'%',
    ])->values()->all();
@endphp

<div class="card mb-3 approval-section">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="mdi mdi-clipboard-list-outline me-1"></i> Ringkasan Program Kerja</h6>
    </div>
    <div class="card-body">
        @include('erkap.approvals.partials.fields', [
            'columns' => 3,
            'fields' => [
                [
                    'label' => 'Identifikasi Risiko',
                    'value' => e($model->riskIdentification?->risk ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Kode Program',
                    'value' => e($model->code ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Program Kerja',
                    'value' => e($model->name),
                    'html' => true,
                ],
                [
                    'label' => 'Satuan',
                    'value' => e($model->units ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Rencana Tahunan',
                    'value' => $model->year_plan === null ? '-' : e(number_format((float) $model->year_plan, 2, ',', '.')).'%',
                    'html' => true,
                ],
                [
                    'label' => 'Status Dokumen',
                    'value' => e($model->statusLabel()),
                    'badge' => 'soft-'.$model->statusClass(),
                ],
            ],
        ])
    </div>
</div>

@include('erkap.approvals.partials.collection', [
    'title' => 'Rencana Bulanan',
    'icon' => 'mdi-calendar-month-outline',
    'unit' => 'bulan',
    'columns' => [
        ['key' => 'month', 'label' => 'Bulan'],
        ['key' => 'plan', 'label' => 'Rencana (%)', 'class' => 'text-center'],
    ],
    'rows' => $monthRows,
    'footer' => 'Total Tahunan: '.e(number_format((float) ($model->year_plan ?? 0), 2, ',', '.')).'%',
])
