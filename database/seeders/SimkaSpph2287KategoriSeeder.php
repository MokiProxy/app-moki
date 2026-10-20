<?php

namespace Database\Seeders;

use App\Models\KategoriPendapatan;
use App\Models\KategoriTarif;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Kategori untuk sub-menu kalkulasi SPPH 2287 yang belum tercakup SPPH 773.
 *
 * SPPH 773 hanya memakai kategori_pendapatan 1 (Pekerjaan Tanah) & 2 (Pekerjaan
 * Batubara) beserta kategori_tarif 1-5. Sub-menu baru SPPH 2287 (Pekerjaan
 * Lingkungan, Reklamasi & Pemeliharaan Tanaman, Management Stockpile, dan
 * Perawatan Jalan dari Stockpile keluar IUP) memakai kategori_pendapatan 3-6
 * dan kategori_tarif 6-9.
 */
class SimkaSpph2287KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $kategoriPendapatan = [
            ['id' => 3, 'name' => 'Pekerjaan Lingkungan'],
            ['id' => 4, 'name' => 'Pekerjaan Reklamasi dan Pemeliharaan Tanaman'],
            ['id' => 5, 'name' => 'Pekerjaan Management Stockpile'],
            ['id' => 6, 'name' => 'Pekerjaan Perawatan Jalan dari Stockpile keluar IUP'],
        ];

        foreach ($kategoriPendapatan as $row) {
            KategoriPendapatan::updateOrCreate(['id' => $row['id']], ['name' => $row['name']]);
        }

        $kategoriTarif = [
            ['id' => 6, 'name' => 'Pekerjaan Lingkungan', 'kategori_pendapatan_id' => 3],
            ['id' => 7, 'name' => 'Pekerjaan Reklamasi dan Pemeliharaan Tanaman', 'kategori_pendapatan_id' => 4],
            ['id' => 8, 'name' => 'Pekerjaan Management Stockpile', 'kategori_pendapatan_id' => 5],
            ['id' => 9, 'name' => 'Pekerjaan Perawatan Jalan dari Stockpile keluar IUP', 'kategori_pendapatan_id' => 6],
        ];

        foreach ($kategoriTarif as $row) {
            KategoriTarif::updateOrCreate(
                ['id' => $row['id']],
                ['name' => $row['name'], 'kategori_pendapatan_id' => $row['kategori_pendapatan_id']]
            );
        }

        // Karena seluruh baris di atas memakai id eksplisit, sequence primary key
        // tidak ikut bergerak. Samakan sequence dengan id terbesar agar insert
        // berikutnya (tanpa id) tidak bertabrakan.
        if (DB::getDriverName() === 'pgsql') {
            foreach (['simka_kategori_pendapatan', 'simka_kategori_tarif'] as $table) {
                DB::statement(
                    "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))",
                    [$table]
                );
            }
        }
    }
}
