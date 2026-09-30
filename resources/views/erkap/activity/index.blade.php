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
                    @can('erkap.activities.create')
                        <a href="{{ route('erkap.activities.create') }}" class="btn btn-primary">
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
                                <th style="width: 100px">Kode (d)</th>
                                <th>Nama</th>
                                <th style="width: 180px">Manajemen Area (c)</th>
                                <th style="width: 110px" class="text-center">Swakelola</th>
                                <th style="width: 100px" class="text-center">Pusat Biaya</th>
                                <th style="width: 140px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $key => $activity)
                            <tr>
                                <td class="text-center">{{ $activities->firstItem() + $key }}</td>
                                <td class="text-center"><span class="badge bg-dark">{{ $activity->code }}</span></td>
                                <td class="fw-bold">{{ $activity->name }}</td>
                                <td>{{ $activity->managementArea?->code }} — {{ $activity->managementArea?->name }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $activity->is_swakelola ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $activity->is_swakelola ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $activity->cost_centers_count }}</td>
                                <td class="text-center">
                                    @can('erkap.activities.edit')
                                        <a href="{{ route('erkap.activities.edit', $activity->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('erkap.activities.delete')
                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $activity->id }}" data-name="{{ $activity->name }}" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data Aktivitas.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $activities->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.delete-confirm', ['deleteUrl' => route('erkap.activities.index'), 'deleteLabel' => 'Aktivitas'])
<script>
    $(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection
