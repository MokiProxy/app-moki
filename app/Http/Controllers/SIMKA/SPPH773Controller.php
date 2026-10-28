<?php

namespace App\Http\Controllers\SIMKA;

use App\Models\KategoriPendapatan;
use App\Models\Spph773AsumsiDasar;
use App\Models\Spph773Bobot;
use App\Models\Spph773AdjustmentValue;
use App\Models\Spph773KomponenTarif;
use App\Models\Spph773Tarif;
use App\Services\Simka\Spph773TarifCalculator;
use Illuminate\Http\Request;
use RuntimeException;

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
        // Tarif gali merupakan satu nilai tarif (tanpa dimensi jarak) sehingga diwakili 1 baris.
        $record = Spph773Tarif::query()
            ->whereHas('kategoriTarif', function ($query) {
                $query->where('name', 'Tarif Gali')
                    ->where('kategori_pendapatan_id', 1);
            })
            ->orderBy('id')
            ->first();

        $tarifRows = [[
            'row' => 1,
            'tarif_dasar' => $record?->tarif_dasar,
            'tarif_adjustment' => $record?->tarif_adjustment,
            'tarif_ekskalasi' => $record?->tarif_ekskalasi,
        ]];

        return view("simka.spph773.tarif-gali-tanah", $this->viewData() + [
            'pageTitle' => 'Tarif Gali - Pekerjaan Tanah',
            'pageIcon' => 'mdi-pickaxe',
            'pageColor' => 'primary',
            'pageDescription' => 'Kelola tarif gali untuk pekerjaan tanah.',
            'tarifRows' => $tarifRows,
            'hasData' => $record !== null,
        ]);
    }

    /**
     * Simpan (create/update) tarif gali pekerjaan tanah.
     * kategori_tarif_id = 1 (Tarif Gali / Pekerjaan Tanah).
     */
    public function simpanTarifGaliTanah(Request $request)
    {
        return $this->simpanTarifSatuBaris(
            $request,
            kategoriTarifId: 1,
            field: 'tarif_gali_dasar',
            fieldLabel: 'Tarif Gali Dasar',
            redirectRoute: 'simka.spph773.tarif-gali-tanah',
            label: 'Tarif gali pekerjaan tanah'
        );
    }

    /**
     * Hapus tarif gali pekerjaan tanah.
     * kategori_tarif_id = 1 (Tarif Gali / Pekerjaan Tanah).
     */
    public function hapusTarifGaliTanah()
    {
        return $this->hapusTarifSatuBaris(
            kategoriTarifId: 1,
            redirectRoute: 'simka.spph773.tarif-gali-tanah',
            label: 'Tarif gali pekerjaan tanah'
        );
    }

    /**
     * Kalkulasi Tarif Gali Adjustment & Ekskalasi pekerjaan tanah, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Gali Dasar sudah tersimpan sebelumnya. Hasil kalkulasi
     * disimpan ke field tarif_adjustment dan tarif_ekskalasi.
     * kategori_tarif_id = 1 (Tarif Gali / Pekerjaan Tanah).
     */
    public function kalkulasiTarifGaliTanah(Spph773TarifCalculator $calculator)
    {
        return $this->kalkulasiTarifPerBaris(
            kategoriTarifId: 1,
            redirectRoute: 'simka.spph773.tarif-gali-tanah',
            label: 'tarif gali pekerjaan tanah',
            dasarLabel: 'Tarif Gali Dasar',
            hitung: fn (float $tarifDasar) => $calculator->hitungGaliTanah($tarifDasar),
        );
    }

    /**
     * Simpan (create/update) tarif sebar pekerjaan tanah.
     * kategori_tarif_id = 3 (Tarif Sebar / Pekerjaan Tanah).
     */
    public function simpanTarifSebarTanah(Request $request)
    {
        return $this->simpanTarifSatuBaris(
            $request,
            kategoriTarifId: 3,
            field: 'tarif_sebar_dasar',
            fieldLabel: 'Tarif Sebar Dasar',
            redirectRoute: 'simka.spph773.tarif-sebar-tanah',
            label: 'Tarif sebar pekerjaan tanah'
        );
    }

    /**
     * Hapus tarif sebar pekerjaan tanah.
     * kategori_tarif_id = 3 (Tarif Sebar / Pekerjaan Tanah).
     */
    public function hapusTarifSebarTanah()
    {
        return $this->hapusTarifSatuBaris(
            kategoriTarifId: 3,
            redirectRoute: 'simka.spph773.tarif-sebar-tanah',
            label: 'Tarif sebar pekerjaan tanah'
        );
    }

    /**
     * Kalkulasi Tarif Sebar Adjustment & Ekskalasi pekerjaan tanah, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Sebar Dasar sudah tersimpan sebelumnya. Hasil kalkulasi
     * disimpan ke field tarif_adjustment dan tarif_ekskalasi.
     * kategori_tarif_id = 3 (Tarif Sebar / Pekerjaan Tanah).
     */
    public function kalkulasiTarifSebarTanah(Spph773TarifCalculator $calculator)
    {
        return $this->kalkulasiTarifPerBaris(
            kategoriTarifId: 3,
            redirectRoute: 'simka.spph773.tarif-sebar-tanah',
            label: 'tarif sebar pekerjaan tanah',
            dasarLabel: 'Tarif Sebar Dasar',
            hitung: fn (float $tarifDasar) => $calculator->hitungSebarTanah($tarifDasar),
        );
    }

    /**
     * Simpan (create/update) tarif angkutan pekerjaan tanah.
     * kategori_tarif_id = 2 (Tarif Angkut / Pekerjaan Tanah).
     */
    public function simpanTarifAngkutanTanah(Request $request)
    {
        return $this->simpanTarifAngkutan(
            $request,
            kategoriTarifId: 2,
            field: 'tarif_angkut_dasar',
            fieldLabel: 'Tarif Angkut Dasar',
            redirectRoute: 'simka.spph773.tarif-angkutan-tanah',
            label: 'Tarif angkutan pekerjaan tanah'
        );
    }

    /**
     * Hapus tarif angkutan pekerjaan tanah.
     * kategori_tarif_id = 2 (Tarif Angkut / Pekerjaan Tanah).
     */
    public function hapusTarifAngkutanTanah()
    {
        return $this->hapusTarifSatuBaris(
            kategoriTarifId: 2,
            redirectRoute: 'simka.spph773.tarif-angkutan-tanah',
            label: 'Tarif angkutan pekerjaan tanah'
        );
    }

    /**
     * Kalkulasi Tarif Angkut Adjustment & Ekskalasi pekerjaan tanah, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Angkut Dasar sudah tersimpan sebelumnya untuk setiap baris jarak.
     * kategori_tarif_id = 2 (Tarif Angkut / Pekerjaan Tanah).
     */
    public function kalkulasiTarifAngkutanTanah(Spph773TarifCalculator $calculator)
    {
        return $this->kalkulasiTarifPerBaris(
            kategoriTarifId: 2,
            redirectRoute: 'simka.spph773.tarif-angkutan-tanah',
            label: 'tarif angkutan pekerjaan tanah',
            dasarLabel: 'Tarif Angkut Dasar',
            hitung: fn (float $tarifDasar) => $calculator->hitungAngkutanTanah($tarifDasar),
        );
    }

    /**
     * Kalkulasi tarif untuk seluruh baris pada kategori tarif tertentu, lalu simpan hasilnya.
     *
     * Berguna untuk tarif dengan dimensi jarak (banyak baris) maupun tarif satu baris.
     * Hasil perhitungan disimpan ke field tarif_adjustment dan tarif_ekskalasi.
     *
     * @param  string  $dasarLabel  Label field tarif dasar (untuk pesan error).
     * @param  callable(float): array{tarif_adjustment:int, tarif_ekskalasi:int}  $hitung
     */
    protected function kalkulasiTarifPerBaris(int $kategoriTarifId, string $redirectRoute, string $label, string $dasarLabel, callable $hitung)
    {
        $records = Spph773Tarif::query()
            ->where('kategori_tarif_id', $kategoriTarifId)
            ->whereNotNull('tarif_dasar')
            ->orderBy('jarak_awal')
            ->get();

        if ($records->isEmpty()) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', "Simpan {$dasarLabel} terlebih dahulu sebelum melakukan kalkulasi.");
        }

        // Hitung seluruh baris terlebih dahulu; batalkan semua bila ada konfigurasi yang belum lengkap.
        try {
            $hasil = [];
            foreach ($records as $record) {
                $hasil[$record->id] = $hitung((float) $record->tarif_dasar);
            }
        } catch (RuntimeException $exception) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', $exception->getMessage());
        }

        foreach ($records as $record) {
            $record->update([
                'tarif_adjustment' => $hasil[$record->id]['tarif_adjustment'],
                'tarif_ekskalasi' => $hasil[$record->id]['tarif_ekskalasi'],
            ]);
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', "Kalkulasi {$label} berhasil dan hasilnya telah disimpan.");
    }

    public function tarifAngkutanTanah()
    {
        // Tarif angkutan memakai interval jarak 250, sebanyak 40 baris (jarak akhir 10.000).
        $interval = 250;
        $totalRows = 40;

        // Ambil nilai tarif yang sudah tersimpan (jika ada) untuk dipetakan per rentang jarak.
        $saved = Spph773Tarif::query()
            ->whereHas('kategoriTarif', function ($query) {
                $query->where('name', 'Tarif Angkut')
                    ->where('kategori_pendapatan_id', 1);
            })
            ->get()
            ->keyBy(function ($tarif) {
                return $tarif->jarak_awal . '-' . $tarif->jarak_akhir;
            });

        // Susun 40 baris dengan Jarak Awal & Jarak Akhir default/fixed.
        // Row 1 -> 0 - 250, Row 2 -> 251 - 500, ... Row 40 -> 9.751 - 10.000.
        $tarifRows = [];
        for ($i = 1; $i <= $totalRows; $i++) {
            $jarakAwal = $i === 1 ? 0 : (($i - 1) * $interval) + 1;
            $jarakAkhir = $i * $interval;

            $record = $saved->get($jarakAwal . '-' . $jarakAkhir);

            $tarifRows[] = [
                'row' => $i,
                'jarak_awal' => $jarakAwal,
                'jarak_akhir' => $jarakAkhir,
                'tarif_dasar' => $record?->tarif_dasar,
                'tarif_adjustment' => $record?->tarif_adjustment,
                'tarif_ekskalasi' => $record?->tarif_ekskalasi,
            ];
        }

        return view("simka.spph773.tarif-angkutan-tanah", $this->viewData() + [
            'pageTitle' => 'Tarif Angkutan - Pekerjaan Tanah',
            'pageIcon' => 'mdi-truck',
            'pageColor' => 'info',
            'pageDescription' => 'Kelola tarif angkutan untuk pekerjaan tanah.',
            'tarifRows' => $tarifRows,
            'hasData' => $saved->isNotEmpty(),
        ]);
    }

    public function tarifSebarTanah()
    {
        // Tarif sebar merupakan satu nilai tarif (tanpa dimensi jarak) sehingga diwakili 1 baris.
        $record = Spph773Tarif::query()
            ->whereHas('kategoriTarif', function ($query) {
                $query->where('name', 'Tarif Sebar')
                    ->where('kategori_pendapatan_id', 1);
            })
            ->orderBy('id')
            ->first();

        $tarifRows = [[
            'row' => 1,
            'tarif_dasar' => $record?->tarif_dasar,
            'tarif_adjustment' => $record?->tarif_adjustment,
            'tarif_ekskalasi' => $record?->tarif_ekskalasi,
        ]];

        return view("simka.spph773.tarif-sebar-tanah", $this->viewData() + [
            'pageTitle' => 'Tarif Sebar - Pekerjaan Tanah',
            'pageIcon' => 'mdi-food',
            'pageColor' => 'success',
            'pageDescription' => 'Kelola tarif sebar untuk pekerjaan tanah.',
            'tarifRows' => $tarifRows,
            'hasData' => $record !== null,
        ]);
    }

    public function tarifGaliBatubara()
    {
        // Tarif gali merupakan satu nilai tarif (tanpa dimensi jarak) sehingga diwakili 1 baris.
        $record = Spph773Tarif::query()
            ->whereHas('kategoriTarif', function ($query) {
                $query->where('name', 'Tarif Gali')
                    ->where('kategori_pendapatan_id', 2);
            })
            ->orderBy('id')
            ->first();

        $tarifRows = [[
            'row' => 1,
            'tarif_dasar' => $record?->tarif_dasar,
            'tarif_adjustment' => $record?->tarif_adjustment,
            'tarif_ekskalasi' => $record?->tarif_ekskalasi,
        ]];

        return view("simka.spph773.tarif-gali-batubara", $this->viewData() + [
            'pageTitle' => 'Tarif Gali - Pekerjaan Batubara',
            'pageIcon' => 'mdi-pickaxe',
            'pageColor' => 'warning',
            'pageDescription' => 'Kelola tarif gali untuk pekerjaan batubara.',
            'tarifRows' => $tarifRows,
            'hasData' => $record !== null,
        ]);
    }

    /**
     * Simpan (create/update) tarif gali pekerjaan batubara.
     * kategori_tarif_id = 4 (Tarif Gali / Pekerjaan Batubara).
     */
    public function simpanTarifGaliBatubara(Request $request)
    {
        return $this->simpanTarifSatuBaris(
            $request,
            kategoriTarifId: 4,
            field: 'tarif_gali_dasar',
            fieldLabel: 'Tarif Gali Dasar',
            redirectRoute: 'simka.spph773.tarif-gali-batubara',
            label: 'Tarif gali pekerjaan batubara'
        );
    }

    /**
     * Hapus tarif gali pekerjaan batubara.
     * kategori_tarif_id = 4 (Tarif Gali / Pekerjaan Batubara).
     */
    public function hapusTarifGaliBatubara()
    {
        return $this->hapusTarifSatuBaris(
            kategoriTarifId: 4,
            redirectRoute: 'simka.spph773.tarif-gali-batubara',
            label: 'Tarif gali pekerjaan batubara'
        );
    }

    /**
     * Kalkulasi Tarif Gali Adjustment & Ekskalasi pekerjaan batubara, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Gali Dasar sudah tersimpan sebelumnya.
     * kategori_tarif_id = 4 (Tarif Gali / Pekerjaan Batubara).
     */
    public function kalkulasiTarifGaliBatubara(Spph773TarifCalculator $calculator)
    {
        return $this->kalkulasiTarifPerBaris(
            kategoriTarifId: 4,
            redirectRoute: 'simka.spph773.tarif-gali-batubara',
            label: 'tarif gali pekerjaan batubara',
            dasarLabel: 'Tarif Gali Dasar',
            hitung: fn (float $tarifDasar) => $calculator->hitungGaliBatubara($tarifDasar),
        );
    }

    /**
     * Simpan (create/update) tarif angkutan pekerjaan batubara.
     * kategori_tarif_id = 5 (Tarif Angkut / Pekerjaan Batubara).
     */
    public function simpanTarifAngkutanBatubara(Request $request)
    {
        return $this->simpanTarifAngkutan(
            $request,
            kategoriTarifId: 5,
            field: 'tarif_angkut_dasar',
            fieldLabel: 'Tarif Angkut Dasar',
            redirectRoute: 'simka.spph773.tarif-angkutan-batubara',
            label: 'Tarif angkutan pekerjaan batubara'
        );
    }

    /**
     * Hapus tarif angkutan pekerjaan batubara.
     * kategori_tarif_id = 5 (Tarif Angkut / Pekerjaan Batubara).
     */
    public function hapusTarifAngkutanBatubara()
    {
        return $this->hapusTarifSatuBaris(
            kategoriTarifId: 5,
            redirectRoute: 'simka.spph773.tarif-angkutan-batubara',
            label: 'Tarif angkutan pekerjaan batubara'
        );
    }

    /**
     * Kalkulasi Tarif Angkut Adjustment & Ekskalasi pekerjaan batubara, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Angkut Dasar sudah tersimpan sebelumnya untuk setiap baris jarak.
     * kategori_tarif_id = 5 (Tarif Angkut / Pekerjaan Batubara).
     */
    public function kalkulasiTarifAngkutanBatubara(Spph773TarifCalculator $calculator)
    {
        return $this->kalkulasiTarifPerBaris(
            kategoriTarifId: 5,
            redirectRoute: 'simka.spph773.tarif-angkutan-batubara',
            label: 'tarif angkutan pekerjaan batubara',
            dasarLabel: 'Tarif Angkut Dasar',
            hitung: fn (float $tarifDasar) => $calculator->hitungAngkutanBatubara($tarifDasar),
        );
    }

    /**
     * Simpan (create/update) tarif dasar untuk kategori tarif tertentu.
     *
     * Hanya field tarif_dasar yang diisi dari input user; field lain dibiarkan null.
     */
    protected function simpanTarifSatuBaris(Request $request, int $kategoriTarifId, string $field, string $fieldLabel, string $redirectRoute, string $label)
    {
        // Hilangkan pemisah ribuan pada input Rupiah sebelum validasi (contoh: "14.000" -> "14000").
        $nilai = str_replace('.', '', (string) $request->input($field, ''));
        $request->merge([$field => $nilai]);

        $request->validate(
            [$field => ['required', 'numeric', 'min:0']],
            [],
            [$field => $fieldLabel]
        );

        Spph773Tarif::updateOrCreate(
            ['kategori_tarif_id' => $kategoriTarifId],
            ['tarif_dasar' => $nilai]
        );

        return redirect()
            ->route($redirectRoute)
            ->with('success', "{$label} berhasil disimpan.");
    }

    /**
     * Simpan (create/update) tarif dasar per baris jarak untuk kategori tarif tertentu.
     *
     * Input berupa array field[row] => nilai. Setiap baris dipetakan ke rentang jarak
     * sesuai interval (Row 1 -> 0-250, Row 2 -> 251-500, dst.). Baris kosong dilewati.
     */
    protected function simpanTarifAngkutan(Request $request, int $kategoriTarifId, string $field, string $fieldLabel, string $redirectRoute, string $label)
    {
        $interval = 250;
        $totalRows = 40;

        $raw = (array) $request->input($field, []);

        // Sanitize (hilangkan pemisah ribuan) & ambil hanya baris yang memiliki nilai.
        $nilaiPerBaris = [];
        foreach ($raw as $row => $value) {
            $row = (int) $row;
            $nilai = str_replace('.', '', trim((string) $value));

            if ($row < 1 || $row > $totalRows || $nilai === '') {
                continue;
            }

            $nilaiPerBaris[$row] = $nilai;
        }

        if ($nilaiPerBaris === []) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', "Isi minimal satu baris {$fieldLabel}.");
        }

        $request->merge([$field => $nilaiPerBaris]);

        $rules = [];
        $attributes = [];
        foreach (array_keys($nilaiPerBaris) as $row) {
            $rules["{$field}.{$row}"] = ['required', 'numeric', 'min:0'];
            $attributes["{$field}.{$row}"] = "{$fieldLabel} baris {$row}";
        }
        $request->validate($rules, [], $attributes);

        foreach ($nilaiPerBaris as $row => $nilai) {
            $jarakAwal = $row === 1 ? 0 : (($row - 1) * $interval) + 1;
            $jarakAkhir = $row * $interval;

            Spph773Tarif::updateOrCreate(
                [
                    'kategori_tarif_id' => $kategoriTarifId,
                    'jarak_awal' => $jarakAwal,
                    'jarak_akhir' => $jarakAkhir,
                ],
                ['tarif_dasar' => $nilai]
            );
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', "{$label} berhasil disimpan.");
    }

    /**
     * Hapus tarif berdasarkan kategori tarif.
     */
    protected function hapusTarifSatuBaris(int $kategoriTarifId, string $redirectRoute, string $label)
    {
        Spph773Tarif::where('kategori_tarif_id', $kategoriTarifId)->delete();

        return redirect()
            ->route($redirectRoute)
            ->with('success', "{$label} berhasil dihapus.");
    }

    public function tarifAngkutanBatubara()
    {
        // Tarif angkutan memakai interval jarak 250, sebanyak 40 baris (jarak akhir 10.000).
        $interval = 250;
        $totalRows = 40;

        // Ambil nilai tarif yang sudah tersimpan (jika ada) untuk dipetakan per rentang jarak.
        $saved = Spph773Tarif::query()
            ->whereHas('kategoriTarif', function ($query) {
                $query->where('name', 'Tarif Angkut')
                    ->where('kategori_pendapatan_id', 2);
            })
            ->get()
            ->keyBy(function ($tarif) {
                return $tarif->jarak_awal . '-' . $tarif->jarak_akhir;
            });

        // Susun 40 baris dengan Jarak Awal & Jarak Akhir default/fixed.
        // Row 1 -> 0 - 250, Row 2 -> 251 - 500, ... Row 40 -> 9.751 - 10.000.
        $tarifRows = [];
        for ($i = 1; $i <= $totalRows; $i++) {
            $jarakAwal = $i === 1 ? 0 : (($i - 1) * $interval) + 1;
            $jarakAkhir = $i * $interval;

            $record = $saved->get($jarakAwal . '-' . $jarakAkhir);

            $tarifRows[] = [
                'row' => $i,
                'jarak_awal' => $jarakAwal,
                'jarak_akhir' => $jarakAkhir,
                'tarif_dasar' => $record?->tarif_dasar,
                'tarif_adjustment' => $record?->tarif_adjustment,
                'tarif_ekskalasi' => $record?->tarif_ekskalasi,
            ];
        }

        return view("simka.spph773.tarif-angkutan-batubara", $this->viewData() + [
            'pageTitle' => 'Tarif Angkutan - Pekerjaan Batubara',
            'pageIcon' => 'mdi-truck',
            'pageColor' => 'info',
            'pageDescription' => 'Kelola tarif angkutan untuk pekerjaan batubara.',
            'tarifRows' => $tarifRows,
            'hasData' => $saved->isNotEmpty(),
        ]);
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

    // ================================================================
    // TARIF TOTAL (view only - logika kalkulasi belum tersedia)
    //
    // Tarif Total adalah penjumlahan seluruh komponen tarif pada satu section
    // untuk setiap rentang jarak. Halaman disiapkan sebagai view (kerangka)
    // dengan 40 baris rentang jarak; logika kalkulasi & penyimpanan ke tabel
    // simka_spph773_tarif_total belum diimplementasikan.
    // ================================================================

    /**
     * Tarif Total - Pekerjaan Tanah.
     */
    public function tarifTotalTanah()
    {
        return view('simka.spph773.tarif-total-tanah', $this->viewData() + [
            'pageTitle' => 'Tarif Total - Pekerjaan Tanah',
            'pageIcon' => 'mdi-sigma',
            'pageColor' => 'primary',
            'pageDescription' => 'Tinjau total tarif pekerjaan tanah per rentang jarak.',
        ]);
    }

    /**
     * Tarif Total - Pekerjaan Batubara.
     */
    public function tarifTotalBatubara()
    {
        return view('simka.spph773.tarif-total-batubara', $this->viewData() + [
            'pageTitle' => 'Tarif Total - Pekerjaan Batubara',
            'pageIcon' => 'mdi-sigma',
            'pageColor' => 'warning',
            'pageDescription' => 'Tinjau total tarif pekerjaan batubara per rentang jarak.',
        ]);
    }

    /**
     * Placeholder aksi (Hapus / Kalkulasi) halaman Tarif Total SPPH 773.
     *
     * Logika kalkulasi & penyimpanan tarif total belum diimplementasikan sehingga
     * aksi dikembalikan ke halaman asal dengan pesan informasi.
     */
    public function placeholderAksi()
    {
        return redirect()
            ->back()
            ->with('error', "Fitur kalkulasi Tarif Total belum tersedia untuk {$this->spphTitle()}.");
    }
}
