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
                                    <option value="{{ $costElement->id }}" data-coa-id="{{ $costElement->chart_of_account_id }}" {{ old('erkap_cost_element_id', $routineCost->erkap_cost_element_id) == $costElement->id ? 'selected' : '' }}>
                                        {{ $costElement->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_cost_element_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chart of Account</label>
                            <select name="chart_of_account_id" class="form-select @error('chart_of_account_id') is-invalid @enderror">
                                <option value="" {{ old('chart_of_account_id', $routineCost->chart_of_account_id) ? '' : 'selected' }}>Ikuti Elemen Biaya</option>
                                @foreach($chartOfAccounts as $chartOfAccount)
                                    <option value="{{ $chartOfAccount->id }}" {{ old('chart_of_account_id', $routineCost->chart_of_account_id) == $chartOfAccount->id ? 'selected' : '' }}>
                                        {{ $chartOfAccount->formattedCode }} - {{ $chartOfAccount->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Kosongkan untuk otomatis mengikuti COA elemen biaya.</div>
                            @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    <div class="mt-4 border rounded p-3 bg-light" id="subtotal-panel">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-chart-pie me-1"></i> Subtotal Reaktif (Termasuk Baris Ini)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <small class="text-muted text-uppercase fw-bold">Per Elemen Biaya</small>
                                <div class="fs-5 fw-bold" id="subtotal-element">-</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted text-uppercase fw-bold">Per Program Kerja</small>
                                <div class="fs-5 fw-bold" id="subtotal-program">-</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted text-uppercase fw-bold">Per Pusat Biaya</small>
                                <div class="fs-5 fw-bold" id="subtotal-cost-center">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 border rounded p-3 bg-light" id="realization-preview">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-chart-line me-1"></i> Budget vs Realisasi (Program Kerja Terpilih)</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <small class="text-muted text-uppercase fw-bold">Total Anggaran</small>
                                <div class="fw-bold" id="preview-budget">-</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted text-uppercase fw-bold">Realisasi YTD</small>
                                <div class="fw-bold text-primary" id="preview-realized">-</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted text-uppercase fw-bold">Variance (Sisa)</small>
                                <div class="fw-bold" id="preview-variance">-</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted text-uppercase fw-bold">% Sisa</small>
                                <div class="fw-bold" id="preview-variance-pct">-</div>
                            </div>
                        </div>
                        <div class="mt-2" id="preview-bar">
                            <div class="progress" style="height: 12px;">
                                <div class="progress-bar bg-success" id="preview-bar-ok" role="progressbar" style="width:0%"></div>
                                <div class="progress-bar bg-danger" id="preview-bar-over" role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
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
    var subtotalByElement = @json($subtotalByElement);
    var subtotalByProgram = @json($subtotalByProgram);
    var subtotalByCostCenter = @json($subtotalByCostCenter);
    var budgetPreview = @json($budgetPreview);
    var ORIGINAL_TOTAL = {{ (float) $routineCost->total }};

    function monthTotal() {
        var total = 0;
        $('.month-cost').each(function() {
            total += Rupiah.parse($(this).val());
        });
        return total;
    }

    function currentTotal() {
        return Rupiah.parse($('#total').val()) || monthTotal();
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
        updateSubtotals();
    }

    function updateSubtotals() {
        var total = currentTotal();
        var programId = $('select[name="erkap_work_program_id"]').val();
        var elementId = $('select[name="erkap_cost_element_id"]').val();
        var costCenterId = $('#cost_center_id').val();

        var baseElement = (subtotalByElement[elementId] || 0) - ORIGINAL_TOTAL;
        var baseProgram = (subtotalByProgram[programId] || 0) - ORIGINAL_TOTAL;
        var baseCostCenter = (subtotalByCostCenter[costCenterId] || subtotalByCostCenter[costCenterId || ''] || 0) - ORIGINAL_TOTAL;

        $('#subtotal-element').text(Rupiah.format(baseElement + total));
        $('#subtotal-program').text(Rupiah.format(baseProgram + total));
        $('#subtotal-cost-center').text(Rupiah.format(baseCostCenter + total));
    }

    function updateRealizationPreview() {
        var programId = $('select[name="erkap_work_program_id"]').val();
        var preview = programId ? (budgetPreview[programId] || null) : null;

        if (!preview) {
            $('#preview-budget').text('-');
            $('#preview-realized').text('-');
            $('#preview-variance').text('-');
            $('#preview-variance-pct').text('-');
            $('#preview-bar-ok').css('width', '0%');
            $('#preview-bar-over').css('width', '0%');
            return;
        }

        $('#preview-budget').text(Rupiah.format(preview.budget));
        $('#preview-realized').text(Rupiah.format(preview.realized));
        $('#preview-variance').text(Rupiah.format(preview.variance));
        $('#preview-variance-pct').text(preview.variance_pct + '%');

        var over = preview.variance < 0;
        $('#preview-budget, #preview-realized, #preview-variance, #preview-variance-pct')
            .removeClass('text-success text-danger text-primary');
        $('#preview-variance, #preview-variance-pct')
            .addClass(over ? 'text-danger' : 'text-success');
        $('#preview-budget').addClass('text-dark');

        if (preview.budget > 0) {
            var pct = Math.min(Math.abs((preview.realized / preview.budget) * 100), 100);
            if (over) {
                $('#preview-bar-ok').css('width', '100%');
                $('#preview-bar-over').css('width', pct + '%');
                $('#preview-bar-over').removeClass('bg-success').addClass('bg-danger');
            } else {
                $('#preview-bar-ok').css('width', pct + '%');
                $('#preview-bar-ok').removeClass('bg-danger').addClass('bg-success');
                $('#preview-bar-over').css('width', '0%');
            }
        } else {
            $('#preview-bar-ok').css('width', '0%');
            $('#preview-bar-over').css('width', '0%');
        }
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
        updateSubtotals();
    }

    function syncCoaFromElement() {
        var coaId = $('select[name="erkap_cost_element_id"] option:selected').data('coa-id');
        if (coaId && ! $('select[name="chart_of_account_id"]').val()) {
            $('select[name="chart_of_account_id"]').val(coaId).trigger('change.select2');
        }
    }

    $(document).ready(function() {
        $(document).on('input', '.month-cost, input[name="qty"], input[name="unit_price"]', updateCostPreview);
        $('#is_kumulatif').on('change', updateCostPreview);
        $(document).on('change', 'select[name="erkap_work_program_id"], select[name="erkap_cost_element_id"]', function() {
            syncCoaFromElement();
            updateSubtotals();
            updateRealizationPreview();
        });
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
        updateRealizationPreview();
    });
</script>
@endsection
