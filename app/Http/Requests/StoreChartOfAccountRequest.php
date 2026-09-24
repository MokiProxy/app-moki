<?php

namespace App\Http\Requests;

use App\Rules\Coa16Digits;
use Illuminate\Foundation\Http\FormRequest;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', new Coa16Digits, 'unique:chart_of_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:revenue,expense'],
            'description' => ['nullable', 'string'],
        ];
    }
}