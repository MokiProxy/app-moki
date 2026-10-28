<?php

namespace Tests\Feature\Simka;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test halaman Tarif Total SPPH 773 & SPPH 2287.
 *
 * Halaman masih berupa view (logika kalkulasi belum tersedia), sehingga test ini
 * fokus menangkap error render Blade, memastikan 40 baris rentang jarak tampil,
 * dan memastikan kartu menu "Tarif Total" ada pada menu kalkulasi.
 */
class SpphTarifTotalViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public function halamanTarifTotal(): array
    {
        return [
            ['simka.spph773.tarif-total-tanah', 'Tarif Total - Pekerjaan Tanah', 'A. Tarif Total - Pekerjaan Tanah'],
            ['simka.spph773.tarif-total-batubara', 'Tarif Total - Pekerjaan Batubara', 'B. Tarif Total - Pekerjaan Batubara'],
            ['simka.spph2287.tarif-total-tanah', 'Tarif Total - Pekerjaan Tanah', 'A. Tarif Total - Pekerjaan Tanah'],
            ['simka.spph2287.tarif-total-batubara', 'Tarif Total - Pekerjaan Batubara', 'B. Tarif Total - Pekerjaan Batubara'],
        ];
    }

    public function test_seluruh_halaman_tarif_total_dapat_ditampilkan(): void
    {
        foreach ($this->halamanTarifTotal() as [$routeName, $pageTitle, $cardTitle]) {
            $response = $this->get(route($routeName))->assertOk();

            // Judul halaman + judul kartu.
            $response->assertSee($pageTitle, false);
            $response->assertSee($cardTitle, false);

            // 40 baris rentang jarak (Row 1 -> 0 - 250 ... Row 40 -> 9.751 - 10.000).
            $response->assertSee('40 Baris', false);
            $response->assertSee('9.751', false);
            $response->assertSee('10.000', false);

            // Kolom hasil kalkulasi masih kosong (placeholder).
            $response->assertSee('placeholder="-"', false);
        }
    }

    public function test_menu_kalkulasi_menampilkan_kartu_tarif_total(): void
    {
        // Section A (Pekerjaan Tanah) & B (Pekerjaan Batubara) pada kedua SPPH.
        $this->get(route('simka.spph773.kalkulasi'))
            ->assertOk()
            ->assertSee(route('simka.spph773.tarif-total-tanah'), false)
            ->assertSee(route('simka.spph773.tarif-total-batubara'), false);

        $this->get(route('simka.spph2287.kalkulasi'))
            ->assertOk()
            ->assertSee(route('simka.spph2287.tarif-total-tanah'), false)
            ->assertSee(route('simka.spph2287.tarif-total-batubara'), false);
    }

    public function test_aksi_kalkulasi_tarif_total_masih_placeholder(): void
    {
        $this->post(route('simka.spph773.tarif-total-tanah.kalkulasi'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->delete(route('simka.spph2287.tarif-total-batubara.hapus'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
