@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.risk-assessments-monthly.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Input Assessment
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

                <form method="GET" action="{{ route('erkap.risk-assessments-monthly.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tahun</label>
                        <input type="number" name="year" class="form-control" value="{{ $year }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Bulan</label>
                        <select name="month" class="form-select">
                            <option value="">Semua Bulan</option>
                            @foreach($monthLabels as $key => $label)
                                <option value="{{ $key + 1 }}" {{ request('month') == $key + 1 ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100"><i class="mdi mdi-filter me-1"></i> Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Risiko</th>
                                <th>Divisi</th>
                                <th class="text-center">Periode</th>
                                <th class="text-center">Inherent (P x I)</th>
                                <th class="text-center">Current (P x I)</th>
                                <th class="text-center">Residual (P x I)</th>
                                <th class="text-center">Status Mitigasi</th>
                                <th>Mitigasi</th>
                                <th>Risk Owner</th>
                                <th style="width: 80px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assessments as $key => $assessment)
                            @php
                                $badge = [
                                    'on_progress' => 'primary',
                                    'done' => 'success',
                                    'overdue' => 'danger',
                                ];
                                $label = [
                                    'on_progress' => 'On Progress',
                                    'done' => 'Done',
                                    'overdue' => 'Overdue',
                                ];
                                $level = fn ($score) => $score >= 70 ? 'danger' : ($score >= 25 ? 'warning' : 'success');
                            @endphp
                            <tr>
                                <td class="text-center">{{ $assessments->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ Str::limit($assessment->riskIdentification->risk ?? '-', 70) }}</td>
                                <td>{{ optional($assessment->riskIdentification?->departmentTarget?->division)->name ?? '-' }}</td>
                                <td class="text-center">{{ $monthLabels[$assessment->month - 1] ?? $assessment->month }} {{ $assessment->year }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $level($assessment->inherent_score) }}">
                                        {{ $assessment->inherent_probability }} x {{ $assessment->inherent_impact }} = {{ $assessment->inherent_score }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($assessment->current_score !== null)
                                        <span class="badge bg-{{ $level($assessment->current_score) }}">{{ $assessment->current_score }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($assessment->residual_score !== null)
                                        <span class="badge bg-{{ $level($assessment->residual_score) }}">{{ $assessment->residual_score }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge[$assessment->mitigation_status] ?? 'secondary' }}">{{ $label[$assessment->mitigation_status] ?? $assessment->mitigation_status }}</span>
                                </td>
                                <td title="{{ $assessment->mitigation_plan }}">{{ Str::limit($assessment->mitigation_plan, 50) ?: '-' }}</td>
                                <td>{{ $assessment->risk_owner ?: '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $assessment->id }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Belum ada data risk assessment bulanan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $assessments->appends(request()->query())->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Risk Assessment Bulanan?',
                text: 'Data assessment akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/risk-assessments-monthly') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection