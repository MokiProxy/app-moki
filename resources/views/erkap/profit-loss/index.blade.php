@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
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

                <form action="{{ route('erkap.profit-loss.generate') }}" method="POST" class="row g-3 align-items-end mb-4">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tahun RKAP <span class="text-danger">*</span></label>
                        <select name="erkap_rkap_id" class="form-select" required>
                            <option value="" disabled selected>Pilih Tahun RKAP</option>
                            @foreach($rkaps as $rkap)
                                <option value="{{ $rkap->id }}" {{ old('erkap_rkap_id') == $rkap->id ? 'selected' : '' }}>{{ $rkap->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Divisi</label>
                        @if($isDivisionScoped)
                            @php $ownDivision = $divisions->first(); @endphp
                            <input type="text" class="form-control" value="{{ $ownDivision->name ?? '-' }}" disabled>
                            <input type="hidden" name="division_id" value="{{ $ownDivision ? $ownDivision->id : '' }}">
                        @else
                            <select name="division_id" class="form-select">
                                <option value="" selected>Seluruh Divisi (Perusahaan)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>{{ $division->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-chart-line me-1"></i> Buat Laporan
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Tahun RKAP</th>
                                <th>Divisi</th>
                                <th class="text-end">Total Pendapatan</th>
                                <th class="text-end">Total Beban</th>
                                <th class="text-end">Laba Bersih</th>
                                <th class="text-center">Margin</th>
                                <th class="text-center">Periode</th>
                                <th class="text-center" style="width: 100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statements as $key => $statement)
                            <tr>
                                <td class="text-center">{{ $statements->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $statement->rkap->year ?? '-' }}</td>
                                <td>{{ $statement->division->name ?? 'Perusahaan' }}</td>
                                <td class="text-end">{{ number_format($statement->total_revenue, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($statement->total_expense, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($statement->net_profit, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($statement->margin, 2, ',', '.') }}%</td>
                                <td class="text-center">{{ ucfirst($statement->period) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.profit-loss.show', $statement->id) }}" class="btn btn-info btn-sm" title="Detail">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada laporan laba rugi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $statements->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection