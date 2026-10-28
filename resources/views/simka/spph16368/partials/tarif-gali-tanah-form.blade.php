{{--
    Halaman Tarif Gali - Pekerjaan Tanah (SPPH 16368).

    Style mengikuti menu Tarif Angkutan: input digabung langsung ke dalam tabel
    (tidak ada tabel view terpisah).

    Rules:
    - Kolom: Tarif Gali Dasar, Tarif Gali Adjustment, Tarif Gali Ekskalasi.
    - Hanya "Tarif Gali Dasar" yang dapat diisi user. Kolom lainnya ter-kunci (disabled).
    - Simpan ke table simka_spph16368_tarif dengan kategori_tarif_id = 1 (Tarif Gali / Pekerjaan Tanah).
    - Hanya field tarif_dasar yang diisi user; field tarif_adjustment dan tarif_ekskalasi
      diisi oleh tombol Kalkulasi (hasil perhitungan otomatis).
    - Alur: user menyimpan Tarif Gali Dasar terlebih dahulu, lalu menekan tombol Kalkulasi
      untuk menghitung sekaligus menyimpan Tarif Gali Adjustment & Tarif Gali Ekskalasi.
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
        Hanya kolom <strong>Tarif Gali Dasar</strong> yang dapat diisi.
        Simpan terlebih dahulu <strong>Tarif Gali Dasar</strong>, kemudian tekan tombol
        <strong>Kalkulasi</strong> untuk menghitung sekaligus menyimpan
        <em>Tarif Gali Adjustment</em> dan <em>Tarif Gali Ekskalasi</em>.
    </div>
</div>

<form id="formTarifGaliTanah" action="{{ route('simka.spph16368.tarif-gali-tanah.simpan') }}" method="POST" autocomplete="off">
    @csrf
    <div class="card spph-form-card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center">
            <div class="spph-icon-soft spph-icon-sm bg-soft-primary me-3">
                <i class="mdi mdi-pickaxe"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-0 fw-bold text-dark">A. Tarif Gali - Pekerjaan Tanah</h6>
                <small class="text-muted">Tarif gali dasar per pekerjaan tanah</small>
            </div>
            <span class="badge bg-soft-primary text-primary">{{ count($tarifRows) }} Baris</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive spph-tarif-scroll">
                <table class="table table-hover table-sm align-middle mb-0 spph-tarif-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">No</th>
                            <th style="min-width: 240px;">Tarif Gali Dasar</th>
                            <th style="min-width: 230px;">Tarif Gali Adjustment</th>
                            <th style="min-width: 230px;">Tarif Gali Ekskalasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tarifRows as $row)
                            <tr>
                                <td class="text-center row-no">{{ $row['row'] }}</td>

                                {{-- Tarif Gali Dasar - satu-satunya input yang dapat diisi user (format Rupiah) --}}
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input
                                            type="text"
                                            name="tarif_gali_dasar"
                                            id="tarif_gali_dasar"
                                            class="form-control currency-input text-end @if(isset($errors) && $errors->has('tarif_gali_dasar')) is-invalid @endif"
                                            data-type="currency"
                                            inputmode="numeric"
                                            placeholder="0"
                                            value="{{ old('tarif_gali_dasar', $row['tarif_dasar'] !== null ? number_format($row['tarif_dasar'], 0, '.', '') : '') }}">
                                    </div>
                                </td>

                                {{-- Tarif Gali Adjustment - otomatis, disabled --}}
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

                                {{-- Tarif Gali Ekskalasi - otomatis, disabled --}}
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
                                    Belum ada data tarif gali.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<form id="formHapusTarifGaliTanah" action="{{ route('simka.spph16368.tarif-gali-tanah.hapus') }}" method="POST" onsubmit="return confirm('Hapus Tarif Gali Pekerjaan Tanah?');">
    @csrf
    @method('DELETE')
</form>

{{-- Form kalkulasi: menghitung Tarif Gali Adjustment & Ekskalasi lalu menyimpannya --}}
<form id="formKalkulasiTarifGaliTanah" action="{{ route('simka.spph16368.tarif-gali-tanah.kalkulasi') }}" method="POST">
    @csrf
</form>

<div class="d-flex justify-content-end gap-2">
    <button type="reset" form="formTarifGaliTanah" class="btn btn-light">
        <i class="mdi mdi-refresh me-1"></i> Reset
    </button>
    @if($hasData)
        <button type="submit" form="formHapusTarifGaliTanah" class="btn btn-outline-danger">
            <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
        </button>
    @endif
    <button type="submit" form="formTarifGaliTanah" class="btn btn-primary">
        <i class="mdi mdi-content-save me-1"></i> Simpan
    </button>
    <button
        type="submit"
        form="formKalkulasiTarifGaliTanah"
        class="btn btn-success"
        @unless($hasData) disabled title="Simpan Tarif Gali Dasar terlebih dahulu" @endunless>
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
