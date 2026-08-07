@extends('layouts.Dokter')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">
                    <i class="mdi mdi-link me-1"></i> {{ $pageName }}
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLinkModal">
                    <i class="mdi mdi-plus me-1"></i> Buat Link Baru
                </button>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Nama Link</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Tahun Akses</th>
                                <th class="text-center" style="width: 100px">Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Terakhir Diakses</th>
                                <th style="width: 250px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($links as $key => $link)
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                <td class="fw-semibold">{{ $link->name }}</td>
                                <td>{{ $link->description ?? '-' }}</td>
                                <td class="text-center">
                                    @foreach($link->allowed_years ?? [] as $year)
                                        <span class="badge bg-info me-1">{{ $year }}</span>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    @if($link->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ $link->created_by ?? '-' }}</td>
                                <td class="text-nowrap">
                                    {{ $link->last_accessed_at?->format('d M Y H:i') ?? 'Belum pernah' }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-info btn-sm btn-copy-link"
                                            data-url="{{ url('/auditor/' . $link->token) }}"
                                            title="Copy Link">
                                        <i class="mdi mdi-content-copy"></i>
                                    </button>
                                    <form action="{{ route('dokter.auditor-access.toggle', $link) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-{{ $link->is_active ? 'warning' : 'success' }} btn-sm"
                                                title="{{ $link->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="mdi mdi-{{ $link->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-primary btn-sm btn-edit-link"
                                            data-id="{{ $link->id }}"
                                            data-name="{{ $link->name }}"
                                            data-description="{{ $link->description ?? '' }}"
                                            data-years="{{ json_encode($link->allowed_years ?? []) }}"
                                            title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <form action="{{ route('dokter.auditor-access.destroy', $link) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus link ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="mdi mdi-link-variant-off text-secondary" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 fw-semibold mb-0">Belum ada link akses auditor.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buat Link Baru -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-labelledby="createLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('dokter.auditor-access.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createLinkModalLabel">
                        <i class="mdi mdi-plus me-1"></i> Buat Link Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Link <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g. Akses Auditor 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Keterangan opsional"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Akses <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @for($year = 2020; $year <= 2030; $year++)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_years[]"
                                       value="{{ $year }}" id="year_{{ $year }}">
                                <label class="form-check-label" for="year_{{ $year }}">{{ $year }}</label>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Link -->
<div class="modal fade" id="editLinkModal" tabindex="-1" aria-labelledby="editLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editLinkForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editLinkModalLabel">
                        <i class="mdi mdi-pencil me-1"></i> Edit Link
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Link <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Akses <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3" id="edit_years_container">
                            @for($year = 2020; $year <= 2030; $year++)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_years[]"
                                       value="{{ $year }}" id="edit_year_{{ $year }}">
                                <label class="form-check-label" for="edit_year_{{ $year }}">{{ $year }}</label>
                            </div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('plugin')
<script>
    $(document).ready(function() {
        // Copy Link
        $(document).on('click', '.btn-copy-link', function() {
            var url = $(this).data('url');
            navigator.clipboard.writeText(url).then(function() {
                showToast('Link berhasil dicopy ke clipboard!', 'success');
            });
        });

        // Edit Link
        $(document).on('click', '.btn-edit-link', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var description = $(this).data('description');
            var years = $(this).data('years');

            $('#edit_name').val(name);
            $('#edit_description').val(description);
            $('#editLinkForm').attr('action', '{{ url("dokter/auditor-access") }}/' + id);

            // Reset all checkboxes
            $('#edit_years_container input[type="checkbox"]').prop('checked', false);

            // Check allowed years
            if (Array.isArray(years)) {
                years.forEach(function(year) {
                    $('#edit_year_' + year).prop('checked', true);
                });
            }

            $('#editLinkModal').modal('show');
        });
    });
</script>
@endsection
