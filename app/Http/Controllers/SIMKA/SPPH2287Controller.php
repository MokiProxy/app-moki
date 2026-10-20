<?php

namespace App\Http\Controllers\SIMKA;

use App\Models\Spph2287AdjustmentValue;
use App\Models\Spph2287AsumsiDasar;
use App\Models\Spph2287Bobot;
use App\Models\Spph2287Tarif;
use App\Services\Simka\Spph2287TarifCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class SPPH2287Controller extends SpphController
{
    protected function spphKey(): string
    {
        return 'spph2287';
    }

    protected function spphTitle(): string
    {
        return 'SPPH 2287';
    }

    /**
     * Pemetaan field form Bobot -> baris tabel simka_spph2287_bobot.
     *
     * @return array<string, array{0: string, 1: string}> field => [name, column]
     */
    protected function bobotFieldMap(): array
    {
        return [
            'bobot_bbm' => ['BBM', 'value'],
            'bobot_umk' => ['UMK', 'value'],
        ];
    }

    /**
     * Pemetaan field form Asumsi Dasar -> baris tabel simka_spph2287_asumsi_dasar.
     *
     * @return array<string, array{0: string, 1: string}> field => [name, column]
     */
    protected function asumsiFieldMap(): array
    {
        return [
            'harga_bbm_acuan' => ['Harga BBM', 'harga_acuan'],
            'harga_bbm_aktual' => ['Harga BBM', 'harga_aktual'],
            'umsk_acuan' => ['UMSK', 'harga_acuan'],
            'umsk_aktual' => ['UMSK', 'harga_aktual'],
        ];
    }

    /**
     * Field mata uang (Rupiah). Pemisah ribuan (titik) dibersihkan sebelum validasi.
     *
     * @return array<int, string>
     */
    protected function currencyFields(): array
    {
        return ['harga_bbm_acuan', 'harga_bbm_aktual', 'umsk_acuan', 'umsk_aktual'];
    }

    /**
     * Tampilkan halaman konfigurasi data SPPH 2287 dengan data yang sudah ada di database.
     */
    public function konfigurasi()
    {
        // Siapkan nilai field untuk pre-populasi form (flat map: name_field => nilai)
        $fieldValues = [];

        // --- Bobot (simka_spph2287_bobot) : nilai persen, 2 angka di belakang koma ---
        $bobot = Spph2287Bobot::orderBy('id')->get()->keyBy('name');
        foreach ($this->bobotFieldMap() as $fieldName => [$name, $column]) {
            $record = $bobot->get($name);
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 2, '.', '') : '';
        }

        // --- Asumsi Dasar (simka_spph2287_asumsi_dasar) : Rupiah tanpa desimal ---
        $asumsiDasar = Spph2287AsumsiDasar::orderBy('id')->get()->keyBy('name');
        foreach ($this->asumsiFieldMap() as $fieldName => [$name, $column]) {
            $record = $asumsiDasar->get($name);
            $fieldValues[$fieldName] = $record?->{$column} !== null ? number_format($record->{$column}, 0, '.', '') : '';
        }

        // --- Nilai Adjustment (simka_spph2287_adjustment_value) : persen, 2 angka di belakang koma ---
        $adjustmentValue = Spph2287AdjustmentValue::first();
        $fieldValues['nilai_adjustment'] = $adjustmentValue?->value !== null ? number_format($adjustmentValue->value, 2, '.', '') : '';

        return view("simka.{$this->spphKey()}.konfigurasi", $this->viewData() + [
            'pageTitle' => 'Konfigurasi Data',
            'pageIcon' => 'mdi-database-cog-outline',
            'pageColor' => 'primary',
            'pageDescription' => "Kelola master data dan parameter {$this->spphTitle()}.",
            'bobot' => $bobot,
            'asumsiDasar' => $asumsiDasar,
            'adjustmentValue' => $adjustmentValue,
            'fieldValues' => $fieldValues,
        ]);
    }

    /**
     * Simpan konfigurasi data SPPH 2287 ke database.
     */
    public function simpanKonfigurasi(Request $request)
    {
        $input = $request->except(['_token', '_method']);

        // Sanitize HANYA input mata uang (Rp): hapus titik pemisah ribuan.
        // Contoh: "13.800" -> "13800". Jangan hapus titik pada input persen (contoh "33.00" -> tetap "33.00").
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
        // A. SIMPAN DATA BOBOT (simka_spph2287_bobot)
        // ================================================================
        foreach ($this->bobotFieldMap() as $fieldName => [$name, $column]) {
            if (!array_key_exists($fieldName, $input)) {
                continue;
            }
            Spph2287Bobot::updateOrCreate(
                ['name' => $name],
                [$column => $input[$fieldName]]
            );
        }

        // ================================================================
        // B. SIMPAN DATA ASUMSI DASAR (simka_spph2287_asumsi_dasar)
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
            Spph2287AsumsiDasar::updateOrCreate(
                ['name' => $record['name']],
                [
                    'harga_acuan' => $record['harga_acuan'] ?? 0,
                    'harga_aktual' => $record['harga_aktual'] ?? 0,
                ]
            );
        }

        // ================================================================
        // C. SIMPAN DATA NILAI ADJUSTMENT (simka_spph2287_adjustment_value)
        // ================================================================
        if (array_key_exists('nilai_adjustment', $input)) {
            Spph2287AdjustmentValue::updateOrCreate(
                ['id' => 1],
                ['value' => $input['nilai_adjustment']]
            );
        }

        return redirect()
            ->route('simka.spph2287.konfigurasi')
            ->with('success', 'Konfigurasi data SPPH 2287 berhasil disimpan.');
    }

    // ================================================================
    // KALKULASI SUB-MENUS (CRUD + kalkulasi)
    //
    // Seluruh sub-menu memakai SATU formula yang sama
    // (lihat App\Services\Simka\Spph2287TarifCalculator), sehingga aksi
    // simpan/hapus/kalkulasi ditangani oleh tiga aksi generik berikut dan
    // sub-menu yang sedang aktif dikenali dari nama route.
    // ================================================================

    /**
     * Interval jarak untuk sub-menu bertipe "interval" (angkutan).
     */
    protected const INTERVAL_JARAK = 250;

    /**
     * Jumlah baris untuk sub-menu bertipe "interval" (angkutan).
     */
    protected const TOTAL_BARIS_JARAK = 40;

    /**
     * Konfigurasi seluruh sub-menu kalkulasi SPPH 2287.
     *
     * Kunci array = slug sub-menu (bagian nama route setelah "simka.spph2287.").
     *
     * - kategori_tarif_id : baris simka_kategori_tarif tempat menyimpan tarif.
     * - view              : blade view halaman sub-menu.
     * - field             : nama input tarif dasar pada form.
     * - field_label       : label tarif dasar (untuk pesan validasi & error).
     * - label             : label sub-menu (untuk pesan sukses).
     * - mode              : "single" (1 baris) atau "interval" (per rentang jarak).
     *
     * @return array<string, array<string, mixed>>
     */
    protected function subMenus(): array
    {
        return [
            'tarif-gali-tanah' => [
                'kategori_tarif_id' => 1,
                'page_title' => 'Tarif Gali - Pekerjaan Tanah',
                'icon' => 'mdi-pickaxe',
                'color' => 'primary',
                'description' => 'Kelola tarif gali untuk pekerjaan tanah.',
                'view' => 'simka.spph2287.tarif-gali-tanah',
                'field' => 'tarif_gali_dasar',
                'field_label' => 'Tarif Gali Dasar',
                'label' => 'Tarif gali pekerjaan tanah',
                'mode' => 'single',
            ],
            'tarif-angkutan-tanah' => [
                'kategori_tarif_id' => 2,
                'page_title' => 'Tarif Angkutan - Pekerjaan Tanah',
                'icon' => 'mdi-truck',
                'color' => 'info',
                'description' => 'Kelola tarif angkutan untuk pekerjaan tanah.',
                'view' => 'simka.spph2287.tarif-angkutan-tanah',
                'field' => 'tarif_angkut_dasar',
                'field_label' => 'Tarif Angkut Dasar',
                'label' => 'Tarif angkutan pekerjaan tanah',
                'mode' => 'interval',
            ],
            'tarif-sebar-tanah' => [
                'kategori_tarif_id' => 3,
                'page_title' => 'Tarif Sebar - Pekerjaan Tanah',
                'icon' => 'mdi-food',
                'color' => 'success',
                'description' => 'Kelola tarif sebar untuk pekerjaan tanah.',
                'view' => 'simka.spph2287.tarif-sebar-tanah',
                'field' => 'tarif_sebar_dasar',
                'field_label' => 'Tarif Sebar Dasar',
                'label' => 'Tarif sebar pekerjaan tanah',
                'mode' => 'single',
            ],
            'tarif-gali-batubara' => [
                'kategori_tarif_id' => 4,
                'page_title' => 'Tarif Gali - Pekerjaan Batubara',
                'icon' => 'mdi-pickaxe',
                'color' => 'warning',
                'description' => 'Kelola tarif gali untuk pekerjaan batubara.',
                'view' => 'simka.spph2287.tarif-gali-batubara',
                'field' => 'tarif_gali_dasar',
                'field_label' => 'Tarif Gali Dasar',
                'label' => 'Tarif gali pekerjaan batubara',
                'mode' => 'single',
            ],
            'tarif-angkutan-batubara' => [
                'kategori_tarif_id' => 5,
                'page_title' => 'Tarif Angkutan - Pekerjaan Batubara',
                'icon' => 'mdi-truck',
                'color' => 'info',
                'description' => 'Kelola tarif angkutan untuk pekerjaan batubara.',
                'view' => 'simka.spph2287.tarif-angkutan-batubara',
                'field' => 'tarif_angkut_dasar',
                'field_label' => 'Tarif Angkut Dasar',
                'label' => 'Tarif angkutan pekerjaan batubara',
                'mode' => 'interval',
            ],
            'pekerjaan-lingkungan' => [
                'kategori_tarif_id' => 6,
                'page_title' => 'Tarif Pekerjaan Lingkungan',
                'icon' => 'mdi-leaf',
                'color' => 'success',
                'description' => 'Kelola tarif pekerjaan lingkungan.',
                'view' => 'simka.spph2287.tarif-pekerjaan-lingkungan',
                'field' => 'tarif_pekerjaan_lingkungan_dasar',
                'field_label' => 'Tarif Pekerjaan Lingkungan Dasar',
                'label' => 'Tarif pekerjaan lingkungan',
                'mode' => 'single',
            ],
            'reklamasi-pemeliharaan-tanaman' => [
                'kategori_tarif_id' => 7,
                'page_title' => 'Tarif Pekerjaan Reklamasi dan Pemeliharaan Tanaman',
                'icon' => 'mdi-sprout',
                'color' => 'info',
                'description' => 'Kelola tarif reklamasi dan pemeliharaan tanaman.',
                'view' => 'simka.spph2287.tarif-reklamasi-pemeliharaan-tanaman',
                'field' => 'tarif_reklamasi_pemeliharaan_tanaman_dasar',
                'field_label' => 'Tarif Reklamasi dan Pemeliharaan Tanaman Dasar',
                'label' => 'Tarif reklamasi dan pemeliharaan tanaman',
                'mode' => 'single',
            ],
            'management-stockpile' => [
                'kategori_tarif_id' => 8,
                'page_title' => 'Tarif Pekerjaan Management Stockpile',
                'icon' => 'mdi-warehouse',
                'color' => 'primary',
                'description' => 'Kelola tarif management stockpile.',
                'view' => 'simka.spph2287.tarif-management-stockpile',
                'field' => 'tarif_management_stockpile_dasar',
                'field_label' => 'Tarif Management Stockpile Dasar',
                'label' => 'Tarif management stockpile',
                'mode' => 'single',
            ],
            'perawatan-jalan-stockpile-iup' => [
                'kategori_tarif_id' => 9,
                'page_title' => 'Tarif Pekerjaan Perawatan Jalan dari Stockpile keluar IUP',
                'icon' => 'mdi-road-variant',
                'color' => 'danger',
                'description' => 'Kelola tarif perawatan jalan dari stockpile keluar IUP.',
                'view' => 'simka.spph2287.tarif-perawatan-jalan-stockpile-iup',
                'field' => 'tarif_perawatan_jalan_stockpile_iup_dasar',
                'field_label' => 'Tarif Perawatan Jalan Dasar',
                'label' => 'Tarif perawatan jalan dari stockpile keluar IUP',
                'mode' => 'single',
            ],
        ];
    }

    /**
     * Resolusi slug sub-menu dari nama route yang sedang aktif.
     *
     * Contoh: "simka.spph2287.tarif-gali-tanah.simpan" -> "tarif-gali-tanah".
     */
    protected function subMenuKey(): string
    {
        $routeName = (string) Route::currentRouteName();

        $key = (string) preg_replace('/^simka\.spph2287\./', '', $routeName);
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
        return "simka.spph2287.{$key}{$suffix}";
    }

    /**
     * Tampilkan halaman sub-menu tarif beserta data yang sudah tersimpan.
     */
    public function tarifSubMenu()
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        $records = Spph2287Tarif::query()
            ->where('kategori_tarif_id', $sub['kategori_tarif_id'])
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

        Spph2287Tarif::query()
            ->where('kategori_tarif_id', $sub['kategori_tarif_id'])
            ->delete();

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
    public function kalkulasiTarif(Spph2287TarifCalculator $calculator)
    {
        $key = $this->subMenuKey();
        $sub = $this->subMenu($key);

        $records = Spph2287Tarif::query()
            ->where('kategori_tarif_id', $sub['kategori_tarif_id'])
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
                $hasil[$record->id] = $calculator->hitung((float) $record->tarif_dasar);
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

        Spph2287Tarif::updateOrCreate(
            ['kategori_tarif_id' => $sub['kategori_tarif_id']],
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

        foreach ($nilaiPerBaris as $row => $nilai) {
            [$jarakAwal, $jarakAkhir] = $this->rentangJarak($row);

            Spph2287Tarif::updateOrCreate(
                [
                    'kategori_tarif_id' => $sub['kategori_tarif_id'],
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
     * @param  \Illuminate\Support\Collection<int, Spph2287Tarif>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function susunTarifRows(array $sub, $records): array
    {
        if ($sub['mode'] === 'interval') {
            $saved = $records->keyBy(function (Spph2287Tarif $tarif) {
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
    // simka_spph2287_tarif_total belum diimplementasikan.
    // ================================================================

    /**
     * Tarif Total - Pekerjaan Tanah.
     */
    public function tarifTotalTanah()
    {
        return view('simka.spph2287.tarif-total-tanah', $this->viewData() + [
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
        return view('simka.spph2287.tarif-total-batubara', $this->viewData() + [
            'pageTitle' => 'Tarif Total - Pekerjaan Batubara',
            'pageIcon' => 'mdi-sigma',
            'pageColor' => 'warning',
            'pageDescription' => 'Tinjau total tarif pekerjaan batubara per rentang jarak.',
        ]);
    }

    /**
     * Placeholder aksi (Hapus / Kalkulasi) halaman Tarif Total SPPH 2287.
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
