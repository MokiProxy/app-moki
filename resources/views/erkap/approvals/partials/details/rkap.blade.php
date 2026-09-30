<div class="card mb-3 approval-section">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold"><i class="mdi mdi-calendar-range me-1"></i> Ringkasan Periode RKAP</h6>
    </div>
    <div class="card-body">
        @include('erkap.approvals.partials.fields', [
            'columns' => 3,
            'fields' => [
                [
                    'label' => 'Periode RKAP',
                    'value' => e($model->year),
                    'html' => true,
                ],
                [
                    'label' => 'Perusahaan',
                    'value' => e($model->company?->name ?? '-'),
                    'html' => true,
                ],
                [
                    'label' => 'Status Dokumen',
                    'value' => e($model->statusLabel()),
                    'badge' => 'soft-'.$model->statusClass(),
                ],
                [
                    'label' => 'Fase Lifecycle',
                    'value' => e($model->phaseLabel()),
                    'html' => true,
                ],
                [
                    'label' => 'Tanggal Kick-off',
                    'value' => $model->kickoff_date ? e($model->kickoff_date->format('d M Y')) : '-',
                    'html' => true,
                ],
                [
                    'label' => 'Tanggal Pengesahan',
                    'value' => $model->resolution_date ? e($model->resolution_date->format('d M Y')) : '-',
                    'html' => true,
                ],
                [
                    'label' => 'Status Distribusi',
                    'value' => e($model->distributionLabel()),
                    'badge' => $model->distributionClass(),
                ],
                [
                    'label' => 'Fase Dimulai',
                    'value' => $model->phase_started_at ? e($model->phase_started_at->format('d M Y H:i')) : '-',
                    'html' => true,
                ],
                [
                    'label' => 'Dokumen Input Terkunci',
                    'value' => $model->isLockedForInput() ? 'Ya' : 'Belum',
                    'badge' => $model->isLockedForInput() ? 'danger' : 'success',
                ],
            ],
        ])

        @if($model->kickoff_notes)
            <div class="row g-3 mt-0">
                <div class="col-12">
                    <div class="detail-field">
                        <div class="detail-field-label">Catatan Kick-off</div>
                        <div class="detail-field-value">{!! nl2br(e($model->kickoff_notes)) !!}</div>
                    </div>
                </div>
            </div>
        @endif

        @if($model->direction_notes)
            <div class="row g-3 mt-0">
                <div class="col-12">
                    <div class="detail-field">
                        <div class="detail-field-label">Arahan Direksi</div>
                        <div class="detail-field-value">{!! nl2br(e($model->direction_notes)) !!}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
