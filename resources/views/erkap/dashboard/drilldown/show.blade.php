@extends('layouts.Erkap')

@section('title', $title ?? 'Detail Drilldown')

@section('css')
<link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .attr-table td { padding: 6px 12px; font-size: 0.9rem; }
    .attr-table td:first-child { width: 200px; font-weight: 600; color: #475569; background: #f8fafc; }
</style>
@endsection

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap align-items-start justify-content-between mb-3 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">{{ $title ?? 'Detail' }}</h4>
            <p class="text-muted mb-0">
                <a href="{{ route('erkap.dashboard.widgets') }}" class="text-decoration-none">Analytics</a>
                <i class="mdi mdi-chevron-right"></i> {{ ucfirst(str_replace('-', ' ', $type)) }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('erkap.dashboard.drilldown.export', ['type' => $type, 'id' => $id]) }}" class="btn btn-sm btn-outline-success">
                <i class="mdi mdi-file-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    @if(!empty($attributes))
    <div class="card shadow-sm mb-3">
        <div class="card-body border-bottom bg-light">
            <h6 class="mb-0 fw-bold"><i class="mdi mdi-information-outline me-2"></i>Informasi</h6>
        </div>
        <div class="card-body p-0">
            <table class="table attrs-table mb-0">
                <tbody class="attr-table">
                    @foreach($attributes as $key => $value)
                    <tr>
                        <td>{{ $key }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Rincian Data ({{ count($rows) }})</h6>
            <a href="#!" class="btn btn-light btn-sm" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
        </div>
        <div class="card-body">
            @php
            $firstRow = $rows->first();
            $columns = is_array($firstRow) ? array_keys($firstRow) : ($firstRow ? array_keys($firstRow->getAttributes()) : []);
            @endphp
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle w-100" id="tbl-drilldown">
                    <thead class="table-dark">
                        <tr>
                            @forelse($columns as $column)
                            <th>{{ Str::title(str_replace('_', ' ', $column)) }}</th>
                            @empty
                            <th>#</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            @php $values = is_array($row) ? array_values($row) : array_values($row->toArray()); @endphp
                            @foreach($values as $value)
                            <td>{{ is_scalar($value) || $value === null ? ($value ?? '-') : json_encode($value) }}</td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="99" class="text-center text-muted">Tidak ada detail data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function () {
        $('#btn-refresh').click(function () { location.reload(); });

        if ($.fn.DataTable && $('#tbl-drilldown tbody tr td').length > 0) {
            $('#tbl-drilldown').DataTable({
                pageLength: 25,
                order: [],
                language: { url: '' },
                dom: '<"d-flex justify-content-between mb-2"lf>rt<"d-flex justify-content-between mt-2"ip>'
            });
        }
    });
</script>
@endsection