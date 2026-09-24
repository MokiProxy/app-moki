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
                        @can('erkap.risk-identifications.submit')
                        @if($submittableCount > 0)
                        <form method="POST" action="{{ route('erkap.risk-identifications.submit-batch') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" title="Ajukan semua Form 1 yang belum diajukan">
                                <i class="mdi mdi-send me-1"></i> Ajukan Semua ({{ $submittableCount }})
                            </button>
                        </form>
                        @endif
                        @endcan
                        <a href="{{ route('erkap.risk-identifications.export') }}" class="btn btn-outline-success" title="Export Excel">
                            <i class="mdi mdi-file-excel me-1"></i> Excel
                        </a>
                        <a href="{{ route('erkap.risk-identifications.export-pdf') }}" class="btn btn-outline-danger" title="Export PDF">
                            <i class="mdi mdi-file-pdf me-1"></i> PDF
                        </a>
                        <a href="{{ route('erkap.risk-identifications.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i> Tambah Identifikasi Risiko
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
                                <th>Risk</th>
                                <th>Arah Risiko</th>
                                <th>Sasaran Departemen</th>
                                <th>Rating</th>
                                <th>Program Kerja</th>
                                <th>Risk Type</th>
                                <th>Risk Taxonomy</th>
                                <th style="width: 110px" class="text-center">Status</th>
                                <th style="width: 150px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riskIdentifications as $key => $riskIdentification)
                            @php
                            $rating = optional(optional($riskIdentification->departmentTarget)->ratingCriteria)->rating ?? null;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $riskIdentifications->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $riskIdentification->risk }}</td>
                                <td>
                                    @if($riskIdentification->risk_direction == 'positive')
                                        <span class="badge bg-success">Positif</span>
                                    @else
                                        <span class="badge bg-danger">Negatif</span>
                                    @endif
                                </td>
                                <td>{{ $riskIdentification->departmentTarget->target ?? '-' }}</td>
                                <td>
                                    @if($rating)
                                        <span class="badge bg-primary">{{ $rating }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($riskIdentification->work_programs_count > 0)
                                        <span class="badge bg-success">Ada Program Kerja</span>
                                    @elseif(in_array($rating, ['AAA', 'AA', 'A'], true))
                                        <a href="{{ route('erkap.work-programs.create', ['risk_identification_id' => $riskIdentification->id]) }}" class="btn btn-outline-primary btn-sm">
                                            Buat Program Kerja
                                        </a>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Ada</span>
                                    @endif
                                </td>
                                <td>{{ $riskIdentification->riskType->name ?? '-' }}</td>
                                <td>{{ $riskIdentification->riskTaxonomy->name ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $riskIdentification->statusClass() }}">{{ $riskIdentification->statusLabel() }}</span>
                                </td>
                                <td class="text-center">
                                    @if(in_array($riskIdentification->status, ['submitted', 'approved'], true))
                                        <span class="text-muted" title="Terkunci setelah evaluasi"><i class="mdi mdi-lock"></i></span>
                                    @else
                                        <a href="{{ route('erkap.risk-identifications.edit', $riskIdentification->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $riskIdentification->id }}" data-name="{{ $riskIdentification->risk }}" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                        @can('erkap.risk-identifications.submit')
                                        @if($riskIdentification->canBeSubmitted())
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-submit" data-id="{{ $riskIdentification->id }}" data-name="{{ $riskIdentification->risk }}" title="Ajukan untuk evaluasi Manajemen Risiko">
                                            <i class="mdi mdi-send"></i>
                                        </button>
                                        @endif
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data identifikasi risiko.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $riskIdentifications->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<form id="form-submit" method="POST" style="display: none;">
    @csrf
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
                title: 'Hapus Identifikasi Risiko?',
                text: 'Identifikasi risiko "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/risk-identifications') }}/" + id);
                    form.submit();
                }
            });
        });

        $(document).on('click', '.btn-submit', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            Swal.fire({
                title: 'Ajukan Form 1?',
                text: 'Form 1 "' + name + '" akan diajukan ke Dept. Manajemen Risiko untuk evaluasi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ajukan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-submit');
                    form.attr('action', "{{ url('erkap/risk-identifications') }}/" + id + "/submit");
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
