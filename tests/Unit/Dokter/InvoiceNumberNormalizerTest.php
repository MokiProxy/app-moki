<?php

namespace Tests\Unit\Dokter;

use App\Services\InvoiceNumberNormalizer;
use PHPUnit\Framework\TestCase;

class InvoiceNumberNormalizerTest extends TestCase
{
    public function test_normalizes_array_of_numbers(): void
    {
        $this->assertSame(
            ['2512014', '2604001'],
            InvoiceNumberNormalizer::normalize(['2512014', '2604001'])
        );
    }

    public function test_normalizes_comma_separated_string(): void
    {
        $this->assertSame(
            ['2512014', '2604001'],
            InvoiceNumberNormalizer::normalize('2512014, 2604001')
        );
    }

    public function test_normalizes_multiline_string(): void
    {
        $this->assertSame(
            ['2512014', '2604001'],
            InvoiceNumberNormalizer::normalize("2512014\n2604001")
        );
    }

    public function test_removes_duplicates_and_trims_values(): void
    {
        $this->assertSame(
            ['2512014'],
            InvoiceNumberNormalizer::normalize([' 2512014 ', '2512014'])
        );
    }

    public function test_handles_integer_values(): void
    {
        $this->assertSame(
            ['2512014'],
            InvoiceNumberNormalizer::normalize(2512014)
        );
    }

    public function test_handles_nested_arrays(): void
    {
        $this->assertSame(
            ['2512014', '2604001'],
            InvoiceNumberNormalizer::normalize([['2512014'], ['2604001']])
        );
    }

    public function test_splits_comma_separated_string_inside_an_array(): void
    {
        $this->assertSame(
            ['2512014', '2604001'],
            InvoiceNumberNormalizer::normalize(['2512014, 2604001'])
        );
    }

    public function test_returns_empty_array_for_empty_or_null_values(): void
    {
        $this->assertSame([], InvoiceNumberNormalizer::normalize([]));
        $this->assertSame([], InvoiceNumberNormalizer::normalize(null));
        $this->assertSame([], InvoiceNumberNormalizer::normalize(''));
    }

    public function test_group_key_preserves_slash_and_dash(): void
    {
        $this->assertSame(
            '724/SBS-HO/DIR/PP/VII/2026',
            InvoiceNumberNormalizer::normalizeGroupKey('  724/sbs-ho/dir/pp/vii/2026 ')
        );
    }

    public function test_vendor_normalization_collapses_spaces_and_case(): void
    {
        $this->assertSame(
            'PT DUTA WAHANA PERSADA',
            InvoiceNumberNormalizer::normalizeVendor('  pt   duta wahana persada ')
        );
    }
}
