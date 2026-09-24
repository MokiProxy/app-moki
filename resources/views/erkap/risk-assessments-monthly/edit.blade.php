@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">{{ $pageName }}</h5>
                    <a href="{{ route('erkap.risk-assessments-monthly.index') }}" class="btn btn-secondary">
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

                <form action="{{ route('erkap.risk-assessments-monthly.update', $riskAssessmentMonthly->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Identifikasi Risiko <span class="text-danger">*</span></label>
                            <select name="erkap_risk_identification_id" class="form-select @error('erkap_risk_identification_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('erkap_risk_identification_id', $riskAssessmentMonthly->erkap_risk_identification_id) ? '' : 'selected' }}>Pilih Risiko</option>
                                @foreach($riskIdentifications as $risk)
                                    @php
                                        $division = optional($risk->departmentTarget?->division);
                                        $rkapYear = optional($risk->departmentTarget?->companyTarget?->rkap)->year;
                                    @endphp
                                    <option value="{{ $risk->id }}" {{ old('erkap_risk_identification_id', $riskAssessmentMonthly->erkap_risk_identification_id) == $risk->id ? 'selected' : '' }}>
                                        [#{{ $risk->id }}] RKAP {{ $rkapYear ?? '-' }} - {{ $division->name ?? '-' }} - {{ Str::limit($risk->risk, 80) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('erkap_risk_identification_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Bulan <span class="text-danger">*</span></label>
                            <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                                <option value="" disabled {{ old('month', $riskAssessmentMonthly->month) ? '' : 'selected' }}>Pilih Bulan</option>
                                @foreach($monthLabels as $key => $label)
                                    <option value="{{ $key + 1 }}" {{ old('month', $riskAssessmentMonthly->month) == $key + 1 ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                            <input type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', $riskAssessmentMonthly->year) }}" required>
                            @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-uppercase fw-bold text-muted mb-3"><i class="mdi mdi-chart-bubble me-1"></i> Skor Risiko (Skala 1-10)</h6>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Inherent Probability <span class="text-danger">*</span></label>
                            <input type="number" name="inherent_probability" min="1" max="10" class="form-control @error('inherent_probability') is-invalid @enderror" value="{{ old('inherent_probability', $riskAssessmentMonthly->inherent_probability) }}" required>
                            @error('inherent_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Inherent Impact <span class="text-danger">*</span></label>
                            <input type="number" name="inherent_impact" min="1" max="10" class="form-control @error('inherent_impact') is-invalid @enderror" value="{{ old('inherent_impact', $riskAssessmentMonthly->inherent_impact) }}" required>
                            @error('inherent_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Current Probability</label>
                            <input type="number" name="current_probability" min="1" max="10" class="form-control @error('current_probability') is-invalid @enderror" value="{{ old('current_probability', $riskAssessmentMonthly->current_probability) }}">
                            @error('current_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Current Impact</label>
                            <input type="number" name="current_impact" min="1" max="10" class="form-control @error('current_impact') is-invalid @enderror" value="{{ old('current_impact', $riskAssessmentMonthly->current_impact) }}">
                            @error('current_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Residual Probability</label>
                            <input type="number" name="residual_probability" min="1" max="10" class="form-control @error('residual_probability') is-invalid @enderror" value="{{ old('residual_probability', $riskAssessmentMonthly->residual_probability) }}">
                            @error('residual_probability') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Residual Impact</label>
                            <input type="number" name="residual_impact" min="1" max="10" class="form-control @error('residual_impact') is-invalid @enderror" value="{{ old('residual_impact', $riskAssessmentMonthly->residual_impact) }}">
                            @error('residual_impact') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status Mitigasi</label>
                            <select name="mitigation_status" class="form-select">
                                <option value="on_progress" {{ old('mitigation_status', $riskAssessmentMonthly->mitigation_status) == 'on_progress' ? 'selected' : '' }}>On Progress</option>
                                <option value="done" {{ old('mitigation_status', $riskAssessmentMonthly->mitigation_status) == 'done' ? 'selected' : '' }}>Done</option>
                                <option value="overdue" {{ old('mitigation_status', $riskAssessmentMonthly->mitigation_status) == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Risk Owner</label>
                            <input type="text" name="risk_owner" class="form-control @error('risk_owner') is-invalid @enderror" value="{{ old('risk_owner', $riskAssessmentMonthly->risk_owner) }}" placeholder="Nama/Divisi pemilik risiko">
                            @error('risk_owner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Risk Appetite</label>
                            <select name="risk_appetite_id" class="form-select @error('risk_appetite_id') is-invalid @enderror">
                                <option value="">-</option>
                                @foreach($riskAppetites as $appetite)
                                    <option value="{{ $appetite->id }}" {{ old('risk_appetite_id', $riskAssessmentMonthly->risk_appetite_id) == $appetite->id ? 'selected' : '' }}>
                                        {{ $appetite->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('risk_appetite_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">Target Tanggal Selesai</label>
                            <input type="date" name="target_date" class="form-control @error('target_date') is-invalid @enderror" value="{{ old('target_date', optional($riskAssessmentMonthly->target_date)->format('Y-m-d')) }}">
                            @error('target_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Rencana Mitigasi</label>
                            <textarea name="mitigation_plan" class="form-control @error('mitigation_plan') is-invalid @enderror" rows="3" placeholder="Keterangan rencana mitigasi">{{ old('mitigation_plan', $riskAssessmentMonthly->mitigation_plan) }}</textarea>
                            @error('mitigation_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-uppercase fw-bold text-muted mb-0"><i class="mdi mdi-cog me-1"></i> Business Process</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-process"><i class="mdi mdi-plus me-1"></i> Tambah Proses</button>
                        </div>
                        <small class="text-muted d-block mb-2">Pemetaan proses bisnis yang terdampak risiko ini.</small>
                        <div id="business-processes">
                            @php
                                $existingProcesses = $riskAssessmentMonthly->businessProcesses;
                                $processes = old('business_processes', $existingProcesses->map(fn ($p) => [
                                    'process_name' => $p->process_name,
                                    'description' => $p->description,
                                    'owner' => $p->owner,
                                    'risk_level' => $p->risk_level,
                                ])->all());
                            @endphp
                            @forelse($processes as $idx => $proc)
                            <div class="border rounded p-2 mb-2 bg-light process-row">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" name="business_processes[{{ $idx }}][process_name]" class="form-control form-control-sm" placeholder="Nama proses" value="{{ $proc['process_name'] ?? '' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="business_processes[{{ $idx }}][description]" class="form-control form-control-sm" placeholder="Deskripsi" value="{{ $proc['description'] ?? '' }}">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="business_processes[{{ $idx }}][owner]" class="form-control form-control-sm" placeholder="Owner" value="{{ $proc['owner'] ?? '' }}">
                                    </div>
                                    <div class="col-md-1">
                                        <select name="business_processes[{{ $idx }}][risk_level]" class="form-select form-select-sm">
                                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                                <option value="{{ $val }}" {{ ($proc['risk_level'] ?? 'medium') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-process"><i class="mdi mdi-close"></i></button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="process-empty text-muted small">Belum ada business process yang ditambahkan.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-content-save me-1"></i> Update
                        </button>
                        <a href="{{ route('erkap.risk-assessments-monthly.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        var processIndex = {{ (int) count($processes) }};

        function addProcess() {
            var html =
                '<div class="border rounded p-2 mb-2 bg-light process-row">' +
                '    <div class="row g-2">' +
                '        <div class="col-md-4">' +
                '            <input type="text" name="business_processes[' + processIndex + '][process_name]" class="form-control form-control-sm" placeholder="Nama proses" required>' +
                '        </div>' +
                '        <div class="col-md-4">' +
                '            <input type="text" name="business_processes[' + processIndex + '][description]" class="form-control form-control-sm" placeholder="Deskripsi">' +
                '        </div>' +
                '        <div class="col-md-2">' +
                '            <input type="text" name="business_processes[' + processIndex + '][owner]" class="form-control form-control-sm" placeholder="Owner">' +
                '        </div>' +
                '        <div class="col-md-1">' +
                '            <select name="business_processes[' + processIndex + '][risk_level]" class="form-select form-select-sm">' +
                '                <option value="low">Low</option>' +
                '                <option value="medium" selected>Medium</option>' +
                '                <option value="high">High</option>' +
                '                <option value="critical">Critical</option>' +
                '            </select>' +
                '        </div>' +
                '        <div class="col-md-1 d-flex align-items-center">' +
                '            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-process"><i class="mdi mdi-close"></i></button>' +
                '        </div>' +
                '    </div>' +
                '</div>';
            $('#business-processes').append(html);
            $('#business-processes .process-empty').remove();
            processIndex++;
        }

        $('#btn-add-process').on('click', addProcess);

        $(document).on('click', '.btn-remove-process', function() {
            $(this).closest('.process-row').remove();
            if ($('#business-processes .process-row').length === 0 && !$('#business-processes .process-empty').length) {
                $('#business-processes').append('<div class="process-empty text-muted small">Belum ada business process yang ditambahkan.</div>');
            }
        });
    });
</script>
@endsection