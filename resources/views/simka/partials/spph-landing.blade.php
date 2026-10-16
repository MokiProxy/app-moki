@include('simka.partials.spph-styles')

@php
    $konfigurasiRoute = route("simka.{$spphKey}.konfigurasi");
    $kalkulasiRoute = route("simka.{$spphKey}.kalkulasi");
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="spph-hero p-4 p-md-5 shadow-sm">
            <div class="d-flex align-items-center position-relative" style="z-index: 1;">
                <div class="spph-hero-icon me-4">
                    <i class="mdi mdi-file"></i>
                </div>
                <div>
                    <span class="badge bg-light text-primary-it-admin fw-semibold mb-2">Pendapatan</span>
                    <h2 class="fw-bold mb-1 text-white">{{ $spphTitle }}</h2>
                    <p class="mb-0 text-white-50">Pilih menu di bawah ini untuk mengelola dokumen {{ $spphTitle }}.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <h6 class="text-uppercase text-muted fw-bold mb-0">Menu</h6>
    </div>

    <div class="col-md-6">
        <a href="{{ $konfigurasiRoute }}" class="text-decoration-none">
            <div class="card spph-menu-card spph-hoverable h-100 shadow-sm">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="spph-icon-soft bg-soft-primary me-3">
                        <i class="mdi mdi-database-cog-outline fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1 text-dark fw-bold">Konfigurasi Data</h5>
                        <p class="mb-0 text-muted small">Atur master data dan parameter {{ $spphTitle }}.</p>
                    </div>
                    <i class="mdi mdi-chevron-right fs-3 text-muted"></i>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-6">
        <a href="{{ $kalkulasiRoute }}" class="text-decoration-none">
            <div class="card spph-menu-card spph-hoverable h-100 shadow-sm">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="spph-icon-soft bg-soft-success me-3">
                        <i class="mdi mdi-calculator-variant-outline fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1 text-dark fw-bold">Kalkulasi</h5>
                        <p class="mb-0 text-muted small">Jalankan dan tinjau proses kalkulasi {{ $spphTitle }}.</p>
                    </div>
                    <i class="mdi mdi-chevron-right fs-3 text-muted"></i>
                </div>
            </div>
        </a>
    </div>
</div>
