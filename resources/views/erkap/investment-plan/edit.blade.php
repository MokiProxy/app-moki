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

                <form action="{{ route('erkap.investment-plans.update', $investmentPlan->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Program Kerja <span class="text-danger">*</span></label>
                            <select name="erkap_work_program_id" class="form-select @error('erkap_work_program_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_work_program_id', $investmentPlan->erkap_work_program_id) ? '' : 'selected' }}>Pilih Program Kerja</option>
                                @foreach($workPrograms as $workProgram)
                                    <option value="{{ $workProgram->id }}" {{ old('erkap_work_program_id', $investmentPlan->erkap_work_program_id) == $workProgram->id ? 'selected' : '' }}>
                                        {{ $workProgram->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_work_program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cost_center_id">Pusat Biaya (Cost Center) <span class="text-danger">*</span></label>
                            <select
                                name="cost_center_id"
                                id="cost_center_id"
                                class="form-select @error('cost_center_id') is-invalid @enderror"
                                required
                            >
                                <option value="">— Pilih Pusat Biaya —</option>
                                @if($swakelolaCostCenters->isNotEmpty())
                                    <optgroup label="Swakelola">
                                        @foreach($swakelolaCostCenters as $costCenter)
                                            <option
                                                value="{{ $costCenter->id }}"
                                                data-code="{{ $costCenter->code }}"
                                                data-owner="{{ $costCenter->owner }}"
                                                @selected(old('cost_center_id', $investmentPlan->cost_center_id) == $costCenter->id)
                                            >{{ $costCenter->formattedCode }} - {{ $costCenter->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($nonSwakelolaCostCenters->isNotEmpty())
                                    <optgroup label="Non Swakelola">
                                        @foreach($nonSwakelolaCostCenters as $costCenter)
                                            <option
                                                value="{{ $costCenter->id }}"
                                                data-code="{{ $costCenter->code }}"
                                                data-owner="{{ $costCenter->owner }}"
                                                @selected(old('cost_center_id', $investmentPlan->cost_center_id) == $costCenter->id)
                                            >{{ $costCenter->formattedCode }} - {{ $costCenter->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <div class="form-text">Kode 11 karakter (a-b-c-d), mewarisi atribut dari segmen a..d.</div>
                            @error('cost_center_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="erkap_cost_element_id">Elemen Biaya <span class="text-danger">*</span></label>
                            <select
                                name="erkap_cost_element_id"
                                id="erkap_cost_element_id"
                                class="form-select @error('erkap_cost_element_id') is-invalid @enderror"
                                required
                                disabled
                            >
                                <option value="">— Pilih Pusat Biaya dahulu —</option>
                            </select>
                            <div class="form-text">Hanya elemen yang sudah punya COA pada Pusat Biaya ini.</div>
                            @error('erkap_cost_element_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="resolved_coa">Chart of Account</label>
                            <input
                                type="text"
                                id="resolved_coa"
                                class="form-control"
                                placeholder="Otomatis dari Pusat Biaya + Elemen Biaya"
                                readonly
                            >
                            <div class="form-text">
                                Diturunkan otomatis dari kombinasi di atas — tidak bisa diganti bebas,
                                sehingga kode a..d selalu sesuai Pusat Biaya.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="cost_center_owner">Pemilik Cost Center <span class="text-danger">*</span></label>
                            <input type="text" name="cost_center_owner" id="cost_center_owner" class="form-control @error('cost_center_owner') is-invalid @enderror" value="{{ old('cost_center_owner', $investmentPlan->cost_center_owner) }}" placeholder="Nama pemilik cost center" required>
                            @error('cost_center_owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kategori Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investattion_category_id" class="form-select @error('erkap_investattion_category_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_investattion_category_id', $investmentPlan->erkap_investattion_category_id) ? '' : 'selected' }}>Pilih Kategori Investasi</option>
                                @foreach($investattionCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('erkap_investattion_category_id', $investmentPlan->erkap_investattion_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->code }} - {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investattion_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipe Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investation_type_id" class="form-select @error('erkap_investation_type_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_investation_type_id', $investmentPlan->erkap_investation_type_id) ? '' : 'selected' }}>Pilih Tipe Investasi</option>
                                @foreach($investationTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('erkap_investation_type_id', $investmentPlan->erkap_investation_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->code }} - {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investation_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kriteria Investasi <span class="text-danger">*</span></label>
                            <select name="erkap_investation_criteria_id" class="form-select @error('erkap_investation_criteria_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_investation_criteria_id', $investmentPlan->erkap_investation_criteria_id) ? '' : 'selected' }}>Pilih Kriteria Investasi</option>
                                @foreach($investationCriterias as $criteria)
                                    <option value="{{ $criteria->id }}" {{ old('erkap_investation_criteria_id', $investmentPlan->erkap_investation_criteria_id) == $criteria->id ? 'selected' : '' }}>
                                        {{ $criteria->code }} - {{ $criteria->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_investation_criteria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $investmentPlan->unit) }}" placeholder="Contoh: unit, pcs, set" required>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Nama Investasi <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $investmentPlan->name) }}" placeholder="Nama barang/investasi" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Deskripsi investasi (opsional)">{{ old('description', $investmentPlan->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="qty" id="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty', $investmentPlan->qty) }}" required>
                            @error('qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Harga Satuan <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="unit_price" id="unit_price" class="form-control rupiah-input @error('unit_price') is-invalid @enderror" value="{{ old('unit_price', $investmentPlan->unit_price) }}" placeholder="0,00" required>
                            @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total</label>
                            <input type="text" inputmode="decimal" name="total" id="total" class="form-control bg-light rupiah-input @error('total') is-invalid @enderror" value="{{ old('total', $investmentPlan->total) }}" readonly>
                            @error('total') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div id="investment-preview" class="form-text"></div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Urutan Prioritas</label>
                            <input type="number" min="1" step="1" name="priority_order" id="priority_order" class="form-control @error('priority_order') is-invalid @enderror" value="{{ old('priority_order', $investmentPlan->priority_order) }}" placeholder="1 = tertinggi">
                            @error('priority_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Prioritas per divisi (opsional).</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="text-uppercase fw-bold text-muted mb-0"><i class="mdi mdi-calendar-month me-1"></i> Rencana Bulanan</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_kumulatif" id="is_kumulatif" value="1" {{ old('is_kumulatif', $investmentPlan->is_kumulatif) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kumulatif">Kumulatif</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @foreach($months as $field => $label)
                        <div class="col-md-2">
                            <label class="form-label fw-bold">{{ $label }}</label>
                            <input type="text" inputmode="decimal" name="{{ $field }}" class="form-control month-plan rupiah-input @error($field) is-invalid @enderror" value="{{ old($field, $investmentPlan->{$field}) }}" placeholder="0,00">
                            @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endforeach
                    </div>

                    @php
    $cba = $investmentPlan->cba_json ?? [];
@endphp
                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3">
                            <i class="mdi mdi-file-document-outline me-1"></i> Proposal & Kajian Kelayakan (CBA)
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Proposal (PDF/DOC) <span class="text-muted fw-normal">- wajib untuk submit</span></label>
                                @if($investmentPlan->proposal_file_path)
                                <div class="mb-2">
                                    <a href="{{ route('erkap.investment-plans.proposal-download', $investmentPlan->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-download me-1"></i> {{ $investmentPlan->proposal_original_name ?: 'Proposal saat ini' }}
                                    </a>
                                </div>
                                @endif
                                <input type="file" name="proposal" class="form-control @error('proposal') is-invalid @enderror" accept=".pdf,.doc,.docx">
                                @error('proposal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Maks. 20MB. Kosongkan jika tidak mengganti file.</small>
                            </div>
                            <!-- <div class="col-md-6">
                                <label class="form-label fw-bold">Lampiran CBA (opsional)</label>
                                @if($investmentPlan->cba_attachment_path)
                                <div class="mb-2">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($investmentPlan->cba_attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="mdi mdi-file-document-outline me-1"></i> Lampiran saat ini
                                    </a>
                                </div>
                                @endif
                                <input type="file" name="cba_attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                <small class="text-muted">Kosongkan jika tidak mengganti file.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">NPV</label>
                                <input type="number" step="0.01" name="cba_npv" class="form-control" value="{{ old('cba_npv', $cba['npv'] ?? '') }}" placeholder="0,00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">IRR (%)</label>
                                <input type="number" step="0.01" name="cba_irr" class="form-control" value="{{ old('cba_irr', $cba['irr'] ?? '') }}" placeholder="0,00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Payback Period (tahun)</label>
                                <input type="number" step="0.01" name="cba_payback" class="form-control" value="{{ old('cba_payback', $cba['payback'] ?? '') }}" placeholder="0,00">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Justifikasi / Rekomendasi</label>
                                <textarea name="cba_justification" class="form-control" rows="2" placeholder="Justifikasi kelayakan investasi (opsional)">{{ old('cba_justification', $cba['justification'] ?? '') }}</textarea>
                            </div> -->
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
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
    window.ERKAP_COA_OPTIONS_URL = @json(route('erkap.coa-options.index'));
</script>
<script src="{{ asset('js/erkap-cascade.js') }}"></script>
<script>
    $(document).ready(function () {
        ErkapCascade.init({
            chain: ['cost_center', 'cost_element'],
            selectors: {
                cost_center: '#cost_center_id',
                cost_element: '#erkap_cost_element_id',
            },
            dependsOn: {
                cost_center: [],
                cost_element: ['cost_center'],
            },
            preview: '#__none__',
            initialValues: {
                cost_element: @json(old('erkap_cost_element_id', $investmentPlan->erkap_cost_element_id)),
            },
            onReady: function (selected) {
                showResolvedAccount(selected);
            },
            onChange: function (selected, key) {
                showResolvedAccount(selected);
                if (key === 'cost_center' || key === 'prefill') {
                    fillOwner($('#cost_center_id'));
                }
            },
        });
    });

    function showResolvedAccount(selected) {
        var code = ErkapCascade.composeCode(selected);
        var $field = $('#resolved_coa');
        if (!code) {
            $field.val('').attr('placeholder', 'Otomatis dari Pusat Biaya + Elemen Biaya');
            return;
        }
        var name = selected.cost_element ? selected.cost_element.name : '';
        $field.val(code + (name ? ' — ' + name : ''))
            .attr('placeholder', 'Otomatis dari Pusat Biaya + Elemen Biaya');
    }

    function fillOwner($select) {
        var owner = $select.find(':selected').data('owner');
        if (owner) {
            $('#cost_center_owner').val(owner);
        }
    }
</script>
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
