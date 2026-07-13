@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Daftar Asset</h5>
                        <div class="flex-shrink-0 d-flex gap-1">
                            <button class="btn btn-primary" id="btn-add-modal">
                                <i class="mdi mdi-plus"></i> Tambah Asset
                            </button>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-import-asset">
                                <i class="mdi mdi-file-excel"></i> Import Excel
                            </button>
                            <a href="#!" class="btn btn-light" onclick="drawTable()"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="asset-table">
                        <thead>
                            <tr>
                                <th style="width: 10px">No</th>
                                <th>Barcode</th>
                                <th>Brand</th>
                                <th>Serial Number</th>
                                <th>Cost Center (COA)</th> {{-- FIELD BARU --}}
                                <th>Kategori</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th>Tgl Input</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL FORM ADD/EDIT --}}
    <div class="modal fade" id="modal-asset" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Tambah Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-asset">
                        @csrf
                        <input type="hidden" name="id" id="asset_id">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Kategori</label>
                                <select name="category_id" id="category_id" class="form-control select2-modal">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-control select2-modal">
                                    <option value="">Pilih Supplier</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Lokasi (Regional)</label>
                                <select name="regional_id" id="regional_id" class="form-control select2-modal">
                                    <option value="">Pilih Lokasi</option>
                                    @foreach($regionals as $reg)
                                        <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Brand / Merk</label>
                                <input type="text" name="brand" id="brand" class="form-control" placeholder="Contoh: Dell, HP, Lenovo">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Serial Number</label>
                                <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Masukkan S/N">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Spesifikasi</label>
                                <textarea name="specification" id="specification" class="form-control" rows="2" placeholder="Contoh: Core i5, RAM 16GB, SSD 512GB"></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Cost Center (COA)</label> {{-- FIELD INPUT BARU --}}
                                <input type="text" name="cost_center" id="cost_center" class="form-control" placeholder="Contoh: 101-2022">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Tahun Produksi</label>
                                <input type="number" class="form-control" name="production_year" id="production_year">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Harga Beli</label>
                                <input type="number" class="form-control" name="purchase_price" id="purchase_price">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Tanggal Beli</label>
                                <input type="date" class="form-control" name="purchase_date" id="purchase_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Kondisi</label>
                                <select name="condition" id="condition" class="form-control">
                                    <option value="1">Baru</option>
                                    <option value="2">Seken</option>
                                    <option value="3">Rusak</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="form-asset" id="btn-submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL VIEW DETAIL --}}
    <div class="modal fade" id="modal-view-asset" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-striped mb-0">
                        <tr><td width="40%" class="ps-3">Barcode</td><td>:</td><td id="view_barcode"></td></tr>
                        <tr><td class="ps-3">UID</td><td>:</td><td id="view_uid"></td></tr>
                        <tr><td class="ps-3">Brand</td><td>:</td><td id="view_brand"></td></tr>
                        <tr><td class="ps-3">Serial Number</td><td>:</td><td id="view_sn"></td></tr>
                        <tr><td class="ps-3">Cost Center (COA)</td><td>:</td><td id="view_cost_center"></td></tr> {{-- VIEW BARU --}}
                        <tr><td class="ps-3">Kategori</td><td>:</td><td id="view_category"></td></tr>
                        <tr><td class="ps-3">Lokasi</td><td>:</td><td id="view_regional"></td></tr>
                        <tr><td class="ps-3">Supplier</td><td>:</td><td id="view_supplier"></td></tr>
                        <tr><td class="ps-3">Spesifikasi</td><td>:</td><td id="view_specification"></td></tr>
                        <tr><td class="ps-3">Tgl Beli</td><td>:</td><td id="view_purchase_date"></td></tr>
                        <tr><td class="ps-3">Harga</td><td>:</td><td id="view_purchase_price"></td></tr>
                        <tr><td class="ps-3">Kondisi</td><td>:</td><td id="view_condition"></td></tr>
                        <tr><td class="ps-3">Status</td><td>:</td><td id="view_status"></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL IMPORT EXCEL --}}
    <div class="modal fade" id="modal-import-asset" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form-import-asset" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <p class="mb-2">Gunakan file Excel (.xlsx) dengan format yang sesuai.</p>
                            <a href="{{ route('asset.template') }}" class="btn btn-sm btn-info text-white">
                                <i class="mdi mdi-download"></i> Download Template
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih File</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btn-import-submit" class="btn btn-success">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
@endsection

