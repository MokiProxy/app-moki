@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .badge { font-size: 0.7rem; padding: 0.4em 0.7em; }
    .bg-soft-warning { background-color: rgba(241, 180, 76, 0.18); }
    .bg-soft-info { background-color: rgba(52, 195, 235, 0.18); }
    .bg-soft-success { background-color: rgba(54, 203, 131, 0.18); }
    .bg-soft-danger { background-color: rgba(240, 101, 101, 0.18); }
    .timeline-item { position: relative; padding-left: 30px; padding-bottom: 20px; }
    .timeline-item::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
    .timeline-item:last-child::before { display: none; }
    .timeline-item::after { content: ''; position: absolute; left: 3px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #e9ecef; border: 2px solid #dee2e6; }
    .timeline-item.active::after { background: #f1b44c; border-color: #f1b44c; }
    .timeline-item.success::after { background: #36c783; border-color: #36c783; }
    .timeline-item.danger::after { background: #f06565; border-color: #f06565; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">
                    <i class="mdi mdi-check-decagram me-1"></i> {{ $pageName }}
                </h5>
                <a href="{{ route('erkap.investment-gates.index') }}" class="btn btn-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
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
                                <h6 class="mb-0"><i class="mdi mdi-file-document-outline me-1"></i> Detail Rencana Investasi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Nama Investasi</label>
                                        <p class="mb-0">{{ $plan->name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Program Kerja</label>
                                        <p class="mb-0">{{ $plan->workProgram->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Deskripsi</label>
                                        <p class="mb-0">{{ $plan->description ?: '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Kategori / Tipe / Kriteria</label>
                                        <p class="mb-0">
                                            {{ $plan->investattionCategory->name ?? '-' }} /
                                            {{ $plan->investationType->name ?? '-' }} /
                                            {{ $plan->investationCriteria->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Pusat Biaya (Cost Center)</label>
                                        <p class="mb-0">{{ $plan->costCenter ? $plan->costCenter->code . ' - ' . $plan->costCenter->name : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Qty</label>
                                        <p class="mb-0">{{ $plan->qty }} {{ $plan->unit }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Harga Satuan</label>
                                        <p class="mb-0">Rp {{ number_format($plan->unit_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Total Investasi</label>
                                        <p class="mb-0 fw-bold">Rp {{ number_format($plan->total, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status Dokumen</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $plan->statusClass() }} text-{{ $plan->statusClass() }}">{{ $plan->statusLabel() }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status Gate Review</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $plan->gateReviewStatusClass() }} text-{{ $plan->gateReviewStatusClass() }}">{{ $plan->gateReviewStatusLabel() }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="mdi mdi-chart-line me-1"></i> Kajian Kelayakan (CBA)</h6>
                                @if($plan->proposal_file_path)
                                <a href="{{ route('erkap.investment-plans.proposal-download', $plan->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="mdi mdi-download me-1"></i> Proposal
                                </a>
                                @endif
                            </div>
                            <div class="card-body">
                                @php
                                    $cba = $plan->cba_json ?? [];
                                @endphp
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">NPV</label>
                                        <p class="mb-0">{{ isset($cba['npv']) ? number_format($cba['npv'], 2, ',', '.') : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">IRR</label>
                                        <p class="mb-0">{{ isset($cba['irr']) ? number_format($cba['irr'], 2, ',', '.') . '%' : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Payback Period</label>
                                        <p class="mb-0">{{ isset($cba['payback']) ? number_format($cba['payback'], 2, ',', '.') . ' th' : '-' }}</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Justifikasi / Rekomendasi</label>
                                        <p class="mb-0">{{ $cba['justification'] ?? '-' }}</p>
                                    </div>
                                    @if($plan->cba_attachment_path)
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Dokumen CBA</label>
                                        <p class="mb-0">
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($plan->cba_attachment_path) }}" target="_blank">
                                                <i class="mdi mdi-file-document me-1"></i> Lihat Lampiran CBA
                                            </a>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-source-branch me-1"></i> Riwayat Approval</h6>
                            </div>
                            <div class="card-body">
                                @forelse($plan->approvals as $approval)
                                <div class="timeline-item {{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'active') }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">{{ $approval->getLevelLabel() }}</strong>
                                            <small class="text-muted">{{ $approval->approver->name ?? '-' }}</small>
                                        </div>
                                        <span class="badge bg-soft-{{ $approval->getStatusClass() }} text-{{ $approval->getStatusClass() }}">{{ $approval->getStatusLabel() }}</span>
                                    </div>
                                    @if($approval->approved_at)
                                    <small class="text-muted d-block mt-1"><i class="mdi mdi-clock-outline me-1"></i>{{ $approval->approved_at->format('d M Y H:i') }}</small>
                                    @endif
                                    @if($approval->notes)
                                    <small class="text-muted d-block mt-1"><i class="mdi mdi-note-text me-1"></i>{{ $approval->notes }}</small>
                                    @endif
                                </div>
                                @empty
                                <p class="text-muted text-center py-3 mb-0">Belum ada approval.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-checkbox-multiple-marked-outline me-1"></i> Progres Stage Gate</h6>
                            </div>
                            <div class="card-body">
                                @forelse($plan->stageGates as $historyGate)
                                <div class="timeline-item {{ $historyGate->status === 'approved' ? 'success' : ($historyGate->status === 'rejected' || $historyGate->status === 'revised' ? 'danger' : 'active') }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">{{ $historyGate->label() }}</strong>
                                            <small class="text-muted">
                                                {{
                                                    $historyGate->status === 'pending'
                                                    ? \App\Models\Erkap\InvestmentStageGate::stageLabel($historyGate->reviewer_role) . ' / ' . $historyGate->reviewer_role
                                                    : ($historyGate->reviewer->name ?? $historyGate->reviewer_role)
                                                }}
                                            </small>
                                        </div>
                                        <span class="badge bg-soft-{{ $historyGate->statusClass() }} text-{{ $historyGate->statusClass() }}">{{ $historyGate->statusLabel() }}</span>
                                    </div>
                                    @if($historyGate->result)
                                    <small class="text-muted d-block mt-1">
                                        Hasil: <strong>{{ \App\Models\Erkap\InvestmentStageGate::resultLabel($historyGate->result) }}</strong>
                                    </small>
                                    @endif
                                    @if($historyGate->reviewed_at)
                                    <small class="text-muted d-block mt-1"><i class="mdi mdi-clock-outline me-1"></i>{{ $historyGate->reviewed_at->format('d M Y H:i') }}</small>
                                    @endif
                                    @if($historyGate->notes)
                                    <small class="text-muted d-block mt-1"><i class="mdi mdi-note-text me-1"></i>{{ $historyGate->notes }}</small>
                                    @endif
                                </div>
                                @empty
                                <p class="text-muted text-center py-3 mb-0">Belum ada stage gate.</p>
                                @endforelse
                            </div>
                        </div>

                        @if($canReview)
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="mdi mdi-clipboard-check-outline me-1"></i> Evaluasi Gate: {{ $gate->label() }}</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('erkap.investment-gates.store', $gate->id) }}" method="POST" enctype="multipart/form-data" id="gateReviewForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Keputusan <span class="text-danger">*</span></label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="status" value="approved" class="btn btn-success" onclick="return confirm('Setujui gate ini?')">
                                                <i class="mdi mdi-check-circle me-1"></i> Setujui
                                            </button>
                                            <button type="submit" name="status" value="revised" class="btn btn-warning" onclick="return confirm('Minta revisi?')">
                                                <i class="mdi mdi-pencil me-1"></i> Minta Revisi
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="showRejectModal()">
                                                <i class="mdi mdi-close-circle me-1"></i> Tolak
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Hasil Kajian</label>
                                        <select name="result" class="form-select">
                                            <option value="" selected disabled>Pilih hasil (opsional)</option>
                                            <option value="layak">Layak</option>
                                            <option value="tidak_layak">Tidak Layak</option>
                                            <option value="revisi">Perlu Revisi</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Catatan Evaluasi</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan evaluasi jika diperlukan..."></textarea>
                                    </div>

                                    @if($gate->stage === 'cba')
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Lampiran CBA (jika diperbarui)</label>
                                        <input type="file" name="cba_attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                        <small class="text-muted">PDF/DOC/XLS maks. 20MB.</small>
                                    </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                        @elseif($gate->status === 'pending')
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="mdi mdi-lock text-warning" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-2 text-warning">Menunggu reviewer</h6>
                                <p class="text-muted small">Gate ini belum dievaluasi. Reviewer yang berhak: <strong>{{ $gate->reviewer_role }}</strong>.</p>
                            </div>
                        </div>
                        @else
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="mdi mdi-check-circle text-success" style="font-size: 2.5rem;"></i>
                                <h6 class="mt-2 text-success">Gate Selesai</h6>
                                <p class="text-muted small">Status: {{ $gate->statusLabel() }}</p>
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
            <form action="{{ route('erkap.investment-gates.store', $gate->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="mdi mdi-close-circle me-1"></i> Tolak Gate
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hasil Kajian</label>
                        <select name="result" class="form-select">
                            <option value="tidak_layak" selected>Tidak Layak</option>
                            <option value="revisi">Perlu Revisi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Masukkan alasan..." required></textarea>
                    </div>
                    @if($gate->stage === 'cba')
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lampiran CBA (jika diperbarui)</label>
                        <input type="file" name="cba_attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    </div>
                    @endif
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