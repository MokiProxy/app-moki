@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    table.table th, table.table td { white-space: nowrap; }
    tr.no-budget { background-color: #fff3cd !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    @if(isset($submittableCount) && $submittableCount > 0 && auth()->user()->can('erkap.work-programs.submit'))
                    <form method="POST" action="{{ route('erkap.work-programs.submit-batch') }}" class="d-inline" onsubmit="return confirm('Ajukan {{ $submittableCount }} program kerja untuk persetujuan sekaligus?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-send-multiple me-1"></i> Ajukan Semua Persetujuan
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('erkap.work-programs.export') }}" class="btn btn-outline-success" title="Export Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('erkap.work-programs.export-pdf') }}" class="btn btn-outline-danger" title="Export PDF">
                        <i class="mdi mdi-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ route('erkap.work-programs.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Program Kerja
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
                                <th class="text-center">Rating</th>
                                <th>Program Kerja</th>
                                <th>Satuan</th>
                                <th class="text-center">Tahunan</th>
                                <th class="text-center">Jan</th>
                                <th class="text-center">Feb</th>
                                <th class="text-center">Mar</th>
                                <th class="text-center">Apr</th>
                                <th class="text-center">Mei</th>
                                <th class="text-center">Jun</th>
                                <th class="text-center">Jul</th>
                                <th class="text-center">Agu</th>
                                <th class="text-center">Sep</th>
                                <th class="text-center">Okt</th>
                                <th class="text-center">Nov</th>
                                <th class="text-center">Des</th>
                                <th class="text-center">Biaya</th>
                                <th style="width: 180px" class="text-center">Status</th>
                                <th style="width: 170px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $ratingBadge = function ($rating) {
                                if (in_array($rating, ['AAA', 'AA', 'A'], true)) {
                                    return '<span class="badge bg-danger">' . e($rating ?? '-') . '</span>';
                                }

                                return '<span class="badge bg-secondary">' . e($rating ?? '-') . '</span>';
                            };
                            @endphp
                            @forelse($workPrograms as $key => $workProgram)
                            @php
                            $ratingProgram = optional(optional(optional($workProgram->riskIdentification)->departmentTarget)->ratingCriteria)->rating ?? null;
                            $hasBudget = $workProgram->routine_costs_count > 0 || $workProgram->investment_plans_count > 0;
                            @endphp
                            <tr class="@if(!$hasBudget) no-budget @endif">
                                <td class="text-center">{{ $workPrograms->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $workProgram->riskIdentification->risk ?? '-' }}</td>
                                <td class="text-center">{!! $ratingBadge($ratingProgram) !!}</td>
                                <td>{{ $workProgram->name }}</td>
                                <td>{{ $workProgram->units }}</td>
                                <td class="text-center">{{ $workProgram->year_plan !== null ? number_format($workProgram->year_plan, 0, ',', '.') : '-' }}</td>
                                <td class="text-center">{{ $workProgram->jan_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->feb_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->mar_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->apr_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->may_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->jun_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->jul_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->aug_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->sep_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->oct_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->nov_plan ?? '-' }}</td>
                                <td class="text-center">{{ $workProgram->dec_plan ?? '-' }}</td>
                                <td class="text-center">
                                    @if($hasBudget)
                                        <span class="badge bg-success" title="Program kerja sudah memiliki biaya">Ada</span>
                                    @else
                                        <span class="badge bg-warning text-dark" title="Program kerja belum memiliki biaya rutin/investasi">Belum Ada</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $workProgram->statusClass() }}">{{ $workProgram->statusLabel() }}</span>
                                </td>
                                <td class="text-center">
                                    @if($workProgram->canBeSubmitted() && auth()->user()->can('erkap.work-programs.submit'))
                                    <form method="POST" action="{{ route('erkap.work-programs.submit', $workProgram->id) }}" class="d-inline" onsubmit="return confirm('Ajukan program kerja ini untuk persetujuan?')">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm" title="Ajukan Persetujuan">
                                            <i class="mdi mdi-send"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('erkap.work-programs.edit', $workProgram->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $workProgram->id }}" data-name="{{ $workProgram->name }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="20" class="text-center text-muted">Belum ada data program kerja.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $workPrograms->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Program Kerja?',
                text: 'Program "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/work-programs') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection