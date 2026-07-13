@extends('layouts.app')

@section('content')
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Pengaturan WhatsApp Gateway (Fonnte)</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h4 class="card-title">Konfigurasi API</h4>
                                    <p class="card-title-desc text-muted">Token ini digunakan untuk menghubungkan sistem dengan layanan Fonnte.</p>

                                    <form id="formFonnte">
                                        <div class="mb-3">
                                            <label class="form-label font-weight-bold">API Token Fonnte</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="ri-key-2-line"></i></span>
                                                <input type="text" name="fonnte_token" id="fonnte_token" class="form-control" 
                                                       value="{{ $api->value ?? '' }}" required placeholder="Paste token di sini">
                                            </div>
                                        </div>

                                        <button type="button" id="btnSubmitManual" onclick="simpanFonnte()" class="btn btn-primary">
                                            Simpan Pengaturan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card bg-soft-info border-info">
                                <div class="card-body">
                                    <h4 class="card-title text-info"><i class="ri-information-line me-1"></i> Cara Penggunaan</h4>
                                    <ul class="mt-3">
                                        <li>Dapatkan token di <b>dashboard.fonnte.com</b>.</li>
                                        <li>Pastikan status di Fonnte adalah <span class="badge bg-success">Connected</span>.</li>
                                        <li>Klik <b>Simpan</b> untuk memperbarui konfigurasi sistem.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> Asset Management System
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="http://127.0.0.1:8000/libs/jquery/jquery.min.js"></script>
    <script src="http://127.0.0.1:8000/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="http://127.0.0.1:8000/libs/metismenu/metisMenu.min.js"></script>
    <script src="http://127.0.0.1:8000/libs/simplebar/simplebar.min.js"></script>
    <script src="http://127.0.0.1:8000/libs/node-waves/waves.min.js"></script>
    <script src="http://127.0.0.1:8000/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="http://127.0.0.1:8000/js/app.js"></script>

    <script>
        function simpanFonnte() {
            const token = $('#fonnte_token').val();
            const btn = $('#btnSubmitManual');
            const originalText = btn.html();

            if (!token || token.trim() === "") {
                Swal.fire('Peringatan', 'API Token tidak boleh kosong!', 'warning');
                return;
            }

            btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin font-size-16 align-middle me-2"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('setting-fonnte.update') }}",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    fonnte_token: token
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload(); 
                        });
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Terjadi kesalahan pada server.';
                    Swal.fire('Error!', msg, 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }
    </script>
