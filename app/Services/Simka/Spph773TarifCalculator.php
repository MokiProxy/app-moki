<?php

namespace App\Services\Simka;

use App\Models\Spph773AdjustmentValue;
use App\Models\Spph773AsumsiDasar;
use App\Models\Spph773Bobot;
use App\Models\Spph773KomponenTarif;
use RuntimeException;

/**
 * Business logic kalkulasi tarif SPPH 773 (Pekerjaan Tanah & Pekerjaan Batubara).
 *
 * Catatan satuan:
 * Nilai bobot, komponen tarif, dan nilai adjustment disimpan sebagai angka
 * persen (contoh: 37.00 berarti 37%). Pada perhitungan nilai tersebut
 * dikonversi ke bentuk fraksi (dibagi 100) agar menghasilkan tarif yang
 * proporsional.
 */
class Spph773TarifCalculator
{
    /**
     * kategori_pendapatan_id untuk Pekerjaan Tanah.
     * Dipakai sebagai acuan baris komponen tarif & bobot pekerjaan tanah.
     */
    public const KATEGORI_PEKERJAAN_TANAH = 1;

    /**
     * kategori_pendapatan_id untuk Pekerjaan Batubara.
     * Dipakai sebagai acuan baris komponen tarif & bobot pekerjaan batubara.
     */
    public const KATEGORI_PEKERJAAN_BATUBARA = 2;

    /**
     * name baris bobot untuk pekerjaan gali.
     */
    private const BOBOT_GALI = 'Gali';

    /**
     * name baris bobot untuk pekerjaan sebar.
     */
    private const BOBOT_SEBAR = 'Sebar';

    /**
     * name baris bobot untuk pekerjaan angkutan.
     */
    private const BOBOT_ANGKUTAN = 'Angkutan';

    /**
     * Hitung Tarif Gali Adjustment & Ekskalasi untuk Pekerjaan Tanah.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     */
    public function hitungGaliTanah(float $tarifDasar): array
    {
        return $this->hitung($tarifDasar, self::KATEGORI_PEKERJAAN_TANAH, self::BOBOT_GALI);
    }

    /**
     * Hitung Tarif Sebar Adjustment & Ekskalasi untuk Pekerjaan Tanah.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     */
    public function hitungSebarTanah(float $tarifDasar): array
    {
        return $this->hitung($tarifDasar, self::KATEGORI_PEKERJAAN_TANAH, self::BOBOT_SEBAR);
    }

    /**
     * Hitung Tarif Angkut Adjustment & Ekskalasi untuk Pekerjaan Tanah.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     */
    public function hitungAngkutanTanah(float $tarifDasar): array
    {
        return $this->hitung($tarifDasar, self::KATEGORI_PEKERJAAN_TANAH, self::BOBOT_ANGKUTAN);
    }

    /**
     * Hitung Tarif Gali Adjustment & Ekskalasi untuk Pekerjaan Batubara.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     */
    public function hitungGaliBatubara(float $tarifDasar): array
    {
        return $this->hitung($tarifDasar, self::KATEGORI_PEKERJAAN_BATUBARA, self::BOBOT_GALI);
    }

    /**
     * Hitung Tarif Angkut Adjustment & Ekskalasi untuk Pekerjaan Batubara.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     */
    public function hitungAngkutanBatubara(float $tarifDasar): array
    {
        return $this->hitung($tarifDasar, self::KATEGORI_PEKERJAAN_BATUBARA, self::BOBOT_ANGKUTAN);
    }

    /**
     * Kalkulasi umum tarif per BCM.
     *
     * A. Tarif Adjustment = Tarif Dasar * (Adjustment Value / 100)
     *
     * B. Tarif Ekskalasi per BCM =
     *      Tarif Adjustment
     *      * ( Komponen Tarif Rupiah + ( Komponen Tarif USD * Harga Aktual Kurs / Harga Acuan Kurs ) )
     *      * ( 1 + ( Bobot BBM * ( Harga Aktual BBM - Harga Acuan BBM ) / Harga Acuan BBM )
     *            + ( Bobot UMSP * ( Harga Aktual UMK - Harga Acuan UMK ) / Harga Acuan UMK ) )
     *
     * @param  int  $kategoriPendapatanId  Kategori pekerjaan (tanah / batubara).
     * @param  string  $bobotName  Nama baris bobot pekerjaan (Gali / Sebar / Angkutan).
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     *
     * @throws RuntimeException Bila konfigurasi pendukung kalkulasi belum lengkap.
     */
    private function hitung(float $tarifDasar, int $kategoriPendapatanId, string $bobotName): array
    {
        $nilaiAdjustment = $this->ambilNilaiAdjustment();
        $komponen = $this->ambilKomponenTarif($kategoriPendapatanId);
        $bobot = $this->ambilBobot($kategoriPendapatanId, $bobotName);
        $kurs = $this->ambilAsumsi('Kurs Dasar');
        $bbm = $this->ambilAsumsi('BBM Dasar');
        $umk = $this->ambilAsumsi('UMK Dasar');

        // A. Tarif Adjustment = Tarif Dasar * Adjustment Value
        $tarifAdjustment = $tarifDasar * $nilaiAdjustment;

        // Faktor komponen tarif: (rupiah + usd * kurs aktual / kurs acuan)
        $rasioKurs = $this->rasio($kurs->harga_aktual, $kurs->harga_acuan, 'Harga Acuan Kurs Dasar');
        $faktorKomponen = $this->persen($komponen->rupiah)
            + ($this->persen($komponen->usd) * $rasioKurs);

        // Faktor ekskalasi: 1 + perubahan BBM + perubahan UMK (terbobot)
        $faktorBbm = $this->persen($bobot->bobot_bbm)
            * $this->perubahan($bbm, 'Harga Acuan BBM Dasar');
        $faktorUmsp = $this->persen($bobot->bobot_umsp)
            * $this->perubahan($umk, 'Harga Acuan UMK Dasar');
        $faktorEkskalasi = 1 + $faktorBbm + $faktorUmsp;

        // B. Tarif Ekskalasi per BCM
        $tarifEkskalasi = $tarifAdjustment * $faktorKomponen * $faktorEkskalasi;

        return [
            'tarif_adjustment' => (int) round($tarifAdjustment),
            'tarif_ekskalasi' => (int) round($tarifEkskalasi),
            'detail' => [
                'tarif_dasar' => $tarifDasar,
                'kategori_pendapatan_id' => $kategoriPendapatanId,
                'bobot' => $bobotName,
                'nilai_adjustment' => $nilaiAdjustment,
                'faktor_komponen' => $faktorKomponen,
                'faktor_ekskalasi' => $faktorEkskalasi,
                'faktor_bbm' => $faktorBbm,
                'faktor_umsp' => $faktorUmsp,
            ],
        ];
    }

    /**
     * Ambil nilai adjustment terbaru (data latest) dalam bentuk fraksi.
     */
    private function ambilNilaiAdjustment(): float
    {
        $adjustment = Spph773AdjustmentValue::query()->orderByDesc('id')->first();

        if ($adjustment === null) {
            throw new RuntimeException('Nilai adjustment belum dikonfigurasi. Lengkapi Konfigurasi Data terlebih dahulu.');
        }

        return $this->persen($adjustment->value);
    }

    /**
     * Ambil komponen tarif berdasarkan kategori pendapatan (tanah / batubara).
     */
    private function ambilKomponenTarif(int $kategoriPendapatanId): Spph773KomponenTarif
    {
        $komponen = Spph773KomponenTarif::query()
            ->where('kategori_pendapatan_id', $kategoriPendapatanId)
            ->first();

        if ($komponen === null) {
            throw new RuntimeException('Komponen tarif belum dikonfigurasi untuk kategori pekerjaan ini.');
        }

        return $komponen;
    }

    /**
     * Ambil bobot berdasarkan kategori pendapatan & name (Gali / Sebar / Angkutan).
     */
    private function ambilBobot(int $kategoriPendapatanId, string $name): Spph773Bobot
    {
        $bobot = Spph773Bobot::query()
            ->where('kategori_pendapatan_id', $kategoriPendapatanId)
            ->where('name', $name)
            ->first();

        if ($bobot === null) {
            throw new RuntimeException("Bobot {$name} belum dikonfigurasi untuk kategori pekerjaan ini.");
        }

        return $bobot;
    }

    /**
     * Ambil asumsi dasar berdasarkan name (BBM Dasar / UMK Dasar / Kurs Dasar).
     */
    private function ambilAsumsi(string $name): Spph773AsumsiDasar
    {
        $asumsi = Spph773AsumsiDasar::query()->where('name', $name)->first();

        if ($asumsi === null) {
            throw new RuntimeException("Asumsi dasar \"{$name}\" belum dikonfigurasi.");
        }

        return $asumsi;
    }

    /**
     * Konversi nilai persen tersimpan (contoh: 37.00) menjadi fraksi (0.37).
     */
    private function persen($value): float
    {
        return (float) $value / 100;
    }

    /**
     * Hitung rasio aktual terhadap acuan dengan proteksi pembagian nol.
     */
    private function rasio($aktual, $acuan, string $label): float
    {
        if ((float) $acuan === 0.0) {
            throw new RuntimeException("{$label} tidak boleh bernilai nol untuk melakukan kalkulasi.");
        }

        return (float) $aktual / (float) $acuan;
    }

    /**
     * Hitung perubahan relatif (aktual - acuan) / acuan untuk data asumsi dasar.
     */
    private function perubahan(Spph773AsumsiDasar $asumsi, string $labelAcuan): float
    {
        return $this->rasio(
            (float) $asumsi->harga_aktual - (float) $asumsi->harga_acuan,
            $asumsi->harga_acuan,
            $labelAcuan
        );
    }
}
