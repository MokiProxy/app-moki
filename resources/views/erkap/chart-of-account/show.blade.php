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
                                <p class="fw-bold mb-0 fs-5">{{ $chartOfAccount->code }}</p>
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

                <h6 class="fw-bold text-muted mb-3"><i class="mdi mdi-format-list-bulleted me-1"></i> Elemen Biaya Terkait</h6>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th style="width: 120px">Kode</th>
                                <th>Nama Elemen Biaya</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costElements as $key => $costElement)
                            <tr>
                                <td class="text-center">{{ $costElements->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $costElement->code }}</td>
                                <td>{{ $costElement->name }}</td>
                                <td>{{ $costElement->costElementCategory->name ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada elemen biaya yang terhubung ke akun ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $costElements->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection