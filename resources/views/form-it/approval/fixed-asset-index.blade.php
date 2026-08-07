@extends('layouts.FormIT')

@section('title', $pageName)

@section('css')
<style>
    .nav-pills .nav-link.active {
        background-color: #556ee6;
    }
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
                    <a href="{{ route('form-it.index') }}" class="btn btn-secondary">
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

                <ul class="nav nav-pills nav-justified bg-light rounded mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#waiting-list">
                            <i class="mdi mdi-clock-outline me-1"></i> Menunggu Persetujuan
                            @if($pendingApprovals->count() > 0)
                            <span class="badge bg-danger ms-1">{{ $pendingApprovals->count() }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#history-list">
                            <i class="mdi mdi-history me-1"></i> Riwayat Approval
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="waiting-list">
                        <table class="table table-striped table-bordered align-middle w-100">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Pemohon</th>
                                    <th>Departemen</th>
                                    <th>Tipe Perangkat</th>
                                    <th>Tujuan Lokasi</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingApprovals as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item->date_start->format('d M Y') }} - {{ $item->date_end->format('d M Y') }}</td>
                                    <td>{{ $item->pemohon->name ?? '-' }}</td>
                                    <td>{{ $item->pemohon->division->name ?? '-' }}</td>
                                    <td><span class="badge bg-soft-info text-info">{{ $item->tipe_perangkat }}</span></td>
                                    <td>{{ $item->tujuan_lokasi }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('form-it.approval.fixed-asset.show', $item->id) }}" class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="mdi mdi-check-circle-outline fs-1 d-block mb-2"></i>
                                        Tidak ada pengajuan menunggu persetujuan.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="history-list">
                        <table class="table table-striped table-bordered align-middle w-100">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Pemohon</th>
                                    <th>Departemen</th>
                                    <th>Tipe Perangkat</th>
                                    <th>Status</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historyApprovals as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item->date_start->format('d M Y') }} - {{ $item->date_end->format('d M Y') }}</td>
                                    <td>{{ $item->pemohon->name ?? '-' }}</td>
                                    <td>{{ $item->pemohon->division->name ?? '-' }}</td>
                                    <td><span class="badge bg-soft-info text-info">{{ $item->tipe_perangkat }}</span></td>
                                    <td class="text-center">
                                        @if($item->status === 'approved')
                                        <span class="badge bg-soft-success text-success">Disetujui</span>
                                        @elseif($item->status === 'rejected')
                                        <span class="badge bg-soft-danger text-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('form-it.approval.fixed-asset.show', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="mdi mdi-information-outline fs-1 d-block mb-2"></i>
                                        Belum ada riwayat approval.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
