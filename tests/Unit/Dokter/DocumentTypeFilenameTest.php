<?php

namespace Tests\Unit\Dokter;

use App\Models\DocumentType;
use Tests\TestCase;

class DocumentTypeFilenameTest extends TestCase
{
    public function test_no_po_is_appended_to_legacy_filename_template(): void
    {
        $documentType = new DocumentType([
            'name' => 'INVOICE',
            'filename_template' => '{vendor}_{number}.{ext}',
        ]);

        $filename = $documentType->resolveFilename('PT ABC', '001/PBT', 'pdf', 'PO-123');

        $this->assertSame('PT ABC_001_PBT_PO-123.pdf', $filename);
    }

    public function test_filename_is_unchanged_when_no_po_is_empty(): void
    {
        $documentType = new DocumentType([
            'name' => 'INVOICE',
            'filename_template' => '{vendor}_{number}.{ext}',
        ]);

        $filename = $documentType->resolveFilename('PT ABC', '001/PBT', 'pdf');

        $this->assertSame('PT ABC_001_PBT.pdf', $filename);
    }

    public function test_no_po_placeholder_is_supported_in_template(): void
    {
        $documentType = new DocumentType([
            'name' => 'INVOICE',
            'filename_template' => 'INV_{vendor}_{number}_{no_po}.{ext}',
        ]);

        $this->assertSame(
            'INV_PT ABC_001_PO 9.pdf',
            $documentType->resolveFilename('PT ABC', '001', 'pdf', 'PO 9')
        );

        $this->assertSame(
            'INV_PT ABC_001.pdf',
            $documentType->resolveFilename('PT ABC', '001', 'pdf', null)
        );
    }

    public function test_no_po_is_sanitized_before_used_as_filename(): void
    {
        $documentType = new DocumentType([
            'name' => 'INVOICE',
            'filename_template' => '{vendor}_{number}.{ext}',
        ]);

        $filename = $documentType->resolveFilename('PT ABC', '001', 'pdf', 'PO/123');

        $this->assertSame('PT ABC_001_PO_123.pdf', $filename);
    }
}
