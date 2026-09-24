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
                <a href="{{ route('erkap.routine-costs.index') }}" class="btn btn-outline-secondary">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('erkap.routine-costs.consolidate') }}" class="row g-3 mb-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Filter Satuan Kerja</label>
                        <select name="division_id" class="form-select">
                            <option value="">Semua Divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" {{ request('division_id') == $division->id ? 'selected' : '' }}>
                                    {{ $division->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-filter-variant me-1"></i> Tampilkan
                        </button>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-primary text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Grand Total OPEX</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($grandTotal, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-info text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Jumlah Elemen</small>
                                <h5 class="mb-0 mt-1">{{ $consolidated->count() }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-dark text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Biaya Terpusat</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($centralizedGroups['centralized']->sum('total'), 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 bg-secondary text-white shadow-sm">
                            <div class="card-body py-3">
                                <small class="text-white-50 text-uppercase fw-bold">Biaya Non-Terpusat</small>
                                <h5 class="mb-0 mt-1">Rp {{ number_format($centralizedGroups['non_centralized']->sum('total'), 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Elemen Biaya</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Total Qty</th>
                                <th class="text-center">Jan</th>
                                <th class="text-center">Feb</th>
                                <th class="text-center">Mar</th>
                                <th class="text-center">Apr</th>
                                <th class="text-center">Mei</th>
                                <th class="text-center">Jun</th>
                                <th class="text-center">Jul</th>
                                <th class="text-center">Agu</th>
                                <th class="text-center">Sep</th>
                                <th class="text-center">Okt</th>
                                <th class="text-center">Nov</th>
                                <th class="text-center">Des</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consolidated as $key => $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="fw-bold">{{ $item['cost_element']->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($item['is_centralized'])
                                        <span class="badge bg-info">Terpusat: {{ $item['coordinator'] ?? '-' }}</span>
                                    @else
                                        <span class="badge bg-secondary">Non-Terpusat</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item['total_qty'] }}</td>
                                @foreach($item['monthly'] as $month => $value)
                                    <td class="text-end">{{ number_format($value, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-end fw-bold">{{ number_format($item['total_cost'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="17" class="text-center text-muted">Belum ada data biaya rutin.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total Bulanan</th>
                                @foreach($totalByMonth as $month => $value)
                                    <th class="text-end">Rp {{ number_format($value, 0, ',', '.') }}</th>
                                @endforeach
                                <th class="text-end">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection