<?php

namespace App\Rules;

use App\Support\CoaCode;
use Illuminate\Contracts\Validation\Rule;

class CostCenterCodeFormat implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && CoaCode::validCostCenter($value);
    }

    public function message(): string
    {
        return 'Kode Pusat Biaya harus tepat '.CoaCode::COST_CENTER_LENGTH.' karakter (a-b-c-d).';
    }
}
