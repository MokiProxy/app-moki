@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.performance-scorecards.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Input KPI
                    </a>
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

                <form method="GET" action="{{ route('erkap.performance-scorecards.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Periode RKAP</label>
                        <select name="rkap_id" class="form-select">
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}" {{ ($selectedRkap->id ?? null) == $rkap->id ? 'selected' : '' }}>{{ $rkap->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Triwulan</label>
                        <select name="quarter" class="form-select">
                            <option value="">Semua Triwulan</option>
                            @foreach([1 => 'Triwulan 1', 2 => 'Triwulan 2', 3 => 'Triwulan 3', 4 => 'Triwulan 4'] as $q => $label)
                                <option value="{{ $q }}" {{ request('quarter') == $q ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100"><i class="mdi mdi-filter me-1"></i> Filter</button>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end">
                        <span class="badge bg-primary fs-6">
                            Total Weighted Score: {{ number_format($totalWeighted, 2, ',', '.') }}
                        </span>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Sasaran Departemen / Divisi</th>
                                <th class="text-center">Triwulan</th>
                                <th>Tahun</th>
                                <th>KPI</th>
                                <th class="text-end">Target</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Score</th>
                                <th class="text-end">Weight</th>
                                <th class="text-end">Weighted Score</th>
                                <th style="width: 80px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scorecards as $key => $scorecard)
                            <tr>
                                <td class="text-center">{{ $scorecards->firstItem() + $key }}</td>
                                <td>
                                    <span class="fw-bold">{{ optional($scorecard->departmentTarget?->division)->name ?? '-' }}</span>
                                    <div class="small text-muted">{{ Str::limit($scorecard->departmentTarget->target ?? '-', 80) }}</div>
                                </td>
                                <td class="text-center">Triwulan {{ $scorecard->quarter }}</td>
                                <td class="text-center">{{ $scorecard->year }}</td>
                                <td class="fw-bold">{{ $scorecard->kpi_name }}</td>
                                <td class="text-end">{{ number_format($scorecard->kpi_target, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($scorecard->kpi_actual, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $scorecard->kpi_score >= 100 ? 'success' : ($scorecard->kpi_score >= 70 ? 'info' : 'danger') }}">
                                        {{ number_format($scorecard->kpi_score, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($scorecard->weight, 2, ',', '.') }}%</td>
                                <td class="text-end fw-bold">{{ number_format($scorecard->weighted_score, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $scorecard->id }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Belum ada data performance scorecard.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $scorecards->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('plugin')
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function() { location.reload(); });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Hapus Performance Scorecard?',
                text: 'Data scorecard akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/performance-scorecards') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection