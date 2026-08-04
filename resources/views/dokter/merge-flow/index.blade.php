@extends('layouts.Dokter')

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
                    <a href="{{ route('dokter.merge-flows.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Alur
                    </a>
                    <a href="{{ route('dokter.merge-flows.groups') }}" class="btn btn-info">
                        <i class="mdi mdi-group me-1"></i> Grup Penggabungan
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

                <h6 class="fw-bold mb-3">Daftar Alur Birokrasi</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Deskripsi</th>
                                <th>Steps</th>
                                <th class="text-center">Status</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($flows as $key => $flow)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                <td class="fw-bold">{{ $flow->name }}</td>
                                <td><code>{{ $flow->slug }}</code></td>
                                <td>{{ $flow->description ?? '-' }}</td>
                                <td>
                                    @foreach($flow->steps->sortBy('order') as $step)
                                        <span class="badge bg-primary me-1">
                                            {{ $step->order }}. {{ $step->documentType->name ?? 'N/A' }}
                                        </span>
                                        @if($step->link_regex)
                                            <small class="text-muted d-block ms-2">Link: {{ $step->link_label ?? $step->link_regex }}</small>
                                        @endif
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($flow->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('dokter.merge-flows.edit', $flow->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $flow->id }}" data-name="{{ $flow->name }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada alur birokrasi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingGroups->count() > 0 || $completeGroups->count() > 0)
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <h5 class="mb-0 card-title text-dark fw-bold">Grup Penggabungan Terbaru</h5>
            </div>
            <div class="card-body">
                <h6 class="fw-bold text-warning">Menunggu (<span class="badge bg-warning">{{ $pendingGroups->total() }}</span>)</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Alur</th>
                                <th>Vendor</th>
                                <th>Nomor Root</th>
                                <th>Items</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingGroups as $key => $group)
                            <tr>
                                <td class="text-center">{{ $pendingGroups->firstItem() + $key }}</td>
                                <td>{{ $group->mergeFlow->name ?? 'N/A' }}</td>
                                <td>{{ $group->vendor_name }}</td>
                                <td><code>{{ $group->root_document_number }}</code></td>
                                <td>{{ $group->items->count() }} / {{ $group->mergeFlow->steps->count() ?? '?' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $group->status_badge_class }}">{{ $group->status_label }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada grup pending.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $pendingGroups->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

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
                title: 'Hapus Alur Birokrasi?',
                text: 'Alur "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('dokter/merge-flows') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
