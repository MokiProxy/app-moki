@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.business-units.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @include('erkap.partials.form-alerts')

                @include('erkap.business-unit._form', [
                    'action' => route('erkap.business-units.update', $businessUnit),
                    'method' => 'PUT',
                ])
            </div>
        </div>
    </div>
</div>
@endsection
