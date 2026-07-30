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
                'description' => 'Slip Pembukuan AP',
                'number_regex' => '/No\s+Inv\s*\n?\s*:\s*(.+)/i',
                'number_label' => 'invoice_number',
                's3_filename_template' => 'INV_{vendor}_{number}.{ext}',
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
