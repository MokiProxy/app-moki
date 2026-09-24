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
                <div class="d-flex gap-2">
                    <form method="GET" action="{{ route('erkap.budget-capex.summary') }}" class="d-flex gap-2 align-items-center">
                        <select name="erkap_rkap_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                            @foreach($rkapList as $item)
                                <option value="{{ $item->id }}" {{ $rkap && $rkap->id == $item->id ? 'selected' : '' }}>
                                    RKAP {{ $item->year }}
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('erkap.budget-capex.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif

                @php
                    $grandPlanTotal = $rows->sum('plan_total');
                    $grandBudgetCurrent = $rows->sum('budget_current_year');
                    $grandPreviousRemaining = $rows->sum('previous_year_remaining');
                    $grandBudgetTotal = $rows->sum('budget_total');
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 bg-primary text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Total Investasi (Rencana)</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($grandPlanTotal, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-success text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Sisa Anggaran Tahun Lalu</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($grandPreviousRemaining, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-info text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Anggaran Tahun Ini</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($grandBudgetCurrent, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 bg-dark text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Total Anggaran</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($grandBudgetTotal, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>Divisi</th>
                                <th>Kategori</th>
                                <th>Jenis Investasi</th>
                                <th>Kriteria</th>
                                <th>Program Kerja</th>
                                <th class="text-center">Prioritas</th>
                                <th>Total Investasi</th>
                                <th>Sisa Anggaran Tahun Lalu</th>
                                <th>Anggaran Tahun Ini</th>
                                <th>Total Anggaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byDivision as $division => $divisionRows)
                                @foreach($divisionRows as $index => $row)
                                <tr>
                                    @if($index === 0)
                                    <td class="fw-bold" rowspan="{{ $divisionRows->count() }}">{{ $division }}</td>
                                    @endif
                                    <td>
                                        {{ $row['category']?->code ?? '-' }}
                                        <small class="d-block text-muted">{{ $row['category']?->name ?? '' }}</small>
                                    </td>
                                    <td>
                                        {{ $row['type']?->code ?? '-' }}
                                        <small class="d-block text-muted">{{ $row['type']?->name ?? '' }}</small>
                                    </td>
                                    <td>
                                        {{ $row['criteria']?->code ?? '-' }}
                                        <small class="d-block text-muted">{{ $row['criteria']?->name ?? '' }}</small>
                                    </td>
                                    <td>{{ $row['work_program'] }}</td>
                                    <td class="text-center">
                                        @if($row['priority'])
                                            <span class="badge bg-soft-primary text-primary">{{ $row['priority'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($row['plan_total'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['previous_year_remaining'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row['budget_current_year'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row['budget_total'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td colspan="6" class="text-end">Subtotal {{ $division }}:</td>
                                    <td class="text-end">{{ number_format($divisionRows->sum('plan_total'), 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($divisionRows->sum('previous_year_remaining'), 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($divisionRows->sum('budget_current_year'), 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($divisionRows->sum('budget_total'), 0, ',', '.') }}</td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data rencana investasi untuk periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                        <tfoot class="table-dark">
                            <tr class="fw-bold">
                                <td class="text-end" colspan="6">Total:</td>
                                <td class="text-end">{{ number_format($grandPlanTotal, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($grandPreviousRemaining, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($grandBudgetCurrent, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($grandBudgetTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection