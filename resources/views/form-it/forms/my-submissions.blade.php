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
    .bg-soft-info {
        background-color: rgba(52, 195, 235, 0.18);
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
                        <i class="mdi mdi-file-document-multiple me-1"></i> {{ $pageName }}
                    </h5>
                    <a href="{{ route('form-it.forms.software-installation') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Buat Pengajuan Baru
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered align-middle w-100">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Software</th>
                            <th>Keterangan</th>
                            <th width="120">Status</th>
                            <th width="80">Approval</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                            <td>
                                @foreach($item->softwares as $sw)
                                <span class="badge bg-soft-info text-info">{{ $sw }}</span>
                                @endforeach
                            </td>
                            <td>{{ Str::limit($item->keterangan ?? '-', 50) }}</td>
                            <td class="text-center">
                                <span class="badge bg-soft-{{ $item->getApprovalStatusClass() }} text-{{ $item->getApprovalStatusClass() }}">
                                    {{ $item->getApprovalStatusLabel() }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $approvedCount = $item->approvals->where('status', 'approved')->count();
                                    $totalCount = $item->approvals->count();
                                @endphp
                                {{ $approvedCount }}/{{ $totalCount }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('form-it.forms.software-installation.show', $item->id) }}" class="btn btn-sm btn-info">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                @if($item->status === 'approved')
                                <a href="{{ route('form-it.forms.software-installation.pdf', $item->id) }}" class="btn btn-sm btn-success" target="_blank">
                                    <i class="mdi mdi-file-pdf"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="mdi mdi-file-document-outline fs-1 d-block mb-2"></i>
                                Belum ada pengajuan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
