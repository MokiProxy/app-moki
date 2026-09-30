<?php

namespace Tests\Feature\Erkap;

use App\Support\CoaCode;
use Tests\TestCase;

class ErkapCascadeSegmentLengthTest extends TestCase
{
    private function jsSource(): string
    {
        return file_get_contents(public_path('js/erkap-cascade.js'));
    }

    public function test_js_segment_lengths_match_php_constants(): void
    {
        $source = $this->jsSource();

        preg_match('/var SEGMENT_LENGTH = \{(.+?)\};/s', $source, $matches);

        $this->assertNotEmpty($matches, 'Blok SEGMENT_LENGTH tidak ditemukan di erkap-cascade.js.');

        preg_match_all('/(\w+):\s*(\d+)/', $matches[1], $pairs, PREG_SET_ORDER);

        $jsLengths = [];
        foreach ($pairs as $pair) {
            $jsLengths[$pair[1]] = (int) $pair[2];
        }

        foreach (CoaCode::SEGMENT_LENGTHS as $key => $length) {
            if ($key === 'cost_element') {
                continue;
            }

            $this->assertArrayHasKey($key, $jsLengths, "Segmen {$key} tidak didefinisikan di JS.");
            $this->assertSame(
                $length,
                $jsLengths[$key],
                "Panjang segmen {$key} berbeda antara CoaCode::SEGMENT_LENGTHS dan erkap-cascade.js."
            );
        }
    }

    public function test_js_cost_center_length_matches_php_constant(): void
    {
        $this->assertStringContainsString(
            'var COST_CENTER_LENGTH = '.CoaCode::COST_CENTER_LENGTH.';',
            $this->jsSource()
        );
    }

    public function test_js_cost_element_length_matches_php_constant(): void
    {
        $this->assertStringContainsString(
            'var COST_ELEMENT_LENGTH = '.CoaCode::SEGMENT_LENGTHS['cost_element'].';',
            $this->jsSource(),
            'Preview kode COA memakai panjang segmen e dari CoaCode::SEGMENT_LENGTHS.'
        );
    }

    public function test_js_does_not_hardcode_the_length_check(): void
    {
        // Panjang harus dibaca dari konstanta, bukan angka polos, agar tidak
        // melenceng lagi saat K-1 berubah.
        $source = $this->jsSource();

        $this->assertStringNotContainsString('code.length !== 11', $source);
        $this->assertStringNotContainsString('padStart(4,', $source);
    }
}
