@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.EQTax')

@section('title', 'EQTax')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --warning-grad: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        --success-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        --danger-grad: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    }

    body {
        background-color: #f1f5f9;
        font-family: 'Inter', sans-serif;
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: 0.3s;
    }

    .stat-card {
        color: white;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .bg-indigo-grad {
        background: var(--primary-grad);
    }

    .bg-amber-grad {
        background: var(--warning-grad);
    }

    .bg-emerald-grad {
        background: var(--success-grad);
    }

    .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    .chart-container {
        position: relative;
        height: 100px;
        width: 100px;
        margin: 0 auto;
    }

    .chart-label {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        font-size: 1.1rem;
    }

    .table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #475569;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('form-it.forms.fixed-asset.my-submissions') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="card-body">
                    <div class="d-flex justify-content-start align-items-center">
                        <form action="{{ route('eqtax.gl.import') }}" id="file-form" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="file" name="file" id="file" hidden>
                            <label for="file" class="p-2 bg-primary text-white fw-bold rounded d-flex align-items-center gap-1 cursor-pointer"><i class="bx bx-import"></i>Import File GL</label>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    const uploadFileForm = document.getElementById('file-form')
    const uploadFile = document.getElementById('file')
    uploadFile.addEventListener("change", () => {
        if(uploadFile.files.length > 0) {
            uploadFileForm.submit()
        }
    })
</script>
@endsection
