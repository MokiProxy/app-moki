<?php

namespace Tests\Unit\Erkap;

use App\Rules\Coa16Digits;
use App\Support\CoaCode;
use Illuminate\Contracts\Validation\Rule;
use PHPUnit\Framework\TestCase;

class CoaCodeTest extends TestCase
{
    public function test_pad_pads_legacy_codes_to_sixteen_digits(): void
    {
        $this->assertSame('6000000000000000', CoaCode::pad('6000'));
        $this->assertSame('1234560000000000', CoaCode::pad('123456'));
        $this->assertSame('7987654321123456', CoaCode::pad('7987654321123456'));
        $this->assertSame('1234567890123456', CoaCode::pad('1234567890123456'));
    }

    public function test_pad_rejects_invalid_input(): void
    {
        $this->assertNull(CoaCode::pad(''));
        $this->assertNull(CoaCode::pad('COA-OPEX'));
        $this->assertNull(CoaCode::pad('12345678901234567'));
    }

    public function test_valid_requires_exactly_sixteen_digits(): void
    {
        $this->assertTrue(CoaCode::valid('6000000000000000'));
        $this->assertTrue(CoaCode::valid('1234567890123456'));
        $this->assertFalse(CoaCode::valid('6000'));
        $this->assertFalse(CoaCode::valid('12345678901234567'));
        $this->assertFalse(CoaCode::valid('abcdef0123456789'));
    }

    public function test_format_groups_digits_in_blocks_of_four(): void
    {
        $this->assertSame('6000-0000-0000-0000', CoaCode::format('6000000000000000'));
        $this->assertSame('1234-5678-9012-3456', CoaCode::format('1234567890123456'));
    }

    public function test_coa16_digits_rule(): void
    {
        $rule = new Coa16Digits();
        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertTrue($rule->passes('code', '6000000000000000'));
        $this->assertFalse($rule->passes('code', '6000'));
        $this->assertFalse($rule->passes('code', '60000000000000001'));
        $this->assertFalse($rule->passes('code', 6000000000000000));
        $this->assertFalse($rule->passes('code', ''));
        $this->assertStringContainsString('16 digit', $rule->message());
    }
}