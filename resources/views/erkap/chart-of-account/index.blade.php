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
                    <a href="{{ route('erkap.chart-of-accounts.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah CoA
                    </a>
                    <form action="{{ route('erkap.chart-of-accounts.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning" title="Sinkronkan CoA dengan elemen biaya">
                            <i class="mdi mdi-sync me-1"></i> Sinkronisasi
                        </button>
                    </form>
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

                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Cari</label>
                        <input type="text" class="form-control" id="search-input" value="{{ request('search') }}" placeholder="Kode / nama akun">
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body py-3">
                                <h6 class="card-title text-success mb-1"><i class="mdi mdi-trending-up me-1"></i> Pendapatan</h6>
                                <span class="fs-5 fw-bold">{{ $totalRevenue }} akun</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body py-3">
                                <h6 class="card-title text-danger mb-1"><i class="mdi mdi-trending-down me-1"></i> Beban</h6>
                                <span class="fs-5 fw-bold">{{ $totalExpense }} akun</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Filter Tipe</label>
                        <select class="form-select" id="filter-type" onchange="filterType(this.value)">
                            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="revenue" {{ request('type') == 'revenue' ? 'selected' : '' }}>Pendapatan (Revenue)</option>
                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Beban (Expense)</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th style="width: 120px">Kode</th>
                                <th>Nama Akun</th>
                                <th style="width: 140px">Tipe</th>
                                <th class="text-center" style="width: 160px">Jumlah Elemen Biaya</th>
                                <th style="width: 80px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($chartOfAccounts as $key => $account)
                            <tr>
                                <td class="text-center">{{ $chartOfAccounts->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $account->formattedCode }}</td>
                                <td>{{ $account->name }}</td>
                                <td>
                                    @if($account->type === 'revenue')
                                        <span class="badge bg-success">Revenue</span>
                                    @else
                                        <span class="badge bg-danger">Expense</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $account->cost_elements_count }}</td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.chart-of-accounts.show', $account->id) }}" class="btn btn-info btn-sm" title="Detail">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data chart of accounts.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $chartOfAccounts->appends(request()->query())->links('pagination::bootstrap-4') }}
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

    function filterType(value) {
        var url = new URL(window.location.href);
        url.searchParams.set('type', value);
        window.location.href = url.toString();
    }

    $('#search-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            var url = new URL(window.location.href);
            url.searchParams.set('search', this.value);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }
    });
</script>
@endsection