@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
@php
$months = [
    'jan_cost' => 'Januari',
    'feb_cost' => 'Februari',
    'mar_cost' => 'Maret',
    'apr_cost' => 'April',
    'may_cost' => 'Mei',
    'jun_cost' => 'Juni',
    'jul_cost' => 'Juli',
    'aug_cost' => 'Agustus',
    'sep_cost' => 'September',
    'oct_cost' => 'Oktober',
    'nov_cost' => 'November',
    'des_cost' => 'Desember',
];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.routine-costs.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.routine-costs.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Kerja <span class="text-danger">*</span></label>
                            <select name="erkap_work_program_id" class="form-select @error('erkap_work_program_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Program Kerja</option>
                                @foreach($workPrograms as $workProgram)
                                    <option value="{{ $workProgram->id }}" {{ old('erkap_work_program_id') == $workProgram->id ? 'selected' : '' }}>
                                        {{ $workProgram->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_work_program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori Biaya <span class="text-danger">*</span></label>
                            <select name="cost_category" class="form-select @error('cost_category') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Kategori Biaya</option>
                                <option value="Biaya Umum" {{ old('cost_category') == 'Biaya Umum' ? 'selected' : '' }}>Biaya Umum</option>
                                <option value="Bahan Bakar Minyak" {{ old('cost_category') == 'Bahan Bakar Minyak' ? 'selected' : '' }}>Bahan Bakar Minyak</option>
                                <option value="Sewa Kendaraan" {{ old('cost_category') == 'Sewa Kendaraan' ? 'selected' : '' }}>Sewa Kendaraan</option>
                            </select>
                            @error('cost_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Elemen Biaya <span class="text-danger">*</span></label>
                            <select name="erkap_cost_element_id" class="form-select @error('erkap_cost_element_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Elemen Biaya</option>
                                @foreach($costElements as $costElement)
                                    <option value="{{ $costElement->id }}" {{ old('erkap_cost_element_id') == $costElement->id ? 'selected' : '' }}>
                                        {{ $costElement->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_cost_element_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Kebutuhan <span class="text-danger">*</span></label>
                            <textarea name="need" class="form-control @error('need') is-invalid @enderror" rows="2" placeholder="Deskripsi kebutuhan" required>{{ old('need') }}</textarea>
                            @error('need') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cost Center ID</label>
                            <input type="number" name="cost_center_id" class="form-control @error('cost_center_id') is-invalid @enderror" value="{{ old('cost_center_id') }}" placeholder="Opsional">
                            @error('cost_center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pemilik Cost Center <span class="text-danger">*</span></label>
                            <input type="text" name="cost_center_owner" class="form-control @error('cost_center_owner') is-invalid @enderror" value="{{ old('cost_center_owner') }}" placeholder="Nama pemilik cost center" required>
                            @error('cost_center_owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty') }}" required>
                            @error('qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Harga Satuan <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}" required>
                            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Total</label>
                            <input type="number" step="0.01" min="0" name="total" id="total" class="form-control bg-light @error('total') is-invalid @enderror" value="{{ old('total') }}" readonly>
                            @error('total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-calendar-month me-1"></i> Biaya Bulanan</h6>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="number" step="0.01" min="0" name="{{ $field }}" class="form-control month-cost @error($field) is-invalid @enderror" value="{{ old($field) }}">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.routine-costs.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    function calculateTotal() {
        var total = 0;
        $('.month-cost').each(function() {
            var val = parseFloat($(this).val());
            if (!isNaN(val)) {
                total += val;
            }
        });
        $('#total').val(total);
    }

    $(document).ready(function() {
        $(document).on('input', '.month-cost', calculateTotal);
        calculateTotal();
    });
</script>
@endsection
