@extends('layouts.SIMKA')

@section('title', $pageTitle)

@section('content')
@include('simka.partials.spph-styles')

<style>
    .spph-subpage-hero {
        background: linear-gradient(135deg, #556ee6 0%, #3a476e 100%);
        color: #fff;
        border-radius: 20px;
        padding: 2.5rem 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .spph-subpage-hero::after {
        content: "";
        position: absolute;
        right: -50px;
        top: -50px;
        width: 160px;
        height: 160px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .spph-subpage-hero h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }
    .spph-subpage-hero p {
        margin: 0.5rem 0 0;
        opacity: 0.9;
        font-size: 1rem;
    }
    .spph-subpage-hero-icon {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        background: rgba(255,255,255,0.15);
        flex-shrink: 0;
    }
    .spph-coming-soon {
        border: 2px dashed #dee2e6;
        border-radius: 16px;
        padding: 3rem 2rem;
        text-align: center;
        background: #f8f9fa;
        margin-bottom: 2rem;
    }
    .spph-coming-soon-icon {
        font-size: 3.5rem;
        color: #adb5bd;
        margin-bottom: 1rem;
        display: block;
    }
    .spph-coming-soon h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    .spph-coming-soon p {
        color: #6c757d;
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    .spph-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
    }
</style>

<!-- Hero Section -->
<div class="spph-subpage-hero">
    <div class="d-flex align-items-center">
        <div class="spph-subpage-hero-icon bg-soft-{{ $pageColor }}">
            <i class="mdi {{ $pageIcon }} text-{{ $pageColor }}"></i>
        </div>
        <div class="flex-grow-1 ms-3">
            <h1 class="mb-1">{{ $pageTitle }}</h1>
            <p class="mb-0">{{ $pageDescription }}</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6 mx-auto">
        <div class="card spph-form-card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="mdi mdi-calculator text-{{ $pageColor }} me-2"></i>
                    {{ $submenuTitle }}
                </h5>
            </div>
            <div class="card-body py-5">
                <div class="spph-coming-soon">
                    <i class="mdi mdi-construction spph-coming-soon-icon"></i>
                    <h3>Segera Tersedia</h3>
                    <p>Fitur kalkulasi untuk <strong>{{ $submenuTitle }}</strong> sedang dalam tahap pengembangan.</p>
                    <p class="mb-3">Silakan kembali nanti atau hubungi administrator sistem.</p>
                    <a href="{{ route('simka.spph2287.konfigurasi') }}" class="spph-back-btn btn btn-outline-{{ $pageColor }}">
                        <i class="mdi mdi-cog me-1"></i> Kembali ke Konfigurasi
                    </a>
                    <a href="{{ route('simka.spph2287.kalkulasi') }}" class="spph-back-btn btn btn-outline-secondary ms-2">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Kalkulasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
