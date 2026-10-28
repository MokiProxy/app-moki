<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\MergeFlow;
use App\Models\MergeFlowStep;
use Illuminate\Database\Seeder;

class MergeFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLegacyFlow();
        $this->seedInvoiceApprovalFlow();
    }

    /**
     * Flow lama tetap dipertahankan agar instalasi yang masih memakainya
     * tidak kehilangan konfigurasi.
     */
    protected function seedLegacyFlow(): void
    {
        $ba = DocumentType::where('slug', 'berita-acara')->first();
        $inv = DocumentType::where('slug', 'invoice')->first();
        $sp = DocumentType::where('slug', 'pembayaran')->first();

        if (! $ba || ! $inv || ! $sp) {
            $this->command?->warn('Document types BA, INV, or SP not found. Skipping legacy merge flow seed.');

            return;
        }

        $flow = MergeFlow::firstOrCreate(
            ['slug' => 'ba-inv-sp'],
            [
                'name' => 'BA-INV-SP',
                'description' => 'Alur birokrasi: Berita Acara -> Invoice -> Slip Pembayaran',
                'is_active' => true,
            ]
        );

        if ($flow->steps()->exists()) {
            return;
        }

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $ba->id,
            'order' => 1,
            'link_regex' => null,
            'link_label' => null,
            'link_field' => null,
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $inv->id,
            'order' => 2,
            'link_regex' => '/No\s*BA\s*\n?\s*:\s*(.+)/i',
            'link_label' => 'No BA',
            'link_field' => 'no_ba',
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $sp->id,
            'order' => 3,
            'link_regex' => '/No\s*Inv\s*\n?\s*:\s*(.+)/i',
            'link_label' => 'No Inv',
            'link_field' => 'no_inv',
        ]);
    }

    /**
     * Fase 1: invoice (SLIP PEMBUKUAN AP) + persetujuan pembayaran.
     */
    protected function seedInvoiceApprovalFlow(): void
    {
        $invoice = DocumentType::where('slug', 'slip-pembukuan-ap')->first();
        $approval = DocumentType::where('slug', 'persetujuan-pembayaran')->first();

        if (! $invoice || ! $approval) {
            $this->command?->warn('Document types SLIP PEMBUKUAN AP or PERSETUJUAN PEMBAYARAN not found. Skipping invoice-approval merge flow seed.');

            return;
        }

        $flow = MergeFlow::updateOrCreate(
            ['slug' => 'invoice-approval'],
            [
                'name' => 'INV-APPROVAL',
                'description' => 'Alur fan-out: Slip Pembukuan AP -> Persetujuan Pembayaran',
                'is_active' => true,
            ]
        );

        $flow->steps()->delete();

        $flow->steps()->createMany([
            [
                'document_type_id' => $invoice->id,
                'order' => 1,
                'link_regex' => null,
                'link_label' => null,
                'link_field' => null,
            ],
            [
                'document_type_id' => $approval->id,
                'order' => 2,
                'link_regex' => null,
                'link_label' => 'Invoice Numbers',
                'link_field' => 'invoice_numbers',
            ],
        ]);
    }
}
