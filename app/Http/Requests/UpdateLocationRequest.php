<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'size:2', 'regex:/^\d{2}$/',
                Rule::unique('erkap_locations')
                    ->where(
                        fn ($query) => $query->where('erkap_business_unit_id', $this->input('erkap_business_unit_id'))
                    )
                    ->ignoreModel($this->route('location')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'erkap_business_unit_id' => ['required', 'integer', Rule::exists('erkap_business_units', 'id')],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Kode Lokasi harus tepat 2 digit (b), contoh 01.',
            'code.regex' => 'Kode Lokasi harus 2 digit angka.',
            'code.unique' => 'Kode Lokasi sudah dipakai pada Bisnis Unit tersebut.',
            'erkap_business_unit_id.required' => 'Bisnis Unit wajib dipilih.',
        ];
    }
}
