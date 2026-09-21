<?php

namespace Database\Seeders;

use App\Models\Erkap\CostElement;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $accounts = [
            // Revenue (6000-6940)
            ['code' => '6000', 'name' => 'Pendapatan Penjualan Batubara BUKIT ASAM 45'],
            ['code' => '6001', 'name' => 'Pendapatan Penjualan Batubara BUKIT ASAM 50'],
            ['code' => '6002', 'name' => 'Pendapatan Penjualan Batubara BUKIT ASAM 55'],
            ['code' => '6003', 'name' => 'Pendapatan Penjualan Batubara BUKIT ASAM 64 HS'],
            ['code' => '6004', 'name' => 'Pendapatan Penjualan Batubara BUKIT ASAM 64 LS / GAR 6400'],
            ['code' => '6005', 'name' => 'Pendapatan Penjualan Batubara GAR 5800'],
            ['code' => '6006', 'name' => 'Pendapatan Penjualan Batubara GAR 5900'],
            ['code' => '6007', 'name' => 'Pendapatan Penjualan Batubara GAR 6100'],
            ['code' => '6008', 'name' => 'Pendapatan Penjualan Batubara GAD 7300'],
            ['code' => '6009', 'name' => 'Pendapatan Penjualan Batubara SEMI ANS'],
            ['code' => '6010', 'name' => 'Pendapatan Penjualan Batubara GAR 4700'],
            ['code' => '6011', 'name' => 'Pendapatan Penjualan Batubara BAS 4800'],
            ['code' => '6012', 'name' => 'Pendapatan Penjualan Batubara GAR 6700'],
            ['code' => '6025', 'name' => 'Pendapatan DMO'],
            ['code' => '6030', 'name' => 'Pendapatan Penjualan Batu Pecah'],
            ['code' => '6040', 'name' => 'Pendapatan Listrik - Komponen A'],
            ['code' => '6041', 'name' => 'Pendapatan Listrik - Komponen B'],
            ['code' => '6042', 'name' => 'Pendapatan Listrik - Komponen C'],
            ['code' => '6043', 'name' => 'Pendapatan Listrik - Komponen D'],
            ['code' => '6044', 'name' => 'Pendapatan Listrik - Komponen E'],
            ['code' => '6045', 'name' => 'Pendapatan Penjualan Kelebihan Tenaga Listrik (Excess Power)'],
            ['code' => '6050', 'name' => 'Pendapatan Penjualan Briket Super'],
            ['code' => '6051', 'name' => 'Pendapatan Penjualan Briket Telur'],
            ['code' => '6052', 'name' => 'Pendapatan Penjualan Briket Kubus'],
            ['code' => '6053', 'name' => 'Pendapatan Penjualan Briket Arang Batok Kelapa'],
            ['code' => '6054', 'name' => 'Pendapatan Penjualan Karbon Aktif'],
            ['code' => '6060', 'name' => 'Pendapatan Jasa Angkutan Kapal'],
            ['code' => '6061', 'name' => 'Pendapatan Sewa Dermaga'],
            ['code' => '6062', 'name' => 'Pendapatan Pengiriman'],
            ['code' => '6067', 'name' => 'Pendapatan Jasa Bongkar Muat'],
            ['code' => '6070', 'name' => 'Pendapatan Jasa Teknik'],
            ['code' => '6071', 'name' => 'Pendapatan Jasa Laboratorium'],
            ['code' => '6080', 'name' => 'Pendapatan Jasa Penambangan'],
            ['code' => '6081', 'name' => 'Pendapatan Jasa Pengupasan Tanah (Overburden)'],
            ['code' => '6082', 'name' => 'Pendapatan Sewa Peralatan Tambang'],
            ['code' => '6100', 'name' => 'Pendapatan Penjualan CPO'],
            ['code' => '6101', 'name' => 'Pendapatan Penjualan PK'],
            ['code' => '6102', 'name' => 'Pendapatan Penjualan Cangkang'],
            ['code' => '6103', 'name' => 'Pendapatan Penjualan Janjangan'],
            ['code' => '6104', 'name' => 'Pendapatan Penjualan Fiber'],
            ['code' => '6105', 'name' => 'Pendapatan Penjualan Tankos'],
            ['code' => '6106', 'name' => 'Pendapatan Penjualan TBS'],
            ['code' => '6107', 'name' => 'Pendapatan Penjualan Composting'],
            ['code' => '6108', 'name' => 'Pendapatan Penjualan Karet'],
            ['code' => '6109', 'name' => 'Pendapatan Penjualan Singkong'],
            ['code' => '6110', 'name' => 'Pendapatan Penjualan Keagenan'],
            ['code' => '6111', 'name' => 'Pendapatan Penjualan Rawat Jalan'],
            ['code' => '6112', 'name' => 'Pendapatan Penjualan Rawat Inap'],
            ['code' => '6113', 'name' => 'Pendapatan Penjualan MCU'],
            ['code' => '6114', 'name' => 'Pendapatan Penjualan BPJS'],
            ['code' => '6115', 'name' => 'Pendapatan Penjualan Farmasi'],
            ['code' => '6116', 'name' => 'Pendapatan Penjualan Klinik'],
            ['code' => '6200', 'name' => 'Pendapatan CBM'],
            ['code' => '6225', 'name' => 'Pendapatan Benefisiasi Batu Bara'],
            ['code' => '6300', 'name' => 'Pendapatan Jasa O&M'],
            ['code' => '6900', 'name' => 'Pendapatan Bunga Bank/Jasa Giro'],
            ['code' => '6901', 'name' => 'Pendapatan Bunga Investasi Derivatif'],
            ['code' => '6902', 'name' => 'Pendapatan Bunga Obligasi'],
            ['code' => '6903', 'name' => 'Pendapatan Bunga Deposito'],
            ['code' => '6904', 'name' => 'Pendapatan Sewa Infrastruktur dan Fasilitas Perusahaan'],
            ['code' => '6905', 'name' => 'Pendapatan Bunga Deposito DHE'],
            ['code' => '6920', 'name' => 'Pendapatan Penjualan Dokumen Tender'],
            ['code' => '6921', 'name' => 'Pendapatan Denda/Klaim'],
            ['code' => '6922', 'name' => 'Pendapatan Selisih Harga  Penj. Saham'],
            ['code' => '6923', 'name' => 'Penerimaan Potongan Harga'],
            ['code' => '6924', 'name' => 'Pendapatan Kelebihan Pajak'],
            ['code' => '6925', 'name' => 'Pendapatan Dividen'],
            ['code' => '6930', 'name' => 'Pendapatan Penjualan Aktiva Tetap'],
            ['code' => '6940', 'name' => 'Pendapatan Penjualan Material Rongsokan'],
            ['code' => '6941', 'name' => 'Pendapatan Bunga PKK'],
            ['code' => '6942', 'name' => 'Pendapatan Tunda Pandu Dan Moring'],

            // Expense (7000-9109)
            ['code' => '7000', 'name' => 'Biaya Konsultan'],
            ['code' => '7001', 'name' => 'Biaya Jasa Pengupasan Lapisan Tanah (Overburden)'],
            ['code' => '7002', 'name' => 'Biaya Jasa Kontraktor (ADP)'],
            ['code' => '7003', 'name' => 'Biaya Jasa Surveyor'],
            ['code' => '7004', 'name' => 'Biaya Jasa Pemasaran'],
            ['code' => '7005', 'name' => 'Biaya Pembuatan Laporan Audit'],
            ['code' => '7006', 'name' => 'Biaya Upah Non Karyawan'],
            ['code' => '7007', 'name' => 'Biaya Pelayanan Kebersihan dan Keamanan'],
            ['code' => '7008', 'name' => 'Biaya Penjamin Emisi Saham'],
            ['code' => '7009', 'name' => 'Biaya Jasa Kontraktor Perawatan'],
            ['code' => '7010', 'name' => 'Biaya Jasa Non Kontraktor Perawatan'],
            ['code' => '7011', 'name' => 'Biaya Jasa Klinik Kesehatan'],
            ['code' => '7012', 'name' => 'Biaya Jasa Galian Batubara'],
            ['code' => '7013', 'name' => 'Biaya Sewa Alat Berat'],
            ['code' => '7014', 'name' => 'Biaya Sewa Peralatan dan Perlengkapan Operasional'],
            ['code' => '7015', 'name' => 'Biaya Sewa Peralatan dan Perlengkapan Non-Operasional'],
            ['code' => '7016', 'name' => 'Biaya Sewa Kendaraan Bermotor Operasional'],
            ['code' => '7017', 'name' => 'Biaya Sewa Kendaraan Bermotor Non Operasional'],
            ['code' => '7018', 'name' => 'Biaya Sewa Gudang/Kantor'],
            ['code' => '7019', 'name' => 'Biaya Sewa Peralatan dan Perlengkapan Kantor'],
            ['code' => '7020', 'name' => 'Biaya Sewa Infrastruktur dan Fasilitas Perusahaan'],
            ['code' => '7021', 'name' => 'Biaya Sewa Rumah Dinas/Mess'],
            ['code' => '7022', 'name' => 'Biaya Sewa Lahan/Tanah'],
            ['code' => '7023', 'name' => 'Biaya Angkutan Truk/Mobilisasi'],
            ['code' => '8000', 'name' => 'Biaya Gaji'],
            ['code' => '8001', 'name' => 'Biaya Upah Karyawan Harian Lepas'],
            ['code' => '8002', 'name' => 'Biaya Honorarium/KKWT'],
            ['code' => '8003', 'name' => 'Tunjangan Lembur'],
            ['code' => '8004', 'name' => 'Biaya Kewajiban Pensiun (PSL)'],
            ['code' => '8005', 'name' => 'Tunjangan PPh 21'],
            ['code' => '8006', 'name' => 'Tunjangan Pegawai'],
            ['code' => '8007', 'name' => 'Tunjangan Giliran/Shift'],
            ['code' => '8008', 'name' => 'Tunjangan Makan'],
            ['code' => '8009', 'name' => 'Tunjangan Perumahan'],
            ['code' => '8010', 'name' => 'Tunjangan Cuti'],
            ['code' => '8011', 'name' => 'Insentif dan Jasa Produksi'],
            ['code' => '8012', 'name' => 'Tunjangan Transportasi'],
            ['code' => '8013', 'name' => 'Tunjangan Prestasi'],
            ['code' => '8014', 'name' => 'Tunjangan Pengobatan'],
            ['code' => '8015', 'name' => 'Tunjangan Hari Raya'],
            ['code' => '8016', 'name' => 'Biaya Pesangon & Uang Jasa'],
            ['code' => '8017', 'name' => 'Tunjangan Sosial Kematian'],
            ['code' => '8018', 'name' => 'Biaya Pendidikan dan Pelatihan'],
            ['code' => '8019', 'name' => 'Biaya Perjalanan Dinas'],
            ['code' => '8021', 'name' => 'Biaya Pemindahan Pegawai'],
            ['code' => '8022', 'name' => 'Biaya Penghargaan dan Pembinaan Pegawai'],
            ['code' => '8100', 'name' => 'Biaya Bahan Bakar Minyak'],
            ['code' => '8101', 'name' => 'Biaya Minyak dan Pelumas'],
            ['code' => '8102', 'name' => 'Biaya Bahan Peledak'],
            ['code' => '8103', 'name' => 'Biaya Bahan Kimia'],
            ['code' => '8104', 'name' => 'Biaya Material dan Suku Cadang'],
            ['code' => '8105', 'name' => 'Biaya Ban'],
            ['code' => '8106', 'name' => 'Biaya Pemboran & Aksesoris'],
            ['code' => '8107', 'name' => 'Biaya Ground Engaging Tools (GET)'],
            ['code' => '8108', 'name' => 'Biaya Undercarriage'],
            ['code' => '8109', 'name' => 'Biaya Common Tools'],
            ['code' => '8110', 'name' => 'Biaya Varians Persediaan Pembelian Barang'],
            ['code' => '8111', 'name' => 'Biaya Penghapusan Persediaan'],
            ['code' => '8112', 'name' => 'Biaya Perlengkapan Kantor dan ATK'],
            ['code' => '8113', 'name' => 'Biaya Perlengkapan Pengaman dan Pakaian Pelindung'],
            ['code' => '8114', 'name' => 'Biaya Perlengkapan Peralatan Mess'],
            ['code' => '8115', 'name' => 'Biaya Kebutuhan Obat'],
            ['code' => '8116', 'name' => 'Biaya Bahan Makanan'],
            ['code' => '8117', 'name' => 'Biaya Pakaian Seragam'],
            ['code' => '8118', 'name' => 'Biaya Pembelian Aset Tidak Dikapitalisir'],
            ['code' => '8200', 'name' => 'Biaya Listrik dan Air'],
            ['code' => '8201', 'name' => 'Biaya Telepon, Telex dan Telegraf'],
            ['code' => '8202', 'name' => 'Biaya Asuransi Aktiva Tetap'],
            ['code' => '8203', 'name' => 'Biaya Asuransi Jiwa dan Purnajabatan'],
            ['code' => '8204', 'name' => 'Biaya Asuransi Kesehatan'],
            ['code' => '8205', 'name' => 'Biaya Lisensi dan Perizinan'],
            ['code' => '8300', 'name' => 'Biaya CSR dan Comdev'],
            ['code' => '8400', 'name' => 'Biaya Penyusutan Alat Tambang/Pelabuhan Utama'],
            ['code' => '8401', 'name' => 'Biaya Penyusutan Bangunan dan infrastuktur'],
            ['code' => '8402', 'name' => 'Biaya Penyusutan Mesin dan Peralatan'],
            ['code' => '8403', 'name' => 'Biaya Penyusutan Kendaraan Bermotor'],
            ['code' => '8404', 'name' => 'Biaya Penyusutan Perlengkapan Kantor'],
            ['code' => '8405', 'name' => 'Biaya Penyusutan Peralatan Engineering'],
            ['code' => '8406', 'name' => 'Biaya Penyusutan Komponen Alat Berat'],
            ['code' => '8407', 'name' => 'Biaya Penyusutan Peralatan Pembantu Tambang'],
            ['code' => '8408', 'name' => 'Biaya Penyusutan Alat Tambang/Pelabuhan Utama - SGU'],
            ['code' => '8409', 'name' => 'Biaya Penyusutan Peralatan Pembantu Tambang - SGU'],
            ['code' => '8410', 'name' => 'Biaya penyusutan Hak Guna Aset - PSAK 73'],
            ['code' => '8411', 'name' => 'Biaya Amortisasi aset tidak berwujud'],
            ['code' => '8600', 'name' => 'Biaya Penelitian dan Pengembangan'],
            ['code' => '9010', 'name' => 'Biaya Jasa Penagihan'],
            ['code' => '9100', 'name' => 'Biaya Jamuan/Rapat'],
            ['code' => '9101', 'name' => 'Biaya Publikasi'],
            ['code' => '9102', 'name' => 'Biaya Kegiatan Sosial Masyarakat'],
            ['code' => '9103', 'name' => 'Biaya Olahraga, Rekreasi, dan Seni Budaya'],
            ['code' => '9104', 'name' => 'Biaya Kontribusi dan Suvenir'],
            ['code' => '9105', 'name' => 'Biaya Paparan Publik'],
            ['code' => '9106', 'name' => 'Biaya Iuran Keanggotan'],
            ['code' => '9107', 'name' => 'Biaya Buku, Majalah, dan Surat Kabar'],
            ['code' => '9108', 'name' => 'Biaya Sumbangan dan Hadiah'],
            ['code' => '9109', 'name' => 'Biaya Pos Surat dan Pengiriman Barang'],
        ];

        foreach ($accounts as $account) {
            $type = str_starts_with($account['code'], '6') ? 'revenue' : 'expense';

            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $type,
                    'description' => null,
                ]
            );
        }

        $this->linkCostElements();
    }

    /**
     * Link existing cost elements to chart of accounts by matching code.
     *
     * @return void
     */
    private function linkCostElements()
    {
        $costElements = CostElement::whereNull('chart_of_account_id')->get();

        foreach ($costElements as $costElement) {
            $chartOfAccount = ChartOfAccount::where('code', $costElement->code)->first();

            if ($chartOfAccount) {
                $costElement->update(['chart_of_account_id' => $chartOfAccount->id]);
            }
        }
    }
}