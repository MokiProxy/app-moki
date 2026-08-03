@extends('layouts.Dokter')

@section('title', $pageName)

@section('css')
<link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet">
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
                    <a href="{{ route('dokter.document-types.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Jenis Dokumen
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
                    <table class="table table-hover table-bordered align-middle w-100" id="document-type-table">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th>Number Regex</th>
                                <th>Number Label</th>
                                <th>Keterangan Regex</th>
                                <th class="text-center">Keterangan Enabled</th>
                                <th>Uraian Regex</th>
                                <th class="text-center">Uraian Enabled</th>
                                <th>Tanggal Regex</th>
                                <th class="text-center">Tanggal Enabled</th>
                                <th class="text-center">Vendor Search</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documentTypes as $key => $dt)
                            <tr>
                                <td class="text-center">{{ $documentTypes->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $dt->name }}</td>
                                <td>{{ $dt->description ?? '-' }}</td>
                                <td><code>{{ $dt->number_regex ?? '-' }}</code></td>
                                <td>{{ $dt->number_label ?? '-' }}</td>
                                <td><code>{{ $dt->keterangan_regex ?? '-' }}</code></td>
                                <td class="text-center">
                                    @if($dt->keterangan_enabled)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td><code>{{ $dt->uraian_regex ?? '-' }}</code></td>
                                <td class="text-center">
                                    @if($dt->uraian_enabled)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td><code>{{ $dt->tanggal_regex ?? '-' }}</code></td>
                                <td class="text-center">
                                    @if($dt->tanggal_enabled)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($dt->vendor_search_enabled)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('dokter.document-types.edit', $dt->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $dt->id }}" data-name="{{ $dt->name }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted">Belum ada data jenis dokumen.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $documentTypes->links() }}
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
        $('#btn-refresh').click(function() {
            location.reload();
        });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            Swal.fire({
                title: 'Hapus Jenis Dokumen?',
                text: 'Jenis dokumen "' + name + '" akan dihapus permanen termasuk folder FTP.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('dokter/document-types') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
