<?php

namespace App\Http\Controllers\SIMKA;

use App\Models\KategoriPendapatan;
use App\Models\Spph773AsumsiDasar;
use App\Models\Spph773Bobot;
use App\Models\Spph773AdjustmentValue;
use App\Models\Spph773KomponenTarif;
use Illuminate\Http\Request;

class SPPH773Controller extends SpphController
{
    protected function spphKey(): string
    {
        return 'spph773';
    }

    protected function spphTitle(): string
    {
        return 'SPPH 773';
    }

    /**
     * Tampilkan halaman konfigurasi data SPPH 773 dengan data yang sudah ada di database.
     */
    public function konfigurasi()
    {
        $kategoriPendapatan = KategoriPendapatan::orderBy('id')->get()->keyBy('id');

        // Ambil data bobot, grouped by kategori_pendapatan_id lalu name
        $bobot = Spph773Bobot::orderBy('kategori_pendapatan_id')->orderBy('name')->get();
        $bobotByKategori = [];
        foreach ($bobot as $b) {
            $bobotByKategori[$b->kategori_pendapatan_id][$b->name] = $b;
        }

        // Ambil data komponen tarif, grouped by kategori_pendapatan_id
        $komponenTarif = Spph773KomponenTarif::orderBy('kategori_pendapatan_id')->get()->keyBy('kategori_pendapatan_id');

        // Ambil data asumsi dasar, grouped by name
        $asumsiDasar = Spph773AsumsiDasar::orderBy('id')->get()->keyBy('name');

        // Siapkan nilai field untuk pre-populasi form (flat map: name_field => nilai)
        $fieldValues = [];

        // --- Bobot field mapping ---
        $bobotFieldMap = [
            'bobot_pekerjaan_tanah_bbm_gali' => [1, 'Gali', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_gali' => [1, 'Gali', 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_angkutan' => [1, 'Angkutan', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_angkutan' => [1, 'Angkutan', 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_sebar' => [1, 'Sebar', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_sebar' => [1, 'Sebar', 'bobot_umsp'],
            'bobot_pekerjaan_batubara_bbm_gali' => [2, 'Gali', 'bobot_bbm'],
            'bobot_pekerjaan_batubara_umsp_gali' => [2, 'Gali', 'bobot_umsp'],
            'bobot_pekerjaan_batubara_bbm_angkutan' => [2, 'Angkutan', 'bobot_bbm'],
            'bobot_pekerjaan_batubara_umsp_angkutan' => [2, 'Angkutan', 'bobot_umsp'],
        ];
        foreach ($bobotFieldMap as $fieldName => [$kategoriId, $name, $column]) {
            $record = $bobotByKategori[$kategoriId][$name] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Komponen Tarif field mapping ---
        $komponenFieldMap = [
            'komponen_tarif_usd_pekerjaan_tanah' => [1, 'usd'],
            'komponen_tarif_rupiah_pekerjaan_tanah' => [1, 'rupiah'],
            'komponen_tarif_usd_pekerjaan_batubara' => [2, 'usd'],
            'komponen_tarif_rupiah_pekerjaan_batubara' => [2, 'rupiah'],
        ];
        foreach ($komponenFieldMap as $fieldName => [$kategoriId, $column]) {
            $record = $komponenTarif[$kategoriId] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Asumsi Dasar field mapping ---
        $asumsiFieldMap = [
            'harga_acuan_bbm_dasar' => ['BBM Dasar', 'harga_acuan'],
            'harga_aktual_bbm_dasar' => ['BBM Dasar', 'harga_aktual'],
            'harga_acuan_umk_dasar' => ['UMK Dasar', 'harga_acuan'],
            'harga_aktual_umk_dasar' => ['UMK Dasar', 'harga_aktual'],
            'harga_acuan_kurs_dasar' => ['Kurs Dasar', 'harga_acuan'],
            'harga_aktual_kurs_dasar' => ['Kurs Dasar', 'harga_aktual'],
        ];
        foreach ($asumsiFieldMap as $fieldName => [$name, $column]) {
            $record = $asumsiDasar[$name] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Nilai Adjustment (dedicated table) ---
        $adjustmentValue = Spph773AdjustmentValue::first();
        $fieldValues['nilai_adjustment'] = $adjustmentValue?->value !== null ? number_format($adjustmentValue->value, 2, '.', '') : '';

        return view("simka.spph773.konfigurasi", $this->viewData() + [
            'pageTitle' => 'Konfigurasi Data',
            'pageIcon' => 'mdi-database-cog-outline',
            'pageColor' => 'primary',
            'pageDescription' => "Kelola master data dan parameter {$this->spphTitle()}.",
            'kategoriPendapatan' => $kategoriPendapatan,
            'bobotByKategori' => $bobotByKategori,
            'komponenTarif' => $komponenTarif,
            'asumsiDasar' => $asumsiDasar,
            'fieldValues' => $fieldValues,
        ]);
    }

    /**
     * Simpan konfigurasi data SPPH 773 ke database dengan relasi kategori pendapatan.
     */
    public function simpanKonfigurasi(Request $request)
    {
        $input = $request->except(['_token', '_method']);

        // Sanitize HANYA input mata uang (Rp): hapus titik pemisah ribuan
        // Contoh: "14.000" -> "14000". Jangan hapus titik pada input persen (contoh "37.00" -> tetap "37.00").
        $currencyFields = [
            'harga_acuan_bbm_dasar',
            'harga_acuan_umk_dasar',
            'harga_acuan_kurs_dasar',
            'harga_aktual_bbm_dasar',
            'harga_aktual_umk_dasar',
            'harga_aktual_kurs_dasar',
        ];
        foreach ($input as $key => $value) {
            if (is_string($value) && in_array($key, $currencyFields)) {
                $input[$key] = str_replace('.', '', $value);
            }
        }

        $rules = [];
        foreach (array_keys($input) as $name) {
            $rules[$name] = ['required', 'numeric', 'min:0'];
        }

        $request->merge($input);
        $request->validate($rules);

        // ================================================================
        // A. SIMPAN DATA BOBOT (simka_spph773_bobot)
        // Relasi: kategori_pendapatan_id -> simka_kategori_pendapatan
        // ================================================================
        $bobotFieldMap = [
            // Pekerjaan Tanah (kategori_pendapatan_id = 1)
            'bobot_pekerjaan_tanah_bbm_gali' => ['kategori_pendapatan_id' => 1, 'name' => 'Gali', 'column' => 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_gali' => ['kategori_pendapatan_id' => 1, 'name' => 'Gali', 'column' => 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_angkutan' => ['kategori_pendapatan_id' => 1, 'name' => 'Angkutan', 'column' => 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_angkutan' => ['kategori_pendapatan_id' => 1, 'name' => 'Angkutan', 'column' => 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_sebar' => ['kategori_pendapatan_id' => 1, 'name' => 'Sebar', 'column' => 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_sebar' => ['kategori_pendapatan_id' => 1, 'name' => 'Sebar', 'column' => 'bobot_umsp'],
            // Pekerjaan Batubara (kategori_pendapatan_id = 2)
            'bobot_pekerjaan_batubara_bbm_gali' => ['kategori_pendapatan_id' => 2, 'name' => 'Gali', 'column' => 'bobot_bbm'],
            'bobot_pekerjaan_batubara_umsp_gali' => ['kategori_pendapatan_id' => 2, 'name' => 'Gali', 'column' => 'bobot_umsp'],
            'bobot_pekerjaan_batubara_bbm_angkutan' => ['kategori_pendapatan_id' => 2, 'name' => 'Angkutan', 'column' => 'bobot_bbm'],
            'bobot_pekerjaan_batubara_umsp_angkutan' => ['kategori_pendapatan_id' => 2, 'name' => 'Angkutan', 'column' => 'bobot_umsp'],
        ];

        $bobotRecords = [];
        foreach ($bobotFieldMap as $fieldName => $map) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            $key = $map['kategori_pendapatan_id'] . '|' . $map['name'];
            if (!isset($bobotRecords[$key])) {
                $bobotRecords[$key] = [
                    'kategori_pendapatan_id' => $map['kategori_pendapatan_id'],
                    'name' => $map['name'],
                ];
            }
            $bobotRecords[$key][$map['column']] = $input[$fieldName];
        }

        foreach ($bobotRecords as $record) {
            Spph773Bobot::updateOrCreate(
                [
                    'kategori_pendapatan_id' => $record['kategori_pendapatan_id'],
                    'name' => $record['name'],
                ],
                [
                    'bobot_bbm' => $record['bobot_bbm'] ?? 0,
                    'bobot_umsp' => $record['bobot_umsp'] ?? 0,
                ]
            );
        }

        // ================================================================
        // B. SIMPAN DATA KOMPONEN TARIF (simka_spph773_komponen_tarif)
        // Relasi: kategori_pendapatan_id -> simka_kategori_pendapatan
        // ================================================================
        $komponenFieldMap = [
            'komponen_tarif_usd_pekerjaan_tanah' => ['kategori_pendapatan_id' => 1, 'column' => 'usd'],
            'komponen_tarif_rupiah_pekerjaan_tanah' => ['kategori_pendapatan_id' => 1, 'column' => 'rupiah'],
            'komponen_tarif_usd_pekerjaan_batubara' => ['kategori_pendapatan_id' => 2, 'column' => 'usd'],
            'komponen_tarif_rupiah_pekerjaan_batubara' => ['kategori_pendapatan_id' => 2, 'column' => 'rupiah'],
        ];

        $komponenRecords = [];
        foreach ($komponenFieldMap as $fieldName => $map) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            $key = $map['kategori_pendapatan_id'];
            if (!isset($komponenRecords[$key])) {
                $komponenRecords[$key] = ['kategori_pendapatan_id' => $map['kategori_pendapatan_id']];
            }
            $komponenRecords[$key][$map['column']] = $input[$fieldName];
        }

        foreach ($komponenRecords as $record) {
            Spph773KomponenTarif::updateOrCreate(
                ['kategori_pendapatan_id' => $record['kategori_pendapatan_id']],
                [
                    'usd' => $record['usd'] ?? 0,
                    'rupiah' => $record['rupiah'] ?? 0,
                ]
            );
        }

        // ================================================================
        // C. SIMPAN DATA ASUMSI DASAR (simka_spph773_asumsi_dasar)
        // Tanpa relasi ke kategori pendapatan
        // ================================================================
        $asumsiFieldMap = [
            'harga_acuan_bbm_dasar' => ['name' => 'BBM Dasar', 'column' => 'harga_acuan'],
            'harga_aktual_bbm_dasar' => ['name' => 'BBM Dasar', 'column' => 'harga_aktual'],
            'harga_acuan_umk_dasar' => ['name' => 'UMK Dasar', 'column' => 'harga_acuan'],
            'harga_aktual_umk_dasar' => ['name' => 'UMK Dasar', 'column' => 'harga_aktual'],
            'harga_acuan_kurs_dasar' => ['name' => 'Kurs Dasar', 'column' => 'harga_acuan'],
            'harga_aktual_kurs_dasar' => ['name' => 'Kurs Dasar', 'column' => 'harga_aktual'],
        ];

        $asumsiRecords = [];
        foreach ($asumsiFieldMap as $fieldName => $map) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            $key = $map['name'];
            if (!isset($asumsiRecords[$key])) {
                $asumsiRecords[$key] = ['name' => $map['name']];
            }
            $asumsiRecords[$key][$map['column']] = $input[$fieldName];
        }

        foreach ($asumsiRecords as $record) {
            Spph773AsumsiDasar::updateOrCreate(
                ['name' => $record['name']],
                [
                    'harga_acuan' => $record['harga_acuan'] ?? 0,
                    'harga_aktual' => $record['harga_aktual'] ?? 0,
                ]
            );
        }

        // ================================================================
        // D. SIMPAN DATA NILAI ADJUSTMENT (simka_spph773_adjustment_value)
        // Tabel terpisah untuk nilai adjustment persen
        // ================================================================
        if (array_key_exists('nilai_adjustment', $input)) {
            Spph773AdjustmentValue::updateOrCreate(
                ['id' => 1],
                ['value' => $input['nilai_adjustment']]
            );
        }

        return redirect()
            ->route('simka.spph773.konfigurasi')
            ->with('success', 'Konfigurasi data SPPH 773 berhasil disimpan.');
    }

    // ================================================================
    // KALKULASI SUB-MENUS (placeholder - implementasi selanjutnya)
    // ================================================================

    public function tarifGaliTanah()
    {
        return $this->kalkulasiSubMenu(
            'Tarif Gali - Pekerjaan Tanah',
            'mdi-pickaxe',
            'primary',
            'Hitung tarif gali untuk pekerjaan tanah.'
        );
    }

    public function tarifAngkutanTanah()
    {
        return $this->kalkulasiSubMenu(
            'Tarif Angkutan - Pekerjaan Tanah',
            'mdi-truck',
            'info',
            'Hitung tarif angkutan untuk pekerjaan tanah.'
        );
    }

    public function tarifSebarTanah()
    {
        return $this->kalkulasiSubMenu(
            'Tarif Sebar - Pekerjaan Tanah',
            'mdi-food',
            'success',
            'Hitung tarif sebar untuk pekerjaan tanah.'
        );
    }

    public function tarifGaliBatubara()
    {
        return $this->kalkulasiSubMenu(
            'Tarif Gali - Pekerjaan Batubara',
            'mdi-pickaxe',
            'warning',
            'Hitung tarif gali untuk pekerjaan batubara.'
        );
    }

    public function tarifAngkutanBatubara()
    {
        return $this->kalkulasiSubMenu(
            'Tarif Angkutan - Pekerjaan Batubara',
            'mdi-truck',
            'info',
            'Hitung tarif angkutan untuk pekerjaan batubara.'
        );
    }

    protected function kalkulasiSubMenu(string $title, string $icon, string $color, string $description)
    {
        return view("simka.spph773.kalkulasi-submenu", $this->viewData() + [
            'pageTitle' => $title,
            'pageIcon' => $icon,
            'pageColor' => $color,
            'pageDescription' => $description,
            'submenuTitle' => $title,
        ]);
    }
}
