<?php

namespace App\Rules;

use App\Support\CoaCode;
use Illuminate\Contracts\Validation\Rule;

class CoaCodeFormat implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && CoaCode::valid($value);
    }

    public function message(): string
    {
        return 'Kode Chart of Account harus tepat '.CoaCode::LENGTH.' karakter (a-b-c-d-e).';
    }
}
