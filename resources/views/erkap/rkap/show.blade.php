@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .badge { font-size: 0.7rem; padding: 0.4em 0.7em; }
    .stepper { display: flex; justify-content: space-between; margin: 0; padding: 0; list-style: none; counter-reset: step; }
    .stepper li { flex: 1; text-align: center; position: relative; counter-increment: step; }
    .stepper li::before { content: counter(step); width: 28px; height: 28px; line-height: 24px; border-radius: 50%; display: inline-block; background: #e9ecef; color: #6c757d; border: 2px solid #dee2e6; font-weight: bold; z-index: 2; position: relative; }
    .stepper li::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: #e9ecef; z-index: 1; }
    .stepper li:last-child::after { display: none; }
    .stepper li .step-label { display: block; margin-top: 6px; font-size: 0.7rem; color: #6c757d; }
    .stepper li.active::before { background: #f1b44c; border-color: #f1b44c; color: #fff; }
    .stepper li.active .step-label { color: #f1b44c; font-weight: bold; }
    .stepper li.done::before { background: #36c783; border-color: #36c783; color: #fff; }
    .stepper li.done::after { background: #36c783; }
    .stepper li.done .step-label { color: #36c783; }
    .stepper li.current::after { background: linear-gradient(90deg, #36c783 50%, #e9ecef 50%); }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 card-title">
                    <i class="mdi mdi-map-marker-path me-1"></i> {{ $pageName }}
                </h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.rkap.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                    @can('erkap.rkap.edit')
                    <a href="{{ route('erkap.rkap.edit', $rkap->id) }}" class="btn btn-warning">
                        <i class="mdi mdi-pencil me-1"></i> Edit
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="mdi mdi-sitemap me-1"></i> Fase Lifecycle RKAP</h6>
                    </div>
                    <div class="card-body">
                        <ul class="stepper">
                            @foreach($phases as $key => $label)
                            <li class="{{ $key === $rkap->phase ? 'active current' : (array_search($key, $phases, true) < $currentIndex ? 'done' : '') }}">
                                <span class="step-label">{{ $label }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-file-document-outline me-1"></i> Detail Periode</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Periode</label>
                                        <p class="mb-0">{{ $rkap->year }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Fase Saat Ini</label>
                                        <p class="mb-0">
                                            <span class="badge bg-primary rounded-pill">{{ $rkap->phaseLabel() }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Status Dokumen</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $rkap->statusClass() }} text-{{ $rkap->statusClass() }}">{{ $rkap->statusLabel() }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Fase Dimulai</label>
                                        <p class="mb-0">{{ $rkap->phase_started_at ? $rkap->phase_started_at->format('d M Y H:i') : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Tanggal Penetapan</label>
                                        <p class="mb-0">{{ $rkap->resolution_date ? $rkap->resolution_date->format('d M Y') : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Distribusi</label>
                                        <p class="mb-0">
                                            <span class="badge bg-soft-{{ $rkap->distributionClass() }} text-{{ $rkap->distributionClass() }}">{{ $rkap->distributionLabel() }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="mdi mdi-calendar-check me-1"></i> Kick-off / Sosialisasi Penyusunan RKAP</h6>
                                @if(auth()->user()->can('erkap.rkap.edit'))
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#collapseKickoff">
                                    <i class="mdi mdi-plus me-1"></i> Isi / Ubah
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Tanggal Kick-off</label>
                                        <p class="mb-0">{{ $rkap->kickoff_date ? $rkap->kickoff_date->format('d M Y') : '-' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Jumlah Peserta</label>
                                        <p class="mb-0">{{ $rkap->kickoffAttendees->count() }} orang</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-muted small">Hadir</label>
                                        <p class="mb-0">{{ $rkap->kickoffAttendees->where('attended', true)->count() }} orang</p>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted small">Catatan</label>
                                        <p class="mb-0">{!! nl2br(e($rkap->kickoff_notes ?: '-')) !!}</p>
                                    </div>
                                </div>

                                @if($rkap->kickoffAttendees->isNotEmpty())
                                <table class="table table-sm table-bordered mt-3 mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Peserta</th>
                                            <th>Divisi</th>
                                            <th class="text-center">Hadir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rkap->kickoffAttendees as $idx => $attendee)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td>{{ $attendee->name }}</td>
                                            <td>{{ $attendee->division->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $attendee->attended ? 'success' : 'secondary' }}">{{ $attendee->attended ? 'Hadir' : 'Tidak Hadir' }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endif

                                @if(auth()->user()->can('erkap.rkap.edit'))
                                <div class="collapse mt-3" id="collapseKickoff">
                                    <form method="POST" action="{{ route('erkap.rkap.kickoff', $rkap->id) }}">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Tanggal Kick-off</label>
                                                <input type="date" name="kickoff_date" class="form-control" value="{{ old('kickoff_date', $rkap->kickoff_date?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="form-label">Catatan Kick-off</label>
                                                <textarea name="kickoff_notes" class="form-control" rows="2">{{ old('kickoff_notes', $rkap->kickoff_notes) }}</textarea>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <label class="form-label fw-bold mb-0">Daftar Peserta</label>
                                            <button type="button" class="btn btn-sm btn-light" id="btn-add-attendee">
                                                <i class="mdi mdi-plus me-1"></i> Tambah Peserta
                                            </button>
                                        </div>
                                        <table class="table table-sm table-bordered mt-2 mb-0" id="attendee-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Peserta</th>
                                                    <th>Divisi</th>
                                                    <th class="text-center">Hadir</th>
                                                    <th style="width: 40px"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($rkap->kickoffAttendees as $attendee)
                                                <tr class="attendee-row">
                                                    <td><input type="text" name="attendees[{{ $loop->index }}][name]" class="form-control form-control-sm attendee-name" value="{{ $attendee->name }}"></td>
                                                    <td>
                                                        <select name="attendees[{{ $loop->index }}][division_id]" class="form-select form-select-sm attendee-division">
                                                            <option value="">— Tanpa Divisi —</option>
                                                            @foreach($divisions as $division)
                                                            <option value="{{ $division->id }}" @selected($attendee->division_id === $division->id)>{{ $division->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="attendees[{{ $loop->index }}][attended]" value="1" class="form-check-input attendee-attended" @checked($attendee->attended)>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-attendee"><i class="mdi mdi-close"></i></button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

                                        <div class="d-flex justify-content-end mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="mdi mdi-content-save me-1"></i> Simpan Jadwal & Peserta
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="mdi mdi-newspaper-variant-outline me-1"></i> Arahan Direksi / Memo Holding</h6>
                                @if(auth()->user()->can('erkap.rkap.edit'))
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#collapseDirection">
                                    <i class="mdi mdi-plus me-1"></i> Unggah / Ubah
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($rkap->direction_file_path)
                                <a href="{{ route('erkap.rkap.direction-download', $rkap->id) }}" class="btn btn-sm btn-outline-primary mb-2">
                                    <i class="mdi mdi-download me-1"></i> Lampiran Arahan
                                </a>
                                @endif
                                <p class="mb-0">{!! nl2br(e($rkap->direction_notes ?: '-')) !!}</p>

                                @if(auth()->user()->can('erkap.rkap.edit'))
                                <div class="collapse mt-3" id="collapseDirection">
                                    <form method="POST" action="{{ route('erkap.rkap.direction', $rkap->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Lampiran Arahan (pdf/doc/xls/ppt/gambar)</label>
                                            <input type="file" name="direction_file" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Catatan Arahan</label>
                                            <textarea name="direction_notes" class="form-control" rows="2">{{ old('direction_notes', $rkap->direction_notes) }}</textarea>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="mdi mdi-content-save me-1"></i> Simpan Arahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-gesture-tap me-1"></i> Kontrol Fase</h6>
                            </div>
                            <div class="card-body">
                                @if($canAdvance)
                                <form method="POST" action="{{ route('erkap.rkap.advance', $rkap->id) }}"
                                    onsubmit="return confirm('Majukan fase RKAP ke \"{{ $rkap->nextPhase() ? $phases[$rkap->nextPhase()] : '' }}\"?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100 mb-2">
                                        <i class="mdi mdi-skip-next me-1"></i> Majukan ke {{ $rkap->nextPhase() ? $phases[$rkap->nextPhase()] : '—' }}
                                    </button>
                                </form>
                                @endif

                                @if($rkap->distribution_status !== 'distributed' && auth()->user()->can('erkap.rkap.edit'))
                                <form method="POST" action="{{ route('erkap.rkap.distribute', $rkap->id) }}"
                                    onsubmit="return confirm('Tandai RKAP ini telah didistribusikan?')">
                                    @csrf
                                    <button type="submit" class="btn btn-{{ $rkap->phase === 'approved' ? 'success' : 'outline-success' }} w-100 mb-2">
                                        <i class="mdi mdi-send-check me-1"></i> Tandai Didistribusikan
                                    </button>
                                </form>
                                @endif

                                @if(in_array($rkap->status, ['draft', 'rejected']) && auth()->user()->can('erkap.rkap.edit'))
                                <form method="POST" action="{{ route('erkap.rkap.reset-phase', $rkap->id) }}"
                                    onsubmit="return confirm('Reset fase lifecycle ke Inisiasi?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100 mb-2">
                                        <i class="mdi mdi-restore me-1"></i> Reset Fase ke Inisiasi
                                    </button>
                                </form>
                                @endif

                                @if($rkap->isLockedForInput())
                                <div class="alert alert-warning mb-0">
                                    <i class="mdi mdi-lock me-1"></i> Data anggaran periode ini terkunci pada fase <strong>{{ $rkap->phaseLabel() }}</strong>.
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-history me-1"></i> Riwayat Persetujuan</h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @forelse($rkap->approvals as $approval)
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ $approval->approver->name ?? '-' }}</strong>
                                            <span class="badge bg-{{ $approval->action === 'approved' ? 'success' : ($approval->action === 'rejected' ? 'danger' : 'secondary') }}">
                                                {{ ucfirst($approval->action ?? $approval->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ $approval->created_at?->format('d M Y H:i') }}</small>
                                        @if($approval->notes)
                                        <div class="small text-muted mt-1">{{ $approval->notes }}</div>
                                        @endif
                                    </li>
                                    @empty
                                    <li class="list-group-item text-muted">Belum ada riwayat persetujuan.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="mdi mdi-text-box-search-outline me-1"></i> Audit Trail</h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @forelse($rkap->audits()->latest()->limit(10)->get() as $audit)
                                    <li class="list-group-item">
                                        <span class="badge bg-secondary me-1">{{ ucfirst($audit->action) }}</span>
                                        <small>{{ $audit->created_at?->format('d M Y H:i') }}</small>
                                        @if($audit->new_values && array_key_exists('phase', $audit->new_values))
                                        <div class="small text-muted mt-1">Fase: {{ $audit->new_values['phase'] }}</div>
                                        @endif
                                    </li>
                                    @empty
                                    <li class="list-group-item text-muted">Belum ada aktivitas audit.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#btn-add-attendee').on('click', function() {
            var idx = $('#attendee-table tbody tr.attendee-row').length;
            var columns = {!! $divisions->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toJson() !!};

            var options = '<option value="">— Tanpa Divisi —</option>';
            columns.forEach(function(d) {
                options += '<option value="' + d.id + '">' + d.name + '</option>';
            });

            $('#attendee-table tbody').append(
                '<tr class="attendee-row">' +
                '<td><input type="text" name="attendees[' + idx + '][name]" class="form-control form-control-sm attendee-name" placeholder="Nama peserta"></td>' +
                '<td><select name="attendees[' + idx + '][division_id]" class="form-select form-select-sm attendee-division">' + options + '</select></td>' +
                '<td class="text-center"><input type="checkbox" name="attendees[' + idx + '][attended]" value="1" class="form-check-input attendee-attended" checked></td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-attendee"><i class="mdi mdi-close"></i></button></td>' +
                '</tr>'
            );
        });

        $(document).on('click', '.btn-remove-attendee', function() {
            $(this).closest('tr.attendee-row').remove();
        });

        $('form[action$="/kickoff"]').on('submit', function() {
            var valid = true;
            $(this).find('.attendee-row').each(function() {
                var name = $(this).find('.attendee-name').val().trim();
                if (!name) {
                    valid = false;
                    $(this).find('.attendee-name').addClass('is-invalid');
                }
            });

            if (!valid) {
                Swal.fire('Periksa Daftar Peserta', 'Nama peserta tidak boleh kosong.', 'warning');
                return false;
            }
        });
    });
</script>
@endsection