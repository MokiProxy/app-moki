@php
    $monthColumns = [
        'jan_plan' => 'Januari', 'feb_plan' => 'Februari', 'mar_plan' => 'Maret',
        'apr_plan' => 'April', 'may_plan' => 'Mei', 'jun_plan' => 'Juni',
        'jul_plan' => 'Juli', 'aug_plan' => 'Agustus', 'sep_plan' => 'September',
        'oct_plan' => 'Oktober', 'nov_plan' => 'November', 'dec_plan' => 'Desember',
    ];

    $rupiah = fn ($value) => $value === null ? '-' : 'Rp '.e(number_format((float) $value, 0, ',', '.'));

    $monthRows = collect($monthColumns)->map(fn ($label, $column) => [
        'month' => $label,
        'plan' => $rupiah($model->{$column}),
    ])->values()->all();

    $gateRows = $model->stageGates->map(fn ($gate) => [
        'stage' => '<span class="badge bg-primary">'.e($gate->label()).'</span>',
        'reviewer_role' => e(App\Models\Erkap\Approval::roleLabel($gate->reviewer_role) ?? $gate->reviewer_role),
        'reviewer' => e($gate->reviewer?->name ?? 'Belum ditentukan'),
        'status' => '<span class="badge bg-soft-'.$gate->statusClass().' text-'.$gate->statusClass().'">'.e($gate->statusLabel()).'</span>',
        'notes' => e($gate->notes ?? '-'),
        'reviewed_at' => $gate->reviewed_at ? e($gate->reviewed_at->format('d M Y H:i')) : '-',
    ])->all();
@endphp

<div class="card mb-3 approval-section">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="mdi mdi-bank me-1"></i> Ringkasan Rencana Investasi</h6>
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
                    'label' => 'Nama Investasi',
                    'value' => e($model->name),
                    'html' => true,
                ],
                [
                    'label' => 'Status Dokumen',
                    'value' => e($model->statusLabel()),
                    'badge' => 'soft-'.$model->statusClass(),
                ],
                [
                    'label' => 'Kategori',
                    'value' => e($model->investattionCategory?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Tipe',
                    'value' => e($model->investationType?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Kriteria',
                    'value' => e($model->investationCriteria?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Qty',
                    'value' => e($model->qty).' '.e($model->unit ?? ''),
                    'html' => true,
                ],
                [
                    'label' => 'Harga Satuan',
                    'value' => $rupiah($model->unit_price),
                    'html' => true,
                ],
                [
                    'label' => 'Nilai Tahun Lalu',
                    'value' => $rupiah($model->prior_year_amount),
                    'html' => true,
                ],
                [
                    'label' => 'Total Investasi',
                    'value' => '<span class="fw-bold">'.$rupiah($model->total).'</span>',
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
            ],
        ])

        @if($model->description)
            <div class="row g-3 mt-0">
                <div class="col-12">
                    <div class="detail-field">
                        <div class="detail-field-label">Deskripsi</div>
                        <div class="detail-field-value">{!! nl2br(e($model->description)) !!}</div>
                    </div>
                </div>
            </div>
        @endif

        @if($model->proposalUrl())
            <div class="row g-3 mt-0">
                <div class="col-12">
                    <div class="detail-field">
                        <div class="detail-field-label">Proposal</div>
                        <div class="detail-field-value">
                            <a href="{{ $model->proposalUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-file-document-outline me-1"></i> {{ $model->proposal_original_name ?? 'Lihat Proposal' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@include('erkap.approvals.partials.collection', [
    'title' => 'Rencana Investasi Bulanan',
    'icon' => 'mdi-calendar-month-outline',
    'unit' => 'bulan',
    'columns' => [
        ['key' => 'month', 'label' => 'Bulan'],
        ['key' => 'plan', 'label' => 'Rencana', 'class' => 'text-end'],
    ],
    'rows' => $monthRows,
    'footer' => 'Total Investasi: Rp '.e(number_format((float) ($model->total ?? 0), 0, ',', '.')),
])

@include('erkap.approvals.partials.collection', [
    'title' => 'Stage Gate Review',
    'icon' => 'mdi-gavel',
    'unit' => 'tahap',
    'empty' => 'Belum ada Stage Gate Review. Rencana investasi belum bisa diajukan tanpa Stage Gate.',
    'columns' => [
        ['key' => 'stage', 'label' => 'Tahap', 'class' => 'text-center'],
        ['key' => 'reviewer_role', 'label' => 'Peran Reviewer'],
        ['key' => 'reviewer', 'label' => 'Reviewer'],
        ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
        ['key' => 'notes', 'label' => 'Catatan'],
        ['key' => 'reviewed_at', 'label' => 'Waktu', 'class' => 'text-center'],
    ],
    'rows' => $gateRows,
])
