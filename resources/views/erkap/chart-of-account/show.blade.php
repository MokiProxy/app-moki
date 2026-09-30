@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    .segment-box { border-left: 3px solid #0d6efd; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.chart-of-accounts.index') }}" class="btn btn-secondary">
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

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small">Kode Akun</label>
                                <p class="fw-bold mb-0 fs-5"><code>{{ $chartOfAccount->formattedCode }}</code></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Nama Akun</label>
                                <p class="fw-bold mb-0 fs-5">{{ $chartOfAccount->name }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-muted small">Tipe</label>
                                <p class="mb-0">
                                    @if($chartOfAccount->type === 'revenue')
                                        <span class="badge bg-success fs-6">Revenue (Pendapatan)</span>
                                    @else
                                        <span class="badge bg-danger fs-6">Expense (Beban)</span>
                                    @endif
                                </p>
                            </div>
                            @if($chartOfAccount->description)
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-muted small">Deskripsi</label>
                                <p class="mb-0">{{ $chartOfAccount->description }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">
                    <i class="mdi mdi-vector-link me-1"></i> Komposisi Kode
                </h6>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 60px">Segmen</th>
                                <th style="width: 90px">Panjang</th>
                                <th style="width: 120px">Kode</th>
                                <th>Referensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rows = [
                                    'a' => ['business_unit', $chartOfAccount->costCenter?->businessUnit->name ?? null],
                                    'b' => ['location', $chartOfAccount->costCenter?->location->name ?? null],
                                    'c' => ['management_area', $chartOfAccount->costCenter?->managementArea->name ?? null],
                                    'd' => ['activity', $chartOfAccount->costCenter?->activity->name ?? null],
                                    'e' => ['cost_element', $chartOfAccount->costElement?->name ?? null],
                                ];
                            @endphp
                            @foreach($rows as $letter => [$key, $reference])
                                <tr>
                                    <td class="text-center fw-bold">{{ $letter }}</td>
                                    <td class="text-muted">{{ strlen($decoded[$key] ?? '') }}</td>
                                    <td><code>{{ $decoded[$key] ?? '—' }}</code></td>
                                    <td class="segment-box ps-3">
                                        {{ $reference ?? '—' }}
                                        @if($key === 'cost_element' && $chartOfAccount->costElement?->costElementCategory)
                                            <span class="badge bg-light text-dark border ms-1">{{ $chartOfAccount->costElement->costElementCategory->name }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($chartOfAccount->costCenter)
                    <div class="alert alert-light border mt-3 mb-0">
                        <i class="mdi mdi-domain me-1"></i>
                        Berasal dari Pusat Biaya
                        <code class="fw-bold">{{ $chartOfAccount->costCenter->formattedCode }}</code>
                        — {{ $chartOfAccount->costCenter->name }}
                        @if($chartOfAccount->costCenter->owner)
                            <span class="d-block small text-muted mt-1">
                                Pemilik: {{ $chartOfAccount->costCenter->owner }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
