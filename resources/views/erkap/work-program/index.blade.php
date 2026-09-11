@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    table.table th, table.table td { white-space: nowrap; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
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
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($workPrograms as $key => $workProgram)
                            <tr>
                                <td class="text-center">{{ $workPrograms->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $workProgram->riskIdentification->risk ?? '-' }}</td>
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
                                <td colspan="18" class="text-center text-muted">Belum ada data program kerja.</td>
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