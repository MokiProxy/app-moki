@extends('layouts.App')

@section('content')
<style>
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        top: 1px !important;
    }
    .select2-container { width: 100% !important; display: block; }
    .select2-selection__rendered { line-height: 38px !important; padding-left: 12px !important; }
    .bg-soft-warning { background-color: #fff3cd !important; border-color: #ffeeba !important; }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body border-bottom bg-light">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 card-title flex-grow-1">Tambah Transaksi (BAST)</h5>
                    <div class="flex-shrink-0 d-flex gap-1">
                        <button type="submit" class="btn btn-primary" form="form-add-transaction">
                            <i class="mdi mdi-content-save-outline me-1"></i> Simpan Transaksi
                        </button>
                        <a href="{{ route('transaction') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('transaction.store') }}" id="form-add-transaction" class="needs-validation" novalidate>
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small text-danger">Tipe Transaksi</label>
                            <select name="status" id="transaction_status" class="form-select fw-bold" required>
                                <option value="OUT">ASSET OUT (Pinjam)</option>
                                <option value="IN" selected>ASSET IN (Kembali)</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small">NIK Karyawan</label>
                            <select name="employee_id" id="employee_id_select" class="form-control" required>
                                <option value="">Cari NIK...</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" 
                                            data-nama="{{ $emp->name }}" 
                                            data-jabatan="{{ $emp->jabatan ?? '-' }}"
                                            data-dept="{{ $emp->division->name ?? '-' }}"
                                            data-division="{{ $emp->division_id }}"> 
                                        {{ $emp->employee_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small">Nama Karyawan</label>
                            <input type="text" id="display_nama" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small">Jabatan</label>
                            <input type="text" id="display_jabatan" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small">Departemen</label>
                            <input type="text" id="display_dept" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold small text-primary">ID Transaksi</label>
                            <input type="text" id="generated_bast_number" class="form-control bg-light fw-bold text-primary text-center" readonly placeholder="AUTO">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Catatan / Komentar</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Input catatan tambahan di sini..."></textarea>
                        </div>
                    </div>

                    <input type="hidden" name="division_id" id="division_id_hidden">

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <h6 class="fw-bold mb-0"><i class="mdi mdi-format-list-bulleted me-1"></i>Daftar Item Aset</h6>
                        <button type="button" class="btn btn-sm btn-success" id="btn-add-row">
                            <i class="mdi mdi-plus"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="table-asset">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 35%;">Pilih Aset Master (Search)</th>
                                    <th style="width: 25%;">Generated UID</th>
                                    <th>Serial Number</th>
                                    <th>Brand / Model</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="asset-row">
                                    <td>
                                        <select class="form-control select2-asset-ajax" name="asset_id[]" required>
                                            <option value="">Cari Nama / SN / UID...</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="generated_uids[]" class="form-control form-control-sm bg-soft-warning gen-uid text-center fw-bold" readonly placeholder="UID-AUTO">
                                    </td>
                                    <td class="serial_number text-center">-</td>
                                    <td class="brand text-muted">-</td>
                                    <td class="action text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete"><i class="mdi mdi-trash-can-outline"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('plugin')
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 Karyawan
            $('#employee_id_select').select2({ placeholder: 'Cari NIK...', width: '100%' });

            // Fungsi Inisialisasi Select2 Aset (AJAX)
            function initSelect2Asset(element) {
                $(element).select2({
                    placeholder: 'Cari Nama / SN / UID...',
                    width: '100%',
                    ajax: {
                        url: "{{ route('select.asset') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) { 
                            return { 
                                q: params.term, 
                                status: $('#transaction_status').val() 
                            }; 
                        },
                        processResults: function (data) { return { results: data }; },
                        cache: true
                    }
                });
            }

            initSelect2Asset('.select2-asset-ajax');

            // Handle Perubahan Karyawan
            $('#employee_id_select').on('change', function() {
                var opt = $(this).find('option:selected');
                if ($(this).val()) {
                    $('#display_nama').val(opt.data('nama'));
                    $('#display_jabatan').val(opt.data('jabatan'));
                    $('#display_dept').val(opt.data('dept'));
                    $('#division_id_hidden').val(opt.data('division'));
                    
                    // Generate Nomor Referensi Visual (BAST Number)
                    var dateNow = moment().format('YYYYMMDD');
                    var employeeNik = opt.text().trim();
                    $('#generated_bast_number').val("TRX-" + dateNow + "-" + employeeNik);

                    // Re-trigger asset UID generation jika aset sudah dipilih sebelumnya agar UID terupdate ke NIK baru
                    $('.select2-asset-ajax').each(function() {
                        if ($(this).val()) {
                            $(this).trigger('change');
                        }
                    });
                }
            });

            // Handle Pilih Aset & Generate UID via Server
            $(document).on('change', '.select2-asset-ajax', function() {
                var assetId = $(this).val();
                var empId   = $('#employee_id_select').val();
                var row     = $(this).closest('tr');

                if (!assetId) return;

                // PROTEKSI: Harus pilih karyawan dulu sebelum pilih aset
                if (!empId) {
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Silakan pilih NIK Karyawan terlebih dahulu agar UID dapat dibuat!',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6'
                    });
                    $(this).val(null).trigger('change');
                    return;
                }

                row.find('.gen-uid').val('Generating...');

                $.ajax({
                    url: "{{ url('/select/asset') }}/" + assetId,
                    type: "GET",
                    data: { employee_id: empId }, // Mengirimkan employee_id agar Controller bisa merakit UID
                    success: function(res) {
                        row.find('.brand').text(res.brand || '-');
                        row.find('.serial_number').html('<code class="text-primary">' + (res.serial_number || '-') + '</code>');
                        // Memasukkan hasil rincian UID dari server ke kolom kuning
                        row.find('.gen-uid').val(res.generated_uid);
                    },
                    error: function(xhr) {
                        row.find('.gen-uid').val('ERR-UID');
                        console.error("Error Detail:", xhr.responseText);
                    }
                });
            });

            // Tombol Tambah Baris (Manual)
            $('#btn-add-row').click(function() {
                var html = `<tr class="asset-row">
                    <td>
                        <select class="form-control select2-asset-ajax" name="asset_id[]" required>
                            <option value="">Cari Nama / SN / UID...</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="generated_uids[]" class="form-control form-control-sm bg-soft-warning gen-uid text-center fw-bold" readonly placeholder="UID-AUTO">
                    </td>
                    <td class="serial_number text-center">-</td>
                    <td class="brand text-muted">-</td>
                    <td class="action text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete"><i class="mdi mdi-trash-can-outline"></i></button>
                    </td>
                </tr>`;
                $('#table-asset tbody').append(html);
                initSelect2Asset($('#table-asset tbody tr:last').find('.select2-asset-ajax'));
            });

            // Tombol Hapus Baris
            $(document).on('click', '.btn-delete', function() {
                if ($('#table-asset tbody tr').length > 1) { 
                    $(this).closest('tr').remove(); 
                } else {
                    Swal.fire('Info', 'Minimal harus ada 1 item aset dalam daftar.', 'info');
                }
            });

            // Submit Form via AJAX
            $('#form-add-transaction').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                
                // Cek Validasi HTML5
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }

                Swal.fire({
                    title: 'Simpan Transaksi?',
                    text: "Pastikan data karyawan dan aset sudah benar.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ 
                            title: 'Memproses...', 
                            allowOutsideClick: false, 
                            didOpen: () => { Swal.showLoading(); } 
                        });

                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                        window.location.href = "{{ route('transaction') }}";
                                    });
                                } else {
                                    Swal.fire('Gagal!', res.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let errorMsg = "Terjadi kesalahan sistem.";
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection