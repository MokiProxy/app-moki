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
                    @can('erkap.company-targets.create')
                    <a href="{{ route('erkap.company-targets.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Sasaran Perusahaan
                    </a>
                    @endcan
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
                                <th>Target</th>
                                <th>Periode RKAP</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companyTargets as $key => $companyTarget)
                            <tr>
                                <td class="text-center">{{ $companyTargets->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $companyTarget->target }}</td>
                                <td>{{ $companyTarget->rkap->year ?? '-' }}</td>
                                <td class="text-center">
                                    @can('erkap.company-targets.edit')
                                    <a href="{{ route('erkap.company-targets.edit', $companyTarget->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('erkap.company-targets.delete')
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $companyTarget->id }}" data-name="{{ $companyTarget->target }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data sasaran perusahaan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $companyTargets->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Sasaran Perusahaan?',
                text: 'Sasaran perusahaan "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/company-targets') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
