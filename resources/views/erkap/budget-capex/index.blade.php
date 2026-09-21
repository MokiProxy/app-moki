@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
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

                <div class="card mb-3">
                    <div class="card-body bg-light">
                        <h6 class="card-title fw-bold"><i class="mdi mdi-cog me-1"></i> Konsolidasi Anggaran</h6>
                        <p class="text-muted small mb-2">Aggregate total investasi dari semua rencana investasi per divisi.</p>
                        <form action="{{ route('erkap.budget-capex.consolidate') }}" method="POST" class="d-flex align-items-end gap-2">
                            @csrf
                            <div class="flex-grow-1" style="max-width:250px">
                                <label class="form-label fw-bold small">Pilih RKAP/Tahun</label>
                                <select name="erkap_rkap_id" class="form-select form-select-sm" required>
                                    <option value="" disabled selected>Pilih RKAP</option>
                                    @foreach($rkapList as $rkap)
                                        <option value="{{ $rkap->id }}">{{ $rkap->year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-cog-outline me-1"></i> Konsolidasi
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Tahun RKAP</th>
                                <th>Divisi</th>
                                <th class="text-end">Total Investasi</th>
                                <th>Status</th>
                                <th style="width: 100px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budgetCapex as $key => $capex)
                            <tr>
                                <td class="text-center">{{ $budgetCapex->firstItem() + $key }}</td>
                                <td>{{ $capex->rkap->year ?? '-' }}</td>
                                <td class="fw-bold">{{ $capex->division->name ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($capex->total_investment, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'submitted' => 'info',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                        $statusLabels = [
                                            'draft' => 'Draft',
                                            'submitted' => 'Submitted',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$capex->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$capex->status] ?? $capex->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.budget-capex.show', $capex->id) }}" class="btn btn-info btn-sm" title="Detail">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data anggaran investasi. Jalankan konsolidasi terlebih dahulu.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $budgetCapex->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection