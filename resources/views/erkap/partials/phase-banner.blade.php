@php
    $phaseConfig = [
        'initiation' => ['label' => 'Inisiasi & Kick-off', 'icon' => 'mdi-rocket-launch', 'class' => 'primary'],
        'preparation' => ['label' => 'Penyusunan', 'icon' => 'mdi-file-document-edit-outline', 'class' => 'info'],
        'consolidation' => ['label' => 'Konsolidasi & Review', 'icon' => 'mdi-account-group-outline', 'class' => 'warning'],
        'finalization' => ['label' => 'Finalisasi & Pengesahan', 'icon' => 'mdi-file-sign', 'class' => 'dark'],
        'approved' => ['label' => 'Disahkan', 'icon' => 'mdi-check-decagram', 'class' => 'success'],
        'archived' => ['label' => 'Arsip', 'icon' => 'mdi-archive', 'class' => 'secondary'],
    ];
    $cfg = $phaseConfig[$rkap->phase] ?? ['label' => $rkap->phaseLabel(), 'icon' => 'mdi-circle', 'class' => 'secondary'];
    $noModule = $moduleName ?? null;
@endphp
<div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <i class="mdi {{ $cfg['icon'] }} text-{{ $cfg['class'] }} mdi-24px"></i>
        <div>
            <div class="small text-muted">Fase Daya Kunci {{ $noModule ?? 'Data Anggaran' }}</div>
            <div class="fw-bold">
                <span class="badge bg-{{ $cfg['class'] }} rounded-pill">{{ $cfg['label'] }}</span>
                <span class="badge bg-{{ $rkap->distributionClass() }} ms-1">{{ $rkap->distributionLabel() }}</span>
            </div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($rkap->isLockedForInput())
            <span class="text-danger small"><i class="mdi mdi-lock me-1"></i>Terkunci — tidak dapat menambah / mengubah data.</span>
        @endif
        @if(auth()->user()->can('erkap.rkap.edit'))
            <a href="{{ route('erkap.rkap.show', $rkap->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="mdi mdi-eye-outline me-1"></i>Kelola Lifecycle
            </a>
        @endif
    </div>
</div>