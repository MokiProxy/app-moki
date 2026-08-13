<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

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
                'description' => 'Slip Pembukuan AP',
                'filename_template' => 'AP_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'INVOICE',
                'slug' => 'invoice',
                'description' => 'Invoice',
                'filename_template' => 'INV_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'BERITA ACARA',
                'slug' => 'berita-acara',
                'description' => 'Berita Acara',
                'filename_template' => 'BA_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
            [
                'name' => 'PEMBAYARAN',
                'slug' => 'pembayaran',
                'description' => 'Pembayaran',
                'filename_template' => 'SP_{vendor}_{number}.{ext}',
                'ftp_folder_template' => '{document_type}/{vendor}',
                'ftp_failed_folder' => 'FAILED',
                'vendor_search_enabled' => true,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::create($documentType);
        }
    }
}
