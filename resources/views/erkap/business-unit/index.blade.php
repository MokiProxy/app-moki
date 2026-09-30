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
                    @can('erkap.business-units.create')
                        <a href="{{ route('erkap.business-units.create') }}" class="btn btn-primary">
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
                                <th style="width: 80px">Segmen (a)</th>
                                <th>Nama</th>
                                <th style="width: 120px" class="text-center">Lokasi</th>
                                <th style="width: 100px" class="text-center">Status</th>
                                <th style="width: 140px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessUnits as $key => $businessUnit)
                            <tr>
                                <td class="text-center">{{ $businessUnits->firstItem() + $key }}</td>
                                <td class="text-center"><span class="badge bg-dark">{{ $businessUnit->code }}</span></td>
                                <td class="fw-bold">{{ $businessUnit->name }}</td>
                                <td class="text-center">{{ $businessUnit->locations_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $businessUnit->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $businessUnit->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @can('erkap.business-units.edit')
                                        <a href="{{ route('erkap.business-units.edit', $businessUnit->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('erkap.business-units.delete')
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $businessUnit->id }}" data-name="{{ $businessUnit->name }}" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data Bisnis Unit.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $businessUnits->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.delete-confirm', ['deleteUrl' => route('erkap.business-units.index'), 'deleteLabel' => 'Bisnis Unit'])
<script>
    $(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection
