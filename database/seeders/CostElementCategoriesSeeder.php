<?php

namespace Database\Seeders;

use App\Models\Erkap\CostElementCategory;
use Illuminate\Database\Seeder;

class CostElementCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            "Pendapatan Penjualan Batubara",
            "Pendapatan Penjualan Listrik",
            "Pendapatan Penjualan Briket",
            "Pendapatan Jasa Kepelabuhanan",
            "Pendapatan Jasa Teknik",
            "Pendapatan Jasa Kontraktor Tambang",
            "Penjualan Perkebunan",
            "Pendapatan Batubara - Lain-lain",
            "Pendapatan Jasa O&M",
            "Pendapatan Bunga",
            "Pendapatan Lain-Lain",
            "Keuntungan Penjualan Aktiva",
            "Penjualan Barang Bekas",
            "Biaya Jasa Pihak Eksternal / Pihak Ketiga",
            "Gaji dan Upah",
            "Bahan Bakar",
            "Minyak dan Pelumas",
            "Suku Cadang dan Bahan",
            "Sewa Alat Berat",
            "Sewa Mobil dan Peralatan",
            "Jasa Pihak Ketiga - Operasi",
            "Jasa Pihak Ketiga - Non Operasi",
            "Iuran dan Retribusi",
            "Perjalanan Dinas",
            "Penyusutan & Amortisasi",
            "Pendidikan",
            "CSR",
            "Lain - lain"
        ];

        foreach($categories as $category) {
            CostElementCategory::create(["name" => $category]);
        }
    }
}
