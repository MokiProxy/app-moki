@extends('layouts.Dokter')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('dokter.merge-flows.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('dokter.merge-flows.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Alur <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Contoh: BA-INV-SP">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" maxlength="1000">
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold">Step Alur Dokumen</h6>
                    <small class="text-muted d-block mb-3">Definisikan urutan dokumen dalam alur birokrasi. Step pertama adalah dokumen root (induk).</small>

                    <div id="steps-container">
                        @php $steps = old('steps', [['document_type_id' => '', 'link_regex' => '', 'link_label' => '', 'link_field' => '']]); @endphp
                        @foreach($steps as $index => $step)
                        <div class="row g-3 mb-3 step-row" data-index="{{ $index }}">
                            <input type="hidden" name="steps[{{ $index }}][order]" value="{{ $index + 1 }}">
                            <div class="col-md-1">
                                <label class="form-label fw-bold">Urutan</label>
                                <input type="text" class="form-control" value="{{ $index + 1 }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Jenis Dokumen <span class="text-danger">*</span></label>
                                <select name="steps[{{ $index }}][document_type_id]" class="form-select @error('steps.'.$index.'.document_type_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($documentTypes as $dt)
                                        <option value="{{ $dt->id }}" {{ (old("steps.{$index}.document_type_id") == $dt->id) ? 'selected' : '' }}>
                                            {{ $dt->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("steps.{$index}.document_type_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Link Regex</label>
                                <input type="text" name="steps[{{ $index }}][link_regex]" class="form-control @error('steps.'.$index.'.link_regex') is-invalid @enderror" value="{{ old("steps.{$index}.link_regex") }}" placeholder="/No\s*BA\s*\n?\s*:\s*(.+)/i">
                                @error("steps.{$index}.link_regex") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Regex untuk extract nomor induk dari OCR</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Link Label</label>
                                <input type="text" name="steps[{{ $index }}][link_label]" class="form-control @error('steps.'.$index.'.link_label') is-invalid @enderror" value="{{ old("steps.{$index}.link_label") }}" placeholder="No BA">
                                @error("steps.{$index}.link_label") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Link Field (Gemini)</label>
                                <input type="text" name="steps[{{ $index }}][link_field]" class="form-control @error('steps.'.$index.'.link_field') is-invalid @enderror" value="{{ old("steps.{$index}.link_field") }}" placeholder="document_number">
                                @error("steps.{$index}.link_field") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Field dari ocr_data Gemini</small>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm btn-remove-step"><i class="mdi mdi-delete"></i></button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-success btn-sm mb-3" id="btn-add-step">
                        <i class="mdi mdi-plus me-1"></i> Tambah Step
                    </button>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('dokter.merge-flows.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    let stepIndex = {{ count($steps) }};

    $('#btn-add-step').click(function() {
        const html = `
        <div class="row g-3 mb-3 step-row" data-index="${stepIndex}">
            <input type="hidden" name="steps[${stepIndex}][order]" value="${stepIndex + 1}">
            <div class="col-md-1">
                <label class="form-label fw-bold">Urutan</label>
                <input type="text" class="form-control" value="${stepIndex + 1}" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Jenis Dokumen <span class="text-danger">*</span></label>
                <select name="steps[${stepIndex}][document_type_id]" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    @foreach($documentTypes as $dt)
                        <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Link Regex</label>
                <input type="text" name="steps[${stepIndex}][link_regex]" class="form-control" placeholder="/No\\s*BA\\s*\\n?\\s*:\\s*(.+)/i">
                <small class="text-muted">Regex untuk extract nomor induk dari OCR</small>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Link Label</label>
                <input type="text" name="steps[${stepIndex}][link_label]" class="form-control" placeholder="No BA">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Link Field (Gemini)</label>
                <input type="text" name="steps[${stepIndex}][link_field]" class="form-control" placeholder="document_number">
                <small class="text-muted">Field dari ocr_data Gemini</small>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm btn-remove-step"><i class="mdi mdi-delete"></i></button>
            </div>
        </div>`;
        $('#steps-container').append(html);
        stepIndex++;
    });

    $(document).on('click', '.btn-remove-step', function() {
        $(this).closest('.step-row').remove();
        reindexSteps();
    });

    function reindexSteps() {
        $('.step-row').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input[name$="[order]"]').val(index + 1);
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/steps\[\d+\]/, 'steps[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
            $(this).find('.form-label.fw-bold').first().prevAll('.col-md-1').find('input').val(index + 1);
        });
        stepIndex = $('.step-row').length;
    }
</script>
@endsection
