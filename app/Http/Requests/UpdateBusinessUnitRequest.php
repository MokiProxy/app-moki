<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'size:1', 'regex:/^[A-Za-z0-9]$/',
                // ignoreModel(), bukan ignore(): route parameter berupa model.
                Rule::unique('erkap_business_units', 'code')->ignoreModel($this->route('businessUnit')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Kode Bisnis Unit harus tepat 1 karakter (a).',
            'code.regex' => 'Kode Bisnis Unit hanya boleh huruf atau angka.',
            'code.unique' => 'Kode Bisnis Unit sudah dipakai.',
        ];
    }
}
