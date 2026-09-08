@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.risk-score-levels.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Risk Score Level
                    </a>
                    <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
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

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px">No</th>
                                <th>Risk Probability</th>
                                <th>Risk Impact</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Level</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riskScoreLevels as $key => $riskScoreLevel)
                            <tr>
                                <td class="text-center">{{ $riskScoreLevels->firstItem() + $key }}</td>
                                <td class="fw-bold">
                                    {{ $riskScoreLevel->riskProbability->name ?? '-' }}
                                    <span class="badge bg-primary text-white">{{ $riskScoreLevel->riskProbability->point ?? '-' }}</span>
                                </td>
                                <td>
                                    {{ $riskScoreLevel->riskImpact->name ?? '-' }}
                                    <span class="badge bg-info text-white">{{ $riskScoreLevel->riskImpact->point ?? '-' }}</span>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary text-white">{{ $riskScoreLevel->score }}</span></td>
                                <td class="text-center">
                                    @php
                                        $levelBadge = [
                                            'Low' => 'bg-success',
                                            'Low To Moderate' => 'bg-info',
                                            'Moderate' => 'bg-warning text-dark',
                                            'Moderate To High' => 'bg-orange text-white',
                                            'High' => 'bg-danger',
                                        ];
                                    @endphp
                                    <span class="badge {{ $levelBadge[$riskScoreLevel->level] ?? 'bg-secondary' }}">{{ $riskScoreLevel->level }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.risk-score-levels.edit', $riskScoreLevel->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $riskScoreLevel->id }}" data-name="{{ $riskScoreLevel->level }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data risk score level.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $riskScoreLevels->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

<form id="form-delete" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('plugin')
<script src="{{ asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#btn-refresh').click(function() { location.reload(); });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            Swal.fire({
                title: 'Hapus Risk Score Level?',
                text: 'Risk score level level "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/risk-score-levels') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection