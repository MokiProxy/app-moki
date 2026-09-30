@php
    $monthColumns = [
        'jan_cost' => 'Januari', 'feb_cost' => 'Februari', 'mar_cost' => 'Maret',
        'apr_cost' => 'April', 'may_cost' => 'Mei', 'jun_cost' => 'Juni',
        'jul_cost' => 'Juli', 'aug_cost' => 'Agustus', 'sep_cost' => 'September',
        'oct_cost' => 'Oktober', 'nov_cost' => 'November', 'dec_cost' => 'Desember',
    ];

    $rupiah = fn ($value) => $value === null ? '-' : 'Rp '.e(number_format((float) $value, 0, ',', '.'));

    $monthRows = collect($monthColumns)->map(fn ($label, $column) => [
        'month' => $label,
        'cost' => $rupiah($model->{$column}),
    ])->values()->all();
@endphp

<div class="card mb-3 approval-section">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="mdi mdi-cash-multiple me-1"></i> Ringkasan Biaya Rutin</h6>
    </div>
    <div class="card-body">
        @include('erkap.approvals.partials.fields', [
            'columns' => 3,
            'fields' => [
                [
                    'label' => 'Program Kerja',
                    'value' => e($model->workProgram?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Kebutuhan',
                    'value' => e($model->need),
                    'html' => true,
                ],
                [
                    'label' => 'Status Dokumen',
                    'value' => e($model->statusLabel()),
                    'badge' => 'soft-'.$model->statusClass(),
                ],
                [
                    'label' => 'Elemen Biaya',
                    'value' => e($model->costElement?->code ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Cost Center',
                    'value' => e($model->costCenter?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Chart of Account',
                    'value' => e($model->chartOfAccount?->code ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Qty',
                    'value' => e($model->qty).' '.e($model->units ?? ''),
                    'html' => true,
                ],
                [
                    'label' => 'Harga Satuan',
                    'value' => $rupiah($model->unit_price),
                    'html' => true,
                ],
                [
                    'label' => 'Realisasi Tahun Lalu',
                    'value' => $rupiah($model->prior_year_amount),
                    'html' => true,
                ],
            ],
        ])
    </div>
</div>

@include('erkap.approvals.partials.collection', [
    'title' => 'Rincian Biaya Bulanan',
    'icon' => 'mdi-calendar-month-outline',
    'unit' => 'bulan',
    'columns' => [
        ['key' => 'month', 'label' => 'Bulan'],
        ['key' => 'cost', 'label' => 'Biaya', 'class' => 'text-end'],
    ],
    'rows' => $monthRows,
    'footer' => 'Total Biaya: Rp '.e(number_format((float) ($model->total ?? 0), 0, ',', '.')),
])
