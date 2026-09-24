@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    table.table th, table.table td { white-space: nowrap; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1 align-items-center">
                    <form method="POST" action="{{ route('erkap.zbb-reviews.build') }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <select name="erkap_rkap_id" class="form-select form-select-sm" required>
                            <option value="" disabled selected>-- Pilih Periode RKAP --</option>
                            @foreach ($rkapList as $rkap)
                            <option value="{{ $rkap->id }}">RKAP {{ $rkap->year }}</option>
                            @endforeach
                        </select>
                        @can('erkap.zbb-reviews.create')
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-refresh me-1"></i> Bangun Review
                        </button>
                        @endcan
                    </form>
                    <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
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

                @if($summary['blocking'] > 0)
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>{{ $summary['blocking'] }} pos anggaran kenaikan belum dijustifikasi/disetujui.</strong>
                    Konsolidasi anggaran (OPEX/CAPEX) akan diblokir sampai seluruh kenaikan direview.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @elseif($reviews->total() > 0)
                <div class="alert alert-success" role="alert">
                    Semua pos anggaran telah direview. Konsolidasi dapat dilakukan.
                </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-primary text-white shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-white-50 text-uppercase fw-bold">Total Pos Anggaran</small>
                                        <h5 class="mb-0 mt-1">{{ $summary['total'] }}</h5>
                                    </div>
                                    <i class="mdi mdi-text-box-multiple-outline mdi-24px text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-warning text-white shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-white-50 text-uppercase fw-bold">Menunggu Review</small>
                                        <h5 class="mb-0 mt-1">{{ $summary['pending'] }}</h5>
                                    </div>
                                    <i class="mdi mdi-clock-outline mdi-24px text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-success text-white shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-white-50 text-uppercase fw-bold">Disetujui</small>
                                        <h5 class="mb-0 mt-1">{{ $summary['approved'] }}</h5>
                                    </div>
                                    <i class="mdi mdi-check-all mdi-24px text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-secondary text-white shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-white-50 text-uppercase fw-bold">Auto-skip</small>
                                        <h5 class="mb-0 mt-1">{{ $summary['skipped'] }}</h5>
                                    </div>
                                    <i class="mdi mdi-check-decagram-outline mdi-24px text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border shadow-sm mb-4">
                    <div class="card-header bg-light py-2 fw-bold">Filter</div>
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('erkap.zbb-reviews.index') }}" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-1">Periode RKAP</label>
                                <select name="erkap_rkap_id" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach ($rkapList as $rkap)
                                    <option value="{{ $rkap->id }}" @selected(request('erkap_rkap_id') == $rkap->id)>
                                        RKAP {{ $rkap->year }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">Divisi</label>
                                <select name="division_id" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}" @selected(request('division_id') == $division->id)>
                                        {{ $division->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">Jenis Pos</label>
                                <select name="subject_type" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach ($subjectTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(request('subject_type') == $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">Status</label>
                                <select name="zbb_status" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('zbb_status') == $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="mdi mdi-magnify"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Pos Anggaran</th>
                                <th>Jenis</th>
                                <th>Divisi</th>
                                <th>Tahun Lalu</th>
                                <th>Diusulkan</th>
                                <th>Selisih</th>
                                <th>%</th>
                                <th>Justifikasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reviews as $review)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>RKAP {{ $review->rkap?->year }}</td>
                                <td>{{ $review->display_name ?: '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $review->subjectTypeLabel() }}</span></td>
                                <td>{{ $review->division?->name ?: '-' }}</td>
                                <td class="text-end">Rp {{ number_format($review->prior_year_amount, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($review->proposed_amount, 0, ',', '.') }}</td>
                                <td class="text-end {{ $review->delta_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(abs($review->delta_amount), 0, ',', '.') }}
                                </td>
                                <td class="text-end {{ $review->delta_percent >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($review->delta_percent, 2, ',', '.') }}%
                                </td>
                                <td>
                                    @if($review->isIncrease())
                                        @if(blank($review->increase_rationale))
                                            <span class="badge bg-danger">Belum diisi</span>
                                        @else
                                            <span class="badge bg-success">Terisi</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $review->statusClass() }}">{{ $review->statusLabel() }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('erkap.zbb-reviews.show', $review->id) }}"
                                       class="btn btn-sm btn-outline-primary" title="Review">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    Belum ada review ZBB. Pilih periode RKAP dan klik <strong>Bangun Review</strong>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $reviews->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    document.getElementById('btn-refresh')?.addEventListener('click', function () {
        window.location.reload();
    });
</script>
@endsection