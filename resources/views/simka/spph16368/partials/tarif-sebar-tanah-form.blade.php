{{--
    Halaman Tarif Sebar - Pekerjaan Tanah (SPPH 16368).

    Style mengikuti menu Tarif Angkutan - Pekerjaan Tanah: input digabung langsung
    ke dalam tabel (tidak ada tabel view terpisah).

    Rules:
    - Kolom: Tarif Sebar Dasar, Tarif Sebar Adjustment, Tarif Sebar Ekskalasi.
    - Hanya "Tarif Sebar Dasar" yang dapat diisi user. Kolom lainnya ter-kunci (disabled).
    - Simpan ke table simka_spph16368_tarif dengan kategori_tarif_id = 3 (Tarif Sebar / Pekerjaan Tanah).
    - Hanya field tarif_dasar yang diisi user; field tarif_adjustment dan tarif_ekskalasi
      diisi oleh tombol Kalkulasi (hasil perhitungan otomatis).
    - Alur: user menyimpan Tarif Sebar Dasar terlebih dahulu, lalu menekan tombol Kalkulasi
      untuk menghitung sekaligus menyimpan Tarif Sebar Adjustment & Tarif Sebar Ekskalasi.
--}}
@php
    $tarifRows = $tarifRows ?? [];
    $hasData = $hasData ?? false;
@endphp

<style>
    .spph-tarif-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8f9fc;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #6c757d;
        white-space: nowrap;
        border-bottom: 1px solid #eef0f4;
    }

    .spph-tarif-table td {
        vertical-align: middle;
    }

    .spph-tarif-table .form-control,
    .spph-tarif-table .input-group-text {
        font-size: 0.8rem;
    }

    .spph-tarif-scroll {
        max-height: 620px;
        overflow-y: auto;
    }

    .spph-tarif-table .row-no {
        font-weight: 600;
        color: #6c757d;
    }
</style>

@if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle-outline me-1"></i>
        Terdapat {{ $errors->count() }} input yang belum valid. Mohon periksa kembali.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info d-flex align-items-start" role="alert">
    <i class="mdi mdi-information-outline fs-5 me-2"></i>
    <div class="small">
        Hanya kolom <strong>Tarif Sebar Dasar</strong> yang dapat diisi.
        Simpan terlebih dahulu <strong>Tarif Sebar Dasar</strong>, kemudian tekan tombol
        <strong>Kalkulasi</strong> untuk menghitung sekaligus menyimpan
        <em>Tarif Sebar Adjustment</em> dan <em>Tarif Sebar Ekskalasi</em>.
    </div>
</div>

<form id="formTarifSebarTanah" action="{{ route('simka.spph16368.tarif-sebar-tanah.simpan') }}" method="POST" autocomplete="off">
    @csrf
    <div class="card spph-form-card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center">
            <div class="spph-icon-soft spph-icon-sm bg-soft-success me-3">
                <i class="mdi mdi-food"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0 fw-bold text-dark">A. Tarif Sebar - Pekerjaan Tanah</h6>
                <small class="text-muted">Tarif sebar dasar per pekerjaan tanah</small>
            </div>
            <span class="badge bg-soft-success text-success">{{ count($tarifRows) }} Baris</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive spph-tarif-scroll">
                <table class="table table-hover table-sm align-middle mb-0 spph-tarif-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">No</th>
                            <th style="min-width: 240px;">Tarif Sebar Dasar</th>
                            <th style="min-width: 230px;">Tarif Sebar Adjustment</th>
                            <th style="min-width: 230px;">Tarif Sebar Ekskalasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tarifRows as $row)
                            <tr>
                                <td class="text-center row-no">{{ $row['row'] }}</td>

                                {{-- Tarif Sebar Dasar - satu-satunya input yang dapat diisi user (format Rupiah) --}}
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="tarif_sebar_dasar"
                                            id="tarif_sebar_dasar"
                                            class="form-control currency-input text-end @if(isset($errors) && $errors->has('tarif_sebar_dasar')) is-invalid @endif"
                                            data-type="currency"
                                            inputmode="numeric"
                                            placeholder="0"
                                            value="{{ old('tarif_sebar_dasar', $row['tarif_dasar'] !== null ? number_format($row['tarif_dasar'], 0, '.', '') : '') }}">
                                    </div>
                                </td>

                                {{-- Tarif Sebar Adjustment - otomatis, disabled --}}
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            class="form-control text-end"
                                            value="{{ $row['tarif_adjustment'] !== null ? number_format($row['tarif_adjustment'], 0, ',', '.') : '' }}"
                                            placeholder="-"
                                            disabled>
                                    </div>
                                </td>

                                {{-- Tarif Sebar Ekskalasi - otomatis, disabled --}}
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            class="form-control text-end"
                                            value="{{ $row['tarif_ekskalasi'] !== null ? number_format($row['tarif_ekskalasi'], 0, ',', '.') : '' }}"
                                            placeholder="-"
                                            disabled>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="mdi mdi-database-outline fs-3 d-block mb-2"></i>
                                    Belum ada data tarif sebar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
</div>
</form>

<form id="formHapusTarifSebarTanah" action="{{ route('simka.spph16368.tarif-sebar-tanah.hapus') }}" method="POST" onsubmit="return confirm('Hapus Tarif Sebar Pekerjaan Tanah?');">
    @csrf
    @method('DELETE')
</form>

{{-- Form kalkulasi: menghitung Tarif Sebar Adjustment & Ekskalasi lalu menyimpannya --}}
<form id="formKalkulasiTarifSebarTanah" action="{{ route('simka.spph16368.tarif-sebar-tanah.kalkulasi') }}" method="POST">
    @csrf
</form>

<div class="d-flex justify-content-end gap-2">
    <button type="reset" form="formTarifSebarTanah" class="btn btn-light">
        <i class="mdi mdi-refresh me-1"></i> Reset
    </button>
    @if($hasData)
        <button type="submit" form="formHapusTarifSebarTanah" class="btn btn-outline-danger">
            <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
        </button>
    @endif
    <button type="submit" form="formTarifSebarTanah" class="btn btn-primary">
        <i class="mdi mdi-content-save me-1"></i> Simpan
    </button>
    <button
        type="submit"
        form="formKalkulasiTarifSebarTanah"
        class="btn btn-success"
        @unless($hasData) disabled title="Simpan Tarif Sebar Dasar terlebih dahulu" @endunless>
        <i class="mdi mdi-calculator-variant-outline me-1"></i> Kalkulasi
    </button>
</div>

<script>
    function formatRupiah(angka) {
        // angka sudah berupa string digit-only, tambahkan titik pemisah ribuan
        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Format input ke format Rupiah saat diketik (hanya field currency/Rupiah yang aktif)
    document.querySelectorAll('input[data-type="currency"]').forEach(function (input) {
        if (input.value) {
            input.value = formatRupiah(input.value.replace(/\D/g, ''));
        }

        input.addEventListener('input', function (e) {
            var value = e.target.value.replace(/\D/g, '');
            e.target.value = value !== '' ? formatRupiah(value) : '';
        });

        input.addEventListener('blur', function () {
            if (input.value) {
                input.value = formatRupiah(input.value.replace(/\D/g, ''));
            }
        });
    });

    // Sebelum submit, hapus format titik pada input currency agar terkirim sebagai angka murni
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('input[data-type="currency"]').forEach(function (input) {
                if (input.value) {
                    input.value = input.value.replace(/\./g, '');
                }
            });
        });
    });
</script>
