<?php

namespace Tests\Unit\Erkap;

use App\Rules\CoaCodeFormat;
use App\Rules\CostCenterCodeFormat;
use App\Support\CoaCode;
use Illuminate\Contracts\Validation\Rule;
use PHPUnit\Framework\TestCase;

class CoaCodeTest extends TestCase
{
    private const SEGMENTS = [
        'business_unit' => 'F',
        'location' => '01',
        'management_area' => '20200',
        'activity' => '110',
        'cost_element' => '6000',
    ];

    public function test_compose_builds_fifteen_character_code(): void
    {
        $this->assertSame('F01202001106000', CoaCode::compose(self::SEGMENTS));
        $this->assertSame(15, strlen(CoaCode::compose(self::SEGMENTS)));
    }

    public function test_compose_cost_center_uses_segments_a_to_d(): void
    {
        $this->assertSame('F0120200110', CoaCode::composeCostCenter(self::SEGMENTS));
        $this->assertSame(11, strlen(CoaCode::composeCostCenter(self::SEGMENTS)));
    }

    public function test_compose_rejects_wrong_length_segment(): void
    {
        $this->assertNull(CoaCode::compose(['business_unit' => 'FF'] + self::SEGMENTS));
        $this->assertNull(CoaCode::compose(['location' => '1'] + self::SEGMENTS));
        $this->assertNull(CoaCode::compose(['management_area' => '2020'] + self::SEGMENTS));
        $this->assertNull(CoaCode::compose(['activity' => '11'] + self::SEGMENTS));
        $this->assertNull(CoaCode::compose(['cost_element' => '60000'] + self::SEGMENTS));
        $this->assertNull(CoaCode::compose(['cost_element' => null] + self::SEGMENTS));
    }

    public function test_valid_requires_exactly_fifteen_characters(): void
    {
        $this->assertTrue(CoaCode::valid('F01202001106000'));
        $this->assertFalse(CoaCode::valid('6000'));
        $this->assertFalse(CoaCode::valid('F012020011060000'));
        $this->assertFalse(CoaCode::valid('abcdefghij12345'));
    }

    public function test_valid_cost_center_requires_exactly_eleven_characters(): void
    {
        $this->assertTrue(CoaCode::validCostCenter('F0120200110'));
        $this->assertFalse(CoaCode::validCostCenter('F01202001106000'));
        $this->assertFalse(CoaCode::validCostCenter('F012020011'));
    }

    public function test_parse_splits_code_into_segments(): void
    {
        $this->assertSame(self::SEGMENTS, CoaCode::parse('F01202001106000'));

        $this->assertSame([
            'business_unit' => 'F',
            'location' => '01',
            'management_area' => '20200',
            'activity' => '110',
        ], CoaCode::parse('F0120200110'));
    }

    public function test_format_groups_segments_with_dashes(): void
    {
        $this->assertSame('F-01-20200-110-6000', CoaCode::format('F01202001106000'));
        $this->assertSame('F-01-20200-110', CoaCode::format('F0120200110'));
    }

    public function test_element_and_cost_center_helpers(): void
    {
        $this->assertSame('6000', CoaCode::elementCode('F01202001106000'));
        $this->assertNull(CoaCode::elementCode('F0120200110'));

        $this->assertSame('F0120200110', CoaCode::costCenterCode('F01202001106000'));
        $this->assertNull(CoaCode::costCenterCode('F0120200110'));
    }

    public function test_coa_code_format_rule(): void
    {
        $rule = new CoaCodeFormat();
        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertTrue($rule->passes('code', 'F01202001106000'));
        $this->assertFalse($rule->passes('code', 'F0120200110'));
        $this->assertFalse($rule->passes('code', 6000));
        $this->assertStringContainsString('15 karakter', $rule->message());
    }

    public function test_cost_center_code_format_rule(): void
    {
        $rule = new CostCenterCodeFormat();
        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertTrue($rule->passes('code', 'F0120200110'));
        $this->assertFalse($rule->passes('code', 'F01202001106000'));
        $this->assertStringContainsString('11 karakter', $rule->message());
    }
}
