<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'SLIP PEMBUKUAN AP',
                'slug' => 'slip-pembukuan-ap',
                'header_regex' => '/^SLIP\s+PEMBUKUAN\s+AP$/mi',
                'description' => 'Slip Pembukuan AP',
                'number_regex' => '/No\s+Inv\s*\n?\s*:\s*(.+)/i',
                'number_label' => 'invoice_number',
                'keterangan_regex' => '/Keterangan\s*:\s*(.+)/i',
                'keterangan_label' => 'keterangan',
                'keterangan_enabled' => true,
                'uraian_regex' => '/URAIAN\s*\n(.+?)\n\s*TOTAL/si',
                'uraian_label' => 'uraian',
                'uraian_enabled' => true,
                'tanggal_regex' => '/Tgl\s*\n?\s*:\s*(.+)/i',
                'tanggal_label' => 'tanggal',
                'tanggal_enabled' => true,
                'filename_template' => 'AP_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'INVOICE',
                'slug' => 'invoice',
                'header_regex' => '/^INVOICE$/mi',
                'description' => 'Invoice',
                'number_regex' => '/No\s+Inv\s*\n?\s*:\s*(.+)/i',
                'number_label' => 'invoice_number',
                'keterangan_regex' => '/Keterangan\s*:\s*(.+)/i',
                'keterangan_label' => 'keterangan',
                'keterangan_enabled' => true,
                'uraian_regex' => '/URAIAN\s*\n(.+?)\n\s*TOTAL/si',
                'uraian_label' => 'uraian',
                'uraian_enabled' => true,
                'tanggal_regex' => '/Tgl\s*\n?\s*:\s*(.+)/i',
                'tanggal_label' => 'tanggal',
                'tanggal_enabled' => true,
                'filename_template' => 'INV_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'BERITA ACARA',
                'slug' => 'berita-acara',
                'header_regex' => '/^BERITA\s+ACARA$/mi',
                'description' => 'Berita Acara',
                'number_regex' => '/No\s+BA\s*\n?\s*:\s*(.+)/i',
                'number_label' => 'ba_number',
                'keterangan_regex' => '/Keterangan\s*:\s*(.+)/i',
                'keterangan_label' => 'keterangan',
                'keterangan_enabled' => true,
                'uraian_regex' => '/URAIAN\s*\n(.+?)\n\s*TOTAL/si',
                'uraian_label' => 'uraian',
                'uraian_enabled' => true,
                'tanggal_regex' => '/Tgl\s*\n?\s*:\s*(.+)/i',
                'tanggal_label' => 'tanggal',
                'tanggal_enabled' => true,
                'filename_template' => 'BA_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'PEMBAYARAN',
                'slug' => 'pembayaran',
                'header_regex' => '/^PEMBAYARAN$/mi',
                'description' => 'Pembayaran',
                'number_regex' => '/No\s+SP\s*\n?\s*:\s*(.+)/i',
                'number_label' => 'payment_number',
                'keterangan_regex' => '/Keterangan\s*:\s*(.+)/i',
                'keterangan_label' => 'keterangan',
                'keterangan_enabled' => true,
                'uraian_regex' => '/URAIAN\s*\n(.+?)\n\s*TOTAL/si',
                'uraian_label' => 'uraian',
                'uraian_enabled' => true,
                'tanggal_regex' => '/Tgl\s*\n?\s*:\s*(.+)/i',
                'tanggal_label' => 'tanggal',
                'tanggal_enabled' => true,
                'filename_template' => 'SP_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
        ];
        // Storage::disk('ftp_final')->deleteDirectory('SLIP PEMBUKUAN AP');

        foreach ($documentTypes as $documentType) {
            DocumentType::create($documentType);
        }
    }
}
