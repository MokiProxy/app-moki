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
                    <a href="{{ route('erkap.routine-costs.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus me-1"></i> Tambah Biaya Rutin
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
                                <th>Kategori</th>
                                <th>Kebutuhan</th>
                                <th>Pusat Biaya</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th>Elemen Biaya</th>
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
                                <th style="width: 120px" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routineCosts as $key => $routineCost)
                            <tr>
                                <td class="text-center">{{ $routineCosts->firstItem() + $key }}</td>
                                <td class="fw-bold">{{ $routineCost->workProgram->name ?? '-' }}</td>
                                <td>{{ $routineCost->cost_category }}</td>
                                <td>{{ $routineCost->need }}</td>
                                <td>{{ $routineCost->cost_center_owner }}</td>
                                <td class="text-center">{{ $routineCost->qty }}</td>
                                <td class="text-end">{{ number_format($routineCost->unit_price, 0, ',', '.') }}</td>
                                <td>{{ $routineCost->costElement->name ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->jan_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->feb_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->mar_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->apr_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->may_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->jun_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->jul_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->aug_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->sep_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->oct_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->nov_cost ?? '-' }}</td>
                                <td class="text-center">{{ $routineCost->des_cost ?? '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($routineCost->total, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('erkap.routine-costs.edit', $routineCost->id) }}" class="btn btn-warning btn-sm btn-edit" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="{{ $routineCost->id }}" data-name="{{ $routineCost->need }}" title="Hapus">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="22" class="text-center text-muted">Belum ada data biaya rutin.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $routineCosts->links('pagination::bootstrap-4') }}
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
                title: 'Hapus Biaya Rutin?',
                text: 'Biaya "' + name + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = $('#form-delete');
                    form.attr('action', "{{ url('erkap/routine-costs') }}/" + id);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
