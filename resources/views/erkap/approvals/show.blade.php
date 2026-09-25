@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.7em;
    }
    .bg-soft-warning {
        background-color: rgba(241, 180, 76, 0.18);
    }
    .bg-soft-info {
        background-color: rgba(52, 195, 235, 0.18);
    }
    .bg-soft-success {
        background-color: rgba(54, 203, 131, 0.18);
    }
    .bg-soft-danger {
        background-color: rgba(240, 101, 101, 0.18);
    }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        padding-bottom: 20px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    .timeline-item:last-child::before {
        display: none;
    }
    .timeline-item::after {
        content: '';
        position: absolute;
        left: 3px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #e9ecef;
        border: 2px solid #dee2e6;
    }
    .timeline-item.active::after {
        background: #f1b44c;
        border-color: #f1b44c;
    }
    .timeline-item.success::after {
        background: #36c783;
        border-color: #36c783;
    }
    .timeline-item.danger::after {
        background: #f06565;
        border-color: #f06565;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">
                        <i class="mdi mdi-file-document me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('erkap.approvals.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-file-document-outline me-1"></i> Detail {{ $typeMeta['label'] }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @if($type === 'work_program')
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Identifikasi Risiko</label>
                                            <p class="mb-0">{{ $model->riskIdentification->risk ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Program Kerja</label>
                                            <p class="mb-0">{{ $model->name }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Satuan</label>
                                            <p class="mb-0">{{ $model->units }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Rencana Tahunan (%)</label>
                                            <p class="mb-0">{{ $model->year_plan !== null ? number_format((float) $model->year_plan, 2, ',', '.') . '%' : '-' }}</p>
                                        </div>
                                    @elseif($type === 'routine_cost')
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Program Kerja</label>
                                            <p class="mb-0">{{ $model->workProgram->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Kebutuhan</label>
                                            <p class="mb-0">{{ $model->need }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Elemen Biaya</label>
                                            <p class="mb-0">{{ $model->costElement->code ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Cost Center</label>
                                            <p class="mb-0">{{ $model->costCenter->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Qty</label>
                                            <p class="mb-0">{{ $model->qty }} {{ $model->units }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Harga Satuan</label>
                                            <p class="mb-0">Rp {{ number_format($model->unit_price, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Total</label>
                                            <p class="mb-0">Rp {{ number_format($model->total, 0, ',', '.') }}</p>
                                        </div>
                                    @elseif($type === 'investment_plan')
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Program Kerja</label>
                                            <p class="mb-0">{{ $model->workProgram->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Nama Investasi</label>
                                            <p class="mb-0">{{ $model->name }}</p>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold text-muted small">Deskripsi</label>
                                            <p class="mb-0">{{ $model->description }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Kategori</label>
                                            <p class="mb-0">{{ $model->investattionCategory->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Tipe</label>
                                            <p class="mb-0">{{ $model->investationType->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Kriteria</label>
                                            <p class="mb-0">{{ $model->investationCriteria->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Qty</label>
                                            <p class="mb-0">{{ $model->qty }} {{ $model->unit }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Harga Satuan</label>
                                            <p class="mb-0">Rp {{ number_format($model->unit_price, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Total</label>
                                            <p class="mb-0">Rp {{ number_format($model->total, 0, ',', '.') }}</p>
                                        </div>
                                    @elseif($type === 'rkap')
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Periode RKAP</label>
                                            <p class="mb-0">{{ $model->year }}</p>
                                        </div>
                                    @elseif($type === 'risk_register')
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Sasaran Departemen</label>
                                            <p class="mb-0">{{ $model->departmentTarget->target ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Divisi</label>
                                            <p class="mb-0">{{ $model->departmentTarget->division->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Rating</label>
                                            <p class="mb-0">{{ $model->departmentTarget->ratingCriteria->rating ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold text-muted small">Risiko</label>
                                            <p class="mb-0">{{ $model->risk }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Arah Risiko</label>
                                            <p class="mb-0">
                                                @if($model->risk_direction === 'positive')
                                                    <span class="badge bg-soft-success text-success">Positif</span>
                                                @else
                                                    <span class="badge bg-soft-danger text-danger">Negatif</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Risk Type</label>
                                            <p class="mb-0">{{ $model->riskType->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Risk Taxonomy</label>
                                            <p class="mb-0">{{ $model->riskTaxonomy->name ?? '-' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Sumber Risiko</label>
                                            <p class="mb-0">
                                                @forelse($model->reasons as $reason)
                                                    <span class="badge bg-light text-dark me-1">{{ $reason->reason }}</span>
                                                @empty
                                                    -
                                                @endforelse
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Dampak</label>
                                            <p class="mb-0">
                                                @forelse($model->impacts as $impact)
                                                    <span class="badge bg-light text-dark me-1">{{ $impact->impact }}</span>
                                                @empty
                                                    -
                                                @endforelse
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-muted small">Analisis Risiko</label>
                                            <p class="mb-0">
                                                @forelse($model->analysis as $analysis)
                                                    <span class="badge bg-info me-1">
                                                        L: {{ $analysis->riskProbability->score ?? '-' }} | I: {{ $analysis->riskImpact->score ?? '-' }}
                                                    </span>
                                                    <span class="badge bg-{{ $analysis->riskScoreValue->score >= 20 ? 'danger' : ($analysis->riskScoreValue->score >= 10 ? 'warning' : 'success') }}">
                                                        Skor {{ $analysis->riskScoreValue->score ?? '-' }} ({{ $analysis->riskScoreValue->level ?? '-' }})
                                                    </span>
                                                @empty
                                                    -
                                                @endforelse
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Strategi Mitigasi</label>
                                            <p class="mb-0">
                                                @forelse($model->departmentRiskStrategies as $strategy)
                                                    <span class="badge bg-primary me-1">{{ $strategy->strategy }}</span>
                                                @empty
                                                    -
                                                @endforelse
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-muted small">Program Kerja</label>
                                            <p class="mb-0">
                                                @forelse($model->workPrograms as $workProgram)
                                                    <span class="badge bg-soft-success text-success me-1">{{ $workProgram->name }}</span>
                                                @empty
                                                    -
                                                @endforelse
                                            </p>
                                        </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status Dokumen</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $model->statusClass() }} text-{{ $model->statusClass() }}">
                                                {{ $model->statusLabel() }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-source-branch me-1"></i> Riwayat Approval</h6>
                            </div>
                            <div class="card-body">
                                @forelse($approvals as $approval)
                                <div class="timeline-item {{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'active') }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">{{ $approval->getLevelLabel() }}</strong>
                                            <small class="text-muted">{{ $approval->approver->name ?? '-' }}</small>
                                        </div>
                                        <span class="badge bg-soft-{{ $approval->getStatusClass() }} text-{{ $approval->getStatusClass() }}">
                                            {{ $approval->getStatusLabel() }}
                                        </span>
                                    </div>
                                    @if($approval->approved_at)
                                    <small class="text-muted d-block mt-1">
                                        <i class="mdi mdi-clock-outline me-1"></i>{{ $approval->approved_at->format('d M Y H:i') }}
                                    </small>
                                    @endif
                                    @if($approval->notes)
                                    <small class="text-muted d-block mt-1">
                                        <i class="mdi mdi-note-text me-1"></i>{{ $approval->notes }}
                                    </small>
                                    @endif
                                </div>
                                @empty
                                <p class="text-muted text-center py-3 mb-0">Belum ada approval.</p>
                                @endforelse
                            </div>
                        </div>

                        @if($canProcess)
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="mdi mdi-check-decagram me-1"></i> Aksi Approval</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('erkap.approvals.approve', [$type, $model->id]) }}" method="POST" id="approvalForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Catatan (Opsional)</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin menyetujui dokumen ini?')">
                                            <i class="mdi mdi-check-circle me-1"></i> Setujui
                                        </button>
                                        <button type="button" class="btn btn-danger" onclick="showRejectModal()">
                                            <i class="mdi mdi-close-circle me-1"></i> Tolak
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif

                        @if($model->status === 'approved')
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="mdi mdi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-success">Dokumen Disetujui</h5>
                                <p class="text-muted small">Semua approval telah selesai</p>
                            </div>
                        </div>
                        @endif

                        @if($model->status === 'rejected')
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <i class="mdi mdi-close-circle text-danger" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-danger">Dokumen Ditolak</h5>
                                <p class="text-muted small">Dokumen dapat diperbaiki dan diajukan ulang oleh pemilik dokumen.</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('erkap.approvals.reject', [$type, $model->id]) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="mdi mdi-close-circle me-1"></i> Tolak Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Masukkan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-close-circle me-1"></i> Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
function showRejectModal() {
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}
</script>
@endsection