@section('plugin')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var table;
        $(document).ready(function() {
            $('.select2-modal').select2({ 
                dropdownParent: $('#modal-asset'), 
                width: '100%' 
            });

            table = $("#asset-table").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('asset.datatable') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: '_barcode', name: 'uid' },
                    { data: 'brand', name: 'brand', defaultContent: '-' },
                    { data: 'serial_number', name: 'serial_number', defaultContent: '-' },
                    { data: 'cost_center', name: 'cost_center', defaultContent: '-' }, // SINKRONISASI FIELD COA
                    { data: 'category_name', name: 'category.name', defaultContent: '-' },
                    { data: 'regional_name', name: 'regional.name', defaultContent: '-' },
                    { 
                        data: 'condition', 
                        name: 'condition',
                        render: function(v) { 
                            if(v == 1) return 'BARU';
                            if(v == 2) return 'SEKEN';
                            if(v == 3) return 'RUSAK';
                            return '-';
                        }
                    },
                    { data: '_status', name: 'status', defaultContent: '-' },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        render: function(v) { return v ? moment(v).format('DD MMM YYYY') : '-'; } 
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            // TOMBOL TAMBAH
            $('#btn-add-modal').click(function() {
                $('#modal-title').text('Tambah Asset');
                $('#form-asset')[0].reset();
                $('#asset_id').val('');
                $('.select2-modal').val(null).trigger('change');
                $('#modal-asset').modal('show');
            });

            // TOMBOL VIEW
            $('#asset-table').on('click', '.btn-view', function() {
                let id = $(this).data('id');
                $.get("{{ url('asset/edit') }}/" + id, function(res) {
                    if (res.success) {
                        let data = res; // Controller Anda mengirim data langsung di root JSON
                        $('#view_barcode').html(data.uid ? data.uid : '-'); 
                        $('#view_uid').text(data.uid || '-');
                        $('#view_brand').text(data.brand || '-');
                        $('#view_sn').text(data.serial_number || '-');
                        $('#view_cost_center').text(data.cost_center || '-'); // TAMPILKAN COA
                        $('#view_category').text(data.category_name || '-');
                        $('#view_regional').text(data.regional_name || '-');
                        $('#view_supplier').text(data.supplier_name || '-');
                        $('#view_specification').text(data.specification || '-');
                        $('#view_purchase_date').text(data.purchase_date || '-');
                        
                        let price = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(data.purchase_price || 0);
                        $('#view_purchase_price').text(price);

                        let cond = 'BARU';
                        if(data.condition == 2) cond = 'SEKEN';
                        if(data.condition == 3) cond = 'RUSAK';
                        $('#view_condition').text(cond);
                        
                        let statusBadge = '<span class="badge bg-success">Standby</span>';
                        if(data.status == 1) statusBadge = '<span class="badge bg-primary">Assigned</span>';
                        if(data.status == 2) statusBadge = '<span class="badge bg-danger">Broken</span>';
                        $('#view_status').html(statusBadge);

                        $('#modal-view-asset').modal('show');
                    }
                });
            });

            // TOMBOL EDIT
            $('#asset-table').on('click', '.btn-edit', function() {
                let id = $(this).data('id');
                $.get("{{ url('asset/edit') }}/" + id, function(res) {
                    if (res.success) {
                        let data = res;
                        $('#modal-title').text('Edit Asset');
                        $('#asset_id').val(data.id);
                        $('#category_id').val(data.category_id).trigger('change');
                        $('#supplier_id').val(data.supplier_id).trigger('change');
                        $('#regional_id').val(data.regional_id).trigger('change');
                        $('#brand').val(data.brand);
                        $('#serial_number').val(data.serial_number);
                        $('#cost_center').val(data.cost_center); // ISI FIELD COA SAAT EDIT
                        $('#specification').val(data.specification);
                        $('#production_year').val(data.production_year);
                        $('#purchase_price').val(data.purchase_price);
                        $('#purchase_date').val(data.purchase_date);
                        $('#condition').val(data.condition);
                        $('#modal-asset').modal('show');
                    }
                });
            });

            // TOMBOL DELETE
            $('#asset-table').on('click', '.btn-delete', function() {
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Hapus Asset?',
                    text: "Data ini tidak bisa dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('asset/delete') }}/" + id,
                            type: "DELETE",
                            data: { _token: "{{ csrf_token() }}" },
                            success: function(res) {
                                if (res.success) {
                                    table.ajax.reload();
                                    Swal.fire('Terhapus!', res.message, 'success');
                                }
                            }
                        });
                    }
                });
            });

            // FORM SUBMIT (ADD/EDIT)
            $('#form-asset').submit(function(e) {
                e.preventDefault();
                $('#btn-submit').prop('disabled', true).text('Menyimpan...');
                $.ajax({
                    type: "POST",
                    url: "{{ route('asset.store') }}",
                    data: $(this).serialize(),
                    success: function(res) {
                        $('#btn-submit').prop('disabled', false).text('Simpan');
                        if (res.success) {
                            $('#modal-asset').modal('hide');
                            table.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        }
                    },
                    error: function(err) {
                        $('#btn-submit').prop('disabled', false).text('Simpan');
                        let msg = err.responseJSON ? err.responseJSON.message : 'Gagal menyimpan data.';
                        Swal.fire('Error!', msg, 'error');
                    }
                });
            });

            // FORM IMPORT SUBMIT
            $('#form-import-asset').submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                $('#btn-import-submit').prop('disabled', true).text('Sedang Import...');
                
                $.ajax({
                    type: "POST",
                    url: "{{ route('asset.import') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#btn-import-submit').prop('disabled', false).text('Mulai Import');
                        if (res.success) {
                            $('#modal-import-asset').modal('hide');
                            $('#form-import-asset')[0].reset();
                            table.ajax.reload();
                            Swal.fire('Berhasil!', res.message, 'success');
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    },
                    error: function(err) {
                        $('#btn-import-submit').prop('disabled', false).text('Mulai Import');
                        Swal.fire('Error!', 'Gagal memproses file.', 'error');
                    }
                });
            });
        });

        function drawTable() { table.ajax.reload(); }
    </script>
@endsection