@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.program-realizations.create') }}" class="btn btn-primary">
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

                <form method="GET" action="{{ route('erkap.program-realizations.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Periode RKAP</label>
                        <select name="rkap_id" class="form-select">
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}" {{ ($selectedRkap->id ?? null) == $rkap->id ? 'selected' : '' }}>{{ $rkap->year }}</option>
                            @endforeach
                        </select>
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
                                <th>Program Kerja</th>
                                <th>Divisi</th>
                                <th class="text-center">Periode</th>
                                <th class="text-end">Target</th>
                                <th class="text-end">Realisasi</th>
                                <th style="width: 180px">% Penyelesaian</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Catatan</th>
                                <th style="width: 80px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($realizations as $key => $realization)
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
                            @endphp
                            <tr>
                                <td class="text-center">{{ $realizations->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ Str::limit($realization->workProgram->name ?? '-', 80) }}</td>
                                <td>{{ optional($realization->workProgram?->riskIdentification?->departmentTarget?->division)->name ?? '-' }}</td>
                                <td class="text-center">{{ $monthLabels[$realization->month - 1] ?? $realization->month }} {{ $realization->year }}</td>
                                <td class="text-end">{{ number_format($realization->target, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($realization->realized, 2, ',', '.') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $badge[$realization->status] ?? 'primary' }}" style="width: {{ min($realization->percent_complete, 100) }}%"></div>
                                        </div>
                                        <span class="small fw-bold">{{ number_format($realization->percent_complete, 2, ',', '.') }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $badge[$realization->status] ?? 'secondary' }}">{{ $label[$realization->status] ?? $realization->status }}</span>
                                </td>
                                <td class="text-center" title="{{ $realization->notes }}">
                                    @if($realization->notes)
                                        <i class="mdi mdi-comment-text-outline text-muted"></i>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $realization->id }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data realisasi program kerja.</td>
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
                title: 'Hapus Realisasi Program Kerja?',
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
                    form.attr('action', "{{ url('erkap/program-realizations') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection