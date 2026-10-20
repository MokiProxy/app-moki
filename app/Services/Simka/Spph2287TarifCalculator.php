<?php

namespace App\Services\Simka;

use App\Models\Spph2287AdjustmentValue;
use App\Models\Spph2287AsumsiDasar;
use App\Models\Spph2287Bobot;
use RuntimeException;

/**
 * Business logic kalkulasi tarif SPPH 2287.
 *
 * SPPH 2287 memakai SATU formula yang sama untuk seluruh sub-menu kalkulasi
 * (Pekerjaan Tanah, Pekerjaan Batubara, Pekerjaan Lingkungan, dan seterusnya).
 *
 * A. Tarif Adjustment = Tarif Dasar * Nilai Adjustment
 * B. Tarif Ekskalasi  = Tarif Adjustment * ( 1
 *        + ( Bobot UMK * ( UMSK Aktual - UMSK Asumsi Dasar ) / UMSK Asumsi Dasar )
 *        + ( Bobot BBM * ( Harga BBM Aktual - Harga BBM Asumsi Dasar ) / Harga BBM Asumsi Dasar )
 *    )
 *
 * Catatan satuan:
 * Nilai adjustment dan bobot disimpan sebagai angka persen
 * (contoh: 10.00 berarti 10%, 33.00 berarti 33%). Pada perhitungan nilai
 * tersebut dikonversi ke bentuk fraksi (dibagi 100) agar hasilnya proporsional.
 *
 * Sumber data:
 * - Nilai adjustment : simka_spph2287_adjustment_value (data latest / id terbesar)
 * - Bobot            : simka_spph2287_bobot (name "BBM" & "UMK")
 * - Asumsi dasar     : simka_spph2287_asumsi_dasar (name "Harga BBM" & "UMSK")
 */
class Spph2287TarifCalculator
{
    /**
     * name baris pada simka_spph2287_bobot.
     */
    private const BOBOT_BBM = 'BBM';

    /**
     * name baris pada simka_spph2287_bobot.
     */
    private const BOBOT_UMK = 'UMK';

    /**
     * name baris pada simka_spph2287_asumsi_dasar.
     */
    private const ASUMSI_HARGA_BBM = 'Harga BBM';

    /**
     * name baris pada simka_spph2287_asumsi_dasar.
     */
    private const ASUMSI_UMSK = 'UMSK';

    /**
     * Hitung Tarif Adjustment & Tarif Ekskalasi SPPH 2287 untuk satu nilai tarif dasar.
     *
     * @return array{tarif_adjustment:int, tarif_ekskalasi:int, detail:array<string, float>}
     *
     * @throws RuntimeException Bila konfigurasi pendukung kalkulasi belum lengkap.
     */
    public function hitung(float $tarifDasar): array
    {
        $nilaiAdjustment = $this->ambilNilaiAdjustment();
        $bobotBbm = $this->ambilBobot(self::BOBOT_BBM);
        $bobotUmk = $this->ambilBobot(self::BOBOT_UMK);
        $hargaBbm = $this->ambilAsumsi(self::ASUMSI_HARGA_BBM);
        $umsk = $this->ambilAsumsi(self::ASUMSI_UMSK);

        // A. Tarif Adjustment = Tarif Dasar * Nilai Adjustment
        $tarifAdjustment = $tarifDasar * $nilaiAdjustment;

        // B. Faktor ekskalasi = 1 + perubahan UMSK + perubahan Harga BBM (masing-masing terbobot)
        $faktorUmk = $this->persen($bobotUmk->value) * $this->perubahanUmsk($umsk);
        $faktorBbm = $this->persen($bobotBbm->value) * $this->perubahanHargaBbm($hargaBbm);
        $faktorEkskalasi = 1 + $faktorUmk + $faktorBbm;

        $tarifEkskalasi = $tarifAdjustment * $faktorEkskalasi;

        return [
            'tarif_adjustment' => (int) round($tarifAdjustment),
            'tarif_ekskalasi' => (int) round($tarifEkskalasi),
            'detail' => [
                'tarif_dasar' => $tarifDasar,
                'nilai_adjustment' => $nilaiAdjustment,
                'faktor_umk' => $faktorUmk,
                'faktor_bbm' => $faktorBbm,
                'faktor_ekskalasi' => $faktorEkskalasi,
            ],
        ];
    }

    /**
     * Ambil nilai adjustment terbaru (data latest) dalam bentuk fraksi.
     */
    private function ambilNilaiAdjustment(): float
    {
        $adjustment = Spph2287AdjustmentValue::query()->orderByDesc('id')->first();

        if ($adjustment === null) {
            throw new RuntimeException('Nilai adjustment belum dikonfigurasi. Lengkapi Konfigurasi Data terlebih dahulu.');
        }

        return $this->persen($adjustment->value);
    }

    /**
     * Ambil bobot berdasarkan name (BBM / UMK).
     */
    private function ambilBobot(string $name): Spph2287Bobot
    {
        $bobot = Spph2287Bobot::query()->where('name', $name)->first();

        if ($bobot === null) {
            throw new RuntimeException("Bobot \"{$name}\" belum dikonfigurasi. Lengkapi Konfigurasi Data terlebih dahulu.");
        }

        return $bobot;
    }

    /**
     * Ambil asumsi dasar berdasarkan name (Harga BBM / UMSK).
     */
    private function ambilAsumsi(string $name): Spph2287AsumsiDasar
    {
        $asumsi = Spph2287AsumsiDasar::query()->where('name', $name)->first();

        if ($asumsi === null) {
            throw new RuntimeException("Asumsi dasar \"{$name}\" belum dikonfigurasi. Lengkapi Konfigurasi Data terlebih dahulu.");
        }

        return $asumsi;
    }

    /**
     * Konversi nilai persen tersimpan (contoh: 33.00) menjadi fraksi (0.33).
     */
    private function persen($value): float
    {
        return (float) $value / 100;
    }

    /**
     * Perubahan relatif UMSK: (aktual - acuan) / acuan.
     */
    private function perubahanUmsk(Spph2287AsumsiDasar $asumsi): float
    {
        return $this->perubahan(
            $asumsi,
            'Harga Acuan UMSK',
            'UMSK Aktual'
        );
    }

    /**
     * Perubahan relatif Harga BBM: (aktual - acuan) / acuan.
     */
    private function perubahanHargaBbm(Spph2287AsumsiDasar $asumsi): float
    {
        return $this->perubahan(
            $asumsi,
            'Harga Acuan BBM',
            'Harga BBM Aktual'
        );
    }

    /**
     * Hitung perubahan relatif (aktual - acuan) / acuan dengan proteksi pembagian nol.
     *
     * @throws RuntimeException Bila nilai acuan bernilai nol.
     */
    private function perubahan(Spph2287AsumsiDasar $asumsi, string $labelAcuan, string $labelAktual): float
    {
        $acuan = (float) $asumsi->harga_acuan;

        if ($acuan === 0.0) {
            throw new RuntimeException("{$labelAcuan} tidak boleh bernilai nol untuk melakukan kalkulasi (periksa {$labelAktual}).");
        }

        return ((float) $asumsi->harga_aktual - $acuan) / $acuan;
    }
}
