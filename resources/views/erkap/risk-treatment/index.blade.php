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
                    <a href="{{ route('erkap.risk-treatments.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Perlakuan Risiko
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

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Identifikasi Risiko</th>
                                <th>Strategi</th>
                                <th>Jenis Perlakuan</th>
                                <th>PIC</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $statusLabels = [
                                    'planned' => 'Direncanakan',
                                    'in_progress' => 'Berjalan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                ];
                                $statusBadges = [
                                    'planned' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary',
                                ];
                            @endphp
                            @forelse($riskTreatments as $key => $riskTreatment)
                            <tr>
                                <td class="text-center">{{ $riskTreatments->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $riskTreatment->riskIdentification->risk ?? '-' }}</td>
                                <td>{{ $riskTreatment->departmentRiskStrategy
                                    ? (\App\Models\Erkap\DepartmentRiskStrategy::getStrategies()[$riskTreatment->departmentRiskStrategy->strategy] ?? $riskTreatment->departmentRiskStrategy->strategy)
                                    : '-' }}</td>
                                <td>{{ \App\Models\Erkap\RiskTreatment::getTreatmentTypes()[$riskTreatment->treatment_type] ?? $riskTreatment->treatment_type }}</td>
                                <td>{{ $riskTreatment->responsible_party }}</td>
                                <td>{{ $riskTreatment->target_date?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusBadges[$riskTreatment->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$riskTreatment->status] ?? $riskTreatment->status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.risk-treatments.edit', $riskTreatment->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $riskTreatment->id }}" data-name="{{ $riskTreatment->riskIdentification->risk ?? 'Perlakuan Risiko' }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada data perlakuan risiko.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $riskTreatments->links('pagination::bootstrap-4') }}
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
            var name = $(this).data('name');
            Swal.fire({
                title: 'Hapus Perlakuan Risiko?',
                text: 'Perlakuan risiko "' + name + '" akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/risk-treatments') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection