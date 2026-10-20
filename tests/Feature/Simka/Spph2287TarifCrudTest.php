<?php

namespace Tests\Feature\Simka;

use App\Models\Spph2287AdjustmentValue;
use App\Models\Spph2287Tarif;
use App\Models\User;
use Database\Seeders\SimkaKategoriPendapatanSeeder;
use Database\Seeders\SimkaSpph2287AsumsiDasarSeeder;
use Database\Seeders\SimkaSpph2287BobotSeeder;
use Database\Seeders\SimkaSpph2287KategoriSeeder;
use Database\Seeders\SimkaSpph773KategoriTarifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test CRUD & kalkulasi tarif SPPH 2287.
 *
 * SPPH 2287 memakai satu formula yang sama untuk seluruh sub-menu:
 *   Tarif Adjustment = Tarif Dasar * Nilai Adjustment
 *   Tarif Ekskalasi  = Tarif Adjustment * (1 + Bobot UMK * ΔUMSK + Bobot BBM * ΔBBM)
 *
 * Nilai harapan dihitung dari data seeder:
 *   Nilai adjustment 10% ; Bobot BBM 33% ; Bobot UMK 9%
 *   ΔBBM  = (18.195 - 13.800) / 13.800 = 0,318478...
 *   ΔUMSK = (4.240.899 - 3.364.511) / 3.364.511 = 0,260480...
 *   faktor ekskalasi = 1 + (9% * ΔUMSK) + (33% * ΔBBM) = 1,128541...
 */
class Spph2287TarifCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SimkaKategoriPendapatanSeeder::class);
        $this->seed(SimkaSpph773KategoriTarifSeeder::class);
        $this->seed(SimkaSpph2287KategoriSeeder::class);
        $this->seed(SimkaSpph2287BobotSeeder::class);
        $this->seed(SimkaSpph2287AsumsiDasarSeeder::class);

        $this->actingAs(User::factory()->create());
    }

    /**
     * Seluruh sub-menu kalkulasi harus dapat dibuka (menangkap error render Blade).
     */
    public function test_seluruh_halaman_submenu_dapat_ditampilkan(): void
    {
        $pages = [
            'simka.spph2287.tarif-gali-tanah' => 'Tarif Gali - Pekerjaan Tanah',
            'simka.spph2287.tarif-angkutan-tanah' => 'Tarif Angkutan - Pekerjaan Tanah',
            'simka.spph2287.tarif-sebar-tanah' => 'Tarif Sebar - Pekerjaan Tanah',
            'simka.spph2287.tarif-gali-batubara' => 'Tarif Gali - Pekerjaan Batubara',
            'simka.spph2287.tarif-angkutan-batubara' => 'Tarif Angkutan - Pekerjaan Batubara',
            'simka.spph2287.pekerjaan-lingkungan' => 'Tarif Pekerjaan Lingkungan',
            'simka.spph2287.reklamasi-pemeliharaan-tanaman' => 'Tarif Pekerjaan Reklamasi dan Pemeliharaan Tanaman',
            'simka.spph2287.management-stockpile' => 'Tarif Pekerjaan Management Stockpile',
            'simka.spph2287.perawatan-jalan-stockpile-iup' => 'Tarif Pekerjaan Perawatan Jalan dari Stockpile keluar IUP',
        ];

        foreach ($pages as $routeName => $title) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($title, false);
        }
    }

    /**
     * Sub-menu satu baris: simpan Tarif Dasar (format Rupiah) lalu kalkulasi.
     */
    public function test_simpan_dan_kalkulasi_submenu_satu_baris(): void
    {
        Spph2287AdjustmentValue::query()->create(['value' => 10.00]);

        $this->post(route('simka.spph2287.pekerjaan-lingkungan.simpan'), [
            'tarif_pekerjaan_lingkungan_dasar' => '14.000',
        ])->assertRedirect(route('simka.spph2287.pekerjaan-lingkungan'));

        $tarif = Spph2287Tarif::query()->where('kategori_tarif_id', 6)->first();
        $this->assertNotNull($tarif, 'Tarif seharusnya tersimpan pada kategori_tarif_id = 6.');
        $this->assertSame(14000, (int) $tarif->tarif_dasar);
        $this->assertNull($tarif->tarif_adjustment, 'Adjustment baru terisi setelah kalkulasi.');

        // Nilai yang tersimpan tampil kembali pada form.
        $this->get(route('simka.spph2287.pekerjaan-lingkungan'))
            ->assertOk()
            ->assertSee('value="14000"', false);

        $this->post(route('simka.spph2287.pekerjaan-lingkungan.kalkulasi'))->assertRedirect();

        $tarif->refresh();
        $this->assertSame(1400, (int) $tarif->tarif_adjustment);
        $this->assertSame(1580, (int) $tarif->tarif_ekskalasi);

        // Hasil kalkulasi tampil pada kolom Adjustment & Ekskalasi.
        $this->get(route('simka.spph2287.pekerjaan-lingkungan'))
            ->assertOk()
            ->assertSee('1.400', false)
            ->assertSee('1.580', false);
    }

    /**
     * Sub-menu bertipe interval: simpan per baris jarak lalu kalkulasi.
     */
    public function test_simpan_dan_kalkulasi_submenu_interval(): void
    {
        Spph2287AdjustmentValue::query()->create(['value' => 10.00]);

        $this->post(route('simka.spph2287.tarif-angkutan-tanah.simpan'), [
            'tarif_angkut_dasar' => [1 => '20.000', 2 => '25000'],
        ])->assertRedirect();

        $row1 = Spph2287Tarif::query()->where('kategori_tarif_id', 2)->where('jarak_awal', 0)->first();
        $row2 = Spph2287Tarif::query()->where('kategori_tarif_id', 2)->where('jarak_awal', 251)->first();

        $this->assertNotNull($row1);
        $this->assertNotNull($row2);
        $this->assertSame(250, (int) $row1->jarak_akhir);
        $this->assertSame(20000, (int) $row1->tarif_dasar);
        $this->assertSame(500, (int) $row2->jarak_akhir);
        $this->assertSame(25000, (int) $row2->tarif_dasar);

        // Rentang jarak default 40 baris (Row 40 -> 9.751 - 10.000).
        $this->get(route('simka.spph2287.tarif-angkutan-tanah'))
            ->assertOk()
            ->assertSee('9.751', false)
            ->assertSee('10.000', false);

        $this->post(route('simka.spph2287.tarif-angkutan-tanah.kalkulasi'))->assertRedirect();

        $this->assertSame(2000, (int) $row1->refresh()->tarif_adjustment);
        $this->assertSame(2257, (int) $row1->tarif_ekskalasi);
        $this->assertSame(2500, (int) $row2->refresh()->tarif_adjustment);
        $this->assertSame(2821, (int) $row2->tarif_ekskalasi);
    }

    /**
     * Kalkulasi harus ditolak bila Tarif Dasar belum disimpan.
     */
    public function test_kalkulasi_tanpa_tarif_dasar_menampilkan_error(): void
    {
        $this->post(route('simka.spph2287.management-stockpile.kalkulasi'))
            ->assertRedirect(route('simka.spph2287.management-stockpile'))
            ->assertSessionHas('error');
    }

    /**
     * Tarif Dasar wajib diisi.
     */
    public function test_simpan_menolak_tarif_dasar_kosong(): void
    {
        $this->post(route('simka.spph2287.pekerjaan-lingkungan.simpan'), [
            'tarif_pekerjaan_lingkungan_dasar' => '',
        ])->assertSessionHasErrors('tarif_pekerjaan_lingkungan_dasar');
    }

    /**
     * Hapus menghapus seluruh tarif pada sub-menu terkait.
     */
    public function test_hapus_tarif_submenu(): void
    {
        Spph2287Tarif::query()->create(['kategori_tarif_id' => 8, 'tarif_dasar' => 5000]);

        $this->delete(route('simka.spph2287.management-stockpile.hapus'))->assertRedirect();

        $this->assertSame(0, Spph2287Tarif::query()->where('kategori_tarif_id', 8)->count());
    }

    /**
     * Bila nilai adjustment belum dikonfigurasi, kalkulasi gagal tanpa menyimpan hasil.
     */
    public function test_kalkulasi_gagal_bila_konfigurasi_belum_lengkap(): void
    {
        Spph2287AdjustmentValue::query()->delete();
        Spph2287Tarif::query()->create(['kategori_tarif_id' => 7, 'tarif_dasar' => 1000]);

        $this->post(route('simka.spph2287.reklamasi-pemeliharaan-tanaman.kalkulasi'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(
            Spph2287Tarif::query()->where('kategori_tarif_id', 7)->first()->tarif_adjustment
        );
    }
}
