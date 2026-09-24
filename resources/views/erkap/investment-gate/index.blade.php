@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-muted { color: #6c757d !important; }
    table.table th, table.table td { white-space: nowrap; }
    .step-indicator { display: flex; align-items: center; min-width: 180px; }
    .step-dot {
        width: 12px; height: 12px; border-radius: 50%; background: #e9ecef;
        border: 2px solid #dee2e6; display: inline-block; flex-shrink: 0;
    }
    .step-dot.success { background: #36c783; border-color: #36c783; }
    .step-dot.danger { background: #f06565; border-color: #f06565; }
    .step-dot.active { background: #f1b44c; border-color: #f1b44c; }
    .step-line { flex: 1; height: 2px; background: #e9ecef; margin: 0 2px; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">
                    <i class="mdi mdi-check-decagram me-1"></i> {{ $pageName }}
                </h5>
                <div>
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

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Rencana Investasi</th>
                                <th>Program Kerja</th>
                                <th class="text-end">Nilai</th>
                                <th>Stage Gate</th>
                                <th style="width: 200px">Progres Gate</th>
                                <th class="text-center">Status Gate</th>
                                <th style="width: 140px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grouped = $gates->groupBy('erkap_investment_plan_id');
                            @endphp
                            @forelse($gates as $key => $gate)
                            @php
                                $plan = $gate->plan;
                                $planGates = $plan ? $plan->stageGates : collect();
                            @endphp
                            <tr>
                                <td class="text-center">{{ $gates->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $plan->name ?? '-' }}</td>
                                <td>{{ $plan->workProgram->name ?? '-' }}</td>
                                <td class="text-end">{{ $plan ? number_format($plan->total, 0, ',', '.') : '-' }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $gate->label() }}</span>
                                </td>
                                <td>
                                    @if($planGates->isNotEmpty())
                                    <div class="step-indicator" title="{{ $planGates->map(fn ($g) => $g->label())->implode(' → ') }}">
                                        @foreach($planGates as $i => $g)
                                            @if($i > 0)<span class="step-line"></span>@endif
                                            <span class="step-dot {{ $g->status === 'approved' ? 'success' : ($g->status === 'rejected' || $g->status === 'revised' ? 'danger' : ($g->id === $gate->id ? 'active' : '')) }}"></span>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">{{ $planGates->where('status', 'approved')->count() }}/{{ $planGates->count() }} gate disetujui</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $gate->statusClass() }}">{{ $gate->statusLabel() }}</span>
                                    @if($plan)
                                    <div class="mt-1">
                                        <small class="badge bg-soft-{{ $plan->gateReviewStatusClass() }} text-{{ $plan->gateReviewStatusClass() }}">{{ $plan->gateReviewStatusLabel() }}</small>
                                    </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($gate->status === 'pending' && auth()->user()->can('erkap.investment-gates.review'))
                                    <a href="{{ route('erkap.investment-gates.show', $gate->id) }}" class="btn btn-primary btn-sm" title="Evaluasi Gate">
                                        <i class="mdi mdi-clipboard-check-outline"></i> Review
                                    </a>
                                    @else
                                    <a href="{{ route('erkap.investment-gates.show', $gate->id) }}" class="btn btn-outline-secondary btn-sm" title="Detail Gate">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="mdi mdi-check-decagram-outline fs-1 d-block mb-2"></i>
                                    Tidak ada gate review yang menunggu atau tersedia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $gates->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function() { location.reload(); });
    });
</script>
@endsection