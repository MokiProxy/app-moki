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
        $ba = DocumentType::where('slug', 'berita-acara')->first();
        $inv = DocumentType::where('slug', 'invoice')->first();
        $sp = DocumentType::where('slug', 'pembayaran')->first();

        if (! $ba || ! $inv || ! $sp) {
            $this->command?->warn('Document types BA, INV, or SP not found. Skipping merge flow seed.');

            return;
        }

        $flow = MergeFlow::create([
            'name' => 'BA-INV-SP',
            'slug' => 'ba-inv-sp',
            'description' => 'Alur birokrasi: Berita Acara -> Invoice -> Slip Pembayaran',
            'is_active' => true,
        ]);

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
            'link_field' => null,
        ]);

        MergeFlowStep::create([
            'merge_flow_id' => $flow->id,
            'document_type_id' => $sp->id,
            'order' => 3,
            'link_regex' => '/No\s*Inv\s*\n?\s*:\s*(.+)/i',
            'link_label' => 'No Inv',
            'link_field' => null,
        ]);
    }
}
