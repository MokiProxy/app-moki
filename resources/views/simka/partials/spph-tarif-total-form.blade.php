{{--
    Halaman Tarif Total (SPPH 773 & SPPH 2287).

    Menampilkan total tarif per rentang jarak (interval 250, 40 baris) untuk satu
    section pekerjaan. Seluruh kolom bersifat read-only karena nilai total
    merupakan hasil penjumlahan komponen tarif pada section tersebut.

    Konfigurasi dikirim lewat variabel $tarifTotal dari masing-masing view:

    - title          : judul kartu, contoh "A. Tarif Total - Pekerjaan Tanah"
    - subtitle       : keterangan kecil di bawah judul
    - icon           : kelas ikon MDI
    - color          : warna tema (primary/success/warning/info/danger)
    - labelPrefix    : label kolom, contoh "Tarif Total"
    - formId         : id form kalkulasi
    - routeHapus     : nama route hapus
    - routeKalkulasi : nama route kalkulasi
    - emptyText      : teks saat belum ada data

    CATATAN: logika kalkulasi & penyimpanan tarif total BELUM diimplementasikan.
    Halaman ini masih berupa view (kerangka). Bila nanti controller sudah
    mengirim $jarakRows (berisi tarif_total_dasar & tarif_total_ekskalasi),
    data tersebut otomatis dipakai dan tidak lagi memakai baris default di bawah.
--}}
@php
    $tarifTotal = $tarifTotal ?? [];
    $jarakRows = $jarakRows ?? [];
    $hasData = $hasData ?? false;

    $interval = 250;
    $totalRows = 40;

    // Baris default (belum ada hasil kalkulasi): Row 1 -> 0 - 250,
    // Row 2 -> 251 - 500, ... Row 40 -> 9.751 - 10.000.
    if (empty($jarakRows)) {
        for ($i = 1; $i <= $totalRows; $i++) {
            $jarakRows[] = [
                'row' => $i,
                'jarak_awal' => $i === 1 ? 0 : (($i - 1) * $interval) + 1,
                'jarak_akhir' => $i * $interval,
                'tarif_total_dasar' => null,
                'tarif_total_ekskalasi' => null,
            ];
        }
    }

    $formId = $tarifTotal['formId'] ?? 'formTarifTotal';
    $formHapusId = $formId . 'Hapus';
    $labelPrefix = $tarifTotal['labelPrefix'] ?? 'Tarif Total';
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

<div class="alert alert-info d-flex align-items-start" role="alert">
    <i class="mdi mdi-information-outline fs-5 me-2"></i>
    <div class="small">
        <strong>{{ $labelPrefix }}</strong> dihitung dari penjumlahan seluruh komponen tarif pada
        section ini untuk setiap rentang jarak, sehingga semua kolom bersifat
        <em>read-only</em>. Tekan tombol <strong>Kalkulasi</strong> untuk menghitung dan
        menyimpan hasilnya.
    </div>
</div>

<div class="card spph-form-card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center">
        <div class="spph-icon-soft spph-icon-sm bg-soft-{{ $tarifTotal['color'] }} me-3">
            <i class="mdi {{ $tarifTotal['icon'] }}"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="mb-0 fw-bold text-dark">{{ $tarifTotal['title'] }}</h6>
            <small class="text-muted">{{ $tarifTotal['subtitle'] }}</small>
        </div>
        <span class="badge bg-soft-{{ $tarifTotal['color'] }} text-{{ $tarifTotal['color'] }}">{{ count($jarakRows) }} Baris</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive spph-tarif-scroll">
            <table class="table table-hover table-sm align-middle mb-0 spph-tarif-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;">No</th>
                        <th style="min-width: 140px;">Jarak Awal</th>
                        <th style="min-width: 140px;">Jarak Akhir</th>
                        <th style="min-width: 230px;">{{ $labelPrefix }} Dasar</th>
                        <th style="min-width: 230px;">{{ $labelPrefix }} Ekskalasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jarakRows as $row)
                        <tr>
                            <td class="text-center row-no">{{ $row['row'] }}</td>

                            {{-- Jarak Awal - default & fixed --}}
                            <td>
                                <input
                                    type="text"
                                    class="form-control form-control-sm text-end"
                                    value="{{ number_format($row['jarak_awal'], 0, ',', '.') }}"
                                    disabled>
                            </td>

                            {{-- Jarak Akhir - default & fixed --}}
                            <td>
                                <input
                                    type="text"
                                    class="form-control form-control-sm text-end"
                                    value="{{ number_format($row['jarak_akhir'], 0, ',', '.') }}"
                                    disabled>
                            </td>

                            {{-- Tarif Total Dasar - hasil perhitungan, read-only --}}
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input
                                        type="text"
                                        class="form-control text-end"
                                        value="{{ $row['tarif_total_dasar'] !== null ? number_format($row['tarif_total_dasar'], 0, ',', '.') : '' }}"
                                        placeholder="-"
                                        disabled>
                                </div>
                            </td>

                            {{-- Tarif Total Ekskalasi - hasil perhitungan, read-only --}}
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input
                                        type="text"
                                        class="form-control text-end"
                                        value="{{ $row['tarif_total_ekskalasi'] !== null ? number_format($row['tarif_total_ekskalasi'], 0, ',', '.') : '' }}"
                                        placeholder="-"
                                        disabled>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="mdi mdi-database-outline fs-3 d-block mb-2"></i>
                                {{ $tarifTotal['emptyText'] ?? 'Belum ada data tarif total.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="{{ $formHapusId }}" action="{{ route($tarifTotal['routeHapus']) }}" method="POST" onsubmit="return confirm('Hapus {{ $tarifTotal['title'] }}?');">
    @csrf
    @method('DELETE')
</form>

{{-- Form kalkulasi: menghitung total tarif dasar & ekskalasi seluruh baris lalu menyimpannya --}}
<form id="{{ $formId }}" action="{{ route($tarifTotal['routeKalkulasi']) }}" method="POST">
    @csrf
</form>

<div class="d-flex justify-content-end gap-2">
    @if($hasData)
        <button type="submit" form="{{ $formHapusId }}" class="btn btn-outline-danger">
            <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
        </button>
    @endif
    <button type="submit" form="{{ $formId }}" class="btn btn-success">
        <i class="mdi mdi-calculator-variant-outline me-1"></i> Kalkulasi
    </button>
</div>
