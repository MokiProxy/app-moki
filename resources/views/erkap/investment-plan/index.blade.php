@extends('layouts.Erkap')

@section('title', $pageName)

@section('css')
<style>
    .text-dark { color: #000000 !important; }
    table.table th, table.table td { white-space: nowrap; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title text-dark fw-bold">{{ $pageName }}</h5>
                <div class="d-flex gap-1">
                    @if(isset($submittableCount) && $submittableCount > 0 && auth()->user()->can('erkap.investment-plans.submit'))
                    <form method="POST" action="{{ route('erkap.investment-plans.submit-batch') }}" class="d-inline" onsubmit="return confirm('Ajukan {{ $submittableCount }} rencana investasi untuk persetujuan sekaligus?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-send-multiple me-1"></i> Ajukan Semua Persetujuan
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('erkap.investment-plans.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Investasi
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
                                <th>Program Kerja</th>
                                <th>Nama Investasi</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th>Kriteria</th>
                                <th class="text-center">Qty</th>
                                <th>Satuan</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Total</th>
                                <th style="width: 180px" class="text-center">Status</th>
                                <th style="width: 170px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($investmentPlans as $key => $investmentPlan)
                            <tr>
                                <td class="text-center">{{ $investmentPlans->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $investmentPlan->workProgram->name ?? '-' }}</td>
                                <td>{{ $investmentPlan->name }}</td>
                                <td>{{ $investmentPlan->investattionCategory->name ?? '-' }}</td>
                                <td>{{ $investmentPlan->investationType->name ?? '-' }}</td>
                                <td>{{ $investmentPlan->investationCriteria->name ?? '-' }}</td>
                                <td class="text-center">{{ $investmentPlan->qty }}</td>
                                <td>{{ $investmentPlan->unit }}</td>
                                <td class="text-end">{{ number_format($investmentPlan->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">{{ number_format($investmentPlan->total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $investmentPlan->statusClass() }}">{{ $investmentPlan->statusLabel() }}</span>
                                </td>
                                <td class="text-center">
                                    @if($investmentPlan->canBeSubmitted() && auth()->user()->can('erkap.investment-plans.submit'))
                                    <form method="POST" action="{{ route('erkap.investment-plans.submit', $investmentPlan->id) }}" class="d-inline" onsubmit="return confirm('Ajukan rencana investasi ini untuk persetujuan?')">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm" title="Ajukan Persetujuan">
                                            <i class="mdi mdi-send"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('erkap.investment-plans.edit', $investmentPlan->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $investmentPlan->id }}" data-name="{{ $investmentPlan->name }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">Belum ada data rencana investasi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $investmentPlans->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Rencana Investasi?',
                text: 'Investasi "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/investment-plans') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection