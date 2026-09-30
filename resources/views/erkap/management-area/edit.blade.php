@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.management-areas.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @include('erkap.partials.form-alerts')

                @include('erkap.management-area._form', [
                    'businessUnits' => $businessUnits,
                    'divisions' => $divisions,
                    'locations' => $locations,
                    'action' => route('erkap.management-areas.update', $managementArea),
                    'method' => 'PUT',
                ])
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    window.ERKAP_COA_OPTIONS_URL = @json(route('erkap.coa-options.index'));
</script>
<script src="{{ asset('js/erkap-cascade.js') }}"></script>
<script>
    $(function() {
        ErkapCascade.init({
            chain: ['business_unit', 'location'],
            selectors: {
                business_unit: '#erkap_business_unit_id',
                location: '#erkap_location_id',
            },
        });
    });
</script>
@endsection
