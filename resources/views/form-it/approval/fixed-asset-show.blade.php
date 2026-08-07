@extends('layouts.FormIT')

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
    .bg-soft-success {
        background-color: rgba(54, 203, 131, 0.18);
    }
    .bg-soft-danger {
        background-color: rgba(240, 101, 101, 0.18);
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
                        <i class="mdi mdi-laptop me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('form-it.approval.fixed-asset.index') }}" class="btn btn-secondary">
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
                                <h6 class="mb-0"><i class="mdi mdi-account-circle me-1"></i> Data Pemohon</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Nama Pemohon</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Jabatan</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->jabatan ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Departemen</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->division->name ?? '-' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Area / Job Site</label>
                                        <p class="mb-0">{{ $borrowing->pemohon->regional->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-laptop me-1"></i> Data Pengajuan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tanggal Peminjaman</label>
                                        <p class="mb-0">{{ $borrowing->date_start->format('d F Y') }} - {{ $borrowing->date_end->format('d F Y') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tipe Perangkat</label>
                                        <p class="mb-0"><span class="badge bg-soft-info text-info">{{ $borrowing->tipe_perangkat }}</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Tujuan Lokasi</label>
                                        <p class="mb-0">{{ $borrowing->tujuan_lokasi }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small">Status</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $borrowing->getApprovalStatusClass() }} text-{{ $borrowing->getApprovalStatusClass() }}">
                                                {{ $borrowing->getApprovalStatusLabel() }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Keperluan</label>
                                        <p class="mb-0">{{ $borrowing->keperluan }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($borrowing->status === 'rejected' && $borrowing->rejection_reason)
                        <div class="card mb-3 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="mdi mdi-close-circle me-1"></i> Alasan Penolakan</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $borrowing->rejection_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        @if($borrowing->status === 'pending')
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="mdi mdi-check-decagram me-1"></i> Aksi Approval</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('form-it.approval.fixed-asset.process', $borrowing->id) }}" method="POST" id="approvalForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Data Penyerahan <span class="text-danger">*</span></label>
                                        <small class="text-muted d-block mb-2">Wajib diisi saat menyetujui pengajuan</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nama Yang Menyerahkan <span class="text-danger">*</span></label>
                                        <input type="text" name="penyerahkan_name" class="form-control @error('penyerahkan_name') is-invalid @enderror" value="{{ old('penyerahkan_name') }}" maxlength="255" required>
                                        @error('penyerahkan_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Jabatan Yang Menyerahkan <span class="text-danger">*</span></label>
                                        <input type="text" name="penyerahkan_jabatan" class="form-control @error('penyerahkan_jabatan') is-invalid @enderror" value="{{ old('penyerahkan_jabatan') }}" maxlength="255" required>
                                        @error('penyerahkan_jabatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Departemen Yang Menyerahkan <span class="text-danger">*</span></label>
                                        <input type="text" name="penyerahkan_departemen" class="form-control @error('penyerahkan_departemen') is-invalid @enderror" value="{{ old('penyerahkan_departemen') }}" maxlength="255" required>
                                        @error('penyerahkan_departemen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Area Yang Menyerahkan <span class="text-danger">*</span></label>
                                        <input type="text" name="penyerahkan_area" class="form-control @error('penyerahkan_area') is-invalid @enderror" value="{{ old('penyerahkan_area') }}" maxlength="255" required>
                                        @error('penyerahkan_area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Catatan (Opsional)</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Tambahkan catatan jika diperlukan...">{{ old('notes') }}</textarea>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">
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

                        @if($borrowing->status === 'approved')
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="mdi mdi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-success">Pengajuan Disetujui</h5>
                                <p class="text-muted small">Peminjaman telah disetujui</p>
                            </div>
                        </div>
                        @endif

                        @if($borrowing->status === 'rejected')
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <i class="mdi mdi-close-circle text-danger" style="font-size: 3rem;"></i>
                                <h5 class="mt-2 text-danger">Pengajuan Ditolak</h5>
                                <p class="text-muted small">Pengajuan telah ditolak</p>
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
            <form action="{{ route('form-it.approval.fixed-asset.process', $borrowing->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="mdi mdi-close-circle me-1"></i> Tolak Pengajuan
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
                    <button type="submit" name="action" value="reject" class="btn btn-danger">
                        <i class="mdi mdi-close-circle me-1"></i> Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showRejectModal() {
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}
</script>
@endsection
