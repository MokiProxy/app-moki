@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    table.table th, table.table td { white-space: nowrap; }
</style>
@endsection

@section('content')
@php
$months = [
    'jan_plan' => 'Jan', 'feb_plan' => 'Feb', 'mar_plan' => 'Mar',
    'apr_plan' => 'Apr', 'may_plan' => 'Mei', 'jun_plan' => 'Jun',
    'jul_plan' => 'Jul', 'aug_plan' => 'Agu', 'sep_plan' => 'Sep',
    'oct_plan' => 'Okt', 'nov_plan' => 'Nov', 'dec_plan' => 'Des',
];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-2">
                    <form method="GET" action="{{ route('erkap.budget-capex.payment-distribution') }}" class="d-flex gap-2 align-items-center">
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

                <div class="table-responsive">
                    <table class="table table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40px">No</th>
                                <th>Program Kerja</th>
                                <th>Nama Investasi</th>
                                <th class="text-center">Prioritas</th>
                                @foreach($months as $label)
                                    <th class="text-center">{{ $label }}</th>
                                @endforeach
                                <th class="text-end">Total Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($investmentPlans as $key => $plan)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                <td>{{ $plan->workProgram->name ?? '-' }}</td>
                                <td class="fw-bold">{{ $plan->name }}</td>
                                <td class="text-center">
                                    @if($plan->priority_order)
                                        <span class="badge bg-soft-primary text-primary">{{ $plan->priority_order }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @foreach($months as $field => $label)
                                    <td class="text-center">{{ (float) $plan->{$field} > 0 ? number_format($plan->{$field}, 0, ',', '.') : '-' }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($plan->total, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="17" class="text-center text-muted">Belum ada rencana investasi untuk periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($investmentPlans->isNotEmpty())
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="text-end">Total per Bulan:</td>
                                @foreach(array_keys($months) as $field)
                                <td class="text-center">{{ number_format($totalByMonth[$field], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-dark fw-bold">
                                <td colspan="4" class="text-end">Total Pembayaran CAPEX Tahunan:</td>
                                <td colspan="12" class="text-center">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($grandTotal, 0, ',', '.') }}</td>
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