@if(isset($lockedRkaps) && $lockedRkaps->isNotEmpty())
@php
    $module = $moduleName ?? 'Data anggaran';
@endphp
<div class="alert alert-warning border mb-3">
    <div class="d-flex align-items-start gap-2">
        <i class="mdi mdi-lock-alert-outline mdi-24px me-1"></i>
        <div>
            <strong>{{ $module }} periode berikut terkunci</strong> karena telah memasuki fase finalisasi/pengesahan. Data tidak dapat ditambah atau diubah:
            <div class="mt-2">
                @foreach($lockedRkaps as $lockedRkap)
                <span class="badge bg-dark rounded-pill me-1 mb-1">
                    {{ $lockedRkap->year }} — {{ $lockedRkap->phaseLabel() }}
                </span>
                @endforeach
                @can('erkap.rkap.edit')
                <a href="{{ route('erkap.rkap.index') }}" class="btn btn-sm btn-link p-0 ms-1">Kelola Lifecycle</a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endif