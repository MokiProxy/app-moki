@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
@php
$months = [
    'jan_plan' => 'Januari',
    'feb_plan' => 'Februari',
    'mar_plan' => 'Maret',
    'apr_plan' => 'April',
    'may_plan' => 'Mei',
    'jun_plan' => 'Juni',
    'jul_plan' => 'Juli',
    'aug_plan' => 'Agustus',
    'sep_plan' => 'September',
    'oct_plan' => 'Oktober',
    'nov_plan' => 'November',
    'dec_plan' => 'Desember',
];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.revenue-plans.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="mdi mdi-alert-circle me-1"></i>Error:</strong> {{ session('error') }}
                    @if(session('error_detail'))
                    <hr>
                    <small class="text-muted">
                        <strong>File:</strong> {{ session('error_detail.file') }}<br>
                        <strong>Line:</strong> {{ session('error_detail.line') }}
                    </small>
                    <details class="mt-2">
                        <summary class="text-muted" style="cursor:pointer">Stack Trace</summary>
                        <pre class="mt-1 p-2 bg-light border rounded" style="font-size:11px;max-height:200px;overflow:auto">{{ session('error_detail.trace') }}</pre>
                    </details>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <form action="{{ route('erkap.revenue-plans.update', $revenuePlan->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tahun RKAP <span class="text-danger">*</span></label>
                            <select name="erkap_rkap_id" class="form-select @error('erkap_rkap_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_rkap_id', $revenuePlan->erkap_rkap_id) ? '' : 'selected' }}>Pilih Tahun RKAP</option>
                                @foreach($rkaps as $rkap)
                                    <option value="{{ $rkap->id }}" {{ old('erkap_rkap_id', $revenuePlan->erkap_rkap_id) == $rkap->id ? 'selected' : '' }}>
                                        {{ $rkap->year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_rkap_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Divisi <span class="text-danger">*</span></label>
                            <select name="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('division_id', $revenuePlan->division_id) ? '' : 'selected' }}>Pilih Divisi</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id', $revenuePlan->division_id) == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('division_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Akun Pendapatan <span class="text-danger">*</span></label>
                            <select name="chart_of_account_id" class="form-select @error('chart_of_account_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('chart_of_account_id', $revenuePlan->chart_of_account_id) ? '' : 'selected' }}>Pilih Akun Pendapatan</option>
                                @foreach($chartOfAccounts as $coa)
                                    <option value="{{ $coa->id }}" {{ old('chart_of_account_id', $revenuePlan->chart_of_account_id) == $coa->id ? 'selected' : '' }}>
                                        {{ $coa->formattedCode }} - {{ $coa->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Keterangan rencana pendapatan">{{ old('description', $revenuePlan->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-calendar-month me-1"></i> Proyeksi Pendapatan Bulanan</h6>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="text" inputmode="decimal" name="{{ $field }}" class="form-control month-plan rupiah-input @error($field) is-invalid @enderror" value="{{ old($field, $revenuePlan->{$field}) }}" placeholder="0,00">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-3 col-md-3">
                        <label class="form-label fw-bold">Total Pendapatan</label>
                        <input type="text" inputmode="decimal" name="total" id="total" class="form-control bg-light rupiah-input @error('total') is-invalid @enderror" value="{{ old('total', $revenuePlan->total) }}" readonly>
                        @error('total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.revenue-plans.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
@include('erkap.partials.rupiah')
<script>
    function monthTotal() {
        var total = 0;
        $('.month-plan').each(function() {
            total += Rupiah.parse($(this).val());
        });
        return total;
    }

    function updateTotal() {
        $('#total').val(Rupiah.format(monthTotal()));
    }

    $(document).ready(function() {
        $(document).on('input', '.month-plan', updateTotal);
        updateTotal();
    });
</script>
@endsection