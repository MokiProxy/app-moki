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
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">
                        <i class="mdi mdi-domain me-1"></i> {{ $pageName }}
                        @if($division)
                        : <span class="text-primary fw-bold">{{ $division->name }}</span>
                        @else
                        : <span class="text-primary fw-bold">Tanpa Divisi</span>
                        @endif
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

                <div class="alert alert-info py-2">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Dokumen dikelompokkan berdasarkan tipe. Anda dapat menyetujui atau menolak
                    dokumen satu per satu. Klik icon <i class="mdi mdi-eye"></i> untuk melihat
                    detail dokumen terlebih dahulu.
                </div>

                @forelse($groups as $group)
                <div class="card mb-3 border shadow-sm">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="mdi mdi-file-document-outline me-1"></i>
                            {{ $group['label'] }}
                        </h6>
                        <span class="badge bg-primary">{{ $group['items']->count() }} dokumen</span>
                    </div>
                    <div class="card-body p-0">
                        @include('erkap.approvals.partials.' . $group['view'], ['items' => $group['items'], 'type' => $group['type']])
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="mdi mdi-check-circle-outline fs-1 d-block mb-2"></i>
                    Tidak ada dokumen yang menunggu persetujuan pada {{ $division ? 'divisi ini' : 'grup ini' }}.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" id="rejectForm">
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
$(document).ready(function() {
    var rejectForm = $('#rejectForm');

    $(document).on('click', '.btn-reject', function() {
        rejectForm.attr('action', $(this).data('url'));
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
});
</script>
@endsection