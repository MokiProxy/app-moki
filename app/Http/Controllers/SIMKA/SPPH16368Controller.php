<?php

namespace App\Http\Controllers\SIMKA;

use App\Models\KategoriPendapatan;
use App\Models\Spph16368AdjustmentValue;
use App\Models\Spph16368AsumsiDasar;
use App\Models\Spph16368Bobot;
use App\Models\Spph16368KategoriTarif;
use App\Models\Spph16368KomponenTarif;
use App\Models\Spph16368Tarif;
use App\Services\Simka\Spph16368TarifCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class SPPH16368Controller extends SpphController
{
    protected function spphKey(): string
    {
        return 'spph16368';
    }

    protected function spphTitle(): string
    {
        return 'SPPH 16368';
    }

    /**
     * Interval jarak untuk sub-menu bertipe "interval" (angkutan).
     */
    protected const INTERVAL_JARAK = 250;

    /**
     * Jumlah baris untuk sub-menu bertipe "interval" (angkutan).
     */
    protected const TOTAL_BARIS_JARAK = 40;

    /**
     * Pemetaan field form Bobot -> baris tabel simka_spph16368_bobot.
     *
     * @return array<string, array{0: int, 1: string, 2: string}> field => [kategori_pendapatan_id, name, column]
     */
    protected function bobotFieldMap(): array
    {
        return [
            // Pekerjaan Tanah (kategori_pendapatan_id = 1)
            'bobot_pekerjaan_tanah_bbm_gali' => [1, 'Gali', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_gali' => [1, 'Gali', 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_angkutan' => [1, 'Angkutan', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_angkutan' => [1, 'Angkutan', 'bobot_umsp'],
            'bobot_pekerjaan_tanah_bbm_sebar' => [1, 'Sebar', 'bobot_bbm'],
            'bobot_pekerjaan_tanah_umsp_sebar' => [1, 'Sebar', 'bobot_umsp'],
            // Sewa Alat Berat & Dump Truck untuk Coal Getting (kategori_pendapatan_id = 2)
            'bobot_coal_getting_bbm_bulldozer_525_hp' => [2, 'Bulldozer 525 HP atau setara', 'bobot_bbm'],
            'bobot_coal_getting_umsp_bulldozer_525_hp' => [2, 'Bulldozer 525 HP atau setara', 'bobot_umsp'],
            'bobot_coal_getting_bbm_bulldozer_225_hp' => [2, 'Bulldozer 225 HP atau setara', 'bobot_bbm'],
            'bobot_coal_getting_umsp_bulldozer_225_hp' => [2, 'Bulldozer 225 HP atau setara', 'bobot_umsp'],
            'bobot_coal_getting_bbm_excavator_3_2_cum' => [2, 'Excavator 3,2 Cum atau setara', 'bobot_bbm'],
            'bobot_coal_getting_umsp_excavator_3_2_cum' => [2, 'Excavator 3,2 Cum atau setara', 'bobot_umsp'],
            'bobot_coal_getting_bbm_excavator_0_9_cum' => [2, 'Excavator 0,9 Cum atau setara', 'bobot_bbm'],
            'bobot_coal_getting_umsp_excavator_0_9_cum' => [2, 'Excavator 0,9 Cum atau setara', 'bobot_umsp'],
            'bobot_coal_getting_bbm_dump_truck_30_ton' => [2, 'Dump Truck Kap 30 Ton (2,61 Km)', 'bobot_bbm'],
            'bobot_coal_getting_umsp_dump_truck_30_ton' => [2, 'Dump Truck Kap 30 Ton (2,61 Km)', 'bobot_umsp'],
        ];
    }

    /**
     * Pemetaan field form Komponen Tarif -> baris tabel simka_spph16368_komponen_tarif.
     *
     * @return array<string, array{0: int, 1: string}> field => [kategori_pendapatan_id, column]
     */
    protected function komponenFieldMap(): array
    {
        return [
            'komponen_tarif_usd_pekerjaan_tanah' => [1, 'usd'],
            'komponen_tarif_rupiah_pekerjaan_tanah' => [1, 'rupiah'],
            'komponen_tarif_usd_pekerjaan_batubara' => [2, 'usd'],
            'komponen_tarif_rupiah_pekerjaan_batubara' => [2, 'rupiah'],
        ];
    }

    /**
     * Pemetaan field form Asumsi Dasar -> baris tabel simka_spph16368_asumsi_dasar.
     *
     * @return array<string, array{0: string, 1: string}> field => [name, column]
     */
    protected function asumsiFieldMap(): array
    {
        return [
            'harga_acuan_bbm_dasar' => ['BBM Dasar', 'harga_acuan'],
            'harga_aktual_bbm_dasar' => ['BBM Dasar', 'harga_aktual'],
            'harga_acuan_umk_dasar' => ['UMK Dasar', 'harga_acuan'],
            'harga_aktual_umk_dasar' => ['UMK Dasar', 'harga_aktual'],
            'harga_acuan_kurs_dasar' => ['Kurs Dasar', 'harga_acuan'],
            'harga_aktual_kurs_dasar' => ['Kurs Dasar', 'harga_aktual'],
        ];
    }

    /**
     * Field mata uang (Rupiah). Pemisah ribuan (titik) dibersihkan sebelum validasi.
     *
     * @return array<int, string>
     */
    protected function currencyFields(): array
    {
        return [
            'harga_acuan_bbm_dasar',
            'harga_acuan_umk_dasar',
            'harga_acuan_kurs_dasar',
            'harga_aktual_bbm_dasar',
            'harga_aktual_umk_dasar',
            'harga_aktual_kurs_dasar',
        ];
    }

    /**
     * Tampilkan halaman konfigurasi data SPPH 16368 dengan data yang sudah ada di database.
     */
    public function konfigurasi()
    {
        $kategoriPendapatan = KategoriPendapatan::orderBy('id')->get()->keyBy('id');

        // Ambil data bobot, grouped by kategori_pendapatan_id lalu name
        $bobot = Spph16368Bobot::orderBy('kategori_pendapatan_id')->orderBy('name')->get();
        $bobotByKategori = [];
        foreach ($bobot as $b) {
            $bobotByKategori[$b->kategori_pendapatan_id][$b->name] = $b;
        }

        // Ambil data komponen tarif, grouped by kategori_pendapatan_id
        $komponenTarif = Spph16368KomponenTarif::orderBy('kategori_pendapatan_id')->get()->keyBy('kategori_pendapatan_id');

        // Ambil data asumsi dasar, grouped by name
        $asumsiDasar = Spph16368AsumsiDasar::orderBy('id')->get()->keyBy('name');

        // Siapkan nilai field untuk pre-populasi form (flat map: name_field => nilai)
        $fieldValues = [];

        // --- Bobot field mapping ---
        foreach ($this->bobotFieldMap() as $fieldName => [$kategoriId, $name, $column]) {
            $record = $bobotByKategori[$kategoriId][$name] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Komponen Tarif field mapping ---
        foreach ($this->komponenFieldMap() as $fieldName => [$kategoriId, $column]) {
            $record = $komponenTarif[$kategoriId] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Asumsi Dasar field mapping ---
        foreach ($this->asumsiFieldMap() as $fieldName => [$name, $column]) {
            $record = $asumsiDasar[$name] ?? null;
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Nilai Adjustment (tabel tersendiri) ---
        $adjustmentValue = Spph16368AdjustmentValue::first();
        $fieldValues['nilai_adjustment'] = $adjustmentValue?->value !== null ? number_format($adjustmentValue->value, 2, '.', '') : '';

        return view("simka.{$this->spphKey()}.konfigurasi", $this->viewData() + [
            'pageTitle' => 'Konfigurasi Data',
            'pageIcon' => 'mdi-database-cog-outline',
            'pageColor' => 'primary',
            'pageDescription' => "Kelola master data dan parameter {$this->spphTitle()}.",
            'kategoriPendapatan' => $kategoriPendapatan,
            'bobotByKategori' => $bobotByKategori,
            'komponenTarif' => $komponenTarif,
            'asumsiDasar' => $asumsiDasar,
            'fieldValues' => $fieldValues,
            // Status ketersediaan data per bagian, dipakai untuk menampilkan tombol hapus.
            'hasData' => [
                'adjustment' => $adjustmentValue !== null,
                'bobot' => $bobot->isNotEmpty(),
                'komponen-tarif' => $komponenTarif->isNotEmpty(),
                'asumsi-dasar' => $asumsiDasar->isNotEmpty(),
            ],
        ]);
    }

    /**
     * Simpan konfigurasi data SPPH 16368 ke database dengan relasi kategori pendapatan.
     */
    public function simpanKonfigurasi(Request $request)
    {
        $input = $request->except(['_token', '_method']);

        // Sanitize HANYA input mata uang (Rp): hapus titik pemisah ribuan.
        // Contoh: "14.000" -> "14000". Jangan hapus titik pada input persen (contoh "37.00" -> tetap "37.00").
        $currencyFields = $this->currencyFields();
        foreach ($input as $key => $value) {
            if (is_string($value) && in_array($key, $currencyFields, true)) {
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
        // A. SIMPAN DATA BOBOT (simka_spph16368_bobot)
        // ================================================================
        $bobotRecords = [];
        foreach ($this->bobotFieldMap() as $fieldName => [$kategoriId, $name, $column]) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            $key = $kategoriId . '|' . $name;
            if (!isset($bobotRecords[$key])) {
                $bobotRecords[$key] = [
                    'kategori_pendapatan_id' => $kategoriId,
                    'name' => $name,
                ];
            }
            $bobotRecords[$key][$column] = $input[$fieldName];
        }

        foreach ($bobotRecords as $record) {
            Spph16368Bobot::updateOrCreate(
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

        // Hapus baris bobot batubara lama yang tidak lagi ada pada form
        // (mis. Gali/Angkutan sebelum diganti Sewa Alat Berat & Dump Truck untuk Coal Getting).
        $bobotBatubaraNames = [];
        foreach ($this->bobotFieldMap() as [$kategoriId, $name]) {
            if ($kategoriId === 2) {
                $bobotBatubaraNames[$name] = true;
            }
        }
        if ($bobotBatubaraNames !== []) {
            Spph16368Bobot::query()
                ->where('kategori_pendapatan_id', 2)
                ->whereNotIn('name', array_keys($bobotBatubaraNames))
                ->delete();
        }

        // ================================================================
        // B. SIMPAN DATA KOMPONEN TARIF (simka_spph16368_komponen_tarif)
        // ================================================================
        $komponenRecords = [];
        foreach ($this->komponenFieldMap() as $fieldName => [$kategoriId, $column]) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            if (!isset($komponenRecords[$kategoriId])) {
                $komponenRecords[$kategoriId] = ['kategori_pendapatan_id' => $kategoriId];
            }
            $komponenRecords[$kategoriId][$column] = $input[$fieldName];
        }

        foreach ($komponenRecords as $record) {
            Spph16368KomponenTarif::updateOrCreate(
                ['kategori_pendapatan_id' => $record['kategori_pendapatan_id']],
                [
                    'usd' => $record['usd'] ?? 0,
                    'rupiah' => $record['rupiah'] ?? 0,
                ]
            );
        }

        // ================================================================
        // C. SIMPAN DATA ASUMSI DASAR (simka_spph16368_asumsi_dasar)
        // ================================================================
        $asumsiRecords = [];
        foreach ($this->asumsiFieldMap() as $fieldName => [$name, $column]) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            if (!isset($asumsiRecords[$name])) {
                $asumsiRecords[$name] = ['name' => $name];
            }
            $asumsiRecords[$name][$column] = $input[$fieldName];
        }

        foreach ($asumsiRecords as $record) {
            Spph16368AsumsiDasar::updateOrCreate(
                ['name' => $record['name']],
                [
                    'harga_acuan' => $record['harga_acuan'] ?? 0,
                    'harga_aktual' => $record['harga_aktual'] ?? 0,
                ]
            );
        }

        // ================================================================
        // D. SIMPAN DATA NILAI ADJUSTMENT (simka_spph16368_adjustment_value)
        // ================================================================
        if (array_key_exists('nilai_adjustment', $input)) {
            Spph16368AdjustmentValue::updateOrCreate(
                ['id' => 1],
                ['value' => $input['nilai_adjustment']]
            );
        }

        return redirect()
            ->route("simka.{$this->spphKey()}.konfigurasi")
            ->with('success', "Konfigurasi data {$this->spphTitle()} berhasil disimpan.");
    }

    /**
     * Hapus data konfigurasi SPPH 16368 berdasarkan bagian (tabel) yang dipilih.
     *
     * Bagian yang didukung:
     * - adjustment     -> simka_spph16368_adjustment_value
     * - bobot          -> simka_spph16368_bobot
     * - komponen-tarif -> simka_spph16368_komponen_tarif
     * - asumsi-dasar   -> simka_spph16368_asumsi_dasar
     * - semua          -> seluruh tabel konfigurasi di atas
     */
    public function hapusKonfigurasi(string $bagian)
    {
        $bagian = strtolower($bagian);

        $bagianMap = [
            'adjustment' => [
                'label' => 'Nilai Adjustment',
                'hapus' => fn () => Spph16368AdjustmentValue::query()->delete(),
            ],
            'bobot' => [
                'label' => 'Bobot',
                'hapus' => fn () => Spph16368Bobot::query()->delete(),
            ],
            'komponen-tarif' => [
                'label' => 'Komponen Tarif',
                'hapus' => fn () => Spph16368KomponenTarif::query()->delete(),
            ],
            'asumsi-dasar' => [
                'label' => 'Asumsi Dasar',
                'hapus' => fn () => Spph16368AsumsiDasar::query()->delete(),
            ],
            'semua' => [
                'label' => 'Seluruh Konfigurasi',
                'hapus' => function () {
                    Spph16368AdjustmentValue::query()->delete();
                    Spph16368Bobot::query()->delete();
                    Spph16368KomponenTarif::query()->delete();
                    Spph16368AsumsiDasar::query()->delete();
                },
            ],
        ];

        abort_unless(array_key_exists($bagian, $bagianMap), 404);

        ($bagianMap[$bagian]['hapus'])();

        return redirect()
            ->route("simka.{$this->spphKey()}.konfigurasi")
            ->with('success', "Data {$bagianMap[$bagian]['label']} {$this->spphTitle()} berhasil dihapus.");
    }

    // ================================================================
    // KALKULASI SUB-MENUS (CRUD + kalkulasi)
    //
    // SPPH 16368 memiliki tabel kategori tarif sendiri
    // (simka_spph16368_kategori_tarif), sehingga baris kategori tarif
    // diresolusi dari pasangan (name, kategori_pendapatan_id) dan dibuat
    // otomatis saat pertama kali tarif disimpan.
    // ================================================================

    /**
     * Konfigurasi seluruh sub-menu kalkulasi SPPH 16368.
     *
     * Kunci array = slug sub-menu (bagian nama route setelah "simka.spph16368.").
     *
     * - kategori_pendapatan_id : kategori pekerjaan (1 = tanah, 2 = batubara).
     * - kategori_tarif_name    : name baris simka_spph16368_kategori_tarif.
     * - view                   : blade view halaman sub-menu.
     * - field                  : nama input tarif dasar pada form.
     * - field_label            : label tarif dasar (untuk pesan validasi & error).
     * - label                  : label sub-menu (untuk pesan sukses).
     * - mode                   : "single" (1 baris) atau "interval" (per rentang jarak).
     * - kalkulasi              : method kalkulator yang dipakai.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function subMenus(): array
    {
        return [
            'tarif-gali-tanah' => [
                'kategori_pendapatan_id' => 1,
                'kategori_tarif_name' => 'Tarif Gali',
                'page_title' => 'Tarif Gali - Pekerjaan Tanah',
                'icon' => 'mdi-pickaxe',
                'color' => 'primary',
                'description' => 'Kelola tarif gali untuk pekerjaan tanah.',
                'view' => 'simka.spph16368.tarif-gali-tanah',
                'field' => 'tarif_gali_dasar',
                'field_label' => 'Tarif Gali Dasar',
                'label' => 'Tarif gali pekerjaan tanah',
                'mode' => 'single',
                'kalkulasi' => 'hitungGaliTanah',
            ],
            'tarif-angkutan-tanah' => [
                'kategori_pendapatan_id' => 1,
                'kategori_tarif_name' => 'Tarif Angkut',
                'page_title' => 'Tarif Angkutan - Pekerjaan Tanah',
                'icon' => 'mdi-truck',
                'color' => 'info',
                'description' => 'Kelola tarif angkutan untuk pekerjaan tanah.',
                'view' => 'simka.spph16368.tarif-angkutan-tanah',
                'field' => 'tarif_angkut_dasar',
                'field_label' => 'Tarif Angkut Dasar',
                'label' => 'Tarif angkutan pekerjaan tanah',
                'mode' => 'interval',
                'kalkulasi' => 'hitungAngkutanTanah',
            ],
            'tarif-sebar-tanah' => [
                'kategori_pendapatan_id' => 1,
                'kategori_tarif_name' => 'Tarif Sebar',
                'page_title' => 'Tarif Sebar - Pekerjaan Tanah',
                'icon' => 'mdi-food',
                'color' => 'success',
                'description' => 'Kelola tarif sebar untuk pekerjaan tanah.',
                'view' => 'simka.spph16368.tarif-sebar-tanah',
                'field' => 'tarif_sebar_dasar',
                'field_label' => 'Tarif Sebar Dasar',
                'label' => 'Tarif sebar pekerjaan tanah',
                'mode' => 'single',
                'kalkulasi' => 'hitungSebarTanah',
            ],
            'tarif-sewa-alat-berat' => [
                'kategori_pendapatan_id' => 2,
                'kategori_tarif_name' => 'Tarif Sewa Alat Berat',
                'page_title' => 'Tarif Dasar Sewa Alat Berat',
                'icon' => 'mdi-bulldozer',
                'color' => 'warning',
                'description' => 'Kelola tarif dasar sewa alat berat.',
                'view' => 'simka.spph16368.tarif-sewa-alat-berat',
                'field' => 'tarif_sewa_alat_berat_dasar',
                'field_label' => 'Tarif Dasar Sewa Alat Berat',
                'label' => 'tarif dasar sewa alat berat',
                'mode' => 'single',
                'kalkulasi' => null,
            ],
            'tarif-jasa-sewa-angkutan-batubara' => [
                'kategori_pendapatan_id' => 2,
                'kategori_tarif_name' => 'Tarif Jasa Sewa Angkutan Batubara',
                'page_title' => 'Tarif Dasar Jasa Sewa Angkutan Batu Bara',
                'icon' => 'mdi-dump-truck',
                'color' => 'warning',
                'description' => 'Kelola tarif dasar jasa sewa angkutan batu bara.',
                'view' => 'simka.spph16368.tarif-jasa-sewa-angkutan-batubara',
                'field' => 'tarif_jasa_sewa_angkutan_batubara_dasar',
                'field_label' => 'Tarif Dasar Jasa Sewa Angkutan',
                'label' => 'tarif dasar jasa sewa angkutan batu bara',
                'mode' => 'interval',
                'kalkulasi' => null,
            ],
            'tarif-gali-batubara' => [
                'kategori_pendapatan_id' => 2,
                'kategori_tarif_name' => 'Tarif Gali',
                'page_title' => 'Tarif Gali - Pekerjaan Batubara',
                'icon' => 'mdi-pickaxe',
                'color' => 'warning',
                'description' => 'Kelola tarif gali untuk pekerjaan batubara.',
                'view' => 'simka.spph16368.tarif-gali-batubara',
                'field' => 'tarif_gali_dasar',
                'field_label' => 'Tarif Gali Dasar',
                'label' => 'Tarif gali pekerjaan batubara',
                'mode' => 'single',
                'kalkulasi' => 'hitungGaliBatubara',
            ],
            'tarif-angkutan-batubara' => [
                'kategori_pendapatan_id' => 2,
                'kategori_tarif_name' => 'Tarif Angkut',
                'page_title' => 'Tarif Angkutan - Pekerjaan Batubara',
                'icon' => 'mdi-truck',
                'color' => 'info',
                'description' => 'Kelola tarif angkutan untuk pekerjaan batubara.',
                'view' => 'simka.spph16368.tarif-angkutan-batubara',
                'field' => 'tarif_angkut_dasar',
                'field_label' => 'Tarif Angkut Dasar',
                'label' => 'Tarif angkutan pekerjaan batubara',
                'mode' => 'interval',
                'kalkulasi' => 'hitungAngkutanBatubara',
            ],
        ];
    }

    /**
     * Resolusi slug sub-menu dari nama route yang sedang aktif.
     *
     * Contoh: "simka.spph16368.tarif-gali-tanah.simpan" -> "tarif-gali-tanah".
     */
    protected function subMenuKey(): string
    {
        $routeName = (string) Route::currentRouteName();

        $key = (string) preg_replace('/^simka\.spph16368\./', '', $routeName);
        $key = (string) preg_replace('/\.(simpan|hapus|kalkulasi)$/', '', $key);

        if (!array_key_exists($key, $this->subMenus())) {
            throw new RuntimeException("Sub-menu tarif \"{$key}\" tidak dikenali.");
        }

        return $key;
    }

    /**
     * @return array<string, mixed>
     */
    protected function subMenu(string $key): array
    {
        return $this->subMenus()[$key];
    }

    /**
     * Nama route halaman sub-menu.
     */
    protected function subMenuRoute(string $key, string $suffix = ''): string
    {
        return "simka.{$this->spphKey()}.{$key}{$suffix}";
    }

    /**
     * Resolusi id kategori tarif SPPH 16368 berdasarkan name & kategori pendapatan.
     *
     * Baris kategori tarif dibuat otomatis saat $create = true (dipakai ketika
     * menyimpan tarif baru), karena tabel simka_spph16368_kategori_tarif khusus
     * untuk SPPH 16368 dan belum tentu terisi.
     *
     * @param  array<string, mixed>  $sub
     */
    protected function kategoriTarifId(array $sub, bool $create = false): ?int
    {
        $attributes = [
            'name' => $sub['kategori_tarif_name'],
            'kategori_pendapatan_id' => $sub['kategori_pendapatan_id'],
        ];

        $kategoriTarif = $create
            ? Spph16368KategoriTarif::firstOrCreate($attributes)
            : Spph16368KategoriTarif::query()->where($attributes)->first();

        return $kategoriTarif?->id;
    }

    /**
     * Tampilkan halaman sub-menu tarif beserta data yang sudah tersimpan.
     */
    public function tarifSubMenu()
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        $kategoriTarifId = $this->kategoriTarifId($sub);

        $records = $kategoriTarifId === null
            ? collect()
            : Spph16368Tarif::query()
                ->where('kategori_tarif_id', $kategoriTarifId)
                ->orderBy('jarak_awal')
                ->orderBy('id')
                ->get();

        return view($sub['view'], $this->viewData() + [
            'pageTitle' => $sub['page_title'],
            'pageIcon' => $sub['icon'],
            'pageColor' => $sub['color'],
            'pageDescription' => $sub['description'],
            'tarifRows' => $this->susunTarifRows($sub, $records),
            'hasData' => $records->isNotEmpty(),
        ]);
    }

    /**
     * Simpan tarif dasar sub-menu yang sedang aktif (satu baris atau per baris jarak).
     */
    public function simpanTarif(Request $request)
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        return $sub['mode'] === 'interval'
            ? $this->simpanTarifInterval($request, $key, $sub)
            : $this->simpanTarifSatuBaris($request, $key, $sub);
    }

    /**
     * Hapus seluruh tarif pada sub-menu yang sedang aktif.
     */
    public function hapusTarif()
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        $kategoriTarifId = $this->kategoriTarifId($sub);

        if ($kategoriTarifId !== null) {
            Spph16368Tarif::query()
                ->where('kategori_tarif_id', $kategoriTarifId)
                ->delete();
        }

        return redirect()
            ->route($this->subMenuRoute($key))
            ->with('success', ucfirst($sub['label']) . ' berhasil dihapus.');
    }

    /**
     * Kalkulasi Tarif Adjustment & Ekskalasi pada sub-menu yang sedang aktif, lalu simpan hasilnya.
     *
     * Mengharuskan Tarif Dasar sudah tersimpan sebelumnya. Seluruh baris dihitung
     * terlebih dahulu; bila ada konfigurasi yang belum lengkap, tidak ada yang disimpan.
     */
    public function kalkulasiTarif(Spph16368TarifCalculator $calculator)
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        // Sub-menu tanpa formula kalkulasi (mis. Tarif Dasar Sewa Alat Berat &
        // Tarif Dasar Jasa Sewa Angkutan Batu Bara) belum dapat dikalkulasi.
        if (empty($sub['kalkulasi'])) {
            return redirect()
                ->route($this->subMenuRoute($key))
                ->with('error', "Fitur kalkulasi {$sub['label']} belum tersedia.");
        }

        $kategoriTarifId = $this->kategoriTarifId($sub);

        $records = $kategoriTarifId === null
            ? collect()
            : Spph16368Tarif::query()
                ->where('kategori_tarif_id', $kategoriTarifId)
                ->whereNotNull('tarif_dasar')
                ->orderBy('jarak_awal')
                ->orderBy('id')
                ->get();

        if ($records->isEmpty()) {
            return redirect()
                ->route($this->subMenuRoute($key))
                ->with('error', "Simpan {$sub['field_label']} terlebih dahulu sebelum melakukan kalkulasi.");
        }

        try {
            $hasil = [];
            foreach ($records as $record) {
                $hasil[$record->id] = $calculator->{$sub['kalkulasi']}((float) $record->tarif_dasar);
            }
        } catch (RuntimeException $exception) {
            return redirect()
                ->route($this->subMenuRoute($key))
                ->with('error', $exception->getMessage());
        }

        foreach ($records as $record) {
            $record->update([
                'tarif_adjustment' => $hasil[$record->id]['tarif_adjustment'],
                'tarif_ekskalasi' => $hasil[$record->id]['tarif_ekskalasi'],
            ]);
        }

        return redirect()
            ->route($this->subMenuRoute($key))
            ->with('success', "Kalkulasi {$sub['label']} berhasil dan hasilnya telah disimpan.");
    }

    /**
     * Simpan tarif dasar untuk sub-menu satu baris (tanpa dimensi jarak).
     *
     * @param  array<string, mixed>  $sub
     */
    protected function simpanTarifSatuBaris(Request $request, string $key, array $sub)
    {
        $field = $sub['field'];

        // Hilangkan pemisah ribuan pada input Rupiah sebelum validasi (contoh: "14.000" -> "14000").
        $nilai = str_replace('.', '', (string) $request->input($field, ''));
        $request->merge([$field => $nilai]);

        $request->validate(
            [$field => ['required', 'numeric', 'min:0']],
            [],
            [$field => $sub['field_label']]
        );

        $kategoriTarifId = $this->kategoriTarifId($sub, true);

        Spph16368Tarif::updateOrCreate(
            ['kategori_tarif_id' => $kategoriTarifId],
            ['tarif_dasar' => $nilai]
        );

        return redirect()
            ->route($this->subMenuRoute($key))
            ->with('success', ucfirst($sub['label']) . ' berhasil disimpan.');
    }

    /**
     * Simpan tarif dasar per baris jarak untuk sub-menu bertipe interval.
     *
     * Input berupa array field[row] => nilai. Setiap baris dipetakan ke rentang jarak
     * sesuai interval (Row 1 -> 0-250, Row 2 -> 251-500, dst.). Baris kosong dilewati.
     *
     * @param  array<string, mixed>  $sub
     */
    protected function simpanTarifInterval(Request $request, string $key, array $sub)
    {
        $field = $sub['field'];
        $raw = (array) $request->input($field, []);

        // Sanitize (hilangkan pemisah ribuan) & ambil hanya baris yang memiliki nilai.
        $nilaiPerBaris = [];
        foreach ($raw as $row => $value) {
            $row = (int) $row;
            $nilai = str_replace('.', '', trim((string) $value));

            if ($row < 1 || $row > self::TOTAL_BARIS_JARAK || $nilai === '') {
                continue;
            }

            $nilaiPerBaris[$row] = $nilai;
        }

        if ($nilaiPerBaris === []) {
            return redirect()
                ->route($this->subMenuRoute($key))
                ->with('error', "Isi minimal satu baris {$sub['field_label']}.");
        }

        $request->merge([$field => $nilaiPerBaris]);

        $rules = [];
        $attributes = [];
        foreach (array_keys($nilaiPerBaris) as $row) {
            $rules["{$field}.{$row}"] = ['required', 'numeric', 'min:0'];
            $attributes["{$field}.{$row}"] = "{$sub['field_label']} baris {$row}";
        }
        $request->validate($rules, [], $attributes);

        $kategoriTarifId = $this->kategoriTarifId($sub, true);

        foreach ($nilaiPerBaris as $row => $nilai) {
            [$jarakAwal, $jarakAkhir] = $this->rentangJarak($row);

            Spph16368Tarif::updateOrCreate(
                [
                    'kategori_tarif_id' => $kategoriTarifId,
                    'jarak_awal' => $jarakAwal,
                    'jarak_akhir' => $jarakAkhir,
                ],
                ['tarif_dasar' => $nilai]
            );
        }

        return redirect()
            ->route($this->subMenuRoute($key))
            ->with('success', ucfirst($sub['label']) . ' berhasil disimpan.');
    }

    /**
     * Susun baris tarif yang siap ditampilkan pada view.
     *
     * @param  array<string, mixed>  $sub
     * @param  \Illuminate\Support\Collection<int, Spph16368Tarif>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function susunTarifRows(array $sub, $records): array
    {
        if ($sub['mode'] === 'interval') {
            $saved = $records->keyBy(function (Spph16368Tarif $tarif) {
                return $tarif->jarak_awal . '-' . $tarif->jarak_akhir;
            });

            // Susun 40 baris dengan Jarak Awal & Jarak Akhir default/fixed.
            // Row 1 -> 0 - 250, Row 2 -> 251 - 500, ... Row 40 -> 9.751 - 10.000.
            $tarifRows = [];
            for ($i = 1; $i <= self::TOTAL_BARIS_JARAK; $i++) {
                [$jarakAwal, $jarakAkhir] = $this->rentangJarak($i);
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

            return $tarifRows;
        }

        // Sub-menu satu baris (tanpa dimensi jarak).
        $record = $records->first();

        return [[
            'row' => 1,
            'tarif_dasar' => $record?->tarif_dasar,
            'tarif_adjustment' => $record?->tarif_adjustment,
            'tarif_ekskalasi' => $record?->tarif_ekskalasi,
        ]];
    }

    /**
     * Rentang jarak untuk baris ke-$row.
     *
     * Row 1 -> 0 - 250, Row 2 -> 251 - 500, ... Row 40 -> 9.751 - 10.000.
     *
     * @return array{0:int, 1:int}
     */
    protected function rentangJarak(int $row): array
    {
        return [
            $row === 1 ? 0 : (($row - 1) * self::INTERVAL_JARAK) + 1,
            $row * self::INTERVAL_JARAK,
        ];
    }

    // ================================================================
    // TARIF TOTAL (view only - logika kalkulasi belum tersedia)
    //
    // Tarif Total adalah penjumlahan seluruh komponen tarif pada satu section
    // untuk setiap rentang jarak. Halaman disiapkan sebagai view (kerangka)
    // dengan 40 baris rentang jarak; logika kalkulasi & penyimpanan ke tabel
    // simka_spph16368_tarif_total belum diimplementasikan.
    // ================================================================

    /**
     * Tarif Total - Pekerjaan Tanah.
     */
    public function tarifTotalTanah()
    {
        return view("simka.{$this->spphKey()}.tarif-total-tanah", $this->viewData() + [
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
        return view("simka.{$this->spphKey()}.tarif-total-batubara", $this->viewData() + [
            'pageTitle' => 'Tarif Total - Pekerjaan Batubara',
            'pageIcon' => 'mdi-sigma',
            'pageColor' => 'warning',
            'pageDescription' => 'Tinjau total tarif pekerjaan batubara per rentang jarak.',
        ]);
    }

    /**
     * Placeholder aksi (Hapus / Kalkulasi) halaman Tarif Total SPPH 16368.
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
