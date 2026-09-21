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
                    <a href="{{ route('erkap.investment-plans.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.investment-plans.store') }}" method="POST">
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
                            <label class="form-label fw-bold">Kategori Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investattion_category_id" class="form-select @error('erkap_investattion_category_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Kategori Investasi</option>
                                @foreach($investattionCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('erkap_investattion_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->code }} - {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investattion_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipe Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investation_type_id" class="form-select @error('erkap_investation_type_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Tipe Investasi</option>
                                @foreach($investationTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('erkap_investation_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->code }} - {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investation_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kriteria Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investation_criteria_id" class="form-select @error('erkap_investation_criteria_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Pilih Kriteria Investasi</option>
                                @foreach($investationCriterias as $criteria)
                                    <option value="{{ $criteria->id }}" {{ old('erkap_investation_criteria_id') == $criteria->id ? 'selected' : '' }}>
                                        {{ $criteria->code }} - {{ $criteria->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investation_criteria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit') }}" placeholder="Contoh: unit, pcs, set" required>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Nama Investasi <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nama barang/investasi" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Deskripsi investasi (opsional)">{{ old('description') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="qty" id="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty') }}" required>
                            @error('qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Harga Satuan <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="unit_price" id="unit_price" class="form-control rupiah-input @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}" placeholder="0,00" required>
                            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total</label>
                            <input type="text" inputmode="decimal" name="total" id="total" class="form-control bg-light rupiah-input @error('total') is-invalid @enderror" value="{{ old('total') }}" readonly>
                            @error('total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div id="investment-preview" class="form-text"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="text-uppercase fw-bold text-muted mb-0"><i class="mdi mdi-calendar-month me-1"></i> Rencana Bulanan</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_kumulatif" id="is_kumulatif" value="1" {{ old('is_kumulatif') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kumulatif">Kumulatif</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="text" inputmode="decimal" name="{{ $field }}" class="form-control month-plan rupiah-input @error($field) is-invalid @enderror" value="{{ old($field) }}" placeholder="0,00">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('erkap.investment-plans.index') }}" class="btn btn-secondary">Batal</a>
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

    function calculateTotal() {
        var qty = parseFloat($('#qty').val()) || 0;
        var price = Rupiah.parse($('#unit_price').val());
        var expected = qty * price;
        var monthly = monthTotal();
        var kumulatif = $('#is_kumulatif').is(':checked');
        $('#total').val(Rupiah.format(expected));

        var msg = 'Total investasi: ' + Rupiah.format(expected) + ' (qty × harga satuan). Total rencana pembayaran bulanan: ' + Rupiah.format(monthly) + '.';
        if (!kumulatif && Math.abs(expected - monthly) > 0.01) {
            msg += ' PERHATIAN: total pembayaran bulanan belum sama dengan qty × harga satuan.';
            $('#investment-preview').removeClass('text-success').addClass('text-danger fw-bold');
        } else {
            $('#investment-preview').removeClass('text-danger fw-bold').addClass('text-success');
        }
        $('#investment-preview').text(msg);
    }

    $(document).ready(function() {
        $(document).on('input', '.month-plan, #qty, #unit_price', calculateTotal);
        $('#is_kumulatif').on('change', calculateTotal);
        calculateTotal();
    });
</script>
@endsection