<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = DocumentType::all();

        $vendors = [
            [
                'name' => 'MADHANI TALATAH NUSANTARA',
                'slug' => 'madhani-talatah-nusantara',
                'description' => 'PT Madhani Talatah Nusantara',
            ],
            [
                'name' => 'UNITED TRACTOR',
                'slug' => 'united-tractor',
                'description' => 'PT United Tractor',
            ],
            [
                'name' => 'PUTRA PERKASA ABADI',
                'slug' => 'putra-perkasa-abadi',
                'description' => 'PT Putra Perkasa Abadi',
            ],
            [
                'name' => 'PUSAKA BUMI TRANSPORTAS',
                'slug' => 'pusaka-bumi-transportasi',
                'description' => 'PT Pusaka Bumi Transportasi',
            ],
            [
                'name' => 'AITI MITRA UTAMA',
                'slug' => 'aiti-mitra-utama',
                'description' => 'PT Aiti Mitra Utama',
            ],
        ];

        foreach ($vendors as $vendorData) {
            $vendor = Vendor::create($vendorData);
            foreach ($documentTypes as $documentType) {
                $vendor->documentTypes()->attach($documentType->id);
                Storage::disk('ftp_final')->makeDirectory("{$documentType->name}/{$vendor->name}");
            }
        }
    }
}
