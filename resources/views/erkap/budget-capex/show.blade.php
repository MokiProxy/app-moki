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
    'jan_plan' => 'Jan',
    'feb_plan' => 'Feb',
    'mar_plan' => 'Mar',
    'apr_plan' => 'Apr',
    'may_plan' => 'Mei',
    'jun_plan' => 'Jun',
    'jul_plan' => 'Jul',
    'aug_plan' => 'Agu',
    'sep_plan' => 'Sep',
    'oct_plan' => 'Okt',
    'nov_plan' => 'Nov',
    'dec_plan' => 'Des',
];
$statusColors = [
    'draft' => 'secondary',
    'submitted' => 'info',
    'approved' => 'success',
    'rejected' => 'danger',
];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.budget-capex.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
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

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">Tahun RKAP</label>
                        <p class="fw-bold mb-0">{{ $budgetCapex->rkap->year ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">Divisi</label>
                        <p class="fw-bold mb-0">{{ $budgetCapex->division->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">Total Investasi</label>
                        <p class="fw-bold mb-0 fs-5">Rp {{ number_format($budgetCapex->total_investment, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-{{ $statusColors[$budgetCapex->status] ?? 'secondary' }} fs-6">
                                {{ ucfirst($budgetCapex->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-title fw-bold"><i class="mdi mdi-cog me-1"></i> Ubah Status</h6>
                        <form action="{{ route('erkap.budget-capex.update', $budgetCapex->id) }}" method="POST" class="d-flex align-items-end gap-2">
                            @csrf
                            @method('PUT')
                            <div class="flex-grow-1" style="max-width:200px">
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="draft" {{ $budgetCapex->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="submitted" {{ $budgetCapex->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="approved" {{ $budgetCapex->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $budgetCapex->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan (opsional)" value="{{ old('notes', $budgetCapex->notes) }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-content-save me-1"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3"><i class="mdi mdi-format-list-bulleted me-1"></i> Daftar Rencana Investasi</h6>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Program Kerja</th>
                                <th>Nama Investasi</th>
                                <th class="text-center">Prioritas</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                @foreach($months as $label)
                                    <th class="text-center">{{ $label }}</th>
                                @endforeach
                                <th class="text-end">Total</th>
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
                                <td>{{ $plan->investattionCategory->name ?? '-' }}</td>
                                <td>{{ $plan->investationType->name ?? '-' }}</td>
                                <td class="text-center">{{ $plan->qty }}</td>
                                <td class="text-end">{{ number_format($plan->unit_price, 0, ',', '.') }}</td>
                                @foreach($months as $field => $label)
                                    <td class="text-center">{{ $plan->{$field} ? number_format($plan->{$field}, 0, ',', '.') : '-' }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($plan->total, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ 9 + count($months) }}" class="text-center text-muted">Belum ada data rencana investasi untuk divisi ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($investmentPlans->isNotEmpty())
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="7" class="text-end">Total per Bulan:</td>
                                <td></td>
                                @foreach($months as $field)
                                    <td class="text-center">{{ number_format($totalByMonth[$field], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end">{{ number_format($budgetCapex->total_investment, 0, ',', '.') }}</td>
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