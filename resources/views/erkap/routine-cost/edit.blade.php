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

                <form action="{{ route('erkap.routine-costs.update', $routineCost->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Kerja <span class="text-danger">*</span></label>
                            <select name="erkap_work_program_id" class="form-select @error('erkap_work_program_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_work_program_id', $routineCost->erkap_work_program_id) ? '' : 'selected' }}>Pilih Program Kerja</option>
                                @foreach($workPrograms as $workProgram)
                                    <option value="{{ $workProgram->id }}" {{ old('erkap_work_program_id', $routineCost->erkap_work_program_id) == $workProgram->id ? 'selected' : '' }}>
                                        {{ $workProgram->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_work_program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Elemen Biaya <span class="text-danger">*</span></label>
                            <select name="erkap_cost_element_id" class="form-select @error('erkap_cost_element_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_cost_element_id', $routineCost->erkap_cost_element_id) ? '' : 'selected' }}>Pilih Elemen Biaya</option>
                                @foreach($costElements as $costElement)
                                    <option value="{{ $costElement->id }}" {{ old('erkap_cost_element_id', $routineCost->erkap_cost_element_id) == $costElement->id ? 'selected' : '' }}>
                                        {{ $costElement->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_cost_element_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Kebutuhan <span class="text-danger">*</span></label>
                            <textarea name="need" class="form-control @error('need') is-invalid @enderror" rows="2" placeholder="Deskripsi kebutuhan" required>{{ old('need', $routineCost->need) }}</textarea>
                            @error('need') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cost Center Swakelola</label>
                            <select id="cost_center_swakelola" class="form-select">
                                <option value="">Pilih Cost Center Swakelola</option>
                                @foreach($swakelolaCostCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" data-owner="{{ $costCenter->owner }}" data-cost-element="{{ $costCenter->cost_element_id }}" {{ old('cost_center_id', $routineCost->cost_center_id) == $costCenter->id ? 'selected' : '' }}>
                                        {{ $costCenter->code }} - {{ $costCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cost Center Non Swakelola</label>
                            <select id="cost_center_non_swakelola" class="form-select">
                                <option value="">Pilih Cost Center Non Swakelola</option>
                                @foreach($nonSwakelolaCostCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" data-owner="{{ $costCenter->owner }}" data-cost-element="{{ $costCenter->cost_element_id }}" {{ old('cost_center_id', $routineCost->cost_center_id) == $costCenter->id ? 'selected' : '' }}>
                                        {{ $costCenter->code }} - {{ $costCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="cost_center_id" id="cost_center_id" value="{{ old('cost_center_id', $routineCost->cost_center_id) }}">

                        @error('cost_center_id')
                        <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                        @enderror

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pemilik Cost Center <span class="text-danger">*</span></label>
                            <input type="text" name="cost_center_owner" id="cost_center_owner" class="form-control @error('cost_center_owner') is-invalid @enderror" value="{{ old('cost_center_owner', $routineCost->cost_center_owner) }}" placeholder="Nama pemilik cost center" required>
                            @error('cost_center_owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty', $routineCost->qty) }}" required>
                            @error('qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="units" class="form-control @error('units') is-invalid @enderror" value="{{ old('units', $routineCost->units ?? 'Unit') }}" placeholder="cth: unit, set, liter" required maxlength="50">
                            @error('units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Harga Satuan <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="unit_price" class="form-control rupiah-input @error('unit_price') is-invalid @enderror" value="{{ old('unit_price', $routineCost->unit_price) }}" placeholder="0,00" required>
                            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total</label>
                            <input type="text" inputmode="decimal" name="total" id="total" class="form-control bg-light rupiah-input @error('total') is-invalid @enderror" value="{{ old('total', $routineCost->total) }}" readonly>
                            @error('total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div id="cost-preview" class="form-text"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="text-uppercase fw-bold text-muted mb-0"><i class="mdi mdi-calendar-month me-1"></i> Biaya Bulanan</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_kumulatif" id="is_kumulatif" value="1" {{ old('is_kumulatif', $routineCost->is_kumulatif) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kumulatif">Kumulatif</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="text" inputmode="decimal" name="{{ $field }}" class="form-control month-cost rupiah-input @error($field) is-invalid @enderror" value="{{ old($field, $routineCost->{$field}) }}" placeholder="0,00">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
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
@include('erkap.partials.rupiah')
<script>
    function monthTotal() {
        var total = 0;
        $('.month-cost').each(function() {
            total += Rupiah.parse($(this).val());
        });
        return total;
    }

    function updateCostPreview() {
        var qty = parseFloat($('input[name="qty"]').val()) || 0;
        var price = Rupiah.parse($('input[name="unit_price"]').val());
        var expected = qty * price;
        var monthly = monthTotal();
        var kumulatif = $('#is_kumulatif').is(':checked');
        $('#total').val(Rupiah.format(kumulatif ? expected : monthly));
        var msg = 'Total kebutuhan: ' + Rupiah.format(expected) + ' (qty × harga satuan). Total bulanan: ' + Rupiah.format(monthly) + '.';
        if (!kumulatif && Math.abs(expected - monthly) > 0.01) {
            msg += ' PERHATIAN: total bulanan belum sama dengan qty × harga satuan.';
            $('#cost-preview').removeClass('text-success').addClass('text-danger fw-bold');
        } else {
            $('#cost-preview').removeClass('text-danger fw-bold').addClass('text-success');
        }
        $('#cost-preview').text(msg);
    }

    function syncCostCenter($select) {
        var owner = $select.find(':selected').data('owner');
        if (owner) {
            $('#cost_center_owner').val(owner);
        }
        var costElementId = $select.find(':selected').data('cost-element');
        if (costElementId) {
            $('select[name="erkap_cost_element_id"]').val(costElementId).trigger('change.select2');
        }
        $('#cost_center_id').val($select.val() || '');
    }

    $(document).ready(function() {
        $(document).on('input', '.month-cost, input[name="qty"], input[name="unit_price"]', updateCostPreview);
        $('#is_kumulatif').on('change', updateCostPreview);
        $(document).on('change', '#cost_center_swakelola', function() {
            if ($(this).val()) {
                $('#cost_center_non_swakelola').val('').trigger('change.select2');
            }
            syncCostCenter($(this));
        });
        $(document).on('change', '#cost_center_non_swakelola', function() {
            if ($(this).val()) {
                $('#cost_center_swakelola').val('').trigger('change.select2');
            }
            syncCostCenter($(this));
        });
        updateCostPreview();
    });
</script>
@endsection
