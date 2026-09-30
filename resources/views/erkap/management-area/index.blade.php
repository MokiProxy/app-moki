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
                    @can('erkap.management-areas.create')
                        <a href="{{ route('erkap.management-areas.create') }}" class="btn btn-primary">
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
                                <th style="width: 110px">Kode (c)</th>
                                <th>Nama</th>
                                <th style="width: 190px">Lokasi (b)</th>
                                <th style="width: 180px">Divisi</th>
                                <th style="width: 100px" class="text-center">Aktivitas</th>
                                <th style="width: 140px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($managementAreas as $key => $managementArea)
                            <tr>
                                <td class="text-center">{{ $managementAreas->firstItem() + $key }}</td>
                                <td class="text-center"><span class="badge bg-dark">{{ $managementArea->code }}</span></td>
                                <td class="fw-bold">{{ $managementArea->name }}</td>
                                <td>{{ $managementArea->location?->code }} — {{ $managementArea->location?->name }}</td>
                                <td>{{ $managementArea->division?->name ?? '—' }}</td>
                                <td class="text-center">{{ $managementArea->activities_count }}</td>
                                <td class="text-center">
                                    @can('erkap.management-areas.edit')
                                        <a href="{{ route('erkap.management-areas.edit', $managementArea->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('erkap.management-areas.delete')
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $managementArea->id }}" data-name="{{ $managementArea->name }}" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data Manajemen Area.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $managementAreas->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.delete-confirm', ['deleteUrl' => route('erkap.management-areas.index'), 'deleteLabel' => 'Manajemen Area'])
<script>
    $(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection
