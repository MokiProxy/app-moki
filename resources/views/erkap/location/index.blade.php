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
                    @can('erkap.locations.create')
                        <a href="{{ route('erkap.locations.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus me-1"></i> Tambah
                        </a>
                    @endcan
                    <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                </div>
            </div>
            <div class="card-body">
                @include('erkap.partials.form-alerts')

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th style="width: 90px">Kode (b)</th>
                                <th>Nama</th>
                                <th style="width: 160px">Bisnis Unit (a)</th>
                                <th style="width: 120px" class="text-center">Area</th>
                                <th style="width: 100px" class="text-center">Status</th>
                                <th style="width: 140px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $key => $location)
                            <tr>
                                <td class="text-center">{{ $locations->firstItem() + $key }}</td>
                                <td class="text-center"><span class="badge bg-dark">{{ $location->code }}</span></td>
                                <td class="fw-bold">{{ $location->name }}</td>
                                <td>{{ $location->businessUnit?->code }} — {{ $location->businessUnit?->name }}</td>
                                <td class="text-center">{{ $location->management_areas_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $location->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $location->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @can('erkap.locations.edit')
                                        <a href="{{ route('erkap.locations.edit', $location->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('erkap.locations.delete')
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $location->id }}" data-name="{{ $location->name }}" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data Lokasi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $locations->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.delete-confirm', ['deleteUrl' => route('erkap.locations.index'), 'deleteLabel' => 'Lokasi'])
<script>
    $(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection
