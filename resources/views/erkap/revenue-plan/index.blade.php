@extends('layouts.Erkap')

@section('title', $pageName)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('erkap.revenue-plans.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Rencana Pendapatan
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
                                <th>Tahun RKAP</th>
                                <th>Divisi</th>
                                <th>Akun Pendapatan</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Jan</th>
                                <th class="text-center">Feb</th>
                                <th class="text-center">Mar</th>
                                <th class="text-center">Apr</th>
                                <th class="text-center">Mei</th>
                                <th class="text-center">Jun</th>
                                <th class="text-center">Jul</th>
                                <th class="text-center">Agu</th>
                                <th class="text-center">Sep</th>
                                <th class="text-center">Okt</th>
                                <th class="text-center">Nov</th>
                                <th class="text-center">Des</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenuePlans as $key => $revenuePlan)
                            <tr>
                                <td class="text-center">{{ $revenuePlans->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $revenuePlan->rkap->year ?? '-' }}</td>
                                <td>{{ $revenuePlan->division->name ?? '-' }}</td>
                                <td>
                                    {{ $revenuePlan->chartOfAccount->code ?? '-' }} - {{ $revenuePlan->chartOfAccount->name ?? '-' }}
                                </td>
                                <td>{{ $revenuePlan->description ?? '-' }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->jan_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->feb_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->mar_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->apr_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->may_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->jun_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->jul_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->aug_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->sep_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->oct_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->nov_plan, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($revenuePlan->dec_plan, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($revenuePlan->total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $badge = [
                                            'draft' => 'secondary',
                                            'submitted' => 'primary',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                        $statusLabel = ucfirst($revenuePlan->status);
                                    @endphp
                                    <span class="badge bg-{{ $badge[$revenuePlan->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.revenue-plans.edit', $revenuePlan->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $revenuePlan->id }}" data-name="Rencana pendapatan ({{ optional($revenuePlan->chartOfAccount)->name }})" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="20" class="text-center text-muted">Belum ada data rencana pendapatan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $revenuePlans->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Rencana Pendapatan?',
                text: name + ' akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/revenue-plans') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection