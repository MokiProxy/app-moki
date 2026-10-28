@include('simka.partials.spph-styles')

@php
    $backRoute = route("simka.{$spphKey}.index");
    $contentView = $contentView ?? null;
@endphp

<div class="row g-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('simka.index') }}" class="text-decoration-none">SIMKA</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ $backRoute }}" class="text-decoration-none">{{ $spphTitle }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>
    </div>

    <div class="col-12">
        <div class="card spph-menu-card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <div class="spph-icon-soft bg-soft-{{ $pageColor }} me-3">
                        <i class="mdi {{ $pageIcon }} fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageTitle }} {{ $spphTitle }}</h5>
                        <small class="text-muted">{{ $pageDescription }}</small>
                    </div>
                    <a href="{{ $backRoute }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle-outline me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-alert-circle-outline me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($contentView)
                    @include($contentView, ['spphKey' => $spphKey, 'spphTitle' => $spphTitle])
                @else
                    <div class="spph-empty text-center py-5">
                        <div class="spph-icon-soft bg-soft-{{ $pageColor }} mx-auto mb-3">
                            <i class="mdi {{ $pageIcon }} fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Halaman masih kosong</h5>
                        <p class="text-muted mb-4 mx-auto" style="max-width: 460px;">
                            Fitur {{ $pageTitle }} {{ $spphTitle }} belum tersedia. Silakan tambahkan pada tahap pengembangan berikutnya.
                        </p>
                        <a href="{{ $backRoute }}" class="btn btn-outline-primary">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali ke {{ $spphTitle }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
