@extends('layouts.Helpdesk')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Kategori Tiket</h5>
                        <div class="flex-shrink-0 d-flex gap-1">
                            <button class="btn btn-primary" id="btn-add-employee">
                                <i class="mdi mdi-plus me-1"></i> Tambah Kategori Tiket
                            </button>
                            <a href="#!" class="btn btn-light" id="btn-refresh"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle dt-responsive nowrap w-100" id="employee-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px">No</th>
                                <th>ID Employee</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Kode Dept</th>
                                <th>Departemen</th>
                                <th>Lokasi</th>
                                <th>HP</th>
                                <th>Email</th>
                                <th style="width: 80px" class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-employee" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="employee-modal-title">Form Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-import-employee" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Import Data Karyawan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-import-employee" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih File Excel (.xlsx / .xls)</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info text-white">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
@endsection
