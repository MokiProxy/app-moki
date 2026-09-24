<?php

namespace App\Rules;

use App\Support\CoaCode;
use Illuminate\Contracts\Validation\Rule;

class Coa16Digits implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && CoaCode::valid($value);
    }

    public function message(): string
    {
        return 'Kode Chart of Account harus tepat 16 digit numerik.';
    }
}