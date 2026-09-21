@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.budget-realizations.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Input Realisasi
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

                <form method="GET" action="{{ route('erkap.budget-realizations.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Periode RKAP</label>
                        <select name="rkap_id" class="form-select">
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}" {{ ($selectedRkap->id ?? null) == $rkap->id ? 'selected' : '' }}>
                                    {{ $rkap->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Bulan</label>
                        <select name="month" class="form-select">
                            @foreach($monthLabels as $key => $label)
                                <option value="{{ $key + 1 }}" {{ $month == $key + 1 ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100"><i class="mdi mdi-filter me-1"></i> Filter</button>
                    </div>
                </form>

                <h6 class="text-uppercase fw-bold text-muted mb-3">
                    <i class="mdi mdi-chart-line me-1"></i> BvA (Budget vs Actual) per Divisi - {{ $monthLabels[$month - 1] ?? '' }} {{ $year }}
                </h6>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Divisi</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Variance</th>
                                <th class="text-center">Variance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bvaRows as $key => $row)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                <td class="fw-bold">{{ $row['division_name'] }}</td>
                                <td class="text-end">{{ number_format($row['budget'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['actual'], 0, ',', '.') }}</td>
                                <td class="text-end {{ $row['variance'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($row['variance'], 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $row['variance'] <= 0 ? 'success' : 'warning' }}">
                                        {{ number_format($row['variance_percent'], 2, ',', '.') }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data BvA untuk periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h6 class="text-uppercase fw-bold text-muted mt-4 mb-3">
                    <i class="mdi mdi-format-list-bulleted me-1"></i> Detail Realisasi
                </h6>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Item Anggaran</th>
                                <th>Divisi</th>
                                <th class="text-center">Periode</th>
                                <th class="text-end">Budgeted</th>
                                <th class="text-end">Realized</th>
                                <th class="text-end">Variance</th>
                                <th class="text-center">Sumber</th>
                                <th style="width: 80px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($realizations as $key => $realization)
                            @php
                                $item = $realization->routineCost ?? $realization->investmentPlan;
                                $division = optional($realization->routineCost?->workProgram?->riskIdentification?->departmentTarget?->division)
                                    ?? $realization->investmentPlan?->workProgram?->riskIdentification?->departmentTarget?->division;
                                $itemName = $realization->routineCost
                                    ? 'Biaya Rutin: ' . Str::limit($realization->routineCost->need, 50)
                                    : 'Investasi: ' . Str::limit($realization->investmentPlan?->name ?? '-', 50);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $realizations->firstItem() + $key }}</td>
                                <td>{{ $itemName }}</td>
                                <td>{{ $division->name ?? '-' }}</td>
                                <td class="text-center">{{ $monthLabels[$realization->month - 1] ?? $realization->month }} {{ $realization->year }}</td>
                                <td class="text-end">{{ number_format($realization->budgeted, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($realization->realized, 0, ',', '.') }}</td>
                                <td class="text-end {{ $realization->variance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($realization->variance, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $realization->source === 'accounting' ? 'info' : 'secondary' }}">
                                        {{ $realization->source }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $realization->id }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada data realisasi anggaran.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $realizations->appends(request()->query())->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Realisasi Anggaran?',
                text: 'Data realisasi akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/budget-realizations') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection